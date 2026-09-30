<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentService;
use App\Services\SigningService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SignerDocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
        protected SigningService $signingService
    ) {}

    /**
     * Show the self-upload form for signers.
     */
    public function create()
    {
        return view('signer-documents.create');
    }

    /**
     * Store and auto-finalize document for self-signing.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
            'title' => 'required|string|max:255',
            'doc_number' => 'required|string|max:100|unique:documents,doc_number',
            'doc_date' => 'required|date',
            'doc_type' => 'required|string|max:100',
            'unit' => 'required|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();

        // Upload document
        $document = $this->documentService->upload(
            $request->file('file'),
            $validated,
            $user->id
        );

        // Auto-assign self as signer
        $this->signingService->assignSigners(
            $document,
            [$user->id],
            'single'
        );

        // Auto-finalize
        $this->signingService->finalizeDocument($document);

        return redirect()
            ->route('signatures.show', $document)
            ->with('success', 'Dokumen berhasil diunggah. Silakan tandatangani dokumen.');
    }

    /**
 * Show list of user's documents (uploaded and assigned to sign).
 */
public function index(Request $request)
{
    $user = auth()->user();
    $tab = $request->get('tab', 'all');

    $query = Document::query()
        ->where(function ($q) use ($user) {
            // Documents uploaded by user
            $q->where('creator_id', $user->id)
              // OR Documents where user is assigned as signer
              ->orWhereHas('signerAssignments', function ($sq) use ($user) {
                  $sq->where('signer_id', $user->id);
              });
        })
        ->with(['signerAssignments.signer', 'creator']);

    // Filter by tab
    if ($tab === 'uploaded') {
        $query->where('creator_id', $user->id);
    } elseif ($tab === 'signed') {
        $query->whereHas('signerAssignments', function ($sq) use ($user) {
            $sq->where('signer_id', $user->id)->where('status', 'signed');
        });
    } elseif ($tab === 'pending') {
        $query->whereHas('signerAssignments', function ($sq) use ($user) {
            $sq->where('signer_id', $user->id)->whereIn('status', ['pending', 'notified']);
        })->where('status', 'pending_signature');
    }

    $documents = $query->latest()->paginate(15)->withQueryString();

    // Count for tabs
    $counts = [
        'all' => Document::where(function ($q) use ($user) {
            $q->where('creator_id', $user->id)
              ->orWhereHas('signerAssignments', fn($sq) => $sq->where('signer_id', $user->id));
        })->count(),
        'uploaded' => Document::where('creator_id', $user->id)->count(),
        'signed' => Document::whereHas('signerAssignments', fn($sq) => 
            $sq->where('signer_id', $user->id)->where('status', 'signed')
        )->count(),
        'pending' => Document::whereHas('signerAssignments', fn($sq) => 
            $sq->where('signer_id', $user->id)->whereIn('status', ['pending', 'notified'])
        )->where('status', 'pending_signature')->count(),
    ];

    return view('signer-documents.index', compact('documents', 'tab', 'counts'));
}
}
