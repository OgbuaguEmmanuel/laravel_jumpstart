<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return
            NotificationResource::collection(
                $user->notifications()
                    ->when($request->get('start_date', null), function (Builder $query) {
                        return $query->where('created_at', '>', Carbon::parse(request()->get('start_date')));
                    })
                    ->when($request->get('end_date'), function (Builder $query) {
                        return $query->where('created_at', '<', Carbon::parse(request()->get('end_date')));
                    })
                    ->when($request->get('type'), function (Builder $query) {
                        return $query->where('data->type', request()->get('type'));
                    })
                    ->paginate(15)
            )
            ->additional(['unread' => $user->unreadNotifications()->count()]);
    }

    public function read(Request $request)
    {
        $user = $request->user();

        return
            NotificationResource::collection(
                $user->readNotifications()
                    ->when($request->get('start_date', null), function (Builder $query) {
                        return $query
                            ->where('created_at', '>', Carbon::parse(request()->get('start_date')));
                    })
                    ->when($request->get('end_date'), function (Builder $query) {
                        return $query
                            ->where('created_at', '<', Carbon::parse(request()->get('end_date')));
                    })
                    ->when($request->get('type'), function (Builder $query) {
                        return $query
                            ->where('data->type', request()->get('type'));
                    })
                    ->paginate(15)
            );
    }

    public function unread(Request $request)
    {
        $user = $request->user();

        return
            NotificationResource::collection(
                $user->unreadNotifications()
                    ->when($request->get('start_date', null), function (Builder $query) {
                        return $query
                            ->where('created_at', '>', Carbon::parse(request()->get('start_date')));
                    })
                    ->when($request->get('end_date'), function (Builder $query) {
                        return $query
                            ->where('created_at', '<', Carbon::parse(request()->get('end_date')));
                    })
                    ->when($request->get('type'), function (Builder $query) {
                        return $query
                            ->where('data->type', request()->get('type'));
                    })
                    ->paginate(15)
            );
    }

    public function markRead(Request $request)
    {
        $notification = DatabaseNotification::findOrFail($request->route('notification'));

        return tap(response()->noContent(), function () use ($notification) {
            $notification->markAsRead();
            $notification->update(['read' => true]);
        });
    }

    public function markUnread(Request $request)
    {
        $notification = DatabaseNotification::findOrFail($request->route('notification'));

        return tap(response()->noContent(), function () use ($notification) {
            $notification->markAsUnread();
            $notification->update(['read' => false]);
        });
    }

    public function view(Request $request)
    {
        $notification = DatabaseNotification::findOrFail($request->route('notification'));

        return NotificationResource::make($notification);
    }

    public function destroy(Request $request)
    {
        $notification = DatabaseNotification::findOrFail($request->route('notification'));

        return tap(response()->noContent(), function () use ($notification) {
            $notification->delete();
        });
    }
}
