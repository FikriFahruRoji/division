<?php

namespace App\Console\Commands;

use App\Services\AuditChainService;
use Illuminate\Console\Command;

class VerifyAuditChain extends Command
{
    protected $signature = 'audit:verify-chain';

    protected $description = 'Periksa keutuhan hash chain audit log beserta anchor yang pernah dicatat';

    public function handle(AuditChainService $chain): int
    {
        $result = $chain->verify();

        if (!$result['valid']) {
            $this->error("Rantai audit RUSAK pada entri #{$result['broken_at']}: {$result['reason']}");
            $this->line("{$result['checked']} entri sebelum titik itu masih utuh.");
            return self::FAILURE;
        }

        $mismatched = $chain->mismatchedAnchors();
        if ($mismatched !== []) {
            $this->error('Rantai konsisten, tetapi tidak cocok dengan anchor yang pernah dicatat (kemungkinan dibangun ulang):');
            $this->table(['Entri', 'Hash anchor', 'Hash sekarang', 'Dicatat'], array_map(fn ($a) => [
                $a['id'], $a['hash'], $a['current_hash'] ?? '(hilang)', $a['anchored_at'] ?? '-',
            ], $mismatched));
            return self::FAILURE;
        }

        $this->info("Rantai audit utuh: {$result['checked']} entri diperiksa.");
        if ($result['tip']) {
            $this->line("Ujung rantai: #{$result['tip']['id']} {$result['tip']['hash']}");
        }

        return self::SUCCESS;
    }
}
