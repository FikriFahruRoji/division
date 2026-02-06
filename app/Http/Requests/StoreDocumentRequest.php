<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin() 
            || $this->user()->isOperator() 
            || $this->user()->isSigner();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-\_\.\,\(\)]+$/u', // Only alphanumeric and safe chars
            ],
            'doc_number' => [
                'required',
                'string',
                'max:100',
                'unique:documents,doc_number',
                'regex:/^[a-zA-Z0-9\-\_\.\/]+$/', // Doc number format
            ],
            'doc_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'doc_type' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9\s\-\_]+$/u',
            ],
            'unit' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9\s\-\_\.]+$/u',
            ],
            'classification' => [
                'nullable',
                'string',
                'in:biasa,terbatas,rahasia',
            ],
            'sign_mode' => [
                'required',
                'in:single,sequential,parallel',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:10240', // 10MB
            ],
            'signers' => [
                'required',
                'array',
                'min:1',
                'max:10', // Limit number of signers
            ],
            'signers.*' => [
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $signer = User::find($value);
                    $authUser = request()->user();
                    
                    // Super admin can assign anyone
                    if ($authUser->isSuperAdmin()) {
                        return;
                    }
                    
                    // Regular admin/operator can only assign signers from same department
                    if ($signer->department_id !== $authUser->department_id) {
                        $fail('Penandatangan harus berasal dari departemen yang sama.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'title.regex' => 'Judul hanya boleh berisi huruf, angka, dan karakter dasar.',
            'doc_number.regex' => 'Nomor dokumen hanya boleh berisi huruf, angka, tanda hubung, garis bawah, titik, dan garis miring.',
            'doc_date.before_or_equal' => 'Tanggal dokumen tidak boleh lebih dari hari ini.',
            'file.max' => 'Ukuran file maksimal 10MB.',
            'signers.max' => 'Maksimal 10 penandatangan yang dapat ditambahkan.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from string inputs
        $this->merge([
            'title' => trim($this->title ?? ''),
            'doc_number' => trim($this->doc_number ?? ''),
            'doc_type' => trim($this->doc_type ?? ''),
            'unit' => trim($this->unit ?? ''),
            'notes' => $this->notes ? trim($this->notes) : null,
        ]);
    }
}
