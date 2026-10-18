<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Services\CertificateService;
use Illuminate\Console\Command;

class RotateSealCertificate extends Command
{
    protected $signature = 'seal:rotate {--force : Lewati konfirmasi}';

    protected $description = 'Arsipkan sertifikat segel sistem, hancurkan kuncinya, dan terbitkan segel baru yang terenkripsi';

    public function handle(CertificateService $certificates): int
    {
        if ($certificates->systemKeyNeedsRotation()) {
            $this->warn('Kunci segel saat ini TIDAK terenkripsi dan harus dirotasi.');
        }

        if (!$this->option('force') && !$this->confirm('Kunci segel lama akan dihapus permanen. Lanjutkan?')) {
            $this->info('Dibatalkan.');
            return self::FAILURE;
        }

        try {
            $result = $certificates->rotateSystemCertificate();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        AuditLog::log(
            'seal_rotated',
            'Sertifikat segel sistem dirotasi',
            null,
            null,
            [
                'archived_cert' => $result['archived'],
                'serial' => $result['current']['serial'],
                'fingerprint' => $result['current']['fingerprint'],
            ]
        );

        if ($result['archived']) {
            $this->line("Sertifikat lama diarsipkan: {$result['archived']}");
        }
        $this->info('Segel baru diterbitkan.');
        $this->table(['Serial', 'SHA-256 fingerprint', 'Berlaku sampai'], [[
            $result['current']['serial'],
            $result['current']['fingerprint'],
            $result['current']['valid_to'],
        ]]);

        return self::SUCCESS;
    }
}
