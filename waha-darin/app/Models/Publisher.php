<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publisher extends Model
{
    public function books()
    {
        return $this->HasMany(Book::class, 'publisher_id', 'id');
    }
}
