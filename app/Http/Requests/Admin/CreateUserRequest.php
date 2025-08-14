<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
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
            'first_name' => 'required|string|max:100|min:2',
            'last_name' => 'required|string|max:100|min:2',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => [
                'nullable',
                'string',
                'unique:users',
                'regex:/^(?:\+?234|0|234)?(70|80|91|90|81|71|070|080|091|090|081|071)\d{8}$/',
            ],
            'roles' => 'sometimes|array',
            'roles.*' => 'sometimes|exists:roles,name',
            'profile_picture' => 'nullable|file|mimes:jpeg,png|max:2048',
        ];
    }
}
