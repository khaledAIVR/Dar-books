<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Model;

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

    public function getCoverPhotoAttribute()
    {
        // Prefer Git-baked files under public/media (Docker on Render often has no storage/app/public blobs;
        // /storage/... then falls through to the SPA and returns HTML — broken <img> tags.)
        $committed = $this->committedCatalogCoverUrl();
        if ($committed !== null) {
            return $committed;
        }

        return $this->image
            ? PublicStorageUrl::url($this->image)
            : PublicStorageUrl::url('default-book.png');
    }

    /**
     * Cover shipped in public/media/covers/{id}.ext (see media:publish-from-storage).
     */
    private function committedCatalogCoverUrl(): ?string
    {
        foreach (['jpg', 'jpeg', 'gif', 'png', 'webp'] as $ext) {
            $rel = 'media/covers/'.$this->id.'.'.$ext;
            if (is_file(public_path($rel))) {
                return asset($rel);
            }
        }

        return null;
    }

}
