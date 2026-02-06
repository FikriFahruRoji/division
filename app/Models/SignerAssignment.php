<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignerAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'signer_id',
        'order_index',
        'status',
        'notified_at',
        'signed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    /**
     * Get the document this assignment belongs to.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the signer user.
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_id')->withTrashed();
    }

    /**
     * Check if assignment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if assignment is notified.
     */
    public function isNotified(): bool
    {
        return $this->status === 'notified';
    }

    /**
     * Check if assignment is signed.
     */
    public function isSigned(): bool
    {
        return $this->status === 'signed';
    }

    /**
     * Check if assignment is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Mark as notified.
     */
    public function markAsNotified(): void
    {
        $this->update([
            'status' => 'notified',
            'notified_at' => now(),
        ]);
    }

    /**
     * Mark as signed.
     */
    public function markAsSigned(): void
    {
        $this->update([
            'status' => 'signed',
            'signed_at' => now(),
        ]);
    }

    /**
     * Mark as rejected.
     */
    public function markAsRejected(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }
}
