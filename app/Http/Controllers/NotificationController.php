<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class NotificationController extends Controller
{
    public function getNotifications()
    {
        $user = Auth::user();
        $notifications = Notification::where('user_id', $user->id)->get();

        return response()->json($notifications, 200);
    }

    public function createNotification(Request $request)
    {
        $notification = Notification::create($request->all());
        broadcast(new NewNotification($notification))->toOthers();

        return response()->json($notification, 201);
    }

    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['read' => true]);

        return response()->json(['message' => 'Notification marked as read'], 200);
    }

    public function getUnreadCount()
    {
        $user = Auth::user();
        $unreadCount = Notification::where('user_id', $user->id)->where('read', false)->count();

        return response()->json(['unread_count' => $unreadCount], 200);
    }


}
