<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BatchVerificationController extends Controller
{
    /**
     * Display the batch verification page with search.
     */
    public function show(string $token)
    {
        $batch = DocumentBatch::where('qr_token', $token)
            ->where('status', 'active')
            ->first();

        if (!$batch) {
            return view('verification.not-found');
        }

        return view('verification.batch', compact('batch', 'token'));
    }

    /**
     * Search documents in a batch by NIM/identifier.
     */
    public function search(Request $request, string $token)
    {
        $batch = DocumentBatch::where('qr_token', $token)
            ->where('status', 'active')
            ->first();

        if (!$batch) {
            return response()->json(['error' => 'Batch tidak ditemukan'], 404);
        }

        $query = $request->get('q', '');

        if (empty($query)) {
            return response()->json(['documents' => []]);
        }

        $documents = Document::where('batch_id', $batch->id)
            ->where(function ($q) use ($query) {
                $q->where('identifier', 'like', '%' . $query . '%')
                  ->orWhere('title', 'like', '%' . $query . '%');
            })
            ->select(['uuid', 'identifier', 'title', 'doc_number', 'doc_date', 'status'])
            ->limit(20)
            ->get();

        return response()->json(['documents' => $documents]);
    }

    /**
     * Preview a document from a batch.
     */
    public function preview(string $token, string $documentUuid)
    {
        $batch = DocumentBatch::where('qr_token', $token)
            ->where('status', 'active')
            ->first();

        if (!$batch) {
            abort(404, 'Batch tidak ditemukan.');
        }

        $document = Document::where('uuid', $documentUuid)
            ->where('batch_id', $batch->id)
            ->first();

        if (!$document) {
            abort(404, 'Dokumen tidak ditemukan dalam batch ini.');
        }

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
     * Download a document from a batch.
     */
    public function download(string $token, string $documentUuid)
    {
        $batch = DocumentBatch::where('qr_token', $token)
            ->where('status', 'active')
            ->first();

        if (!$batch) {
            abort(404, 'Batch tidak ditemukan.');
        }

        $document = Document::where('uuid', $documentUuid)
            ->where('batch_id', $batch->id)
            ->first();

        if (!$document) {
            abort(404, 'Dokumen tidak ditemukan dalam batch ini.');
        }

        $path = $document->signed_file_path ?? $document->file_path;

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }

        $filename = ($document->identifier ?? 'document') . '.pdf';

        return Storage::disk('local')->download($path, $filename);
    }
}
