<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    public function index()
    {
        $conversations = Conversation::where('user1_id', Auth::id())
            ->orWhere('user2_id', Auth::id())
            ->with(['user1', 'user2', 'messages'])
            ->get();

        return response()->json($conversations, 200);
    }

    public function store(Request $request)
    {
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
        $conversation = Conversation::with(['messages.sender'])->findOrFail($id);
        return response()->json($conversation, 200);
    }

    public function destroy($id)
    {
        $conversation = Conversation::findOrFail($id);

        if ($conversation->user1_id == Auth::id() || $conversation->user2_id == Auth::id()) {
            $conversation->delete();
            return response()->json(null, 204);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
