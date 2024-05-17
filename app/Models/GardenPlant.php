<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GardenPlant extends Pivot
{
    protected $table = 'garden_plant';
}
