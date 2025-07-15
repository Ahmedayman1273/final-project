<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Get user's notifications
    public function index(Request $request)
    {
        return response()->json([
            'notifications' => $request->user()->notifications
        ]);
    }

    // Mark a notification as read
    public function markAsRead($id, Request $request)
    {
        $notification = $request->user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['message' => 'Notification marked as read']);
    }

    // Get count of unread notifications
    public function unreadCount(Request $request)
    {
        return response()->json([
            'unread' => $request->user()->unreadNotifications->count()
        ]);
    }
}
