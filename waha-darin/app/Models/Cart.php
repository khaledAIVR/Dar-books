<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $casts = ['books' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
