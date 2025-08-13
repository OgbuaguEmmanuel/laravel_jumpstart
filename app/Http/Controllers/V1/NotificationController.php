<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $user = $request->user();

        return
            NotificationResource::collection(
                $user->notifications()
                    ->when($request->filled('start_date'), function (Builder $query) use ($request) {
                        $startDate = Carbon::parse($request->get('start_date'))->startOfDay();
                        $query->where('created_at', '>=', $startDate);
                    })
                    ->when($request->filled('end_date'), function (Builder $query) use ($request) {
                        $endDate = Carbon::parse($request->get('end_date'))->endOfDay();
                        $query->where('created_at', '<=', $endDate);
                    })
                    ->when($request->filled('type'), function (Builder $query) use ($request) {
                        $query->where('type', $request->get('type'));
                    })
                    ->paginate(15)
            )->additional(['unread' => $user->unreadNotifications()->count()]);
    }

    public function read(Request $request)
    {
        return
            NotificationResource::collection(
                $request->user()->readNotifications()
                    ->when($request->filled('start_date'), function (Builder $query) use ($request) {
                        $startDate = Carbon::parse($request->get('start_date'))->startOfDay();
                        $query->where('created_at', '>=', $startDate);
                    })
                    ->when($request->filled('end_date'), function (Builder $query) use ($request) {
                        $endDate = Carbon::parse($request->get('end_date'))->endOfDay();
                        $query->where('created_at', '<=', $endDate);
                    })
                    ->when($request->get('type'), function (Builder $query) {
                        return $query
                            ->where('type', request()->get('type'));
                    })
                    ->paginate(15)
            );
    }

    public function unread(Request $request)
    {
        return
            NotificationResource::collection(
                $request->user()->unreadNotifications()
                    ->when($request->filled('start_date'), function (Builder $query) use ($request) {
                        $startDate = Carbon::parse($request->get('start_date'))->startOfDay();
                        $query->where('created_at', '>=', $startDate);
                    })
                    ->when($request->filled('end_date'), function (Builder $query) use ($request) {
                        $endDate = Carbon::parse($request->get('end_date'))->endOfDay();
                        $query->where('created_at', '<=', $endDate);
                    })
                    ->when($request->get('type'), function (Builder $query) {
                        return $query
                            ->where('type', request()->get('type'));
                    })
                    ->paginate(15)
            );
    }

    public function markRead(DatabaseNotification $notification)
    {
        $this->authorize('markRead', $notification);

        return tap(response()->noContent(), function () use ($notification) {
            $notification->markAsRead();
        });
    }

    public function markUnread(DatabaseNotification $notification)
    {
        $this->authorize('markUnread', $notification);

        return tap(response()->noContent(), function () use ($notification) {
            $notification->markAsUnread();
        });
    }

    public function view(DatabaseNotification $notification)
    {
        $this->authorize('view', $notification);

        return NotificationResource::make($notification);
    }

    public function destroy(DatabaseNotification $notification)
    {
        $this->authorize('destroy', $notification);

        return tap(response()->noContent(), function () use ($notification) {
            $notification->delete();
        });
    }

}
