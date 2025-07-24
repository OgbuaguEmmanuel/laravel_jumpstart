<?php

namespace App\Services;


use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class RolesService
{
    /**
     * List all roles
     *
     * @return Builder
     */
    public function index(): Builder
    {
        return Role::query();
    }

    public function store(array $data): Role
    {
        $role = new Role();

        $role->name = ucwords($data['name']);
        $role->guard_name = $data['guard_name'];
        $role->save();

        return $role;
    }

    /**
     * Give a permission to a role
     *
     * @param GivePermissionToRoleRequest $request
     * @param Role $role
     * @return mixed
     */
    public function givePermission(GivePermissionToRoleRequest $request, Role $role): mixed
    {
        $permission = $request->get('permission');

        $role->givePermissionTo($permission);

        return $role->permissions;
    }

    /**
     * Revoke permission from role
     *
     * @param RevokePermissionFromRoleRequest $request
     * @param Role $role
     * @return Role
     */
    public function revokePermission(RevokePermissionFromRoleRequest $request, Role $role): Role
    {
        return $role->revokePermissionTo($request->get('permission'));
    }

    /**
     * Assign role to user
     *
     * @param Role $role
     * @param User $user
     * @return User
     */
    public function assignRole(Role $role, User $user): User
    {
        return $user->assignRole($role);
    }

    /**
     * Remove a role from user
     *
     * @param User $user
     * @param Role $role
     * @return User
     */
    public function removeRole(User $user, Role $role): User
    {
        return $user->removeRole($role);
    }
}
