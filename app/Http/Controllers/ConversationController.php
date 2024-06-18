<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\User;
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
            'user1_id' => 'required|exists:users,id',
        ]);
    
        // Create the conversation record
        $conversation = Conversation::create([
            'user1_id' => $request->user1_id,
            'user2_id' => Auth::id(), // Assigning the admin's ID as user2_id
            'updated_at' => now(),
            'created_at' => now(),
        ]);
    
        return response()->json($conversation, 201);
    }
    

    public function show($id)
    {
        $conversation = Conversation::with(['messages.sender'])->findOrFail($id);

        if (!Auth::user()->isAdmin() && ($conversation->user1_id != Auth::id() && $conversation->user2_id != Auth::id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($conversation, 200);
    }

    public function destroy($id)
    {
        $conversation = Conversation::findOrFail($id);

        if (Auth::user()->isAdmin() || $conversation->user1_id == Auth::id() || $conversation->user2_id == Auth::id()) {
            $conversation->delete();
            return response()->json(null, 204);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function showUserConversation()
    {
        $user = Auth::user();

        $conversation = Conversation::where('user1_id', $user->id)->first();

        if (!$conversation) {
            return response()->json(['message' => 'No conversation found for the user'], 404);
        }

        return response()->json(['conversation' => $conversation]);
    }
}
