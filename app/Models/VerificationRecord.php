<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'token',
        'fingerprint',
        'status',
        'access_count',
        'last_accessed_at',
    ];

    protected $casts = [
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Get the document this verification record belongs to.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Check if document is valid.
     */
    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    /**
     * Check if document is superseded.
     */
    public function isSuperseded(): bool
    {
        return $this->status === 'superseded';
    }

    /**
     * Check if document is revoked.
     */
    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    /**
     * Increment access count.
     */
    public function incrementAccessCount(): void
    {
        $this->increment('access_count');
        $this->update(['last_accessed_at' => now()]);
    }
}
