<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'soil_type',
        'category',
        'fertilization',
        'pruning',
        'support',
        'spacing',
        'season',
        'water_need',
        'light_needed',
        'temperature',
        'description',
        'picture',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'support' => 'boolean', // Convert 'support' field to boolean
    ];

    // Define the relationship with FavoriteList
    public function favoriteLists(): BelongsToMany
    {
        return $this->belongsToMany(FavoriteList::class);
    }
}
