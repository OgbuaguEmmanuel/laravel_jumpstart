<?php

namespace App\Http\Requests\Roles;

use App\Enums\PermissionTypeEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class GivePermissionToRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo(PermissionTypeEnum::grantPermission);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'permissions' => 'required|array',
            'permissions.*' => ['required', 'exists:permissions,name']
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
        throw new AuthorizationException('Unauthorized to grant permission to role.', 403);
    }
}
