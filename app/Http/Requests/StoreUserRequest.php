<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $allowedRoles = $this->getAllowedRoles();
        $user = $this->user();

        // Build department validation
        $departmentRules = ['nullable', 'integer', 'exists:departments,id'];
        
        // For non-super admin, force their department
        if (!$user->isSuperAdmin() && $user->role === 'admin') {
            $departmentRules[] = Rule::in([$user->department_id]);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\.\,\-\']+$/u',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'role' => [
                'required',
                'string',
                Rule::in($allowedRoles),
            ],
            'department_id' => $departmentRules,
            'position' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\.\,\-\']+$/u',
            ],
            'status' => [
                'required',
                'string',
                'in:active,inactive',
            ],
        ];
    }

    /**
     * Get allowed roles based on current user's role.
     */
    protected function getAllowedRoles(): array
    {
        $user = $this->user();

        // Super admin can create any role
        if ($user->isSuperAdmin()) {
            return ['super_admin', 'admin', 'operator', 'signer'];
        }

        // Regular admin can only create operator/signer
        return ['operator', 'signer'];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        
        // Force admin's department for non-super admin
        if (!$user->isSuperAdmin() && $user->role === 'admin' && $user->department_id) {
            $this->merge([
                'department_id' => $user->department_id,
            ]);
        }
        
        $this->merge([
            'name' => trim($this->name ?? ''),
            'email' => strtolower(trim($this->email ?? '')),
            'position' => $this->position ? trim($this->position) : null,
        ]);
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'Nama hanya boleh berisi huruf dan karakter dasar.',
            'email.email' => 'Format email tidak valid.',
            'password.min' => 'Password minimal 8 karakter.',
            'department_id.in' => 'Anda hanya bisa membuat user di departemen Anda.',
            'position.regex' => 'Jabatan hanya boleh berisi huruf, angka, dan karakter dasar.',
            'role.in' => 'Anda tidak memiliki izin untuk membuat role ini.',
        ];
    }
}
