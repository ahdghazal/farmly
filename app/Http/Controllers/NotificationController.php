<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Messaging\CloudMessage;
use GuzzleHttp\Client;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;


class NotificationController extends Controller
{

    protected $firebaseUrl;

    public function __construct()
    {
        $this->firebaseUrl = env('FIREBASE_DATABASE_URL'); 
    }

    
    public function getNotifications()
    {
        $user = Auth::user();
        $notifications = \App\Models\Notification::where('user_id', $user->id)->get();

        return response()->json($notifications, 200);
    }


    public function createNotification(Request $request)
    {
        $notification = \App\Models\Notification::create($request->all());

        // Send Firebase notification
        $this->sendFirebaseNotification($notification);

        return response()->json($notification, 201);
    }


    public function sendNotificationToUser(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'title' => 'required|string',
        'body' => 'required|string',
    ]);

    $user = User::findOrFail($request->user_id);

    if (!$user->fcm_token) {
        return response()->json(['message' => 'User does not have an FCM token'], 404);
    }

    // Load the service account credentials from the environment variable
    $serviceAccountPath = env('FIREBASE_CREDENTIALS');
    
    if (!$serviceAccountPath) {
        return response()->json(['message' => 'Firebase service account credentials not found in .env'], 500);
    }

    // Check if the file exists and is readable
    if (!file_exists($serviceAccountPath) || !is_readable($serviceAccountPath)) {
        return response()->json(['message' => 'Firebase service account credentials file not found or not readable'], 500);
    }

    try {
        $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
        $messaging = $firebase->createMessaging();

        $message = CloudMessage::withTarget('token', $user->fcm_token)
            ->withNotification(FirebaseNotification::create($request->title, $request->body))
            ->withData(['key' => 'value']); // Additional data if needed

        $messaging->send($message);
        return response()->json(['message' => 'Notification sent successfully']);
    } catch (\Kreait\Firebase\Exception\MessagingException $e) {
        return response()->json(['message' => 'Failed to send notification', 'error' => $e->getMessage()], 500);
    }
}











public function testFirebaseCredentials()
{
    $serviceAccountPath = env('FIREBASE_CREDENTIALS');

    if (!$serviceAccountPath) {
        return response()->json(['message' => 'Firebase service account credentials not found in .env'], 500);
    }

    if (!file_exists($serviceAccountPath) || !is_readable($serviceAccountPath)) {
        return response()->json(['message' => 'Firebase service account credentials file not found or not readable'], 500);
    }

    return response()->json(['message' => 'Firebase service account credentials are accessible']);
}





























    public function markAsRead($id)
    {
        $notification = \App\Models\Notification::findOrFail($id);
        $notification->update(['read' => true]);

        return response()->json(['message' => 'Notification marked as read'], 200);
    }


    public function getUnreadCount()
    {
        $user = Auth::user();
        $unreadCount = \App\Models\Notification::where('user_id', $user->id)->where('read', false)->count();

        return response()->json(['unread_count' => $unreadCount], 200);
    }
}

