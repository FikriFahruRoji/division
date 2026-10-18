<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\SignerAssignment;
use App\Models\User;
use App\Models\VerificationRecord;
use App\Services\CertificateService;
use App\Services\SigningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use TCPDF;
use Tests\TestCase;

/**
 * T3: system seal private key is encrypted at rest and rotatable.
 */
class SealCertificateTest extends TestCase
{
    use RefreshDatabase;

    private const PASSPHRASE = 'testing-seal-passphrase-not-secret';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        config(['signing.seal.passphrase' => self::PASSPHRASE, 'signing.seal.key_bits' => 2048]);
    }

    public function test_generated_seal_key_is_encrypted_and_needs_the_passphrase(): void
    {
        $paths = app(CertificateService::class)->getSystemCertificate();

        $keyPem = file_get_contents($paths['key']);
        $this->assertStringContainsString('BEGIN ENCRYPTED PRIVATE KEY', $keyPem);
        $this->assertFalse(openssl_pkey_get_private($keyPem, 'wrong-passphrase-123456'));
        $this->assertNotFalse(openssl_pkey_get_private($keyPem, self::PASSPHRASE));
        $this->assertSame(self::PASSPHRASE, $paths['passphrase']);
    }

    public function test_seal_label_states_it_is_not_psre_certified(): void
    {
        $paths = app(CertificateService::class)->getSystemCertificate();

        $subject = openssl_x509_parse(file_get_contents($paths['cert']))['subject'];
        $this->assertStringContainsString('tidak tersertifikasi PSrE', $subject['OU']);
    }

    public function test_missing_or_short_passphrase_is_refused(): void
    {
        config(['signing.seal.passphrase' => 'short']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SIGNING_SEAL_PASSPHRASE');

        app(CertificateService::class)->getSystemCertificate();
    }

    public function test_plaintext_legacy_key_is_refused(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $cert = openssl_csr_sign(openssl_csr_new(['commonName' => 'legacy'], $key), null, $key, 30);
        openssl_x509_export($cert, $certOut);
        openssl_pkey_export($key, $plainKey);
        Storage::put(config('signing.seal.cert_path'), $certOut);
        Storage::put(config('signing.seal.key_path'), $plainKey);

        $service = app(CertificateService::class);
        $this->assertTrue($service->systemKeyNeedsRotation());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('seal:rotate');

        $service->getSystemCertificate();
    }

    public function test_wrong_passphrase_for_existing_key_is_refused(): void
    {
        app(CertificateService::class)->getSystemCertificate();
        config(['signing.seal.passphrase' => 'a-different-passphrase-entirely']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tidak cocok');

        app(CertificateService::class)->getSystemCertificate();
    }

    public function test_rotate_command_archives_old_cert_and_issues_new_encrypted_key(): void
    {
        $service = app(CertificateService::class);
        $service->getSystemCertificate();
        $oldSerial = $service->describe(Storage::get(config('signing.seal.cert_path')))['serial'];

        $this->artisan('seal:rotate', ['--force' => true])->assertSuccessful();

        $archived = Storage::files(config('signing.seal.archive_dir'));
        $this->assertCount(1, $archived);
        $this->assertStringContainsString($oldSerial, $archived[0]);

        $newSerial = $service->describe(Storage::get(config('signing.seal.cert_path')))['serial'];
        $this->assertNotSame($oldSerial, $newSerial);
        $this->assertFalse($service->systemKeyNeedsRotation());
        $this->assertDatabaseHas('audit_logs', ['action' => 'seal_rotated']);
    }

    public function test_signing_works_with_encrypted_seal_key(): void
    {
        $dept = Department::factory()->create();
        $signer = User::factory()->signer()->withDepartment($dept->id)->create();
        $doc = Document::factory()->create(['status' => 'pending_signature', 'department_id' => $dept->id]);

        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->Write(0, 'Dokumen uji segel');
        $content = $pdf->Output('', 'S');
        Storage::disk('local')->put($doc->file_path, $content);
        $doc->update(['hash' => hash('sha256', $content), 'signed_file_path' => null]);

        VerificationRecord::factory()->create(['document_id' => $doc->id, 'token' => $doc->qr_token]);
        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'order_index' => 1,
            'status' => 'pending',
        ]);

        $signature = app(SigningService::class)->signDocument($doc, $signer);

        $signed = Storage::disk('local')->get($signature->signature_blob_path);
        $this->assertStringContainsString('/ByteRange', $signed);
        $this->assertStringContainsString('adbe.pkcs7.detached', $signed);

        // The PKCS#7 placeholder must actually hold a signature made with the decrypted key
        $this->assertMatchesRegularExpression('/\/Contents\s*<([0-9a-fA-F]+)>/', $signed);
        preg_match('/\/Contents\s*<([0-9a-fA-F]+)>/', $signed, $m);
        $this->assertGreaterThan(1000, strlen(rtrim($m[1], '0')));
    }
}
