<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\AssignPermissionToUserRequest;
use App\Http\Requests\Permission\RevokePermissionFromUserRequest;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Models\User;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\Permission\Models\Permission;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class PermissionsController extends Controller
{

    public function index(Request $request): Response
    {
        $permissions = QueryBuilder::for(Permission::query())
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
        $permission = Permission::create([
            'name' => $request->validated('name')
        ]);

        $permission->refresh();

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_CREATED)
            ->withMessage('Permission successfully created!!!')
            ->withData([
                'permission' => $permission
            ])
            ->build();
    }

    public function assignPermissionToUser(AssignPermissionToUserRequest $request, User $user)
    {
        $permissionNames = $request->validated('permissions');
        $alreadyAssignedPermissions = $user->permissions()->whereIn('name', $permissionNames)->pluck('name')->toArray();

        if (!empty($alreadyAssignedPermissions)) {
            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withMessage('User already has the following permissions: ' . implode(', ', $alreadyAssignedPermissions))
                ->build();
        }

        $user->givePermissionTo($permissionNames);
        $user->refresh();

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_CREATED)
            ->withMessage('Permission successfully assigned to user!!!')
            ->withData([
                'user' => $user->permissions->pluck('name')->toArray()
            ])
            ->build();
    }

    public function removePermissionToUser(RevokePermissionFromUserRequest $request, User $user)
    {
        $permissionNames = $request->validated('permissions');
        $userActuallyHas = $user->permissions()->whereIn('name', $permissionNames)
            ->pluck('name')->toArray();

        $notAssigned = array_diff($permissionNames, $userActuallyHas);

        if (!empty($notAssigned)) {
            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withMessage('User does not have the following permissions: ' . implode(', ', $notAssigned))
                ->build();
        }

        $user->revokePermissionTo($permissionNames);
        $user->refresh();

        return ResponseBuilder::asSuccess()
            ->withMessage('Permissions successfully revoked from user!!!')
            ->withData([
                'user_permissions' => $user->permissions->pluck('name')->toArray()
            ])
            ->build();
    }
}
