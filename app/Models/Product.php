<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'price', 'description', 'picture', 'email', 'is_sold','phone_number'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


