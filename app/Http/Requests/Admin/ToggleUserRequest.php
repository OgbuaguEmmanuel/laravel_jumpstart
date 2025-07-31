<?php

namespace App\Http\Requests\Admin;

use App\Enums\PermissionTypeEnum;
use App\Enums\ToggleStatusReasonEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToggleUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo(PermissionTypeEnum::toggleUserStatus);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $action = $this->input('action');
        $validReasons = [];

        if ($action === 'deactivate') {
            $validReasons = ToggleStatusReasonEnum::getDeactivationReasons();
        } elseif ($action === 'activate') {
            $validReasons = ToggleStatusReasonEnum::getActivationReasons();
        }

        return [
            'action' => [
                'required',
                'string',
                Rule::in(['activate', 'deactivate']),
            ],
            'reason' => [
                'required',
                'string',
                Rule::in($validReasons),
            ],
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
        throw new AuthorizationException('Unauthorized to toggle user status.', 403);
    }
}
