<?php

namespace App\Policies;

use App\Enums\PermissionTypeEnum;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PermissionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::viewPermissions)
            ? Response::allow()
            : Response::deny('Unauthorized to view list of permissions.', 403);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::viewPermissions)
            ? Response::allow()
            : Response::deny('Unauthorized to view a permission.', 403);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::createPermission)
            ? Response::allow()
            : Response::deny('Unauthorized to add a permission.', 403);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::updatePermission)
            ? Response::allow()
            : Response::deny('Unauthorized to update a permission.', 403);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::deletePermission)
            ? Response::allow()
            : Response::deny('Unauthorized to delete a permission.', 403);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): Response
    {
        return Response::deny('Restoring permission is not supported.', 403);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user): Response
    {
        return Response::deny('Force deleting permission is not supported.', 403);
    }
}
