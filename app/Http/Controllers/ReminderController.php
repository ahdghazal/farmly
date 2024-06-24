<?php

namespace App\Http\Controllers;

use App\Models\Garden;
use App\Models\Plant;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;

class ReminderController extends Controller
{
    public function sendWateringReminders()
    {
        $gardens = Garden::all();
        foreach ($gardens as $garden) {
            foreach ($garden->plantEntries as $entry) {
                $plant = $entry->plant;
                $nextWateringDate = $plant->calculateNextWateringDate();
                
                if ($nextWateringDate && now()->greaterThanOrEqualTo($nextWateringDate)) {
                    $this->createReminder($garden->user, $garden, $entry, 'water');
                }
            }
        }
        return response()->json(['message' => 'Watering reminders processed.'], 200);
    }

    public function sendPruningReminders()
    {
        $gardens = Garden::all();
        foreach ($gardens as $garden) {
            foreach ($garden->plantEntries as $entry) {
                $plant = $entry->plant;
                $nextPruningDate = $plant->calculateNextPruningDate();
                
                if ($nextPruningDate && now()->greaterThanOrEqualTo($nextPruningDate)) {
                    $this->createReminder($garden->user, $garden, $entry, 'prune');
                }
            }
        }
        return response()->json(['message' => 'Pruning reminders processed.'], 200);
    }

    protected function createReminder($user, $garden, $plantEntry, $taskType)
    {
        $reminder = Reminder::create([
            'user_id' => $user->id,
            'garden_id' => $garden->id,
            'plant_entry_id' => $plantEntry->id,
            'task_type' => $taskType,
            'task_done' => false,
        ]);

        $this->sendReminderNotification($user, $plantEntry->plant, $taskType);
    }

    protected function sendReminderNotification($user, $plant, $taskType)
    {
        $message = "Reminder: It's time to {$taskType} your plant: {$plant->name}.";
        $this->sendFirebaseNotification($user, $message);
    }

    protected function sendFirebaseNotification($user, $messageData)
    {
        $firebaseToken = $user->firebase_token;

        if (!$firebaseToken) {
            Log::error('User does not have an FCM token');
            return;
        }

        $serviceAccountPath = env('FIREBASE_CREDENTIALS');

        if (!file_exists($serviceAccountPath) || !is_readable($serviceAccountPath)) {
            Log::error('Firebase service account credentials file not found or not readable');
            return;
        }

        try {
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $messaging = $firebase->createMessaging();

            $message = CloudMessage::withTarget('token', $firebaseToken)
                ->withNotification(FirebaseNotification::create('Plant Reminder', $messageData));

            $messaging->send($message);
            Log::info('Notification sent successfully');
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            Log::error('Failed to send notification: ' . $e->getMessage());
        }
    }

    public function getPendingReminders()
    {
        $user = Auth::user();
        $reminders = Reminder::where('user_id', $user->id)->where('task_done', false)->get();

        return response()->json($reminders, 200);
    }

    public function getGardenTasks($gardenId)
    {
        $user = Auth::user();
        $garden = Garden::where('user_id', $user->id)->findOrFail($gardenId);

        $waterCount = $garden->plantEntries->filter(function($entry) {
            $plant = $entry->plant;
            $nextWateringDate = $plant->calculateNextWateringDate();
            return $nextWateringDate && now()->greaterThanOrEqualTo($nextWateringDate);
        })->count();

        $pruneCount = $garden->plantEntries->filter(function($entry) {
            $plant = $entry->plant;
            $nextPruningDate = $plant->calculateNextPruningDate();
            return $nextPruningDate && now()->greaterThanOrEqualTo($nextPruningDate);
        })->count();

        return response()->json([
            'water_count' => $waterCount,
            'prune_count' => $pruneCount,
            'plant_entries' => $garden->plantEntries->load('plant'),
        ], 200);
    }
}
