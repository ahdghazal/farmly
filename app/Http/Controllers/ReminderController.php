<?php

namespace App\Http\Controllers;

use App\Models\Garden;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Factory;
use App\Models\Notification;
use App\Models\User;
use App\Models\Plant;
use App\Models\GardenPlantEntry;
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
        $users = User::all();

        foreach ($users as $user) {
            $gardens = Garden::where('user_id', $user->id)->get();

            foreach ($gardens as $garden) {
                foreach ($garden->plantEntries as $plantEntry) {
                    $plant = $plantEntry->plant;
                    $waterNeed = strtolower($plant->water_need);
                    $interval = 0;

                    if ($waterNeed === 'high') {
                        $interval = 2;
                    } elseif ($waterNeed === 'moderate') {
                        $interval = 4;
                    } elseif ($waterNeed === 'low') {
                        $interval = 7;
                    }

                    if ($interval > 0) {
                        $this->scheduleReminder($user, $garden, $plantEntry, 'water', $interval);
                    }
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
        $users = User::all();

        foreach ($users as $user) {
            $gardens = Garden::where('user_id', $user->id)->get();

            foreach ($gardens as $garden) {
                foreach ($garden->plantEntries as $plantEntry) {
                    $plant = $plantEntry->plant;
                    $pruning = strtolower($plant->pruning);
                    $interval = 0;

                    if ($pruning === 'annually') {
                        $interval = 365;
                    } elseif ($pruning === 'regularly') {
                        $interval = 30;
                    } elseif ($pruning === 'weekly') {
                        $interval = 7;
                    }

                    if ($interval > 0) {
                        $this->scheduleReminder($user, $garden, $plantEntry, 'prune', $interval);
                    }
                }
            }
        }

        return response()->json(['message' => 'Pruning reminders sent successfully.'], 200);
    }

    /**
     * Schedule a reminder.
     *
     * @param User $user
     * @param Garden $garden
     * @param GardenPlantEntry $plantEntry
     * @param string $taskType
     * @param int $interval
     */
    protected function scheduleReminder($user, $garden, $plantEntry, $taskType, $interval)
    {
        $lastReminder = Reminder::where('user_id', $user->id)
            ->where('garden_id', $garden->id)
            ->where('garden_plant_entry_id', $plantEntry->id)
            ->where('task_type', $taskType)
            ->latest()
            ->first();

        $nextReminderDate = now();

        if ($lastReminder) {
            $nextReminderDate = $lastReminder->created_at->addDays($interval);
        }

        if (now()->greaterThanOrEqualTo($nextReminderDate)) {
            $this->sendReminder($user, $plantEntry->plant, $taskType);

            Reminder::create([
                'user_id' => $user->id,
                'garden_id' => $garden->id,
                'garden_plant_entry_id' => $plantEntry->id,
                'task_type' => $taskType,
                'task_done' => false,
            ]);
        }
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
        $this->sendFirebaseNotification(new Request([
            'user_id' => $user->id,
            'title' => "Plant {$type} Reminder",
            'body' => $message,
        ]), $user->id, $message);
    }

    /**
     * Confirm task done.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmTaskDone(Request $request)
    {
        $request->validate([
            'reminder_id' => 'required|exists:reminders,id',
            'task_done' => 'required|boolean',
        ]);

        $reminder = Reminder::find($request->reminder_id);
        $reminder->task_done = $request->task_done;
        $reminder->save();

        if (!$request->task_done) {
            // Reschedule reminder in 6 hours if the task was not done
            $this->rescheduleReminder($reminder, 6);
        }

        return response()->json(['message' => 'Task confirmation updated successfully.'], 200);
    }

    /**
     * Reschedule a reminder.
     *
     * @param Reminder $reminder
     * @param int $hours
     */
    protected function rescheduleReminder($reminder, $hours)
    {
        Reminder::create([
            'user_id' => $reminder->user_id,
            'garden_id' => $reminder->garden_id,
            'garden_plant_entry_id' => $reminder->garden_plant_entry_id,
            'task_type' => $reminder->task_type,
            'task_done' => false,
            'created_at' => now()->addHours($hours),
        ]);
    }

    /**
     * Get pending reminders.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingReminders()
    {
        $user = Auth::user();

        $pendingReminders = Reminder::where('user_id', $user->id)
            ->where('task_done', false)
            ->get();

        return response()->json($pendingReminders, 200);
    }

    /**
     * Get garden plants needs.
     *
     * @param int $gardenId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGardenPlantsNeeds($gardenId)
    {
        $garden = Garden::findOrFail($gardenId);

        $waterNeeds = [];
        $pruneNeeds = [];

        foreach ($garden->plantEntries as $plantEntry) {
            $plant = $plantEntry->plant;

            $waterNeed = strtolower($plant->water_need);
            $pruneNeed = strtolower($plant->pruning);

            if ($waterNeed === 'high' || $waterNeed === 'moderate' || $waterNeed === 'low') {
                $waterNeeds[] = $plant;
            }

            if ($pruneNeed === 'annually' || $pruneNeed === 'regularly' || $pruneNeed === 'weekly') {
                $pruneNeeds[] = $plant;
            }
        }

        return response()->json([
            'water_needs' => $waterNeeds,
            'prune_needs' => $pruneNeeds,
        ], 200);
    }

    /**
     * Send Firebase notification.
     *
     * @param Request $request
     * @param int $userId
     * @param string $messageData
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
                ->withNotification(FirebaseNotification::create($notificationTitle, $notificationBody));

            $messaging->send($message);

            // Save notification details in the database
            Notification::create([
                'user_id' => $userId,
                'title' => $notificationTitle,
                'message' => $messageData,
            ]);

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Error sending Firebase notification: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
