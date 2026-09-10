<?php

namespace App\Http\Controllers;

use App\Models\VerificationRecord;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    public function __construct(
        protected VerificationService $verificationService
    ) {}

    /**
     * Display verification result for a document.
     */
    public function show(string $token)
    {
        $result = $this->verificationService->verify($token);
        
        if (!$result) {
            return view('verification.not-found');
        }
        
        $statusLabel = $this->verificationService->getStatusLabel($result['status']);
        $statusColor = $this->verificationService->getStatusColor($result['status']);
        
        return view('verification.show', compact('result', 'statusLabel', 'statusColor', 'token'));
    }

    /**
     * Preview the document via verification token.
     */
    public function preview(string $token, ?string $filename = null)
    {
        $record = VerificationRecord::where('token', $token)->first();
        
        if (!$record) {
            abort(404, 'Dokumen tidak ditemukan.');
        }
        
        $document = $record->document;
        if (!$document) {
            abort(404, 'Dokumen tidak ditemukan.');
        }
        
        $path = $document->signed_file_path ?? $document->file_path;
        
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }

        $safeDocNumber = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($document->doc_number ?? ''));
        $expectedFilename = ($safeDocNumber ?: 'dokumen') . '_' . ($document->signed_file_path ? 'signed' : 'original') . '.pdf';
        
        $fullPath = Storage::disk('local')->path($path);
        
        return response()->file(
            $fullPath,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $expectedFilename . '"; filename*=UTF-8\'\'' . rawurlencode($expectedFilename),
                'Content-Length' => (string) filesize($fullPath),
            ]
        );
    }

    /**
     * Download the document via verification token.
     * Dinonaktifkan untuk verifikasi QR (semua role), dialihkan ke preview.
     */
    public function download(string $token, ?string $filename = null)
    {
        $record = VerificationRecord::where('token', $token)->first();
        if ($record && $record->document) {
            $safeDocNumber = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($record->document->doc_number ?? ''));
            $expectedFilename = ($safeDocNumber ?: 'dokumen') . '_' . ($record->document->signed_file_path ? 'signed' : 'original') . '.pdf';
            return redirect()->route('verify.preview', ['token' => $token, 'filename' => $expectedFilename]);
        }
        return redirect()->route('verify.preview', ['token' => $token]);
    }
}
