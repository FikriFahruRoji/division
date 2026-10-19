<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Append-only, hash-chained audit trail (T5).
 *
 * Each entry stores the previous entry's hash and a SHA-256 over its own
 * content plus that link, so editing, deleting or reordering any row breaks
 * the chain (see `php artisan audit:verify-chain`). A unique index on
 * prev_hash keeps the chain linear when two requests write at once.
 */
class AuditLog extends Model
{
    use HasFactory;

    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    private const MAX_APPEND_ATTEMPTS = 5;

    protected $fillable = [
        'user_id',
        'document_id',
        'action',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (AuditLog $log) {
            // Fix the timestamps before hashing so the stored values are exactly what was hashed
            $now = $log->freshTimestamp();
            $log->setCreatedAt($now);
            $log->setUpdatedAt($now);

            // Locking read so a retry sees the latest committed tip, not a stale snapshot
            $log->prev_hash = static::query()->orderByDesc('id')->lockForUpdate()->value('entry_hash') ?? self::GENESIS_HASH;
            $log->entry_hash = $log->computeHash();
        });

        static::updating(fn () => throw new LogicException('Audit log bersifat append-only dan tidak dapat diubah.'));
        static::deleting(fn () => throw new LogicException('Audit log bersifat append-only dan tidak dapat dihapus.'));
    }

    /**
     * Get the user associated with this log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Get the document associated with this log.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Create an audit log entry.
     */
    public static function log(
        string $action,
        string $description,
        ?int $userId = null,
        ?int $documentId = null,
        ?array $metadata = null
    ): self {
        $attributes = [
            'user_id' => $userId ?? auth()->id(),
            'document_id' => $documentId,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        // Another writer may claim the same prev_hash first; re-read the tip and retry
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(fn () => self::create($attributes));
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= self::MAX_APPEND_ATTEMPTS) {
                    throw $e;
                }
            }
        }
    }

    /**
     * SHA-256 over the chain link and every stored field except the id.
     */
    public function computeHash(): string
    {
        return hash('sha256', json_encode([
            'prev_hash' => $this->prev_hash,
            'user_id' => $this->user_id === null ? null : (int) $this->user_id,
            'document_id' => $this->document_id === null ? null : (int) $this->document_id,
            'action' => $this->action,
            'description' => $this->description,
            'metadata' => self::canonicalize($this->metadata),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
    }

    /**
     * Sort keys recursively so the hash does not depend on key order after a JSON round-trip.
     */
    private static function canonicalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (!array_is_list($value)) {
            ksort($value);
        }

        return array_map(self::canonicalize(...), $value);
    }
}
