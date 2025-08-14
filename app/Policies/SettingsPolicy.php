<?php

namespace App\Policies;

use App\Enums\PermissionTypeEnum;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SettingsPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::viewSettings) ?
            Response::allow() : Response::deny();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::viewSettings) ?
            Response::allow() : Response::deny();
    }

    public function storeOrUpdate(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::setSettings) ?
            Response::allow() : Response::deny();
    }
}
