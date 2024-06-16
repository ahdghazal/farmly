<?php

namespace App\Http\Controllers;

use App\Models\Garden;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PlantReminderNotification;

class ReminderController extends Controller
{
    /**
     * Send watering reminders.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendWateringReminders()
    {
        $intervals = [
            'high' => 2,
            'moderate' => 4,
            'low' => 7,
        ];

        $gardens = Garden::with(['plants'])->get();

        foreach ($gardens as $garden) {
            foreach ($garden->plants as $plant) {
                $waterNeed = strtolower($plant->water_need);
                $waterInterval = $intervals[$waterNeed] ?? null;

                if ($waterInterval) {
                    $lastWatered = DB::table('plant_reminders')
                        ->where('plant_id', $plant->id)
                        ->where('type', 'water')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    $nextWateringDate = $lastWatered ? $lastWatered->created_at->addDays($waterInterval) : now();

                    if (now()->greaterThanOrEqualTo($nextWateringDate)) {
                        $this->sendReminder($garden->user, $plant, 'water');

                        DB::table('plant_reminders')->insert([
                            'plant_id' => $plant->id,
                            'type' => 'water',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
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
        $intervals = [
            'weekly' => 7,
            'regularly' => 30,
            'annually' => 365,
        ];

        $gardens = Garden::with(['plants'])->get();

        foreach ($gardens as $garden) {
            foreach ($garden->plants as $plant) {
                $pruningNeed = strtolower($plant->pruning);
                $pruningInterval = $intervals[$pruningNeed] ?? null;

                if ($pruningInterval) {
                    $lastPruned = DB::table('plant_reminders')
                        ->where('plant_id', $plant->id)
                        ->where('type', 'prune')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    $nextPruningDate = $lastPruned ? $lastPruned->created_at->addDays($pruningInterval) : now();

                    if (now()->greaterThanOrEqualTo($nextPruningDate)) {
                        $this->sendReminder($garden->user, $plant, 'prune');

                        DB::table('plant_reminders')->insert([
                            'plant_id' => $plant->id,
                            'type' => 'prune',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
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
        Notification::send($user, new PlantReminderNotification($message));
    }


}
