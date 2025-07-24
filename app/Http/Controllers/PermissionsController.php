<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionRequest;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\Permission\Models\Permission;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class PermissionsController extends Controller
{

    public function index(Request $request): Response
    {
        $permissions = Permission::query();

        $permissions = QueryBuilder::for($permissions)
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
        $permission = new Permission();
        $permission->name = $request->validated('name');
        $permission->guard_name = $request->validated('guard_name');
        $permission->save();

        $permission->refresh();

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_CREATED)
            ->withMessage('Permission successfully created!!!')
            ->withData([
                'permission' => $permission
            ])
            ->build();
    }
}
