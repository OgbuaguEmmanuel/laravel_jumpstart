<?php

namespace App\Http\Controllers\V1\Auth;

use App\Actions\CreateUserAction;
use App\Enums\RoleTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CreateUserRequest;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    public function register(CreateUserRequest $request, CreateUserAction $action)
    {
        $file = null;
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
        }

        $user = $action->handle($request->validated(), $file);

        $role = Role::where('name', RoleTypeEnum::User)->get();
        $user->syncRoles($role);

        return ResponseBuilder::asSuccess()
            ->withHttpCode(201)
            ->withMessage('User registered successfully.')
            ->build();
    }
}
