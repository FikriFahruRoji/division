<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

/**
 * Trait for applying department-based scoping to queries.
 * Use this trait in models that need department isolation.
 */
trait DepartmentScope
{
    /**
     * Scope query to user's department via a relationship.
     * 
     * @param Builder $query
     * @param User $user
     * @param string $relationshipPath Path to user relationship (e.g., 'creator', 'user')
     * @return Builder
     */
    public function scopeForUserDepartment(Builder $query, User $user, string $relationshipPath = 'creator'): Builder
    {
        // Super admin sees everything
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // User must have department
        if (!$user->department_id) {
            return $query->whereRaw('1 = 0'); // Return empty
        }

        // Filter by relationship's department
        return $query->whereHas($relationshipPath, function ($q) use ($user) {
            $q->where('department_id', $user->department_id);
        });
    }

    /**
     * Check if model belongs to user's department.
     * Override in model to specify custom relationship path.
     * 
     * @param User $user
     * @return bool
     */
    public function belongsToUserDepartment(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->department_id) {
            return false;
        }

        // Default: check creator relationship
        if (method_exists($this, 'creator') && $this->creator) {
            return $this->creator->department_id === $user->department_id;
        }

        // Fallback: check user relationship
        if (method_exists($this, 'user') && $this->user) {
            return $this->user->department_id === $user->department_id;
        }

        // If model has direct department_id
        if (isset($this->department_id)) {
            return $this->department_id === $user->department_id;
        }

        return false;
    }
}
