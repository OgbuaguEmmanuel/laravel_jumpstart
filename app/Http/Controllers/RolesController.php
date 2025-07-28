<?php

namespace App\Http\Controllers;

use App\Enums\ActivityLogType;
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
        $user = $request->user();
        $ipAddress = $request->ip();

        $role = Role::create([
            'name' => ucwords($request->validated('name'))
        ]);

        $role->refresh();

        activity()
            ->inLog(ActivityLogType::RolesAndPermissions)
            ->performedOn($role)
            ->causedBy($user)
            ->withProperties([
                'name' => $role->name,
                'ip_address' => $ipAddress,
                'action_type' => "Role with name: {{$role->name}} created",
            ])
            ->log("Role with name: {{$role->name}} created");

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
        $ipAddress= $request->ip();
        $user = $request->user();

        $permissionNames = $request->validated('permissions');

        $alreadyAssignedPermissions = $role->permissions()->whereIn('name', $permissionNames)
            ->pluck('name')->toArray();

        $msg = 'Role already has the following permissions: ' . implode(', ', $alreadyAssignedPermissions);
        if (!empty($alreadyAssignedPermissions)) {
             activity()
                ->inLog(ActivityLogType::RolesAndPermissions)
                ->causedBy($user)
                ->withProperties([
                    'alreadyAssignedPermissions' => $alreadyAssignedPermissions,
                    'ip_address' => $ipAddress,
                    'action_type' => $msg,
                ])
                ->log($msg);
            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withMessage($msg)
                ->build();
        }

        $role->givePermissionTo($permissionNames);
        $role->refresh();

        activity()
            ->inLog(ActivityLogType::RolesAndPermissions)
            ->causedBy($user)
            ->withProperties([
                'permissions' => $permissionNames,
                'ip_address' => $ipAddress,
                'action_type' => 'Permission successfully assigned to role',
            ])
            ->log('Permission successfully assigned to role');

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
        $ipAddress = $request->ip();
        $user = $request->user();

        $permissionNames = $request->validated('permissions');

        $roleActuallyHas = $role->permissions()->whereIn('name', $permissionNames)
            ->pluck('name')->toArray();

        $notAssigned = array_diff($permissionNames, $roleActuallyHas);
        $msg = 'Role does not have the following permissions: ' . implode(', ', $notAssigned);
        if (!empty($notAssigned)) {
            activity()
                ->inLog(ActivityLogType::RolesAndPermissions)
                ->causedBy($user)
                ->withProperties([
                    'notAssignedPermission' => $notAssigned,
                    'ip_address' => $ipAddress,
                    'action_type' => $msg,
                ])
                ->log($msg);

            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withMessage($msg)
                ->build();
        }

        $role->revokePermissionTo($permissionNames);
        $role->refresh();

        activity()
            ->inLog(ActivityLogType::RolesAndPermissions)
            ->causedBy($user)
            ->withProperties([
                'permissions' => $permissionNames,
                'ip_address' => $ipAddress,
                'action_type' => 'Permission successfully revoked from role',
            ])
            ->log('Permission successfully revoked from role');

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
        $ipAddress = request()->ip();

        if ($user->hasRole($role)) {
            activity()
                ->inLog(ActivityLogType::RolesAndPermissions)
                ->performedOn($role)
                ->causedBy($user)
                ->withProperties([
                    'name' => $role->name,
                    'ip_address' => $ipAddress,
                    'action_type' => "Role with name: {{$role->name}} already assigned to user",
                ])
                ->log("Role with name: {{$role->name}} already assigned to user");

            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withMessage('User already has this role!!!')
                ->build();
        }

        $user->assignRole($role);
        $user->refresh();

        activity()
            ->inLog(ActivityLogType::RolesAndPermissions)
            ->performedOn($role)
            ->causedBy($user)
            ->withProperties([
                'name' => $role->name,
                'ip_address' => $ipAddress,
                'action_type' => "Role with name: {{$role->name}} assigned to user",
            ])
            ->log("Role with name: {{$role->name}} assigned to user");

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
        $ipAddress = request()->ip();

        if ($user->hasRole($role)) {
            $user->removeRole($role);
            $user->refresh();

            activity()
                ->inLog(ActivityLogType::RolesAndPermissions)
                ->performedOn($role)
                ->causedBy($user)
                ->withProperties([
                    'name' => $role->name,
                    'ip_address' => $ipAddress,
                    'action_type' => "Role with name: {{$role->name}} revoked from user",
                ])
                ->log("Role with name: {{$role->name}} revoked from user");

            return ResponseBuilder::asSuccess()
                ->withMessage('Role successfully removed from user!!!')
                ->withData([
                    'user_roles' => $user->roles->pluck('name')->toArray()
                ])
                ->build();
        }

        activity()
            ->inLog(ActivityLogType::RolesAndPermissions)
            ->performedOn($role)
            ->causedBy($user)
            ->withProperties([
                'name' => $role->name,
                'ip_address' => $ipAddress,
                'action_type' => "Role with name: {{$role->name}} hasn't been assigned to this user",
            ])
            ->log("Role with name: {{$role->name}} hasn't been assigned to this user");

        return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
            ->withMessage("This role hasn't been assigned to this user!!!")
            ->build();
    }
}
