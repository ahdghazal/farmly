<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id', 'title', 'message',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
