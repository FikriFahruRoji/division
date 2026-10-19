<?php

namespace App\Console\Commands;

use App\Services\AuditChainService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class AnchorAuditChain extends Command
{
    protected $signature = 'audit:anchor';

    protected $description = 'Catat hash ujung rantai audit log dan kirim ke penerima di luar server';

    public function handle(AuditChainService $chain): int
    {
        $result = $chain->verify();
        if (!$result['valid']) {
            $this->error("Rantai audit rusak pada entri #{$result['broken_at']}; anchor tidak dicatat. Jalankan audit:verify-chain.");
            return self::FAILURE;
        }

        $tip = $chain->tip();
        if (!$tip) {
            $this->info('Audit log masih kosong; tidak ada yang di-anchor.');
            return self::SUCCESS;
        }

        $anchor = $chain->recordAnchor($tip);
        $this->info("Anchor dicatat: #{$anchor['id']} {$anchor['hash']}");

        $recipients = config('audit.anchor_recipients');
        if ($recipients === []) {
            $this->warn('AUDIT_ANCHOR_EMAILS belum diatur; anchor hanya tersimpan di server ini.');
            return self::SUCCESS;
        }

        $app = config('app.name');
        Mail::raw(
            "Anchor harian audit log {$app}\n\n"
            . "Entri terakhir : #{$anchor['id']}\n"
            . "Hash           : {$anchor['hash']}\n"
            . "Waktu entri    : {$anchor['created_at']}\n"
            . "Dicatat pada   : {$anchor['anchored_at']}\n\n"
            . "Simpan email ini. Jika suatu saat entri #{$anchor['id']} di sistem tidak lagi memiliki hash di atas, "
            . "berarti audit log telah diubah setelah tanggal ini.",
            fn ($message) => $message->to($recipients)->subject("[{$app}] Anchor audit log #{$anchor['id']}")
        );
        $this->line('Dikirim ke: ' . implode(', ', $recipients));

        return self::SUCCESS;
    }
}
