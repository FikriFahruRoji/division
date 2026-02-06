<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any documents.
     */
    public function viewAny(User $user): bool
    {
        // Admin and operator can view all, signers see filtered list
        return true;
    }

    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, Document $document): bool
    {
        // Super admin can view all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can view documents from their department
        if ($user->isAdmin()) {
            return $user->department_id === $document->creator?->department_id;
        }

        // Creator can view their documents
        if ($document->creator_id === $user->id) {
            return true;
        }

        // Assigned signers can view
        return $document->signerAssignments()
            ->where('signer_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can create documents.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator() || $user->isSigner();
    }

    /**
     * Determine whether the user can update the document.
     */
    public function update(User $user, Document $document): bool
    {
        // Only draft or rejected documents can be updated
        if (!$document->isDraft() && $document->status !== 'rejected') {
            return false;
        }

        // Super admin can update all drafts
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can update drafts in their department
        if ($user->isAdmin()) {
            return $user->department_id === $document->creator?->department_id;
        }

        // Only creator can update
        return $document->creator_id === $user->id;
    }

    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, Document $document): bool
    {
        // Cannot delete signed documents
        if ($document->isSignedValid()) {
            return false;
        }

        // Super admin can delete all non-signed
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can delete non-signed in their department
        if ($user->isAdmin()) {
            return $user->department_id === $document->creator?->department_id;
        }

        // Creator can delete their own draft/pending documents
        return $document->creator_id === $user->id;
    }

    /**
     * Determine whether the user can download the document.
     */
    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    /**
     * Determine whether the user can preview the document.
     */
    public function preview(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    /**
     * Determine whether the user can finalize the document for signing.
     */
    public function finalize(User $user, Document $document): bool
    {
        // Only draft or rejected documents can be updated
        if (!$document->isDraft() && $document->status !== 'rejected') {
            return false;
        }

        // Super admin can finalize all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can finalize if in same department
        if ($user->isAdmin()) {
            return $user->department_id === $document->creator?->department_id;
        }

        // Only creator (admin/operator) can finalize
        return $document->creator_id === $user->id 
            && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * Determine whether the user can revoke the document.
     */
    public function revoke(User $user, Document $document): bool
    {
        // Only signed documents can be revoked
        if (!$document->isSignedValid()) {
            return false;
        }

        // Super Admin can revoke all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can revoke in their department
        if ($user->isAdmin()) {
            return $user->department_id === $document->creator?->department_id;
        }

        // Creator can revoke their own documents
        return $document->creator_id === $user->id;
    }

    /**
     * Determine whether the user can sign the document.
     */
    public function sign(User $user, Document $document): bool
    {
        // Document must be pending signature
        if (!$document->isPendingSignature()) {
            return false;
        }

        // User must be an assigned signer with pending status
        return $document->signerAssignments()
            ->where('signer_id', $user->id)
            ->whereIn('status', ['pending', 'notified'])
            ->exists();
    }
}
