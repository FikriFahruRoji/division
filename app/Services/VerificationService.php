<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\VerificationRecord;
use Illuminate\Support\Facades\Storage;

class VerificationService
{
    /**
     * Verify a document by its token.
     * Returns null if not found.
     */
    public function verify(string $token): ?array
    {
        $record = VerificationRecord::where('token', $token)->first();
        
        if (!$record || !$record->document) {
            return null;
        }
        
        $document = $record->document;

        // Increment access count
        $record->incrementAccessCount();

        // 1. Determine verified status according to document state machine (SEC-01)
        $computedStatus = match ($document->status) {
            'draft' => 'draft',
            'pending_signature' => 'pending',
            'rejected' => 'rejected',
            'revoked' => 'revoked',
            'superseded' => 'superseded',
            'signed_valid' => 'valid',
            default => 'invalid',
        };

        // If database record is revoked or superseded, respect that
        if (in_array($record->status, ['revoked', 'superseded'])) {
            $computedStatus = $record->status;
        }

        // 2. Perform File Integrity Check (SEC-02)
        // If status claims to be valid, verify the actual file and hash on disk
        if ($computedStatus === 'valid') {
            $filePath = $document->signed_file_path 
                ? Storage::disk('local')->path($document->signed_file_path)
                : null;

            if (!$filePath || !file_exists($filePath)) {
                $computedStatus = 'invalid';
            } else {
                $actualHash = hash_file('sha256', $filePath);
                if ($actualHash !== $document->hash) {
                    $computedStatus = 'invalid';
                }
            }
        }
        
        // Log verification attempt without leaking raw token (SEC-21)
        $tokenPrefix = substr($token, 0, 8) . '...';
        AuditLog::log(
            'verify',
            "Verifikasi publik untuk dokumen '{$document->doc_number}'",
            auth()->id(),
            $document->id,
            ['token_prefix' => $tokenPrefix, 'status' => $computedStatus]
        );

        // SEC-03: Minimum public verification information:
        // Do not leak secret/confidential document titles to unauthenticated public
        $isPublic = !auth()->check();
        $isConfidential = in_array($document->classification, ['rahasia', 'sangat_rahasia']);
        
        $publicTitle = ($isPublic && $isConfidential) 
            ? 'Dokumen Terbatas' 
            : $document->title;
        
        return [
            'status' => $computedStatus,
            'fingerprint' => $record->fingerprint,
            'document' => [
                'id' => $document->id,
                'doc_number' => $document->doc_number,
                'title' => $publicTitle,
                'doc_date' => $document->doc_date ? $document->doc_date->format('d F Y') : null,
                'doc_type' => $document->doc_type,
                'unit' => $document->unit,
                'classification' => $document->classification,
                'version' => $document->version,
                'signed_at' => ($computedStatus === 'valid' && $document->isSignedValid()) 
                    ? $document->signatures()->latest()->first()?->created_at?->format('d F Y H:i') 
                    : null,
            ],
            'signers' => $document->signerAssignments()
                ->with('signer:id,name,position,nip_nidn')
                ->get()
                ->map(fn($a) => [
                    'name' => $a->signer->name,
                    'position' => $a->signer->position,
                    'nip_nidn' => $a->signer->nip_nidn,
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
            'rejected' => 'DITOLAK',
            'draft' => 'DRAFT',
            'pending' => 'MENUNGGU TTD',
            'invalid' => 'TIDAK VALID',
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
            'rejected' => 'red',
            'draft' => 'gray',
            'pending' => 'yellow',
            'invalid' => 'red',
            default => 'gray',
        };
    }
}
