<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GardenPlantEntry extends Model
{
    protected $fillable = ['garden_id', 'plant_id', 'quantity'];

    public function garden()
    {
        return $this->belongsTo(Garden::class);
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }
}
