<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorLoginChallengeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            '2fa_challenge_key' => ['required', 'string', 'uuid'],
            'code' => ['nullable', 'numeric', 'digits:6', 'required_without:recovery_code','prohibits:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'required_without:code','prohibits:code'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required_without' => 'Either a 2FA code or a recovery code is required.',
            'recovery_code.required_without' => 'Either a 2FA code or a recovery code is required.',
            'code.prohibits' => 'The 2FA code cannot be present when a recovery code is also provided.',
            'recovery_code.prohibits' => 'A recovery code cannot be present when a 2FA code is also provided.',
        ];
    }
}
