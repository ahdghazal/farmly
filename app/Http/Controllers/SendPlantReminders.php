<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Plant;
use App\Models\Garden;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class SendPlantReminders extends Command
{
    protected $signature = 'send:plant-reminders';
    protected $description = 'Send reminders for plant watering and pruning needs';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $gardens = Garden::with('plants')->get();

        foreach ($gardens as $garden) {
            foreach ($garden->plants as $plant) {
                // Check watering needs
                $lastWatered = $plant->pivot->updated_at; // Assuming last watered time is updated in pivot table
                $waterNeed = strtolower($plant->water_need);
                $nextWaterDate = null;

                switch ($waterNeed) {
                    case 'high':
                        $nextWaterDate = $lastWatered->addDays(2);
                        break;
                    case 'moderate':
                        $nextWaterDate = $lastWatered->addDays(4);
                        break;
                    case 'low':
                        $nextWaterDate = $lastWatered->addWeek();
                        break;
                }

                // Send reminder if due
                if ($nextWaterDate && $nextWaterDate->isToday()) {
                    // Logic to send reminder (e.g., email)
                }

                // Check pruning needs
                $pruningNeed = strtolower($plant->pruning);
                $nextPruneDate = null;

                switch ($pruningNeed) {
                    case 'regularly':
                        $nextPruneDate = $lastWatered->addMonth();
                        break;
                    case 'weekly':
                        $nextPruneDate = $lastWatered->addWeek();
                        break;
                    case 'annually':
                        $nextPruneDate = $lastWatered->addYear();
                        break;
                }

                // Send reminder if due
                if ($nextPruneDate && $nextPruneDate->isToday()) {
                    // Logic to send reminder (e.g., email)
                }
            }
        }
    }
}
