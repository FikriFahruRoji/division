<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentBatch;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class DocumentBatchController extends Controller
{
    /**
     * Display a listing of batches.
     */
    public function index(Request $request)
    {
        $query = DocumentBatch::with('creator')->withCount('documents');

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $batches = $query->latest()->paginate(10)->withQueryString();

        return view('batches.index', compact('batches'));
    }

    /**
     * Show the form for creating a new batch.
     */
    public function create()
    {
        return view('batches.create');
    }

    /**
     * Store a newly created batch with bulk uploaded documents.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'doc_type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'files' => ['required'],
            'files.*' => ['file', 'mimes:pdf', 'max:10240'],
        ], [
            'name.required' => 'Nama batch wajib diisi.',
            'doc_type.required' => 'Jenis dokumen wajib diisi.',
            'files.required' => 'Minimal upload 1 file PDF.',
            'files.*.mimes' => 'File harus berformat PDF.',
            'files.*.max' => 'Ukuran file maksimal 10MB.',
        ]);

        // Create the batch
        $batch = DocumentBatch::create([
            'name' => $request->name,
            'doc_type' => $request->doc_type,
            'description' => $request->description,
            'creator_id' => auth()->id(),
            'status' => 'active',
        ]);

        $uploadedCount = 0;

        // Check if a ZIP file was uploaded
        $files = $request->file('files');

        foreach ($files as $file) {
            if ($file->getClientOriginalExtension() === 'zip') {
                $uploadedCount += $this->processZipFile($file, $batch);
            } else {
                $this->processPdfFile($file, $batch);
                $uploadedCount++;
            }
        }

        // Update document count
        $batch->updateDocumentCount();

        return redirect()->route('batches.show', $batch)
            ->with('success', "Batch berhasil dibuat dengan {$uploadedCount} dokumen.");
    }

    /**
     * Display the specified batch.
     */
    public function show(DocumentBatch $batch)
    {
        $batch->load(['creator', 'documents' => function ($q) {
            $q->orderBy('identifier');
        }]);

        return view('batches.show', compact('batch'));
    }

    /**
     * Remove the specified batch and its documents.
     */
    public function destroy(DocumentBatch $batch)
    {
        // Delete all document files
        foreach ($batch->documents as $document) {
            if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }
        }

        $batch->delete();

        return redirect()->route('batches.index')
            ->with('success', 'Batch dan semua dokumen berhasil dihapus.');
    }

    /**
     * Download QR code as PNG.
     */
    public function downloadQr(DocumentBatch $batch)
    {
        $qrUrl = $batch->getVerificationUrl();

        // Generate QR code using Google Charts API (simple approach)
        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($qrUrl);

        $imageContent = file_get_contents($qrImageUrl);

        return response($imageContent, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="qr_batch_' . Str::slug($batch->name) . '.png"',
        ]);
    }

    /**
     * Process a single PDF file and create a document.
     */
    private function processPdfFile($file, DocumentBatch $batch): void
    {
        // Extract NIM from filename (e.g., "2210001.pdf" → "2210001")
        $identifier = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Store the file
        $path = $file->store('documents/batches/' . $batch->uuid, 'local');

        // Calculate hash and fingerprint
        $fullPath = Storage::disk('local')->path($path);
        $hash = hash_file('sha256', $fullPath);
        $fingerprint = strtoupper(substr($hash, 0, 16));

        // Create document record
        Document::create([
            'title' => $batch->name . ' - ' . $identifier,
            'doc_number' => 'BATCH-' . substr($batch->uuid, 0, 8) . '/' . $identifier,
            'doc_date' => now(),
            'doc_type' => $batch->doc_type,
            'unit' => '-',
            'classification' => 'biasa',
            'status' => 'signed_valid',
            'creator_id' => auth()->id(),
            'batch_id' => $batch->id,
            'identifier' => $identifier,
            'file_path' => $path,
            'hash' => $hash,
            'fingerprint' => $fingerprint,
            'sign_mode' => 'single',
        ]);
    }

    /**
     * Process a ZIP file containing PDFs.
     */
    private function processZipFile($file, DocumentBatch $batch): int
    {
        $count = 0;
        $zipPath = $file->store('temp', 'local');
        $fullZipPath = Storage::disk('local')->path($zipPath);

        $zip = new ZipArchive();
        if ($zip->open($fullZipPath) === true) {
            $tempDir = storage_path('app/temp/zip_' . Str::random(10));
            $zip->extractTo($tempDir);
            $zip->close();

            // Process each PDF in the extracted folder
            $pdfFiles = glob($tempDir . '/{,*/}*.pdf', GLOB_BRACE);
            foreach ($pdfFiles as $pdfPath) {
                $identifier = pathinfo(basename($pdfPath), PATHINFO_FILENAME);
                $storagePath = 'documents/batches/' . $batch->uuid . '/' . basename($pdfPath);

                Storage::disk('local')->put($storagePath, file_get_contents($pdfPath));

                // Calculate hash and fingerprint
                $fullStoredPath = Storage::disk('local')->path($storagePath);
                $hash = hash_file('sha256', $fullStoredPath);
                $fingerprint = strtoupper(substr($hash, 0, 16));

                Document::create([
                    'title' => $batch->name . ' - ' . $identifier,
                    'doc_number' => 'BATCH-' . substr($batch->uuid, 0, 8) . '/' . $identifier,
                    'doc_date' => now(),
                    'doc_type' => $batch->doc_type,
                    'unit' => '-',
                    'classification' => 'biasa',
                    'status' => 'signed_valid',
                    'creator_id' => auth()->id(),
                    'batch_id' => $batch->id,
                    'identifier' => $identifier,
                    'file_path' => $storagePath,
                    'hash' => $hash,
                    'fingerprint' => $fingerprint,
                    'sign_mode' => 'single',
                ]);

                $count++;
            }

            // Cleanup temp directory
            $this->deleteDirectory($tempDir);
        }

        // Cleanup uploaded zip
        Storage::disk('local')->delete($zipPath);

        return $count;
    }

    /**
     * Recursively delete a directory.
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Preview the first document in the batch (for QR positioning).
     */
    public function previewFirstDocument(DocumentBatch $batch)
    {
        $document = $batch->documents()->orderBy('identifier')->first();

        if (!$document || !Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Dokumen tidak ditemukan.');
        }

        return response()->file(
            Storage::disk('local')->path($document->file_path),
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Apply QR code to all documents in batch at specified position.
     */
    public function applyQr(Request $request, DocumentBatch $batch)
    {
        $request->validate([
            'qr_page' => ['required', 'integer', 'min:1'],
            'qr_x' => ['required', 'numeric'],
            'qr_y' => ['required', 'numeric'],
        ]);

        $qrPosition = [
            'page' => (int) $request->qr_page,
            'x' => (float) $request->qr_x,
            'y' => (float) $request->qr_y,
        ];

        $qrCodeService = app(QrCodeService::class);
        $batchVerificationUrl = $batch->getVerificationUrl();

        // Generate QR code once
        $qrTempPath = storage_path('app/temp_batch_qr_' . $batch->id . '.png');
        $qrCodeService->saveQrToFile($batchVerificationUrl, $qrTempPath);

        $successCount = 0;

        foreach ($batch->documents as $document) {
            try {
                $sourcePath = Storage::disk('local')->path($document->file_path);

                $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
                $pdf->SetAutoPageBreak(false, 0);

                $pageCount = $pdf->setSourceFile($sourcePath);
                $targetPage = min($qrPosition['page'], $pageCount);

                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);

                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);

                    if ($pageNo === $targetPage) {
                        $qrWidth = 25;
                        $qrX = $qrPosition['x'] - ($qrWidth / 2);
                        $qrY = $qrPosition['y'] - ($qrWidth / 2);

                        $pdf->Image($qrTempPath, $qrX, $qrY, $qrWidth, $qrWidth, 'PNG');

                        // Add text below QR
                        $pdf->SetFont('helvetica', '', 7);
                        $pdf->SetTextColor(0, 0, 0);

                        $textY = $qrY + $qrWidth + 2;
                        $pdf->SetXY($qrX - 10, $textY);
                        $pdf->Cell($qrWidth + 20, 4, 'Fingerprint: ' . $document->fingerprint, 0, 1, 'C');

                        $pdf->SetXY($qrX - 10, $textY + 4);
                        $pdf->Cell($qrWidth + 20, 4, 'Scan QR untuk verifikasi', 0, 1, 'C');

                        $pdf->SetXY($qrX - 10, $textY + 8);
                        $pdf->SetFont('helvetica', 'B', 6);
                        $pdf->Cell($qrWidth + 20, 4, $document->doc_number, 0, 1, 'C');
                    }
                }

                // Save as signed file
                $signedPath = 'documents/batches/' . $batch->uuid . '/signed_' . $document->identifier . '.pdf';
                $signedFullPath = Storage::disk('local')->path($signedPath);

                $dir = dirname($signedFullPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                $pdf->Output($signedFullPath, 'F');
                $document->update(['signed_file_path' => $signedPath]);
                $successCount++;
            } catch (\Exception $e) {
                \Log::warning('Failed to embed QR for document ' . $document->identifier . ': ' . $e->getMessage());
            }
        }

        // Cleanup temp QR
        if (file_exists($qrTempPath)) {
            unlink($qrTempPath);
        }

        return redirect()->route('batches.show', $batch)
            ->with('success', "QR code berhasil diterapkan ke {$successCount} dokumen.");
    }
}
