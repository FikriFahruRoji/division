<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'position',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the department this user belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get departments managed by this admin.
     */
    public function managedDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'admin_departments', 'admin_id', 'department_id')
            ->withTimestamps();
    }

    /**
     * Get documents created by this user.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'creator_id');
    }

    /**
     * Get signer assignments for this user.
     */
    public function signerAssignments(): HasMany
    {
        return $this->hasMany(SignerAssignment::class, 'signer_id');
    }

    /**
     * Get signatures made by this user.
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class, 'signer_id');
    }

    /**
     * Get audit logs for this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Check if user is super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is admin (includes super_admin).
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    /**
     * Check if user is operator.
     */
    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    /**
     * Check if user is signer.
     */
    public function isSigner(): bool
    {
        return $this->role === 'signer';
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if admin can manage a specific department.
     */
    public function canManageDepartment(int $departmentId): bool
    {
        // Super admin can manage all departments
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Admin can only manage assigned departments
        if ($this->role === 'admin') {
            return $this->managedDepartments()->where('departments.id', $departmentId)->exists();
        }

        return false;
    }

    /**
     * Get IDs of departments this admin can manage.
     */
    public function getManagedDepartmentIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Department::pluck('id')->toArray();
        }

        return $this->managedDepartments()->pluck('departments.id')->toArray();
    }
}
