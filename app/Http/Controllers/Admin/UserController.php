<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CreateUserAction;
use App\Enums\ActivityLogTypeEnum;
use App\Enums\ToggleStatusReasonEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\DeleteUserRequest;
use App\Http\Requests\Admin\ToggleUserRequest;
use App\Http\Requests\Admin\UnlockUserAccountRequest;
use App\Models\User;
use App\Traits\AuthHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    use AuthHelpers;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $users = QueryBuilder::for(User::query())
            ->defaultSort('-created_at')
            ->allowedFilters('first_name','last_name','email','is_active')
            ->allowedSorts(['first_name','last_name'])
            ->paginate($request->get('per_page'));

        return ResponseBuilder::asSuccess()
            ->withData(['users' => $users])
            ->withMessage('Users successfully fetched')
            ->build();
    }

    public function lockedUsers(Request $request): Response
    {
        $users = QueryBuilder::for(User::query()->isLocked())
            ->defaultSort('-created_at')
            ->allowedFilters('first_name','last_name','email')
            ->allowedSorts(['first_name','last_name'])
            ->paginate($request->get('per_page'));

        return ResponseBuilder::asSuccess()
            ->withData(['users' => $users])
            ->withMessage('Locked users successfully fetched')
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
        $deletedCount = User::whereIn('id', $request->validated('ids'))->delete();

        $message = $deletedCount === 1 ? 'User deleted successfully.'
            : ($deletedCount > 1 ? 'Users deleted successfully.' : 'No users were deleted.');

        activity()
            ->inLog(ActivityLogTypeEnum::UserManagement)
            ->causedBy(Auth::user())
            ->withProperties([
                'deleted_accounts' => $request->validated('ids'),
                'deleted_by' => Auth::user()->email,
                'ip_address' => request()->ip(),
            ])
            ->log($message);

        return ResponseBuilder::asSuccess()
            ->withMessage($message)
            ->withHttpCode(Response::HTTP_OK)
            ->build();

    }

    public function toggleUserStatus(User $user, ToggleUserRequest $request)
    {
        $user->is_active = !$user->is_active;

        $timestampField = $user->is_active ? 'activated_at' : 'deactivated_at';
        $user->{$timestampField} = now();
        $user->reason = $request->validated('reason');
        $user->save();

        $statusText = $user->is_active ? 'activated' : 'deactivated';

        activity()
            ->inLog(ActivityLogTypeEnum::UserManagement)
            ->causedBy(Auth::user())
            ->performedOn($user)
            ->withProperties([
                'status' => $statusText,
                'toggled_by' => Auth::user()->email,
                'ip_address' => request()->ip(),
                'reason' => $user->reason
            ])
            ->log("User {$statusText} successfully.");

        return ResponseBuilder::asSuccess()
            ->withMessage("User {$statusText} successfully.")
            ->withHttpCode(204)
            ->build();
    }

    /**
     * Unlock a user account.
     *
     * @param User $user The user to unlock
     * @param UnlockUserAccountRequest $request The request for validation and authorization
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function unlockUser(User $user, UnlockUserAccountRequest $request): Response
    {
        if (!$user->isLocked()) {
            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withMessage('User account is not currently locked.')
                ->build();
        }

        $reason = $request->validated('reason') ?? ToggleStatusReasonEnum::ADMIN_ACTIVATION;
        $user->unlockAccount($reason);

        activity()
            ->inLog(ActivityLogTypeEnum::UserManagement)
            ->performedOn($user)
            ->causedBy(Auth::user())
            ->withProperties([
                'user_id' => $user->id,
                'user_email' => $user->email,
                'unlocked_by' => Auth::user()->email,
                'reason' => $reason,
                'ip_address' => request()->ip(),
            ])
            ->log("User '{$user->email}' account unlocked by admin. Reason: {$reason}");

        return ResponseBuilder::asSuccess()
            ->withMessage("User account unlocked successfully.")
            ->withHttpCode(Response::HTTP_OK)
            ->build();
    }

    /**
     * Store a newly created user in storage by an admin.
     *
     * @param CreateUserRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(CreateUserRequest $request, CreateUserAction $action): Response
    {
        $password = $this->generateRandomPassword();
        $userData = $request->validated();
        $userData['password'] = $password;

        $user = $action->handle($userData);

        if (!empty($userData['roles'])) {
            $roles = Role::whereIn('name', $userData['roles'])->get();
            $user->syncRoles($roles);
        }

        $user->forcePasswordReset();
        
        // send welcome email

        activity()
            ->inLog(ActivityLogTypeEnum::UserManagement)
            ->performedOn($user)
            ->causedBy(Auth::user())
            ->withProperties([
                'user_id' => $user->id,
                'user_email' => $user->email,
                'created_by' => Auth::user()->email,
                'assigned_roles' => $user->roles->pluck('name')->toArray(),
                'ip_address' => request()->ip(),
            ])
            ->log("New user '{$user->email}' created by admin.");

        return ResponseBuilder::asSuccess()
            ->withMessage('User created successfully.')
            ->withHttpCode(Response::HTTP_CREATED)
            ->withData(['user' => $user->load('roles')])
            ->build();
    }

}
