<?php
// Controlled interleaving of two approvals, with SQLite :memory: and temp storage.
// Rendering is stubbed so no real certificate or document is accessed.
$root = '/Users/fikrifahruroji/Documents/gravity project/ttd-digital';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$dir = sys_get_temp_dir() . '/sqa-race-probe-' . bin2hex(random_bytes(6));
mkdir($dir, 0700);
$app->useEnvironmentPath($dir);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqa_memory',
    'database.connections.sqa_memory' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false],
    'filesystems.default' => 'local',
    'filesystems.disks.local.root' => $dir,
    'logging.default' => 'null',
    'session.driver' => 'array',
]);
Illuminate\Support\Facades\DB::purge();
if (Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('Refusing any non-memory DB');
}
Illuminate\Support\Facades\DB::statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, role TEXT, status TEXT, position TEXT, deleted_at TEXT, created_at TEXT, updated_at TEXT)');
Illuminate\Support\Facades\DB::statement('CREATE TABLE documents (id INTEGER PRIMARY KEY, uuid TEXT, qr_token TEXT, title TEXT, file_path TEXT, signed_file_path TEXT, hash TEXT, status TEXT, sign_mode TEXT, created_at TEXT, updated_at TEXT)');
foreach (['2024_01_31_000003_create_signer_assignments_table.php', '2024_01_31_000004_create_signatures_table.php', '2024_01_31_000006_create_audit_logs_table.php'] as $migration) {
    (require $root . '/database/migrations/' . $migration)->up();
}

class RaceProbeService extends App\Services\SigningService
{
    public bool $nested = false;
    protected function applyVisualSignature(App\Models\Document $document, App\Models\User $signer, string $sourcePath, array $qrPosition = []): string
    {
        $outer = !$this->nested;
        if ($outer) {
            $this->nested = true;
            // Request B begins after A checked pending assignment, before A writes.
            $this->signDocument($document->fresh(), $signer);
        }
        $path = $outer ? 'outer.txt' : 'inner.txt';
        Illuminate\Support\Facades\Storage::disk('local')->put($path, 'Synthetic ' . $path);
        return $path;
    }
}

try {
    $user = App\Models\User::create(['name' => 'Synthetic Signer', 'email' => 'synthetic@example.test', 'role' => 'signer', 'status' => 'active']);
    $doc = App\Models\Document::create(['title' => 'Synthetic', 'file_path' => 'original.txt', 'status' => 'pending_signature', 'sign_mode' => 'single']);
    Illuminate\Support\Facades\Storage::disk('local')->put('original.txt', 'Synthetic original');
    App\Models\SignerAssignment::create(['document_id' => $doc->id, 'signer_id' => $user->id, 'order_index' => 1, 'status' => 'notified']);
    $service = new RaceProbeService(new App\Services\QrCodeService(), new App\Services\CertificateService(), new App\Services\PdfCompatibilityService());
    $service->signDocument($doc, $user);
    echo json_encode(['scenario' => 'two_interleaved_approvals_same_assignment', 'signature_rows' => App\Models\Signature::count(), 'signed_assignments' => App\Models\SignerAssignment::where('status', 'signed')->count(), 'document_status' => $doc->fresh()->status, 'final_file' => $doc->fresh()->signed_file_path]) . PHP_EOL;
} finally {
    foreach (glob($dir . '/*') as $file) {
        if (is_file($file)) unlink($file);
    }
    rmdir($dir);
}
