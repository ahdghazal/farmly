<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

class NotificationController extends Controller
{
    protected $pusher;

    public function __construct()
    {
        $this->pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => true
            ]
        );
    }

    public function getNotifications()
    {
        $user = Auth::user();
    
        // Log the user for debugging
        Log::info('Authenticated user:', ['user' => $user]);
    
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }
    
        $notifications = Notification::where('user_id', $user->id)->get();
    
        // Log the notifications for debugging
        Log::info('Notifications retrieved:', ['notifications' => $notifications]);
    
        if ($notifications->isEmpty()) {
            return response()->json(['message' => 'No notifications found'], 404);
        }
    
        return response()->json($notifications, 200);
    }

    public function createNotification(Request $request)
    {
        $notification = Notification::create($request->all());

        // Send Pusher notification
        $this->sendPusherNotification($notification);

        return response()->json($notification, 201);
    }

    public function sendNotificationToUser(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
        ]);
    
        $user = Auth::user();
    
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
    
        $notificationData = [
            'title' => $request->title,
            'body' => $request->body,
        ];
    
        $this->pusher->trigger('user-' . $user->id, 'notification', $notificationData);
    
        return response()->json(['message' => 'Notification sent successfully']);
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

    protected function sendPusherNotification($notification)
    {
        $user = User::find($notification->user_id);

        if ($user) {
            $notificationData = [
                'id' => $notification->id,
                'title' => $notification->title,
                'body' => $notification->body,
                'read' => $notification->read,
                'created_at' => $notification->created_at->toDateTimeString(),
                'updated_at' => $notification->updated_at->toDateTimeString(),
            ];

            $this->pusher->trigger('user-' . $user->id, 'notification', $notificationData);
        }
    }
}
