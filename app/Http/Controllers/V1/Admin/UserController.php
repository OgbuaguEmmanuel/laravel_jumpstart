<?php

namespace App\Http\Controllers\V1\Admin;

use App\Actions\CreateUserAction;
use App\Enums\ActivityLogTypeEnum;
use App\Enums\ToggleStatusReasonEnum;
use App\Exports\UsersExport;
use App\Facades\Settings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\DeleteUserRequest;
use App\Http\Requests\Admin\ToggleUserRequest;
use App\Http\Requests\Admin\UnlockUserAccountRequest;
use App\Http\Requests\FileImportRequest;
use App\Imports\UsersImport;
use App\Jobs\ExportUsersJob;
use App\Jobs\ImportUsersJob;
use App\Mail\ImportUsersReportMail;
use App\Models\User;
use App\Notifications\UserAccountDeletedNotification;
use App\Notifications\UserAccountUnlockedNotification;
use App\Notifications\UserStatusToggledNotification;
use App\Traits\AuthHelpers;
use App\Traits\Helper;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    use AuthHelpers, Helper;
    use AuthorizesRequests;

    protected int $per_page;

    public function __construct()
    {
        $this->per_page = Settings::get('pagination_size');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $users = QueryBuilder::for(User::query())
            ->defaultSort('-created_at')
            ->allowedFilters('first_name', 'last_name', 'email', 'is_active')
            ->allowedSorts(['first_name', 'last_name', 'created_at'])
            ->paginate($request->get('per_page') ?? $this->per_page);

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
        $this->authorize('viewAny', User::class);

        return ResponseBuilder::asSuccess()
            ->withData(['user' => $user])
            ->withMessage('User fetched')
            ->build();
    }

    /**
     * Store a newly created user in storage by an admin.
     */
    public function store(CreateUserRequest $request, CreateUserAction $action): Response
    {
        $this->authorize('create', User::class);

        $userData = $request->validated();
        $userData['password'] = $this->generateRandomPassword();

        $file = null;
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
        }

        $admin = Auth::user();
        $user = $action->handle($userData, $file, $admin->id);

        if (! empty($userData['roles'])) {
            $roles = Role::whereIn('name', $userData['roles'])->get();
            $user->syncRoles($roles);
        }

        $user->forcePasswordReset();

        $user->profile_picture_url = $user->profilePicture();

        activity()
            ->inLog(ActivityLogTypeEnum::UserManagement)
            ->performedOn($user)
            ->causedBy(Auth::user())
            ->withProperties([
                'user_id' => $user->id,
                'user_email' => $user->email,
                'created_by' => $admin->email,
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteUserRequest $request)
    {
        $this->authorize('delete', User::class);

        $ids = $request->validated('ids');
        $deletedCount = User::whereIn('id', $ids)->delete();

        $message = $deletedCount === 1 ? 'User deleted successfully.'
            : ($deletedCount > 1 ? 'Users deleted successfully.' : 'No users were deleted.');

        $deletedUsers = User::withTrashed()->whereIn('id', $ids)->get();
        $loggedInUser = Auth::user();

        foreach ($deletedUsers as $user) {
            $user->notify(new UserAccountDeletedNotification($user, $loggedInUser));
            $loggedInUser->notify(new UserAccountDeletedNotification($user, $loggedInUser));
        }

        activity()
            ->inLog(ActivityLogTypeEnum::UserManagement)
            ->causedBy($loggedInUser)
            ->withProperties([
                'deleted_accounts' => $request->validated('ids'),
                'deleted_by' => $loggedInUser->email,
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
        if ($request->validated('action') === 'activate' && $user->is_active) {
            return ResponseBuilder::asError(421)
                ->withMessage('User is already active')
                ->build();
        }

        if ($request->validated('action') === 'deactivate' && ! $user->is_active) {
            return ResponseBuilder::asError(421)
                ->withMessage('User is already not active')
                ->build();
        }

        $user->is_active = ! $user->is_active;

        if ($user->is_active) {
            $user->activated_at = now();
            $user->deactivated_at = null;
        } else {
            $user->activated_at = null;
            $user->deactivated_at = now();
        }
        $user->status_reason = $request->validated('reason');
        $user->save();

        $statusText = $user->is_active ? 'activated' : 'deactivated';
        $admin = Auth::user();

        activity()
            ->inLog(ActivityLogTypeEnum::UserManagement)
            ->causedBy(Auth::user())
            ->performedOn($user)
            ->withProperties([
                'status' => $statusText,
                'toggled_by' => Auth::user()->email,
                'ip_address' => request()->ip(),
                'reason' => $user->status_reason,
            ])
            ->log("User {$statusText} successfully.");

        $user->refresh();

        $user->notify(new UserStatusToggledNotification($user, $admin, $statusText, $user->status_reason));
        $admin->notify(new UserStatusToggledNotification($user, $admin, $statusText, $user->status_reason));

        return ResponseBuilder::asSuccess()
            ->withMessage("User {$statusText} successfully.")
            ->withData($user)
            ->withHttpCode(200)
            ->build();
    }

    public function lockedUsers(Request $request): Response
    {
        $this->authorize('viewLock', User::class);

        $users = QueryBuilder::for(User::query()->isLocked())
            ->defaultSort('-created_at')
            ->allowedFilters('first_name', 'last_name', 'email')
            ->allowedSorts(['first_name', 'last_name', 'created_at'])
            ->paginate($request->get('per_page') ?? $this->per_page);

        return ResponseBuilder::asSuccess()
            ->withData(['users' => $users])
            ->withMessage('Locked users successfully fetched')
            ->build();
    }

    /**
     * Unlock a user account.
     *
     * @param  User  $user  The user to unlock
     * @param  UnlockUserAccountRequest  $request  The request for validation and authorization
     */
    public function unlockUser(User $user, UnlockUserAccountRequest $request): Response
    {
        $this->authorize('unlockUser', User::class);

        if (! $user->isLocked()) {
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

        $admin = Auth::user();
        $notification = new UserAccountUnlockedNotification($user, $admin, $reason);

        $user->notify($notification);
        $admin->notify($notification);

        return ResponseBuilder::asSuccess()
            ->withMessage('User account unlocked successfully.')
            ->withHttpCode(Response::HTTP_OK)
            ->build();
    }

    public function import(FileImportRequest $request)
    {
        $this->authorize('importUsers', User::class);

        if (! $request->hasFile('file')) {
            abort(400, 'No file uploaded');
        }

        $file = $request->file('file');
        $path = 'app/private/imports/';
        $fileName = $file->getClientOriginalName();
        $file->move(storage_path($path), $fileName);
        $fullPath = storage_path($path . $fileName);

        $admin = Auth::user();
        $import = new UsersImport($admin->id);
        Excel::import($import, $fullPath);

        Storage::disk('local')->delete('/imports/'.$fileName);

        if ($import->failures()->isNotEmpty()) {
            Mail::to($admin->email)->send(new ImportUsersReportMail($import->failures()));

            return ResponseBuilder::asError(422)
                ->withHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->withMessage('Import completed with some errors. Please check your email for details.')
                ->withData(['failures' => $import->failures()])
                ->build();
        }

        return ResponseBuilder::asSuccess()
            ->withMessage('Import successful. All rows processed successfully.')
            ->build();
    }

    public function importAsync(FileImportRequest $request)
    {
        $this->authorize('importUsers', User::class);

        $file = $request->file('file');
        $path = 'app/private/imports/';
        $fileName = $file->getClientOriginalName();
        $file->move(storage_path($path), $fileName);
        $fullPath = storage_path($path . $fileName);
        $pathForDelete = '/imports/'.$fileName;

        ImportUsersJob::dispatch(Auth::user(), $fullPath, $pathForDelete);

        return ResponseBuilder::asSuccess()
            ->withMessage('Import started. You will receive an email when it is completed.')
            ->build();
    }

    public function export(Request $request)
    {
        $this->authorize('exportUsers', User::class);

        $type = strtolower($request->query('type', 'excel'));
        $fileName = 'users_export_'.now()->format('Y_m_d_His');

        if ($type === 'csv') {
            return Excel::download(new UsersExport, $fileName.'.csv', ExcelExcel::CSV);
        }

        return Excel::download(new UsersExport, $fileName.'.xlsx', ExcelExcel::XLSX);
    }

    public function exportAsync(Request $request)
    {
        $this->authorize('exportUsers', User::class);

        $type = strtolower($request->query('type', 'excel'));
        ExportUsersJob::dispatch(auth()->user(), $type);

        return ResponseBuilder::asSuccess()
            ->withMessage('Export started. You will receive an email with the file when it is ready.')
            ->build();
    }

    public function download(string $file): StreamedResponse
    {
        if ((int) request()->owner !== auth()->id()) {
            abort(403, 'You are not authorized to download this file.');
        }

        $path = 'exports/'.$file;
        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('local')->download($path);
    }
}
