<?php

namespace App\Http\Controllers;

use App\Models\Garden;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Factory;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;


class ReminderController extends Controller
{
    /**
     * Send watering reminders.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendWateringReminders()
    {
        $user = Auth::user(); // Assuming you are using authentication
        $gardens = Garden::where('user_id', $user->id)->get();

        foreach ($gardens as $garden) {
            foreach ($garden->plants as $plant) {
                $nextWateringDate = $plant->next_watering_date;

                if (now()->greaterThanOrEqualTo($nextWateringDate)) {
                    // Send reminder using Firebase
                    $this->sendReminder($user, $plant, 'water');

                    // Save reminder details
                    DB::table('plant_reminders')->insert([
                        'plant_id' => $plant->id,
                        'type' => 'water',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Watering reminders sent successfully.'], 200);
    }

    /**
     * Send pruning reminders.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendPruningReminders()
    {
        $user = Auth::user(); // Assuming you are using authentication
        $gardens = Garden::where('user_id', $user->id)->get();

        foreach ($gardens as $garden) {
            foreach ($garden->plants as $plant) {
                $nextPruningDate = $plant->next_pruning_date;

                if (now()->greaterThanOrEqualTo($nextPruningDate)) {
                    // Send reminder using Firebase
                    $this->sendReminder($user, $plant, 'prune');

                    // Save reminder details
                    DB::table('plant_reminders')->insert([
                        'plant_id' => $plant->id,
                        'type' => 'prune',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Pruning reminders sent successfully.'], 200);
    }

    /**
     * Send reminder notification to the user.
     *
     * @param User $user
     * @param Plant $plant
     * @param string $type
     */
    protected function sendReminder($user, $plant, $type)
    {
        $message = "Reminder: It's time to {$type} your plant: {$plant->name}.";

        // Send notification using Firebase
        $this->sendFirebaseNotification($request, $user, $message);
    }

    /**
     * Send Firebase notification.
     *
     * @param User $user
     * @param string $message
     * @return array
     */
    protected function sendFirebaseNotification(Request $request, $userId, $messageData)
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
}

