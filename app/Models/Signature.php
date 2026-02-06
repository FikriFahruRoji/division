<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signature extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'signer_id',
        'signature_blob_path',
        'certificate_serial',
        'tsa_token',
        'ocsp_response',
        'signed_hash',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the document this signature belongs to.
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
}
