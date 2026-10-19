<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\SigningService;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;

class SignatureController extends Controller
{
    public function __construct(
        protected SigningService $signingService,
        protected TwoFactorService $twoFactor
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

        // Check view authorization first (SEC-17)
        $this->authorize('view', $document);
        
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
        
        $needsTwoFactor = $canSign && !$user->hasTwoFactorEnabled();

        return view('signatures.show', compact('document', 'assignment', 'canSign', 'needsTwoFactor'));
    }

    /**
     * Approve and sign the document.
     */
    public function approve(Request $request, Document $document)
    {
        $this->authorize('sign', $document);
        
        $user = auth()->user();

        // T1: signing must be confirmed with a fresh TOTP code
        if ($redirect = $this->requireTwoFactor($document)) {
            return $redirect;
        }
        $request->validate(['otp' => 'required|digits:6']);
        $this->twoFactor->verify($user, $request->input('otp'));
        
        // Get QR position from request
        $qrPosition = [
            'page' => (int) $request->input('qr_page', 1),
            'x' => $request->input('qr_x') ? (float) $request->input('qr_x') : null,
            'y' => $request->input('qr_y') ? (float) $request->input('qr_y') : null,
            'width' => $request->input('qr_width') ? (int) $request->input('qr_width') : 20,
        ];
        
        try {
            $this->signingService->signDocument($document, $user, $qrPosition, 'totp');
            
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
        
        if ($redirect = $this->requireTwoFactor($document)) {
            return $redirect;
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'otp' => 'required|digits:6',
        ]);
        
        $user = auth()->user();
        $this->twoFactor->verify($user, $validated['otp']);
        
        try {
            $this->signingService->rejectDocument($document, $user, $validated['reason'], 'totp');
            
            return redirect()
                ->route('signatures.pending')
                ->with('success', 'Dokumen berhasil ditolak.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Send users without a confirmed second factor to enrollment first.
     */
    protected function requireTwoFactor(Document $document)
    {
        if (auth()->user()->hasTwoFactorEnabled()) {
            return null;
        }

        session()->put('two_factor_intended', route('signatures.show', $document));

        return redirect()
            ->route('two-factor.show')
            ->with('error', 'Aktifkan autentikasi dua faktor terlebih dahulu sebelum menandatangani atau menolak dokumen.');
    }

    /**
     * History of signed documents.
     */
    public function history()
    {
        $user = auth()->user();
        
        // Include rounds that were later superseded: the signer still signed them (T4)
        $documents = Document::whereHas('allSignerAssignments', function ($query) use ($user) {
            $query->where('signer_id', $user->id)
                  ->where('status', 'signed');
        })
        ->with(['creator', 'signerAssignments.signer'])
        ->latest()
        ->paginate(15);
        
        return view('signatures.history', compact('documents'));
    }
}
