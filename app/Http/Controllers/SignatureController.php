<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\SigningService;
use Illuminate\Http\Request;

class SignatureController extends Controller
{
    public function __construct(
        protected SigningService $signingService
    ) {}

    /**
     * Display list of documents pending signature for current user.
     */
    public function pending()
    {
        $user = auth()->user();
        
        $documents = Document::whereHas('signerAssignments', function ($query) use ($user) {
            $query->where('signer_id', $user->id)
                  ->whereIn('status', ['pending', 'notified']);
        })
        ->where('status', 'pending_signature')
        ->with(['creator', 'signerAssignments.signer'])
        ->latest()
        ->paginate(15);
        
        return view('signatures.pending', compact('documents'));
    }

    /**
     * Show document for signing.
     */
    public function show(Document $document)
    {
        $user = auth()->user();
        
        // Check if user is assigned to this document
        $assignment = $document->signerAssignments()
            ->where('signer_id', $user->id)
            ->first();
            
        if (!$assignment && !$user->isAdmin()) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }
        
        $document->load(['creator', 'signerAssignments.signer', 'signatures.signer']);
        
        $canSign = $assignment 
            && in_array($assignment->status, ['pending', 'notified']) 
            && $document->status === 'pending_signature';
        
        // For sequential mode, check if it's this signer's turn
        if ($canSign && $document->sign_mode === 'sequential') {
            $nextSigner = $document->getNextSigner();
            $canSign = $nextSigner && $nextSigner->signer_id === $user->id;
        }
        
        return view('signatures.show', compact('document', 'assignment', 'canSign'));
    }

    /**
     * Approve and sign the document.
     */
    public function approve(Request $request, Document $document)
    {
        $this->authorize('sign', $document);
        
        $user = auth()->user();
        
        // Get QR position from request
        $qrPosition = [
            'page' => (int) $request->input('qr_page', 1),
            'x' => $request->input('qr_x') ? (float) $request->input('qr_x') : null,
            'y' => $request->input('qr_y') ? (float) $request->input('qr_y') : null,
            'width' => $request->input('qr_width') ? (int) $request->input('qr_width') : 20,
        ];
        
        try {
            $this->signingService->signDocument($document, $user, $qrPosition);
            
            $freshDoc = $document->fresh();
            if ($freshDoc->isSignedValid()) {
                return redirect()
                    ->route('documents.show', $freshDoc)
                    ->with('success', 'Dokumen berhasil ditandatangani dan siap diunduh.');
            }
                
            return redirect()
                ->route('signatures.pending')
                ->with('success', 'Dokumen berhasil ditandatangani. Menunggu penandatangan lainnya.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject the document.
     */
    public function reject(Request $request, Document $document)
    {
        $this->authorize('sign', $document);
        
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);
        
        $user = auth()->user();
        
        try {
            $this->signingService->rejectDocument($document, $user, $validated['reason']);
            
            return redirect()
                ->route('signatures.pending')
                ->with('success', 'Dokumen berhasil ditolak.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * History of signed documents.
     */
    public function history()
    {
        $user = auth()->user();
        
        $documents = Document::whereHas('signerAssignments', function ($query) use ($user) {
            $query->where('signer_id', $user->id)
                  ->where('status', 'signed');
        })
        ->with(['creator', 'signerAssignments.signer'])
        ->latest()
        ->paginate(15);
        
        return view('signatures.history', compact('documents'));
    }
}
