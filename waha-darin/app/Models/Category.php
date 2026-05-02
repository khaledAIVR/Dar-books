<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $appends = ['image_url'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function books()
    {
        return $this->belongsToMany(Book::class, 'pivot_book_categories', 'category_id', 'book_id')->withTimestamps();
    }

    public function getImageUrlAttribute($value)
    {
        return $this->image ? PublicStorageUrl::url($this->image) : '/holder.jpg';
    }
}
