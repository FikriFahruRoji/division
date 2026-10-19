<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Department;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\SigningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
        protected SigningService $signingService
    ) {}

    /**
     * Display a listing of documents.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Document::with(['creator', 'signerAssignments.signer'])
            ->latest();
        
        // RA-02 / RA-03: Strict authorization-aligned document query scoping
        if ($user->isSuperAdmin()) {
            // Super admin sees all documents
        } elseif ($user->role === 'admin') {
            if ($user->department_id) {
                // RA-03: Filter strictly by persisted document department_id (with fallback for legacy records)
                $query->where(function ($q) use ($user) {
                    $q->where('department_id', $user->department_id)
                      ->orWhere(fn($sq) => $sq->whereNull('department_id')->whereHas('creator', fn($cq) => $cq->where('department_id', $user->department_id)));
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->isOperator()) {
            // RA-02: Operator sees only their own documents or explicit assignments
            $query->where(function ($q) use ($user) {
                $q->where('creator_id', $user->id)
                  ->orWhereHas('signerAssignments', function ($sq) use ($user) {
                      $sq->where('signer_id', $user->id);
                  });
            });
        } else {
            // Signer sees only documents they created or are assigned to sign
            $query->where(function ($q) use ($user) {
                $q->where('creator_id', $user->id)
                  ->orWhereHas('signerAssignments', function ($sq) use ($user) {
                      $sq->where('signer_id', $user->id);
                  });
            });
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('doc_number', 'like', "%{$search}%");
            });
        }
        
        $documents = $query->paginate(15);
        
        return view('documents.index', compact('documents'));
    }

    /**
     * Show the form for creating a new document.
     */
    public function create()
    {
        [$signers, $departments] = $this->getFormData();

        return view('documents.create', compact('signers', 'departments'));
    }

    /**
     * Store a newly created document.
     */
    public function store(StoreDocumentRequest $request)
    {
        $validated = $request->validated();
        
        $document = $this->documentService->upload(
            $request->file('file'),
            $validated,
            auth()->id()
        );
        
        // Assign signers
        $this->signingService->assignSigners(
            $document,
            $validated['signers'],
            $validated['sign_mode']
        );
        
        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Dokumen berhasil diunggah.');
    }

    /**
     * Display the specified document.
     */
    public function show(Document $document)
    {
        $this->authorize('view', $document);

        $document->load(['creator', 'signerAssignments.signer', 'signatures.signer', 'auditLogs.user', 'allSignerAssignments.signer', 'signatureHistory']);
        
        return view('documents.show', compact('document'));
    }
    /**
     * Show the form for editing the specified document.
     */
    public function edit(Document $document)
    {
        $this->authorize('update', $document);

        [$signers, $departments] = $this->getFormData();

        return view('documents.edit', compact('document', 'signers', 'departments'));
    }

    /**
     * Update the specified document.
     */
    public function update(UpdateDocumentRequest $request, Document $document)
    {
        $this->authorize('update', $document);
        
        $validated = $request->validated();
        
        $document->update($validated);
        
        // Handle file update if present
        if ($request->hasFile('file')) {
            $this->documentService->updateFile($document, $request->file('file'));
        }
        
        // Update signers
        $this->signingService->assignSigners(
            $document,
            $validated['signers'],
            $validated['sign_mode']
        );
        
        // Force update timestamp to indicate activity (crucial for rejected documents to be resubmitted)
        if ($document->wasChanged() === false) {
             $document->touch();
        }
        
        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Dokumen berhasil diperbarui.');
    }

    /**
     * Remove the specified document.
     */
    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        // Record before deleting: the audit entry outlives the document row
        AuditLog::log(
            'delete',
            "Dokumen '{$document->title}' dihapus",
            auth()->id(),
            $document->id,
            [
                'doc_number' => $document->doc_number,
                'status' => $document->status,
                'hash' => $document->hash,
            ]
        );
        
        // Delete file
        if ($document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        }
        
        $document->delete();
        
        // Redirect based on role
        $user = auth()->user();
        $redirectRoute = ($user->isSigner() && !$user->isAdmin() && !$user->isOperator()) 
            ? 'signer-documents.index' 
            : 'documents.index';
        
        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Dokumen berhasil dihapus.');
    }

    /**
     * Finalize document for signing.
     */
    public function finalize(Document $document)
    {
        $freshDoc = $document->fresh();
        $this->authorize('finalize', $freshDoc);
        
        if ($freshDoc->signerAssignments()->count() === 0) {
            return back()->with('error', 'Dokumen harus memiliki minimal satu penandatangan.');
        }

        try {
            $this->signingService->finalizeDocument($freshDoc);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
        
        return redirect()
            ->route('documents.show', $freshDoc)
            ->with('success', 'Dokumen berhasil difinalisasi dan siap untuk ditandatangani.');
    }

    /**
     * Revoke a signed document.
     */
    public function revoke(Request $request, Document $document)
    {
        $this->authorize('revoke', $document);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);
        
        $this->documentService->revoke($document, $validated['reason']);
        
        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Dokumen berhasil dicabut.');
    }

    /**
     * Download PDF document.
     */
    public function download(Document $document, ?string $filename = null)
    {
        $this->authorize('download', $document);
        
        $path = $document->signed_file_path ?? $document->file_path;
        
        if (!Storage::disk('local')->exists($path)) {
            return back()->with('error', 'File tidak ditemukan.');
        }

        $safeDocNumber = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($document->doc_number ?? ''));
        $expectedFilename = ($safeDocNumber ?: 'dokumen') . '_' . ($document->signed_file_path ? 'signed' : 'original') . '.pdf';
        
        $fullPath = Storage::disk('local')->path($path);
        
        return response()->download($fullPath, $expectedFilename, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => (string) filesize($fullPath),
        ]);
    }

    /**
     * Preview PDF document.
     */
    public function preview(Document $document, ?string $filename = null)
    {
        $this->authorize('preview', $document);
        
        $path = $document->signed_file_path ?? $document->file_path;
        
        if (!Storage::disk('local')->exists($path)) {
            return back()->with('error', 'File tidak ditemukan.');
        }

        $safeDocNumber = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($document->doc_number ?? ''));
        $expectedFilename = ($safeDocNumber ?: 'dokumen') . '_' . ($document->signed_file_path ? 'signed' : 'original') . '.pdf';
        
        $fullPath = Storage::disk('local')->path($path);
        
        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $expectedFilename . '"; filename*=UTF-8\'\'' . rawurlencode($expectedFilename),
            'Content-Length' => (string) filesize($fullPath),
        ]);
    }

    /**
     * Build the signers and departments collections for document create/edit forms.
     * Scopes to the authenticated user's department for non-super-admin users.
     *
     * @return array{0: \Illuminate\Database\Eloquent\Collection, 1: \Illuminate\Database\Eloquent\Collection}
     */
    private function getFormData(): array
    {
        $user = auth()->user();

        $departments = Department::where('status', 'active')->orderBy('name')->get();

        $signersQuery = User::where('role', 'signer')
            ->where('status', 'active')
            ->with('department');

        // RA-07: Limit dropdown options to the user's own department for non-super-admin (fail closed if null)
        if (!$user->isSuperAdmin()) {
            if (!$user->department_id) {
                return [collect(), collect()];
            }
            $signersQuery->where('department_id', $user->department_id);
            $departments = $departments->where('id', $user->department_id);
        }

        return [$signersQuery->orderBy('name')->get(), $departments];
    }
}
