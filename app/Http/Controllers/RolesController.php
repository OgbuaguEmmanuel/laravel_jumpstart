<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\GivePermissionToRoleRequest;
use App\Http\Requests\Roles\RevokePermissionFromRoleRequest;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Models\User;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class RolesController extends Controller
{
    /**
     * List  all roles
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $roles = QueryBuilder::for(Role::query())
            ->defaultSort('-created_at')
            ->paginate($request->get('per_page'));

        return ResponseBuilder::asSuccess()
            ->withMessage('Roles fetched successfully!!!')
            ->withData([
                'roles' => $roles,
            ])
            ->build();
    }

    /**
     * Store a new role
     *
     * @param StoreRoleRequest $request
     * @return Response
     */
    public function store(StoreRoleRequest $request): Response
    {
        $role = Role::create([
            'name' => ucwords($request->validated('name'))
        ]);

        $role->refresh();

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_CREATED)
            ->withMessage('Role successfully created!!!')
            ->withData([
                'role' => $role
            ])
            ->build();
    }

    /**
     * Give permission to a role
     *
     * @param GivePermissionToRoleRequest $request
     * @param Role $role
     * @return Response
     */
    public function givePermissions(GivePermissionToRoleRequest $request, Role $role): Response
    {
        $permissionNames = $request->validated('permissions');

        $alreadyAssignedPermissions = $role->permissions()->whereIn('name', $permissionNames)
            ->pluck('name')->toArray();

        if (!empty($alreadyAssignedPermissions)) {
            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withMessage('Role already has the following permissions: ' . implode(', ', $alreadyAssignedPermissions))
                ->build();
        }

        $role->givePermissionTo($permissionNames);
        $role->refresh();

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_CREATED)
            ->withMessage('Permissions successfully assigned to role!!!')
            ->withData([
                'role_permissions' => $role->permissions->pluck('name')->toArray()
            ])
            ->build();
    }

    /**
     * Revoke permission from role
     *
     * @param RevokePermissionFromRoleRequest $request
     * @param Role $role
     * @return Response
     */
    public function revokePermissions(RevokePermissionFromRoleRequest $request, Role $role): Response
    {
        $permissionNames = $request->validated('permissions');

        $roleActuallyHas = $role->permissions()->whereIn('name', $permissionNames)
            ->pluck('name')->toArray();

        $notAssigned = array_diff($permissionNames, $roleActuallyHas);

        if (!empty($notAssigned)) {
            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withMessage('Role does not have the following permissions: ' . implode(', ', $notAssigned))
                ->build();
        }

        $role->revokePermissionTo($permissionNames);
        $role->refresh();

        return ResponseBuilder::asSuccess()
            ->withMessage('Permissions successfully revoked from role!!!')
            ->withData([
                'role_permissions' => $role->permissions->pluck('name')->toArray()
            ])
            ->build();
    }

    /**
     * Assign a role to a user
     *
     * @param User $user
     * @param Role $role
     * @return Response
     */
    public function assignRole(User $user, Role $role): Response
    {
        if ($user->hasRole($role)) {
            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withMessage('User already has this role!!!')
                ->build();
        }

        $user->assignRole($role);
        $user->refresh();

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_CREATED)
            ->withMessage('Role successfully assigned to user!!!')
            ->withData([
                'user_roles' => $user->roles->pluck('name')->toArray()
            ])
            ->build();
    }

    /**
     * Remove a role from a user
     *
     * @param User $user
     * @param Role $role
     * @return Response
     */
    public function removeRole(User $user, Role $role): Response
    {
        if ($user->hasRole($role)) {
            $user->removeRole($role);
            $user->refresh();

            return ResponseBuilder::asSuccess()
                ->withMessage('Role successfully removed from user!!!')
                ->withData([
                    'user_roles' => $user->roles->pluck('name')->toArray()
                ])
                ->build();
        }

        return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
            ->withMessage("This role hasn't been assigned to this user!!!")
            ->build();
    }
}
