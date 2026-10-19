<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Storage;

/**
 * T5: verifies and anchors the hash-chained audit trail.
 */
class AuditChainService
{
    public const ANCHOR_FILE = 'audit-anchors/anchors.jsonl';

    /**
     * Walk the whole chain in id order.
     *
     * @return array{valid: bool, checked: int, broken_at: ?int, reason: ?string, tip: ?array}
     */
    public function verify(): array
    {
        $prev = AuditLog::GENESIS_HASH;
        $checked = 0;
        $tip = null;
        $failure = null;

        AuditLog::query()->orderBy('id')->chunkById(1000, function ($logs) use (&$prev, &$checked, &$tip, &$failure) {
            foreach ($logs as $log) {
                if ($log->prev_hash !== $prev) {
                    $failure = [$log->id, 'Tautan ke entri sebelumnya tidak cocok (entri dihapus, disisipkan, atau diurutkan ulang).'];
                    return false;
                }

                if ($log->entry_hash !== $log->computeHash()) {
                    $failure = [$log->id, 'Isi entri tidak cocok dengan hash-nya (entri diubah).'];
                    return false;
                }

                $prev = $log->entry_hash;
                $tip = ['id' => $log->id, 'hash' => $log->entry_hash, 'created_at' => $log->created_at?->toIso8601String()];
                $checked++;
            }
        });

        return [
            'valid' => $failure === null,
            'checked' => $checked,
            'broken_at' => $failure[0] ?? null,
            'reason' => $failure[1] ?? null,
            'tip' => $tip,
        ];
    }

    /**
     * Current tip of the chain, or null when the log is empty.
     */
    public function tip(): ?array
    {
        $log = AuditLog::query()->orderByDesc('id')->first();

        return $log ? [
            'id' => $log->id,
            'hash' => $log->entry_hash,
            'created_at' => $log->created_at?->toIso8601String(),
        ] : null;
    }

    /**
     * Previously recorded anchors whose entry no longer carries the anchored hash.
     * Catches a chain that was rebuilt from scratch, which verify() alone cannot see.
     *
     * @return list<array>
     */
    public function mismatchedAnchors(): array
    {
        if (!Storage::exists(self::ANCHOR_FILE)) {
            return [];
        }

        $mismatched = [];
        foreach (preg_split('/\R/', trim(Storage::get(self::ANCHOR_FILE))) as $line) {
            $anchor = json_decode($line, true);
            if (!is_array($anchor) || !isset($anchor['id'], $anchor['hash'])) {
                continue;
            }

            $current = AuditLog::query()->whereKey($anchor['id'])->value('entry_hash');
            if ($current !== $anchor['hash']) {
                $mismatched[] = $anchor + ['current_hash' => $current];
            }
        }

        return $mismatched;
    }

    /**
     * Append the current tip to the local anchor file and return it.
     */
    public function recordAnchor(array $tip): array
    {
        $anchor = $tip + ['anchored_at' => now()->toIso8601String()];

        Storage::append(self::ANCHOR_FILE, json_encode($anchor, JSON_UNESCAPED_SLASHES));

        return $anchor;
    }
}
