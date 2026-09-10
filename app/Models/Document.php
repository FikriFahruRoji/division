<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use App\Traits\DepartmentScope;

class Document extends Model
{
    use HasFactory, DepartmentScope;

    protected $fillable = [
        'uuid',
        'title',
        'doc_number',
        'doc_date',
        'doc_type',
        'unit',
        'classification',
        'status',
        'version',
        'creator_id',
        'parent_id',
        'file_path',
        'signed_file_path',
        'hash',
        'fingerprint',
        'qr_token',
        'sign_mode',
        'notes',
    ];

    protected $casts = [
        'doc_date' => 'date',
    ];

    /**
     * Boot method to generate QR token on creation.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($document) {
            if (empty($document->uuid)) {
                $document->uuid = Str::uuid();
            }
            if (empty($document->qr_token)) {
                $document->qr_token = Str::uuid();
            }
        });
    }

    /**
     * Get the creator of this document.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id')->withTrashed();
    }

    /**
     * Get the parent document (for versioning).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_id');
    }

    /**
     * Get child versions of this document.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_id');
    }

    /**
     * Get signer assignments for this document.
     */
    public function signerAssignments(): HasMany
    {
        return $this->hasMany(SignerAssignment::class)->orderBy('order_index');
    }

    /**
     * Get signatures for this document.
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }

    /**
     * Get verification record for this document.
     */
    public function verificationRecord(): HasOne
    {
        return $this->hasOne(VerificationRecord::class);
    }

    /**
     * Get audit logs for this document.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Check if document is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if document is pending signature.
     */
    public function isPendingSignature(): bool
    {
        return $this->status === 'pending_signature';
    }

    /**
     * Check if document is signed and valid.
     */
    public function isSignedValid(): bool
    {
        return $this->status === 'signed_valid';
    }

    /**
     * Check if document is superseded by a newer version.
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
     * Get the next signer in sequence who hasn't signed yet.
     */
    public function getNextSigner(): ?SignerAssignment
    {
        return $this->signerAssignments()
            ->whereIn('status', ['pending', 'notified'])
            ->orderBy('order_index')
            ->first();
    }

    /**
     * Check if all signers have signed.
     */
    public function allSignersSigned(): bool
    {
        return $this->signerAssignments()->where('status', '!=', 'signed')->count() === 0
            && $this->signerAssignments()->count() > 0;
    }

    /**
     * Get verification URL.
     */
    public function getVerificationUrl(): string
    {
        return route('verify', $this->qr_token);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
