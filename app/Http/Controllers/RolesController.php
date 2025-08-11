<?php

namespace App\Http\Controllers;

use App\Enums\ActivityLogTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\GivePermissionToRoleRequest;
use App\Http\Requests\Roles\RevokePermissionFromRoleRequest;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class RolesController extends Controller
{
    use AuthorizesRequests;

    /**
     * List  all roles
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Role::class);

        $roles = QueryBuilder::for(Role::query())
            ->defaultSort('-created_at')
            ->allowedFilters('name')
            ->allowedSorts(['name','created_at'])
            ->paginate($request->get('per_page'));

        return ResponseBuilder::asSuccess()
            ->withMessage('Roles fetched successfully')
            ->withData([
                'roles' => $roles,
            ])
            ->build();
    }

    public function show(Role $role): Response
    {
        $this->authorize('view', Role::class);

        return ResponseBuilder::asSuccess()
            ->withData($role)
            ->withMessage('Role fetched successfully')
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
        $user = Auth::user();
        $this->authorize('create', Role::class);

        $ipAddress = request()->ip();

        $role = Role::create([
            'name' => ucwords($request->validated('name'))
        ]);

        $role->refresh();

        activity()
            ->inLog(ActivityLogTypeEnum::RolesAndPermissions)
            ->performedOn($role)
            ->causedBy($user)
            ->withProperties([
                'name' => $role->name,
                'ip_address' => $ipAddress,
            ])
            ->log("Role with name: {$role->name} created");

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_CREATED)
            ->withMessage('Role successfully created!!!')
            ->withData([
                'role' => $role
            ])
            ->build();
    }

    public function update(UpdateRoleRequest $request, Role $role): Response
    {
        $this->authorize('update', Role::class);
        $ipAddress = request()->ip();
        $oldRoleName = $role->name;

        $role->name = $request->validated('name');
        $role->save();
        $role->refresh();

        activity()
            ->inLog(ActivityLogTypeEnum::RolesAndPermissions)
            ->performedOn($role)
            ->causedBy(Auth::user())
            ->withProperties([
                'old_name' => $oldRoleName,
                'new_name' => $role->name,
                'ip_address' => $ipAddress,
            ])
            ->log("Role name updated from '{$oldRoleName}' to '{$role->name}'.");

        return ResponseBuilder::asSuccess()
            ->withData($role)
            ->withMessage('Role updated successfully')
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
        $ipAddress = request()->ip();
        $user = Auth::user();

        $permissionNames = $request->validated('permissions');

        $alreadyAssignedPermissions = $role->permissions()->whereIn('name', $permissionNames)->pluck('name')->toArray();

        $responseMessage = 'Role already has the following permissions: ' . implode(', ', $alreadyAssignedPermissions);

        if (!empty($alreadyAssignedPermissions)) {
            activity()
                ->inLog(ActivityLogTypeEnum::RolesAndPermissions)
                ->performedOn($role)
                ->causedBy($user)
                ->withProperties([
                    'role_name' => $role->name,
                    'permissions_requested' => $permissionNames,
                    'permissions_already_assigned' => $alreadyAssignedPermissions,
                    'ip_address' => $ipAddress,
                ])
                ->log("Failed to assign permissions to role '{$role->name}'. Reason: {$responseMessage}");

            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withMessage($responseMessage)
                ->build();
        }

        $role->givePermissionTo($permissionNames);
        $role->refresh();

        activity()
            ->inLog(ActivityLogTypeEnum::RolesAndPermissions)
            ->performedOn($role)
            ->causedBy($user)
            ->withProperties([
                'role_name' => $role->name,
                'permissions_assigned' => $permissionNames,
                'ip_address' => $ipAddress,
            ])
            ->log("Permissions [" . implode(', ', $permissionNames) . "] successfully assigned to role '{$role->name}'.");

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
        $ipAddress = request()->ip();
        $user = Auth::user();

        $permissionNames = $request->validated('permissions');

        $roleActuallyHas = $role->permissions()->whereIn('name', $permissionNames)
            ->pluck('name')->toArray();

        $notAssigned = array_diff($permissionNames, $roleActuallyHas);
        $msg = 'Role does not have the following permissions: ' . implode(', ', $notAssigned);
        if (!empty($notAssigned)) {
            activity()
                ->inLog(ActivityLogTypeEnum::RolesAndPermissions)
                ->causedBy($user)
                ->withProperties([
                    'notAssignedPermission' => $notAssigned,
                    'ip_address' => $ipAddress,
                ])
                ->log($msg);

            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withMessage($msg)
                ->build();
        }

        $role->revokePermissionTo($permissionNames);
        $role->refresh();

        activity()
            ->inLog(ActivityLogTypeEnum::RolesAndPermissions)
            ->causedBy($user)
            ->withProperties([
                'permissions' => $permissionNames,
                'ip_address' => $ipAddress,
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
        $this->authorize('assignRoleToUser', Role::class);

        $ipAddress = request()->ip();

        if ($user->hasRole($role)) {
            activity()
                ->inLog(ActivityLogTypeEnum::RolesAndPermissions)
                ->performedOn($role)
                ->causedBy($user)
                ->withProperties([
                    'name' => $role->name,
                    'ip_address' => $ipAddress,
                ])
                ->log("Role with name: {$role->name} already assigned to user");

            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withMessage('User already has this role!!!')
                ->build();
        }

        $user->assignRole($role);
        $user->refresh();

        activity()
            ->inLog(ActivityLogTypeEnum::RolesAndPermissions)
            ->performedOn($role)
            ->causedBy($user)
            ->withProperties([
                'name' => $role->name,
                'ip_address' => $ipAddress,
            ])
            ->log("Role with name: {$role->name} assigned to user");

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
        $this->authorize('removeRoleFromUser', Role::class);

        $ipAddress = request()->ip();

        if ($user->hasRole($role)) {
            $user->removeRole($role);
            $user->refresh();

            activity()
                ->inLog(ActivityLogTypeEnum::RolesAndPermissions)
                ->performedOn($role)
                ->causedBy($user)
                ->withProperties([
                    'name' => $role->name,
                    'ip_address' => $ipAddress,
                ])
                ->log("Role with name: {$role->name} revoked from user");

            return ResponseBuilder::asSuccess()
                ->withMessage('Role successfully removed from user!!!')
                ->withData([
                    'user_roles' => $user->roles->pluck('name')->toArray()
                ])
                ->build();
        }

        activity()
            ->inLog(ActivityLogTypeEnum::RolesAndPermissions)
            ->performedOn($role)
            ->causedBy($user)
            ->withProperties([
                'name' => $role->name,
                'ip_address' => $ipAddress,
            ])
            ->log("Role with name: {$role->name} hasn't been assigned to this user");

        return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
            ->withMessage("This role hasn't been assigned to this user!!!")
            ->build();
    }

}
