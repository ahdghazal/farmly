<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationsController extends Controller
{
    public function getNotifications()
    {
        $notifications = Notification::where('user_id', Auth::id())->get();
        return response()->json($notifications, 200);
    }

    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())->where('id', $id)->first();

        if ($notification) {
            $notification->read = true;
            $notification->save();
            return response()->json(null, 204);
        }

        return response()->json(['error' => 'Notification not found'], 404);
    }
}
