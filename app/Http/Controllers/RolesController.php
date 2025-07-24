<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\GivePermissionToRoleRequest;
use App\Http\Requests\Roles\RevokePermissionFromRoleRequest;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\RolesService;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class RolesController extends Controller
{
    /**
     * @var RolesService $rolesService
     */
    public RolesService $rolesService;

    /**
     * Instantiate the class and inject classes it depends on.
     */
    public function __construct(RolesService $rolesService)
    {
        $this->rolesService = $rolesService;
    }

    /**
     * List  all roles
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $roles = $this->rolesService->index();

        $roles = QueryBuilder::for($roles)
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
        $role = $this->rolesService->store($request->validated());

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
    public function givePermission(GivePermissionToRoleRequest $request, Role $role): Response
    {
        $permission = Permission::where('name', $request->get('permission'))
            ->where('guard_name', $request->get('guard_name'))
            ->first();

        if ($role->hasPermissionTo($permission->name)) {
            return ResponseBuilder::asError(400)
                ->withMessage('Role already has this permission!!!')
                ->build();
        }
        $role = $this->rolesService->givePermission($request, $role);

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_CREATED)
            ->withMessage('Permission successfully assigned to role!!!')
            ->withData([
                'role' => $role
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
    public function revokePermission(RevokePermissionFromRoleRequest $request, Role $role): Response
    {
        $permission = Permission::where('name', $request->get('permission'))
            ->where('guard_name', $request->get('guard_name'))
            ->first();

        if ($role->hasPermissionTo($permission->name)) {
            $this->rolesService->revokePermission($request, $role);

            return ResponseBuilder::asSuccess()
                ->withMessage('Permission successfully revoked from role!!!')
                ->build();
        }

        return ResponseBuilder::asError(400)
            ->withMessage("This permission hasn't been assigned to this role!!!")
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
            return ResponseBuilder::asError(400)
                ->withMessage('User already has this role!!!')
                ->build();
        }
        $user = $this->rolesService->assignRole($role, $user);

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_CREATED)
            ->withMessage('Role successfully assigned to user!!!')
            ->withData([
                'user' => $user
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
            $this->rolesService->removeRole($user, $role);

            return ResponseBuilder::asSuccess()
                ->withMessage('Role successfully removed from role!!!')
                ->build();
        }

        return ResponseBuilder::asError(400)
            ->withMessage("This role hasn't been assigned to this user!!!")
            ->build();
    }
}
