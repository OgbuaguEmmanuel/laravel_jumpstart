<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionTypeEnum;
use App\Facades\Settings;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogController extends Controller
{
    protected int $per_page;

    public function __construct()
    {
        $this->per_page = Settings::get('pagination_size');
    }

    /**
     * List activity logs with various filtering and pagination options.
     *
     * @throws ValidationException|AuthorizationException
     */
    public function listActivities(Request $request): Response
    {
        $user = Auth::user();

        if (! $user->can(PermissionTypeEnum::viewActivity)) {
            throw new AuthorizationException('You do not have permission to view activity logs.');
        }

        try {
            $validatedData = $request->validate([
                'log_name' => 'nullable|string', // Comma-separated or single log name
                'causer_id' => 'nullable|integer|exists:users,id',
                'description' => 'nullable|string',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ]);

            $query = Activity::query();

            $canViewAllActivities = $user->can(PermissionTypeEnum::viewAllActivities);

            if (! empty($validatedData['log_name'])) {
                $logNames = explode(',', $validatedData['log_name']);
                $filteredLogNames = array_map('trim', $logNames);

                $query->whereIn('log_name', $filteredLogNames);
            }

            if (isset($validatedData['causer_id'])) {
                if ($canViewAllActivities) {
                    $query->where('causer_id', $validatedData['causer_id']);
                } elseif ($validatedData['causer_id'] != $user->id) {
                    throw new AuthorizationException('You do not have permission to view activities of other users.');
                } else {
                    // User viewing their own activities
                    $query->where('causer_id', $user->id);
                }
            } else {
                if (! $canViewAllActivities) {
                    $query->where('causer_id', $user->id);
                }
            }

            if (! empty($validatedData['description'])) {
                $query->where('description', 'like', '%'.$validatedData['description'].'%');
            }

            if (! empty($validatedData['from_date'])) {
                $query->where('created_at', '>=', $validatedData['from_date'].' 00:00:00');
            }
            if (! empty($validatedData['to_date'])) {
                $query->where('created_at', '<=', $validatedData['to_date'].' 23:59:59');
            }

            $query->latest();

            $perPage = $validatedData['per_page'] ?? $this->per_page;
            $activities = $query->paginate($perPage);

            if ($activities->isEmpty()) {
                return ResponseBuilder::asSuccess()
                    ->withHttpCode(Response::HTTP_OK)
                    ->withMessage('No activities found matching your criteria.')
                    ->withData(['data' => [], 'meta' => $activities->toArray()])
                    ->build();
            }

            return ResponseBuilder::asSuccess()
                ->withHttpCode(Response::HTTP_OK)
                ->withMessage('Activities retrieved successfully.')
                ->withData($activities->toArray())
                ->build();

        } catch (ValidationException $e) {
            return ResponseBuilder::asError($e->status)
                ->withHttpCode($e->status)
                ->withMessage($e->getMessage())
                ->withData($e->errors())
                ->build();
        } catch (AuthorizationException $e) {
            Log::error('Error fetching activity logs: '.$e->getMessage(), ['exception' => $e, 'user_id' => $user->id ?? 'guest']);

            return ResponseBuilder::asError(Response::HTTP_FORBIDDEN)
                ->withHttpCode(Response::HTTP_FORBIDDEN)
                ->withMessage($e->getMessage())
                ->build();

        } catch (\Exception $e) {
            Log::error('Error fetching activity logs: '.$e->getMessage(), ['exception' => $e, 'user_id' => $user->id ?? 'guest']);

            return ResponseBuilder::asError(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->withHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->withMessage('An unexpected error occurred while fetching activity logs. Please try again later.')
                ->build();
        }
    }
}
