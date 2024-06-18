<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\MessageRead;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    public function index()
    {
        if (Auth::user()->isAdmin()) {
            $conversations = Conversation::with(['user1', 'user2', 'messages'])->get();
        } else {
            $conversations = Conversation::where('user1_id', Auth::id())
                ->orWhere('user2_id', Auth::id())
                ->with(['user1', 'user2', 'messages'])
                ->get();
        }

        return response()->json($conversations, 200);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user2_id' => 'required|exists:users,id',
        ]);

        $conversation = Conversation::firstOrCreate([
            'user1_id' => Auth::id(),
            'user2_id' => $request->user2_id,
        ]);

        return response()->json($conversation, 201);
    }

    public function show($id)
    {
        try {
            $conversation = Conversation::with(['messages.sender'])->findOrFail($id);
    
            if (!Auth::user()->isAdmin() && ($conversation->user1_id != Auth::id() && $conversation->user2_id != Auth::id())) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
    
            $user = Auth::user();
            $messages = $conversation->messages;
    
            if ($user->isAdmin()) {
                $messagesToMark = $messages->where('sender_id', '!=', $user->id)->where('is_read', false);
            } else {
                $messagesToMark = $messages->where('sender_id', '!=', $conversation->user1_id)->where('is_read', false);
            }
    
            foreach ($messagesToMark as $message) {
                $message->is_read = true;
                $message->save();
    
                broadcast(new MessageRead($message))->toOthers();
            }
    
            return response()->json($conversation, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Conversation not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch conversation'], 500);
        }
    }
    

    public function showUserConversation()
    {
        $user = Auth::user();

        $conversation = Conversation::where('user1_id', $user->id)->first();

        if (!$conversation) {
            return response()->json(['message' => 'No conversation found for the user'], 404);
        }

        if (!$user->isAdmin()) {
            $this->markAdminMessagesAsRead($conversation);
        }

        $messages = $conversation->messages()->with('sender')->get();

        $conversation->messages = $messages;

        return response()->json(['conversation' => $conversation]);
    }

    /**
     * Mark admin messages in a conversation as read.
     *
     * @param \App\Models\Conversation $conversation
     */
    protected function markAdminMessagesAsRead($conversation)
    {
        $user = Auth::user();

        $adminMessages = $conversation->messages()->where('sender_id', '!=', $conversation->user1_id)->get();

        foreach ($adminMessages as $message) {
            if (!$message->is_read) {
                $message->is_read = true;
                $message->save();
            }
        }
    }

public function countUnseenMessages($conversationId)
    {
        try {
            $conversation = Conversation::findOrFail($conversationId);

            if (!Auth::user()->isAdmin() && ($conversation->user1_id != Auth::id() && $conversation->user2_id != Auth::id())) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $user = Auth::user();
            $messages = $conversation->messages;
            $countUnseen = 0;

            if ($user->isAdmin()) {
                $countUnseen = $messages->where('sender_id', '!=', $user->id)->where('is_read', false)->count();
            } else {
                $countUnseen = $messages->where('sender_id', '!=', $conversation->user1_id)->where('is_read', false)->count();
            }

            return response()->json(['unseen_count' => $countUnseen], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Conversation not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to count unseen messages'], 500);
        }
    }
}
