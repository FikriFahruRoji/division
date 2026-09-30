<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\VerificationRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    /**
     * Allowed MIME types for upload.
     */
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
    ];

    /**
     * Maximum file size in bytes (10MB).
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    protected PdfCompatibilityService $pdfCompatibilityService;

    public function __construct(PdfCompatibilityService $pdfCompatibilityService)
    {
        $this->pdfCompatibilityService = $pdfCompatibilityService;
    }

    /**
     * Upload and create a new document.
     */
    public function upload(UploadedFile $file, array $metadata, int $creatorId): Document
    {
        // Validate file MIME type using finfo (not just extension)
        $this->validateFileSecurity($file);
        
        // Generate random filename to prevent any path traversal or execution attacks
        $randomFilename = Str::uuid() . '.pdf';
        
        // Store in secure location (not publicly accessible)
        $path = $file->storeAs('documents/secure', $randomFilename, 'local');
        
        $fullPath = Storage::disk('local')->path($path);

        // Ensure PDF is compatible with FPDI (normalizes compressed streams if needed)
        $this->pdfCompatibilityService->ensureCompatible($fullPath);
        
        // Calculate hash and fingerprint
        $hash = $this->calculateHash($fullPath);
        $fingerprint = $this->generateFingerprint($hash);
        
        // Create document record
        $document = Document::create([
            'title' => $metadata['title'],
            'doc_number' => $metadata['doc_number'],
            'doc_date' => $metadata['doc_date'],
            'doc_type' => $metadata['doc_type'],
            'unit' => $metadata['unit'],
            'classification' => $metadata['classification'] ?? 'biasa',
            'status' => 'draft',
            'version' => 1,
            'creator_id' => $creatorId,
            'file_path' => $path,
            'hash' => $hash,
            'fingerprint' => $fingerprint,
            'sign_mode' => $metadata['sign_mode'] ?? 'single',
            'notes' => $metadata['notes'] ?? null,
        ]);
        
        // Create verification record
        VerificationRecord::create([
            'document_id' => $document->id,
            'token' => $document->qr_token,
            'fingerprint' => $fingerprint,
            'status' => 'valid',
        ]);
        
        // Log the upload
        AuditLog::log(
            'upload',
            "Dokumen '{$document->title}' ({$document->doc_number}) diunggah",
            $creatorId,
            $document->id
        );
        
        return $document;
    }

    /**
     * Validate file security: MIME type and size.
     * Uses finfo to check real MIME type, not just file extension.
     *
     * @throws \InvalidArgumentException If file validation fails
     */
    protected function validateFileSecurity(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('Ukuran file melebihi batas maksimum 10MB.');
        }

        // Validate MIME type using finfo (checks actual file content, not extension)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMimeType = $finfo->file($file->getRealPath());
        
        if (!in_array($realMimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException(
                'Tipe file tidak diizinkan. Hanya file PDF yang diterima.'
            );
        }

        // Additional check: ensure file has actual content
        if ($file->getSize() === 0) {
            throw new \InvalidArgumentException('File tidak boleh kosong.');
        }
    }
    
    /**
     * Calculate SHA-256 hash of a file.
     */
    public function calculateHash(string $filePath): string
    {
        return hash_file('sha256', $filePath);
    }
    
    /**
     * Generate a short fingerprint from hash (first 16 chars).
     */
    public function generateFingerprint(string $hash): string
    {
        return strtoupper(substr($hash, 0, 16));
    }
    
    /**
     * Create a new version of an existing document.
     */
    public function createNewVersion(Document $originalDocument, UploadedFile $file, array $metadata): Document
    {
        // Mark old document as superseded
        $originalDocument->update(['status' => 'superseded']);
        $originalDocument->verificationRecord?->update(['status' => 'superseded']);
        
        // Store the new file
        $path = $file->store('documents', 'local');
        $fullPath = Storage::disk('local')->path($path);

        // Ensure PDF is compatible with FPDI
        $this->pdfCompatibilityService->ensureCompatible($fullPath);

        $hash = $this->calculateHash($fullPath);
        $fingerprint = $this->generateFingerprint($hash);
        
        // Create new version
        $newDocument = Document::create([
            'title' => $metadata['title'] ?? $originalDocument->title,
            'doc_number' => $metadata['doc_number'] ?? $originalDocument->doc_number . '-v' . ($originalDocument->version + 1),
            'doc_date' => $metadata['doc_date'] ?? now(),
            'doc_type' => $originalDocument->doc_type,
            'unit' => $originalDocument->unit,
            'classification' => $originalDocument->classification,
            'status' => 'draft',
            'version' => $originalDocument->version + 1,
            'creator_id' => auth()->id(),
            'parent_id' => $originalDocument->id,
            'file_path' => $path,
            'hash' => $hash,
            'fingerprint' => $fingerprint,
            'sign_mode' => $originalDocument->sign_mode,
            'notes' => $metadata['notes'] ?? null,
        ]);
        
        // Create verification record for new version
        VerificationRecord::create([
            'document_id' => $newDocument->id,
            'token' => $newDocument->qr_token,
            'fingerprint' => $fingerprint,
            'status' => 'valid',
        ]);
        
        // Log the version creation
        AuditLog::log(
            'version_created',
            "Versi baru dokumen '{$newDocument->title}' (v{$newDocument->version}) dibuat",
            auth()->id(),
            $newDocument->id,
            ['parent_id' => $originalDocument->id]
        );
        
        return $newDocument;
    }
    
    /**
     * Update document status.
     */
    public function updateStatus(Document $document, string $status): void
    {
        $oldStatus = $document->status;
        $document->update(['status' => $status]);
        
        // Sync verification record status if applicable
        if (in_array($status, ['superseded', 'revoked'])) {
            $document->verificationRecord?->update(['status' => $status]);
        } elseif ($status === 'signed_valid') {
            $document->verificationRecord?->update(['status' => 'valid']);
        }
        
        AuditLog::log(
            'status_change',
            "Status dokumen '{$document->title}' berubah dari {$oldStatus} ke {$status}",
            auth()->id(),
            $document->id,
            ['old_status' => $oldStatus, 'new_status' => $status]
        );
    }
    
    /**
     * Revoke a document.
     */
    public function revoke(Document $document, string $reason): void
    {
        $document->update(['status' => 'revoked']);
        $document->verificationRecord?->update(['status' => 'revoked']);
        
        AuditLog::log(
            'revoke',
            "Dokumen '{$document->title}' dicabut. Alasan: {$reason}",
            auth()->id(),
            $document->id,
            ['reason' => $reason]
        );
    }
    
    /**
     * Get the PDF file path for a document.
     */
    public function getFilePath(Document $document, bool $signed = false): ?string
    {
        $path = $signed && $document->signed_file_path 
            ? $document->signed_file_path 
            : $document->file_path;
            
        return Storage::disk('local')->path($path);
    }

    /**
     * Update the file of an existing document (e.g. for Draft corrections).
     */
    public function updateFile(Document $document, UploadedFile $file): Document
    {
        $this->validateFileSecurity($file);
        
        // Delete old file
        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
             Storage::disk('local')->delete($document->file_path);
        }
        
        // Generate new filename
        $randomFilename = Str::uuid() . '.pdf';
        $path = $file->storeAs('documents/secure', $randomFilename, 'local');
        
        // Calculate hash
        $fullPath = Storage::disk('local')->path($path);

        // Ensure PDF is compatible with FPDI
        $this->pdfCompatibilityService->ensureCompatible($fullPath);

        $hash = $this->calculateHash($fullPath);
        $fingerprint = $this->generateFingerprint($hash);
        
        $document->update([
            'file_path' => $path,
            'hash' => $hash,
            'fingerprint' => $fingerprint,
        ]);
        
        // Update VerificationRecord
        $document->verificationRecord?->update([
            'fingerprint' => $fingerprint,
        ]);

        AuditLog::log(
            'file_update',
            "File dokumen '{$document->title}' diperbarui",
            auth()->id(),
            $document->id
        );
        
        return $document;
    }
}
