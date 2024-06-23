<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Pusher\Pusher;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Models\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class MessageController extends Controller
{

    protected function sendNotificationToUser(Request $request, $type, $userId, $messageData)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);
    
        $user = User::find($request->user_id);
    
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
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
    
            $notificationTitle = $request->title;
            $notificationBody = $request->body;
    
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(FirebaseNotification::create($notificationTitle, $notificationBody))
                ->withData($messageData); // Use messageData directly for additional data
        
            $messaging->send($message);
            return response()->json(['message' => 'Notification sent successfully']);
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            return response()->json(['message' => 'Failed to send notification', 'error' => $e->getMessage()], 500);
        }
    }
    


    
    public function store(Request $request, $conversationId)
    {
        $request->validate(['message' => 'required|string']);
    
        $conversation = Conversation::findOrFail($conversationId);
    
        if (!($conversation->user1_id == Auth::id() || $conversation->user2_id == Auth::id() || Auth::user()->isAdmin())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    
        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => Auth::id(),
            'message' => $request->message,
            'is_read' => false,
        ]);
    
        broadcast(new MessageSent($message))->toOthers();
    
        $recipientId = $conversation->user1_id == Auth::id() ? $conversation->user2_id : $conversation->user1_id;
    
        if (Auth::user()->isAdmin()) {
            $this->sendNotificationToUser($request, 'message', $recipientId, [
                'title' => 'New Message from Admin',
                'body' => Auth::user()->name . ' sent you a message: ' . $request->message,
                'conversation_id' => $conversationId, // Include conversation_id in the message data
                'message' => $request->message,
            ]);
        }
    
        return response()->json($message->load('sender'), 201);
    }
    
    public function update(Request $request, $conversationId, $messageId)
    {
        $request->validate(['message' => 'required|string']);

        try {
            $message = Message::where('conversation_id', $conversationId)
                              ->findOrFail($messageId);

            if (!Auth::user()->isAdmin() && $message->sender_id != Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $message->message = $request->message;
            $message->save();

            $this->broadcastMessage($message, 'message-updated');

            return response()->json($message->load('sender'), 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Message not found'], 404);
        }
    }
    
    public function destroy(Request $request, $conversationId, $messageId)
    {
        try {
            $message = Message::where('conversation_id', $conversationId)
                              ->findOrFail($messageId);
    
            if (!Auth::user()->isAdmin() && $message->sender_id != Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
    
            $message->delete();
    
            $this->broadcastMessage($message, 'message-deleted');
    
            return response()->json(['status' => 'success', 'message' => 'Message deleted successfully.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Message not found: {$e->getMessage()}");
            return response()->json(['status' => 'error', 'message' => 'Message not found.'], 404);
        } catch (\Exception $e) {
            Log::error("An error occurred while deleting the message: {$e->getMessage()}");
            return response()->json(['status' => 'error', 'message' => 'An error occurred while deleting the message.'], 500);
        }
    }
    

    public function markAsRead($conversationId, $messageId)
    {
        try {
            $message = Message::where('conversation_id', $conversationId)
                              ->findOrFail($messageId);

            if (!Auth::user()->isAdmin() && ($message->conversation->user1_id != Auth::id() && $message->conversation->user2_id != Auth::id())) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $message->is_read = true;
            $message->save();

            broadcast(new MessageRead($message))->toOthers();

            return response()->json($message->load('sender'), 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Message not found'], 404);
        }
    }

    protected function broadcastMessage(Message $message, $event = 'message-sent')
    {
        $pusher = new Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id'),
            ['cluster' => config('broadcasting.connections.pusher.options.cluster'), 'useTLS' => true]
        );

        $pusher->trigger('chat', $event, [
            'message' => $message->load('sender'),
        ]);
    }
}
