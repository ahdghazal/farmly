<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Garden extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'location', // optional: a human-readable location name
        'area',
        'is_inside', 
        'user_id',
        'latitude', //added new
        'longitude', //added new
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plants(): BelongsToMany
    {
        return $this->belongsToMany(Plant::class)->withPivot('spacing')->withTimestamps();
    }
}
