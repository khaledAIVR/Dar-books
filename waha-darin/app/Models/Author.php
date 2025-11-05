<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Author extends Model
{
    /**
     * @var mixed
     */
    private $name;
    protected $appends = ['avatar_photo'];

    public function books()
    {
        return $this->HasMany(Book::class, 'author_id', 'id');
    }

    public function getAvatarPhotoAttribute()
    {
        return $this->avatar ? Storage::disk('public')->url('') . $this->avatar : '/author-placeholder.png';
    }
}
