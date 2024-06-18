<?php

namespace App\Http\Controllers;

use App\Models\Garden;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class ReminderController extends Controller
{
    protected $firebaseUrl;

    public function __construct()
    {
        $this->firebaseUrl = 'https://fcm.googleapis.com/fcm/send';
    }

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
        $this->sendFirebaseNotification($user, $message);
    }

    /**
     * Send Firebase notification.
     *
     * @param User $user
     * @param string $message
     * @return array
     */
    protected function sendFirebaseNotification($user, $message)
    {
        $client = new Client();

        $response = $client->post($this->firebaseUrl, [
            'headers' => [
                'Authorization' => 'key=' . env('FIREBASE_SERVER_KEY'),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'to' => $user->device_token, // Example: send to a specific device token
                'notification' => [
                    'title' => 'Plant Reminder',
                    'body' => $message,
                ],
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
}

