<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'books_quota',
        'borrow_period_months',
        'max_books_per_period',
        'note',
    ];

    public function subscription(){
        return $this->hasOne(Subscription::class);
    }
}
