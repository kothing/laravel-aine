<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    /**
     * List the current user's notifications with an unread count.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $limit = min((int) $request->get('limit', 20), 50);

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            });

        return response()->json([
            'data' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ], 200);
    }

    /**
     * Mark notifications as read. Accepts an optional list of ids; without
     * ids every unread notification is marked as read.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markRead(Request $request)
    {
        $user = $request->user();

        $ids = $request->get('ids');

        if (is_array($ids) && count($ids) > 0) {
            $user->notifications()
                ->whereIn('id', $ids)
                ->whereNull('read_at')
                ->get()
                ->each->markAsRead();
        } else {
            $user->unreadNotifications->each->markAsRead();
        }

        return response()->json(['message' => 'Notifications marked as read.'], 200);
    }
}
