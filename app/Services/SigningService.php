<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Signature;
use App\Models\SignerAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use TCPDF;
use setasign\Fpdi\Tcpdf\Fpdi;

class SigningService
{
    protected QrCodeService $qrCodeService;
    protected CertificateService $certificateService;
    protected PdfCompatibilityService $pdfCompatibilityService;
    
    public function __construct(
        QrCodeService $qrCodeService, 
        CertificateService $certificateService,
        PdfCompatibilityService $pdfCompatibilityService
    )
    {
        $this->qrCodeService = $qrCodeService;
        $this->certificateService = $certificateService;
        $this->pdfCompatibilityService = $pdfCompatibilityService;
    }
    
    /**
     * Assign signers to a document.
     */
    public function assignSigners(Document $document, array $signerIds, string $mode = 'single'): void
    {
        // For now, enforce single signature mode (sequential and parallel disabled)
        $mode = 'single';
        $signerIds = array_slice($signerIds, 0, 1);

        // Update document sign mode and clear previous signatures
        // Recalculate original hash to ensure it matches the original file
        $originalPath = Storage::disk('local')->path($document->file_path);
        $originalHash = hash_file('sha256', $originalPath);

        $document->update([
            'sign_mode' => $mode,
            'signed_file_path' => null,
            'hash' => $originalHash
        ]);
        
        // Remove existing assignments
        $document->signerAssignments()->delete();
        
        // Create new assignments
        foreach ($signerIds as $index => $signerId) {
            SignerAssignment::create([
                'document_id' => $document->id,
                'signer_id' => $signerId,
                'order_index' => $index + 1,
                'status' => 'pending',
            ]);
        }
        
        AuditLog::log(
            'assign_signers',
            "Penandatangan ditugaskan ke dokumen '{$document->title}'",
            auth()->id(),
            $document->id,
            ['signer_ids' => $signerIds, 'mode' => $mode]
        );
    }
    
    /**
     * Finalize document for signing (change status, notify signers).
     * Note: QR code is now added during signing at position chosen by signer.
     */
    public function finalizeDocument(Document $document): void
    {
        // RA-06: Atomic transaction with row lock to prevent stale finalize overwriting active/completed signatures
        \Illuminate\Support\Facades\DB::transaction(function () use ($document) {
            $lockedDoc = Document::where('id', $document->id)->lockForUpdate()->firstOrFail();

            if (!in_array($lockedDoc->status, ['draft', 'rejected'])) {
                throw new \Exception('Dokumen tidak dapat diajukan karena status saat ini bukan draft atau perbaikan (' . $lockedDoc->status . ').');
            }

            // Reset assignments and signatures (in case of re-finalization after rejection)
            $lockedDoc->signerAssignments()->update([
                'status' => 'pending',
                'signed_at' => null,
                'rejection_reason' => null
            ]);

            // Update document status and clear signed file (Reset to original)
            $originalPath = Storage::disk('local')->path($lockedDoc->file_path);
            if (!file_exists($originalPath)) {
                throw new \Exception('Berkas asli dokumen tidak ditemukan di penyimpanan.');
            }
            $originalHash = hash_file('sha256', $originalPath);

            // Delete old signatures on re-finalization (SEC-18)
            $lockedDoc->signatures()->delete();

            $lockedDoc->update([
                'status' => 'pending_signature',
                'signed_file_path' => null,
                'hash' => $originalHash
            ]);

            if (\Illuminate\Support\Facades\Schema::hasTable('verification_records')) {
                $lockedDoc->verificationRecord?->update([
                    'status' => 'pending',
                    'fingerprint' => $lockedDoc->fingerprint,
                ]);
            }
            
            // Notify first signer(s) based on mode
            $this->notifyNextSigners($lockedDoc);
            
            AuditLog::log(
                'finalize',
                "Dokumen '{$lockedDoc->title}' difinalisasi dan siap untuk ditandatangani",
                auth()->id() ?? $lockedDoc->creator_id,
                $lockedDoc->id
            );
        });
    }
    
    /**
     * Mark signers as notified based on signing mode.
     */
    protected function notifyNextSigners(Document $document): void
    {
        if ($document->sign_mode === 'parallel') {
            // Notify all signers at once
            $document->signerAssignments()
                ->where('status', 'pending')
                ->each(function($assignment) {
                    $assignment->markAsNotified();
                    $assignment->signer->notify(new \App\Notifications\SignatureRequired($assignment));
                });
        } else {
            // Sequential or single - notify next in order
            $nextAssignment = $document->getNextSigner();
            if ($nextAssignment) {
                $nextAssignment->markAsNotified();
                $nextAssignment->signer->notify(new \App\Notifications\SignatureRequired($nextAssignment));
            }
        }
    }
    
