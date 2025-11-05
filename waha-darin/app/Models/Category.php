<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    protected $appends = ['image_url'];
    public function books()
    {
        return $this->belongsToMany(Book::class, 'pivot_book_categories', 'category_id', 'book_id')->withTimestamps();
    }

    public function getImageUrlAttribute($value)
    {
        return $this->image ? Storage::disk('public')->url('') . $this->image : '/holder.jpg';
    }
}
