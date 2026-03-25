<?php

namespace App\Models;
use App\Support\PublicStorageUrl;
use TCG\Voyager\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use Translatable;
    protected $translatable = ['title', 'slug', 'description', 'short_description'];
    protected $appends = ['cover_image'];

    public function categories()
    {
        return $this->belongsToMany(EventCategory::class, 'pivot_event_event_ategory', 'event_id', 'event_category_id')->withTimestamps();
    }

    public function scopeFilter($query, $request)
    {
        return $query;
    }


    public function getCoverImageAttribute()
    {
        return $this->image
            ? PublicStorageUrl::url($this->image)
            : PublicStorageUrl::url('default-book.png');
    }
}
