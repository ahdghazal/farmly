<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Factory;

class NotificationController extends Controller
{
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
        $user = Auth::user();

        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'body' => $request->body,
            'read' => false,
        ]);

        // Send Firebase notification
        $this->sendFirebaseNotification($notification);

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
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        if (!$user->fcm_token) {
            return response()->json(['message' => 'User does not have an FCM token'], 404);
        }

        $serviceAccountPath = env('FIREBASE_CREDENTIALS');
    
        Log::info('FIREBASE_CREDENTIALS path: ' . $serviceAccountPath);
        
        if (!$serviceAccountPath) {
            return response()->json(['message' => 'Firebase service account credentials not found in .env'], 500);
        }
    
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

    protected function sendFirebaseNotification($notification)
    {
        $user = User::find($notification->user_id);

        if ($user && $user->fcm_token) {
            $serviceAccountPath = env('FIREBASE_CREDENTIALS');
        
            Log::info('FIREBASE_CREDENTIALS path: ' . $serviceAccountPath);
            
            if (!$serviceAccountPath) {
                return response()->json(['message' => 'Firebase service account credentials not found in .env'], 500);
            }
        
            if (!file_exists($serviceAccountPath) || !is_readable($serviceAccountPath)) {
                return response()->json(['message' => 'Firebase service account credentials file not found or not readable'], 500);
            }

            try {
                $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
                $messaging = $firebase->createMessaging();
        
                $message = CloudMessage::withTarget('token', $user->fcm_token)
                    ->withNotification(FirebaseNotification::create($notification->title, $notification->body))
                    ->withData(['key' => 'value']); // Additional data if needed
        
                $messaging->send($message);
            } catch (\Kreait\Firebase\Exception\MessagingException $e) {
                Log::error('Failed to send Firebase notification', ['error' => $e->getMessage()]);
            }
        }
    }
}
