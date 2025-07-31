<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserProfile extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('user');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'email' => 'sometimes|email'
        ];
    }

     /**
     * This method runs after all other rules have been evaluated.
     *
     * @return array<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $updateableFields = [
                    'first_name',
                    'last_name',
                    'email',
                    // Add new updateable fields to this list:

                ];

                // Check if at least one of the defined updateable fields is 'filled' (present and not empty).
                if (! collect($updateableFields)->contains(fn($field) => $this->filled($field))) {
                    // Make field names readable for the error message
                    $readableFields = implode(', ', array_map(function($field) {
                        return str_replace('_', ' ', $field); // Converts 'first_name' to 'first name'
                    }, $updateableFields));

                    $validator->errors()->add(
                        'update_data_required', // A general error key for this specific condition
                        "At least one of the following fields must be provided for update: {$readableFields}."
                    );
                }
            }
        ];
    }

    /**
     * Handle a failed authorization attempt.
     * Override this method to customize the response for unauthorized requests.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization()
    {
        throw new AuthorizationException('Unauthorized to update someone else\'s profile.', 403);
    }
}
