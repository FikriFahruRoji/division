<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\VerificationRecord;

class VerificationService
{
    /**
     * Verify a document by its token.
     * Returns null if not found.
     */
    public function verify(string $token): ?array
    {
        $record = VerificationRecord::where('token', $token)->first();
        
        if (!$record) {
            return null;
        }
        
        // Increment access count
        $record->incrementAccessCount();
        
        // Get document info (without exposing sensitive data)
        $document = $record->document;
        
        // Log verification attempt
        AuditLog::log(
            'verify',
            "Verifikasi publik untuk dokumen '{$document->doc_number}'",
            null,
            $document->id,
            ['token' => $token]
        );
        
        return [
            'status' => $record->status,
            'fingerprint' => $record->fingerprint,
            'document' => [
                'id' => $document->id,
                'doc_number' => $document->doc_number,
                'title' => $document->title,
                'doc_date' => $document->doc_date->format('d F Y'),
                'doc_type' => $document->doc_type,
                'unit' => $document->unit,
                'classification' => $document->classification,
                'version' => $document->version,
                'signed_at' => $document->isSignedValid() 
                    ? $document->signatures()->latest()->first()?->created_at?->format('d F Y H:i') 
                    : null,
            ],
            'signers' => $document->signerAssignments()
                ->with('signer:id,name,position')
                ->get()
                ->map(fn($a) => [
                    'name' => $a->signer->name,
                    'position' => $a->signer->position,
                    'status' => $a->status,
                    'signed_at' => $a->signed_at?->format('d F Y H:i'),
                ]),
            'access_count' => $record->access_count,
            'last_accessed_at' => $record->last_accessed_at?->format('d F Y H:i'),
        ];
    }
    
    /**
     * Get status label in Indonesian.
     */
    public function getStatusLabel(string $status): string
    {
        return match ($status) {
            'valid' => 'VALID',
            'superseded' => 'DIGANTIKAN',
            'revoked' => 'DICABUT',
            default => strtoupper($status),
        };
    }
    
    /**
     * Get status color class for UI.
     */
    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'valid' => 'green',
            'superseded' => 'yellow',
            'revoked' => 'red',
            default => 'gray',
        };
    }
}
