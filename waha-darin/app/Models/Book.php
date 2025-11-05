<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Book extends Model
{
    protected $appends = ['cover_photo'];
    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'pivot_book_categories', 'book_id', 'category_id')->withTimestamps();
    }

    public function scopeFilter($query, $request)
    {
        if ($search = request('search')) {
            $query->where(function ($query) use ($search) {
                    $query->orWhere('title', 'like', '%' . $search . '%');
                    $query->orWhereHas('author', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($category = request('category_id')) {
            $query->where(function ($query) use ($category) {
                    $query->WhereHas('categories', function ($query) use ($category) {
                        $query->where('category_id', $category);
                    });
            });
        }

        if ($author = request('author_id')) {
            $query->where(function ($query) use ($author) {
                    $query->WhereHas('author', function ($query) use ($author) {
                        $query->where('id', $author);
                    });
            });
        }

        return $query;
    }

    public function getCoverPhotoAttribute(){
        return $this->image ? Storage::disk('public')->url('/') . $this->image : 'https://waha.dar-in.org/storage/default-book.png';
    }

}
