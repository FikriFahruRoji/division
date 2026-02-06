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
    public function preview(string $token)
    {
        $record = VerificationRecord::where('token', $token)->first();
        
        if (!$record || $record->status !== 'valid') {
            abort(404, 'Dokumen tidak ditemukan atau tidak valid.');
        }
        
        $document = $record->document;
        $path = $document->signed_file_path ?? $document->file_path;
        
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }
        
        return response()->file(
            Storage::disk('local')->path($path),
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Download the document via verification token.
     */
    public function download(string $token)
    {
        $record = VerificationRecord::where('token', $token)->first();
        
        if (!$record || $record->status !== 'valid') {
            abort(404, 'Dokumen tidak ditemukan atau tidak valid.');
        }
        
        $document = $record->document;
        $path = $document->signed_file_path ?? $document->file_path;
        
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }
        
        $filename = str_replace(' ', '_', $document->title) . '_' . $document->doc_number . '.pdf';
        
        return Storage::disk('local')->download($path, $filename);
    }
}
