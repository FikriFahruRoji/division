<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends UserRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return $this->baseRules(
            emailRules: [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email,' . $userId,
            ],
            passwordRules: [
                'nullable',
                'string',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ]
        );
    }
}
