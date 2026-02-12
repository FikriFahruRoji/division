<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DocumentBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'qr_token',
        'doc_type',
        'creator_id',
        'status',
        'document_count',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($batch) {
            if (empty($batch->uuid)) {
                $batch->uuid = Str::uuid();
            }
            if (empty($batch->qr_token)) {
                $batch->qr_token = Str::uuid();
            }
        });
    }

    /**
     * Get the creator of this batch.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get documents in this batch.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'batch_id');
    }

    /**
     * Get the verification URL for this batch.
     */
    public function getVerificationUrl(): string
    {
        return route('verify.batch', $this->qr_token);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Update the document count cache.
     */
    public function updateDocumentCount(): void
    {
        $this->update(['document_count' => $this->documents()->count()]);
    }
}
