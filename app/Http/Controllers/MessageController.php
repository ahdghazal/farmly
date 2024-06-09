<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Pusher\Pusher;

class MessageController extends Controller
{
    public function store(Request $request, $conversationId)
    {
        $request->validate(['message' => 'required|string']);

        $conversation = Conversation::findOrFail($conversationId);
        if ($conversation->user1_id != Auth::id() && $conversation->user2_id != Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => Auth::id(),
            'message' => $request->message,
            'is_read' => false,
        ]);

        $this->broadcastMessage($message);

        return response()->json($message->load('sender'), 201);
    }

    public function update(Request $request, $conversationId, $messageId)
    {
        $request->validate(['message' => 'required|string']);
        $message = Message::where('conversation_id', $conversationId)
            ->where('id', $messageId)
            ->where('sender_id', Auth::id())
            ->firstOrFail();

        $message->message = $request->message;
        $message->save();

        $this->broadcastMessage($message);

        return response()->json($message, 200);
    }

    public function destroy($conversationId, $messageId)
    {
        $message = Message::where('conversation_id', $conversationId)
            ->where('id', $messageId)
            ->where('sender_id', Auth::id())
            ->firstOrFail();

        $message->delete();

        $this->broadcastMessage($message, 'message-deleted');

        return response()->json(null, 204);
    }

    public function markAsRead($conversationId, $messageId)
    {
        $message = Message::where('conversation_id', $conversationId)
            ->where('id', $messageId)
            ->firstOrFail();

        if ($message->conversation->user1_id == Auth::id() || $message->conversation->user2_id == Auth::id()) {
            $message->is_read = true;
            $message->save();
            return response()->json($message, 200);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
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
