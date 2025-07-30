<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $users = QueryBuilder::for(User::query())
            ->defaultSort('-created_at')
            ->allowedFilters('first_name','last_name','email')
            ->allowedSorts(['first_name','last_name'])
            ->paginate($request->get('per_page'));

        return ResponseBuilder::asSuccess()
            ->withData(['users' => $users])
            ->withMessage('Users successfully fetched')
            ->build();
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return ResponseBuilder::asSuccess()
            ->withData(['user' => $user])
            ->withMessage('User fetched')
            ->build();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteUserRequest $request)
    {
        $deletedCount = User::whereIn('id', $request->ids)->delete();

        $message = $deletedCount === 1 ? 'User deleted successfully.'
            : ($deletedCount > 1 ? 'Users deleted successfully.' : 'No users were deleted.');

        return ResponseBuilder::asSuccess()
            ->withMessage($message)
            ->withHttpCode(204)
            ->build();

    }

    public function toggleUserStatus(User $user)
    {
        $user->is_active = !$user->is_active;

        $timestampField = $user->is_active ? 'activated_at' : 'deactivated_at';
        $user->{$timestampField} = now();

        $user->save();

        $statusText = $user->is_active ? 'activated' : 'deactivated';

        return ResponseBuilder::asSuccess()
            ->withMessage("User {$statusText} successfully.")
            ->withHttpCode(204)
            ->build();
    }

}
