<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Department;
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
        
        // Fix IDOR: Filter by department for regular admins/operators
        if (!$user->isSuperAdmin()) {
            if ($user->department_id) {
                // Filter documents created by users in the same department
                $query->whereHas('creator', function ($q) use ($user) {
                    $q->where('department_id', $user->department_id);
                });
            } elseif ($user->role === 'signer') {
                // Signers handled by policy/query below, but ensuring they don't see random docs
                // Handled in separate controller usually, but good to be safe
            } elseif ($user->role === 'admin' || $user->role === 'operator') {
                // Admin/Operator without department shouldn't see anything (edge case)
                $query->whereRaw('1 = 0');
            }
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

        $document->load(['creator', 'signerAssignments.signer', 'signatures.signer', 'auditLogs.user']);
        
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
        $this->authorize('finalize', $document);
        
        if ($document->signerAssignments()->count() === 0) {
            return back()->with('error', 'Dokumen harus memiliki minimal satu penandatangan.');
        }
        
        $this->signingService->finalizeDocument($document);
        
        return redirect()
            ->route('documents.show', $document)
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

        // Limit dropdown options to the user's own department for non-super-admin
        if (!$user->isSuperAdmin() && $user->department_id) {
            $signersQuery->where('department_id', $user->department_id);
            $departments = $departments->where('id', $user->department_id);
        }

        return [$signersQuery->orderBy('name')->get(), $departments];
    }
}
