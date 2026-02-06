<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Super admin can view all users
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can view users in their department
        if ($user->role === 'admin') {
            // Admin must have department assigned
            if (!$user->department_id) {
                return false;
            }
            // Can view users in same department
            return $model->department_id === $user->department_id;
        }

        // Users can view themselves
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Super admin can always create
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        // Admin can create only if they have department assigned
        if ($user->role === 'admin') {
            return $user->department_id !== null;
        }
        
        return false;
    }

    /**
     * Check if user can create a specific role.
     */
    public function createRole(User $user, string $role): bool
    {
        // Super admin can create any role
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Regular admin can only create operator/signer
        if ($user->role === 'admin') {
            return in_array($role, ['operator', 'signer']);
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Super admin can update anyone
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Regular admin cannot modify super_admin or other admins
        if ($user->role === 'admin') {
            if (in_array($model->role, ['super_admin', 'admin'])) {
                return false;
            }
            
            // Admin must have department assigned
            if (!$user->department_id) {
                return false;
            }
            
            // Admin can only update users in their department
            return $model->department_id === $user->department_id;
        }

        // Users can update their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete self
        if ($user->id === $model->id) {
            return false;
        }

        // Only super admin can delete admins
        if (in_array($model->role, ['super_admin', 'admin'])) {
            return $user->isSuperAdmin();
        }

        // Super admin can delete anyone
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can only delete users in their department
        if ($user->role === 'admin') {
            if (!$user->department_id) {
                return false;
            }
            return $model->department_id === $user->department_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        if (in_array($model->role, ['super_admin', 'admin'])) {
            return $user->isSuperAdmin();
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->role === 'admin' && $user->department_id) {
            return $model->department_id === $user->department_id;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }
}
