<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    protected $fillable = [
        'user_id',
        'garden_id',
        'garden_plant_entry_id',
        'task_type',
        'task_done',
        'created_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function garden()
    {
        return $this->belongsTo(Garden::class);
    }

    public function plantEntry()
    {
        return $this->belongsTo(GardenPlantEntry::class);
    }
}
