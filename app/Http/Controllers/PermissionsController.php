<?php

namespace App\Http\Controllers;

use App\Enums\ActivityLogType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\AssignPermissionToUserRequest;
use App\Http\Requests\Permission\RevokePermissionFromUserRequest;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\Permission\Models\Permission;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class PermissionsController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = QueryBuilder::for(Permission::query())
            ->defaultSort('-created_at')
            ->allowedSorts(['name'])
            ->allowedFilters(['name'])
            ->paginate($request->get('per_page'));

        return ResponseBuilder::asSuccess()
            ->withMessage('Permissions fetched successfully!!!')
            ->withData([
                'permissions' => $permissions,
            ])
            ->build();
    }

    public function store(StorePermissionRequest $request): Response
    {
        $this->authorize('create', Permission::class);

        $user = Auth::user();
        $ipAddress = request()->ip();

        $permission = Permission::create([
            'name' => $request->validated('name')
        ]);

        $permission->refresh();

        activity()
            ->inLog(ActivityLogType::RolesAndPermissions)
            ->performedOn($permission)
            ->causedBy($user)
            ->withProperties([
                'name' => $permission->name,
                'ip_address' => $ipAddress,
            ])
            ->log("Permission with name: {$permission->name} created");

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_CREATED)
            ->withMessage('Permission successfully created!!!')
            ->withData([
                'permission' => $permission
            ])
            ->build();
    }

    public function assignPermissionsToUser(AssignPermissionToUserRequest $request, User $user)
    {
        $this->authorize('assignPermissionToRole', Permission::class);

        $ipAddress = request()->ip();

        $permissionNames = $request->validated('permissions');
        $alreadyAssignedPermissions = $user->permissions()->whereIn('name', $permissionNames)->pluck('name')->toArray();
        $message ='User already has the following permissions: ' . implode(', ', $alreadyAssignedPermissions);

        if (!empty($alreadyAssignedPermissions)) {
            activity()
                ->inLog(ActivityLogType::RolesAndPermissions)
                ->causedBy($user)
                ->withProperties([
                    'alreadyAssignedPermissions' => $alreadyAssignedPermissions,
                    'ip_address' => $ipAddress,
                ])
                ->log($message);

            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withMessage($message)
                ->build();
        }

        $user->givePermissionTo($permissionNames);
        $user->refresh();

        activity()
            ->inLog(ActivityLogType::RolesAndPermissions)
            ->causedBy($user)
            ->withProperties([
                'permissions' => $permissionNames,
                'ip_address' => $ipAddress,
            ])
            ->log('Permission successfully assigned to user');

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_CREATED)
            ->withMessage('Permission successfully assigned to user!!!')
            ->withData([
                'user' => $user->permissions->pluck('name')->toArray()
            ])
            ->build();
    }

    public function removePermissionsToUser(RevokePermissionFromUserRequest $request, User $user)
    {
        $this->authorize('revokePermissionFromRole', Permission::class);

        $ipAddress = request()->ip();

        $permissionNames = $request->validated('permissions');
        $userActuallyHas = $user->permissions()->whereIn('name', $permissionNames)
            ->pluck('name')->toArray();

        $notAssigned = array_diff($permissionNames, $userActuallyHas);
        $msg = 'User does not have the following permissions: ' . implode(', ', $notAssigned);

        if (!empty($notAssigned)) {
            activity()
                ->inLog(ActivityLogType::RolesAndPermissions)
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

        $user->revokePermissionTo($permissionNames);
        $user->refresh();

        activity()
            ->inLog(ActivityLogType::RolesAndPermissions)
            ->causedBy($user)
            ->withProperties([
                'permissions' => $permissionNames,
                'ip_address' => $ipAddress,
            ])
            ->log('Permission successfully revoked from user');

        return ResponseBuilder::asSuccess()
            ->withMessage('Permissions successfully revoked from user!!!')
            ->withData([
                'user_permissions' => $user->permissions->pluck('name')->toArray()
            ])
            ->build();
    }
}
