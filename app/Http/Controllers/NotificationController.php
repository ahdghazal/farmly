<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;

class NotificationController extends Controller
{
    protected $firebaseUrl;

    public function __construct()
    {
        $this->firebaseUrl = 'https://fcm.googleapis.com/fcm/send';
    }

    /**
     * Get all notifications for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNotifications()
    {
        $user = Auth::user();
        $notifications = Notification::where('user_id', $user->id)->get();

        return response()->json($notifications, 200);
    }

    /**
     * Create a new notification and send it via Firebase.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createNotification(Request $request)
    {
        $notification = Notification::create($request->all());

        // Send Firebase notification
        $this->sendFirebaseNotification($notification);

        return response()->json($notification, 201);
    }

    /**
     * Send Firebase notification.
     *
     * @param Notification $notification
     * @return array
     */
    protected function sendFirebaseNotification($notification)
    {
        $client = new Client();

        $response = $client->post($this->firebaseUrl, [
            'headers' => [
                'Authorization' => 'key=' . env('FIREBASE_SERVER_KEY'),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'to' => '/topics/all', // Example: send to a specific topic or user device token
                'notification' => [
                    'title' => 'New Notification',
                    'body' => $notification->message,
                ],
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Mark a notification as read.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['read' => true]);

        return response()->json(['message' => 'Notification marked as read'], 200);
    }

    /**
     * Get the count of unread notifications for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnreadCount()
    {
        $user = Auth::user();
        $unreadCount = Notification::where('user_id', $user->id)->where('read', false)->count();

        return response()->json(['unread_count' => $unreadCount], 200);
    }
}

