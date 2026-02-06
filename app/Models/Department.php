<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
    ];

    /**
     * Get users in this department.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get admins assigned to manage this department.
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_departments', 'department_id', 'admin_id')
            ->withTimestamps();
    }

    /**
     * Check if department is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope to get only active departments.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
