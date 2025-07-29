<?php

namespace App\Policies;

use App\Enums\PermissionTypeEnum;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::viewRoles)
            ? Response::allow()
            : Response::deny('Unauthorized to view list of roles.', 403);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::viewRoles)
            ? Response::allow()
            : Response::deny('Unauthorized to view a role .', 403);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::createRole)
            ? Response::allow()
            : Response::deny('Unauthorized to add a role.', 403);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::createRole)
            ? Response::allow()
            : Response::deny('Unauthorized to update a role.', 403);
    }

    /**
     * Determine whether the user can assign permissions to a role.
     */
    public function assignPermissionToRole(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::grantPermission)
            ? Response::allow()
            : Response::deny('Unauthorized to assign permissions to a role.', 403);
    }

    /**
     * Determine whether the user can revoke permissions from a role.
     */
    public function revokePermissionFromRole(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::revokePermission)
            ? Response::allow()
            : Response::deny('Unauthorized to revoke permissions from a role.', 403);
    }

    /**
     * Determine whether the user can assign a role to a user.
     */
    public function assignRoleToUser(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::assignRole)
            ? Response::allow()
            : Response::deny('Unauthorized to assign roles to users.', 403);
    }

    /**
     * Determine whether the user can remove a role from a user.
     */
    public function removeRoleFromUser(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::removeRole)
            ? Response::allow()
            : Response::deny('Unauthorized to remove roles from users.', 403);
    }


    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::deleteRole)
            ? Response::allow()
            : Response::deny('Unauthorized to delete a role.', 403);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): Response
    {
        return Response::deny('Restoring roles is not supported.', 403);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user): Response
    {
        return Response::deny('Force deleting roles is not supported.', 403);
    }
}