    /**
     * Sign a document.
     */
    public function signDocument(Document $document, User $signer, array $qrPosition = [], ?string $authMethod = null): Signature
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($document, $signer, $qrPosition, $authMethod) {
            // Lock document and assignment for update (SEC-09)
            $lockedDoc = Document::where('id', $document->id)->lockForUpdate()->firstOrFail();
            
            if (!$lockedDoc->isPendingSignature()) {
                throw new \Exception('Dokumen tidak dalam status menunggu tanda tangan.');
            }

            $assignment = $lockedDoc->signerAssignments()
                ->where('signer_id', $signer->id)
                ->lockForUpdate()
                ->first();
                
            if (!$assignment || !in_array($assignment->status, ['pending', 'notified'])) {
                throw new \Exception('Anda tidak memiliki izin atau sudah menandatangani dokumen ini.');
            }

            // Prevent duplicate signatures
            if ($lockedDoc->signatures()->where('signer_id', $signer->id)->exists()) {
                throw new \Exception('Anda sudah menandatangani dokumen ini.');
            }
            
            // For sequential mode, enforce signing order
            if ($lockedDoc->sign_mode === 'sequential') {
                $nextSigner = $lockedDoc->getNextSigner();
                if (!$nextSigner || $nextSigner->signer_id !== $signer->id) {
                    throw new \Exception('Belum giliran Anda untuk menandatangani dokumen ini. Harap tunggu penandatangan sebelumnya.');
                }
            }
            
            // Get the source PDF (signed version if exists, otherwise original with QR)
            $sourcePath = $lockedDoc->signed_file_path 
                ? Storage::disk('local')->path($lockedDoc->signed_file_path)
                : Storage::disk('local')->path($lockedDoc->file_path);

            // RA-05: Strict source file integrity verification before signing
            if (!file_exists($sourcePath)) {
                throw new \Exception('Berkas sumber dokumen tidak ditemukan di penyimpanan.');
            }
            $sourceActualHash = hash_file('sha256', $sourcePath);
            $expectedSourceHash = $lockedDoc->signed_file_path
                ? $lockedDoc->signatures()->latest()->first()?->signed_hash
                : $lockedDoc->hash;

            if ($expectedSourceHash && $sourceActualHash !== $expectedSourceHash) {
                throw new \Exception('Integritas berkas sumber telah berubah atau tidak sesuai dengan hash dokumen yang disetujui (RA-05).');
            }
                
            // Apply visual signature to PDF with custom QR position
            $signedPdfPath = $this->applyVisualSignature($lockedDoc, $signer, $sourcePath, $qrPosition);

            // Re-verify after PDF generation to prevent interleaving race conditions (SEC-09)
            if ($assignment->fresh()->status === 'signed' || $lockedDoc->fresh()->signatures()->where('signer_id', $signer->id)->exists()) {
                throw new \Exception('Dokumen atau penugasan telah ditandatangani oleh proses lain.');
            }
            
            // Calculate new hash after signing
            $newHash = hash_file('sha256', Storage::disk('local')->path($signedPdfPath));
            $newFingerprint = strtoupper(substr($newHash, 0, 16));
            
            // Create signature record
            $signature = Signature::create([
                'document_id' => $lockedDoc->id,
                'signer_id' => $signer->id,
                'signature_blob_path' => $signedPdfPath,
                'signed_hash' => $newHash,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'auth_method' => $authMethod,
            ]);
            
            // Update document with new signed file path
            $updateFields = [
                'signed_file_path' => $signedPdfPath,
                'hash' => $newHash,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn($lockedDoc->getTable(), 'fingerprint')) {
                $updateFields['fingerprint'] = $newFingerprint;
            }
            $lockedDoc->update($updateFields);
            
            // Mark assignment as signed
            $assignment->markAsSigned();
            
            // Check if all signers have signed
            if ($lockedDoc->fresh()->allSignersSigned()) {
                $lockedDoc->update(['status' => 'signed_valid']);
                
                // Update verification record to valid upon full approval & final seal (SEC-01)
                if (\Illuminate\Support\Facades\Schema::hasTable('verification_records')) { $lockedDoc->verificationRecord?->update([
                    'status' => 'valid',
                    'fingerprint' => $newFingerprint,
                ]); }

                AuditLog::log(
                    'fully_signed',
                    "Dokumen '{$lockedDoc->title}' telah ditandatangani lengkap",
                    $signer->id,
                    $lockedDoc->id
                );
            } else {
                // Notify next signer (for sequential mode)
                $this->notifyNextSigners($lockedDoc);
            }
        
            AuditLog::log(
                'sign',
                "Dokumen '{$lockedDoc->title}' ditandatangani oleh {$signer->name}",
                $signer->id,
                $lockedDoc->id,
                ['signer_position' => $signer->position, 'qr_position' => $qrPosition, 'auth_method' => $authMethod]
            );
            
            return $signature;
        });
    }
    
    /**
     * Apply visual signature and QR code to PDF.
     */
    protected function applyVisualSignature(Document $document, User $signer, string $sourcePath, array $qrPosition = []): string
    {
        // Ensure source PDF is compatible with FPDI free parser (normalizes if compressed streams exist)
        $sourcePath = $this->pdfCompatibilityService->ensureCompatible($sourcePath);

        // RA-04: Institutional System Seal - cryptographic signature uses official System Seal
        // while individual approvals are immutably captured in database ledger and visual stamp
        $certPaths = $this->certificateService->getSystemCertificate();
        
        $pdf = new Fpdi();
        
        // Digital Signature Configuration (System Seal)
        $info = array(
            'Name' => config('app.name', 'TTD Digital') . ' Segel Sistem Internal',
            'Location' => config('app.name', 'TTD Digital'),
            'Reason' => "Segel sistem internal (tidak tersertifikasi PSrE): disetujui oleh {$signer->name}",
            'ContactInfo' => config('mail.from.address', 'noreply@uniga.ac.id'),
        );

        // Load certificate and key content
        $certContent = file_get_contents($certPaths['cert']);
        $keyContent = file_get_contents($certPaths['key']);

        // Set document signature (key is encrypted at rest, unlocked with the seal passphrase)
        $pdf->setSignature($certContent, $keyContent, $certPaths['passphrase'], '', 2, $info);
        
        $pageCount = $pdf->setSourceFile($sourcePath);
        
        // Get existing signature count to position new signature
        $signatureIndex = $document->signatures()->count();
        
        // Determine target page for signature/QR (use qrPosition page or default to last page)
        $targetPage = isset($qrPosition['page']) && $qrPosition['page'] > 0 && $qrPosition['page'] <= $pageCount
            ? $qrPosition['page']
            : $pageCount;
        
        // Generate QR code file for this signing
        $qrTempPath = $this->qrCodeService->generateQrFile($document);
        
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
            
            // Add QR on target page
            if ($pageNo === $targetPage) {
                // Add QR code at the chosen position
                if (isset($qrPosition['x']) && isset($qrPosition['y']) && $qrPosition['x'] !== null && $qrPosition['y'] !== null) {
                    $qrX = $qrPosition['x'];
                    $qrY = $qrPosition['y'];
                    
                    // Use custom width or default
                    $qrWidth = isset($qrPosition['width']) ? $qrPosition['width'] : 20;
                    
                    // Ensure QR stays within page bounds
                    $margin = 10;
                    $qrX = max($margin + $qrWidth/2, min($qrX, $size['width'] - $qrWidth/2 - $margin));
                    $qrY = max($margin + $qrWidth/2, min($qrY, $size['height'] - $qrWidth/2 - $margin));
                    
                    $this->qrCodeService->addQrAtPosition($pdf, $qrTempPath, $qrX, $qrY, $qrWidth);
                    
                    // Add visible signature box ONLY if explicit visual signature is requested (handled by visual signature method)
                    // Or we could attach a visual annotation for the digital signature here
                    // For now, we rely on the QR code and signature panel validation
                    
                    // Define active area for digital signature (invisible or covering the QR)
                    // TCPDF automatically creates a signature widget, but we can't easily control its position 
                    // relative to the visual content in standard TCPDF without more complex code.
                    // By default TCPDF adds the signature, it's invisible on the page content but visible in the "Signatures" panel.
                } else {
                    // Default position: bottom-right corner
                    $qrWidth = isset($qrPosition['width']) ? $qrPosition['width'] : 20;
                    $qrX = $size['width'] - ($qrWidth + 5);
                    $qrY = $size['height'] - ($qrWidth + 15);
                    $this->qrCodeService->addQrAtPosition($pdf, $qrTempPath, $qrX, $qrY, $qrWidth);
                }
            }
        }
        
        // Cleanup temporary QR file
        $this->qrCodeService->cleanupQrFile($qrTempPath);
        
        // Ensure directory exists
        Storage::makeDirectory('documents');
        
        // Save the signed PDF
        $signedPath = 'documents/signed_' . $document->id . '_sig' . ($signatureIndex + 1) . '_' . time() . '.pdf';
        $signedFullPath = Storage::disk('local')->path($signedPath);
        
        $pdf->Output($signedFullPath, 'F');
        
        return $signedPath;
    }
    
    /**
     * Add visual signature block to PDF page.
     */
    protected function addSignatureToPage(Fpdi $pdf, User $signer, array $pageSize, int $signatureIndex, array $qrPosition = []): void
    {
        $boxWidth = 50;
        $boxHeight = 25;
        $margin = 15;
        
        // Use custom position if provided, otherwise calculate auto position
        if (isset($qrPosition['x']) && isset($qrPosition['y']) && $qrPosition['x'] !== null && $qrPosition['y'] !== null) {
            // Convert from center position to top-left corner
            $x = $qrPosition['x'] - ($boxWidth / 2);
            $y = $qrPosition['y'] - ($boxHeight / 2);
            
            // Ensure signature stays within page bounds
            $x = max($margin, min($x, $pageSize['width'] - $boxWidth - $margin));
            $y = max($margin, min($y, $pageSize['height'] - $boxHeight - $margin));
        } else {
            // Auto-position: arrange signatures in a row at the bottom
            $signaturesPerRow = 3;
            $row = intval($signatureIndex / $signaturesPerRow);
            $col = $signatureIndex % $signaturesPerRow;
            
            $x = $margin + ($col * ($boxWidth + 10));
            $y = $pageSize['height'] - $boxHeight - 45 - ($row * ($boxHeight + 10));
        }
        
        // Draw signature box
        $pdf->SetDrawColor(100, 100, 100);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect($x, $y, $boxWidth, $boxHeight);
        
        // Add "Ditandatangani secara digital"
        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetXY($x, $y + 2);
        $pdf->Cell($boxWidth, 4, 'Ditandatangani secara digital oleh:', 0, 1, 'C');
        
        // Add signer name
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x, $y + 7);
        $pdf->Cell($boxWidth, 5, $signer->name, 0, 1, 'C');
        
        // Add NIP/NIDN (if available)
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY($x, $y + 12);
        $pdf->Cell($boxWidth, 4, $signer->position ?? '', 0, 1, 'C');

        // Add NIP/NIDN below position
        if (!empty($signer->nip_nidn)) {
            $pdf->SetFont('helvetica', '', 6);
            $pdf->SetXY($x, $y + 16);
            $pdf->Cell($boxWidth, 4, 'NIP/NIDN: ' . $signer->nip_nidn, 0, 1, 'C');
        }
        
        // Add date
        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetTextColor(100, 100, 100);
        $yDate = !empty($signer->nip_nidn) ? $y + 20 : $y + 18;
        $pdf->SetXY($x, $yDate);
        $pdf->Cell($boxWidth, 4, now()->format('d/m/Y H:i'), 0, 1, 'C');
    }
    
    /**
     * Reject a document signing.
     */
    public function rejectDocument(Document $document, User $signer, string $reason, ?string $authMethod = null): void
    {
        // RA-11: Atomic transactional rejection with row locking
        \Illuminate\Support\Facades\DB::transaction(function () use ($document, $signer, $reason, $authMethod) {
            $lockedDoc = Document::where('id', $document->id)->lockForUpdate()->firstOrFail();
            $assignment = $lockedDoc->signerAssignments()
                ->where('signer_id', $signer->id)
                ->whereIn('status', ['pending', 'notified'])
                ->lockForUpdate()
                ->first();
                
            if (!$assignment) {
                throw new \Exception('Anda tidak memiliki izin untuk menolak dokumen ini.');
            }
            
            $assignment->markAsRejected($reason);
            
            // Update document status to rejected
            $lockedDoc->update(['status' => 'rejected']);
            if (\Illuminate\Support\Facades\Schema::hasTable('verification_records')) {
                $lockedDoc->verificationRecord?->update(['status' => 'rejected']);
            }
            
            AuditLog::log(
                'reject',
                "Dokumen '{$lockedDoc->title}' ditolak oleh {$signer->name}. Alasan: {$reason}",
                $signer->id,
                $lockedDoc->id,
                ['reason' => $reason, 'auth_method' => $authMethod]
            );
        });
    }
}
