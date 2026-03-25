<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Model;

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
        return $this->avatar ? PublicStorageUrl::url($this->avatar) : '/author-placeholder.png';
    }
}
