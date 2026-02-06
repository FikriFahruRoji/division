<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

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

    /**
     * Get the user associated with this log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'document_id' => $documentId,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
