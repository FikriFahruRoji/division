<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation logic for creating and updating users.
 * Concrete classes must implement authorize() and override password rules.
 */
abstract class UserRequest extends FormRequest
{
    /**
     * Get roles that the authenticated user is permitted to assign.
     */
    protected function getAllowedRoles(): array
    {
        if ($this->user()->isSuperAdmin()) {
            return ['super_admin', 'admin', 'operator', 'signer'];
        }

        return ['operator', 'signer'];
    }

    /**
     * Build the department validation rule array.
     */
    protected function departmentRules(): array
    {
        $rules = ['nullable', 'integer', 'exists:departments,id'];

        // Non-super-admin may only assign users to their own department
        if (!$this->user()->isSuperAdmin() && $this->user()->role === 'admin') {
            $rules[] = Rule::in([$this->user()->department_id]);
        }

        return $rules;
    }

    /**
     * Shared field rules — subclasses provide email uniqueness and password rules.
     */
    protected function baseRules(array $emailRules, array $passwordRules): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\.\,\-\']+$/u',
            ],
            'email'         => $emailRules,
            'password'      => $passwordRules,
            'role'          => ['required', 'string', Rule::in($this->getAllowedRoles())],
            'department_id' => $this->departmentRules(),
            'position'      => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\.\,\-\']+$/u',
            ],
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * Normalise common fields before validation runs.
     */
    protected function prepareForValidation(): void
    {
        // Force admin's department for non-super-admin
        if (!$this->user()->isSuperAdmin() && $this->user()->role === 'admin' && $this->user()->department_id) {
            $this->merge(['department_id' => $this->user()->department_id]);
        }

        $this->merge([
            'name'     => trim($this->name ?? ''),
            'email'    => strtolower(trim($this->email ?? '')),
            'position' => $this->position ? trim($this->position) : null,
        ]);
    }

    /**
     * Shared human-readable validation messages.
     */
    public function messages(): array
    {
        return [
            'name.regex'          => 'Nama hanya boleh berisi huruf dan karakter dasar.',
            'email.email'         => 'Format email tidak valid.',
            'password.min'        => 'Password minimal 8 karakter.',
            'department_id.in'    => 'Anda hanya bisa mengelola user di departemen Anda.',
            'position.regex'      => 'Jabatan hanya boleh berisi huruf, angka, dan karakter dasar.',
            'role.in'             => 'Anda tidak memiliki izin untuk role ini.',
        ];
    }
}
