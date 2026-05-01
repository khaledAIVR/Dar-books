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
        $committed = $this->committedCatalogAvatarUrl();
        if ($committed !== null) {
            return $committed;
        }

        return $this->avatar ? PublicStorageUrl::url($this->avatar) : '/author-placeholder.png';
    }

    /**
     * Avatar shipped in public/media/authors/{id}.ext (see media:publish-from-storage).
     */
    private function committedCatalogAvatarUrl(): ?string
    {
        foreach (['jpg', 'jpeg', 'gif', 'png', 'webp'] as $ext) {
            $rel = 'media/authors/'.$this->id.'.'.$ext;
            if (is_file(public_path($rel))) {
                return asset($rel);
            }
        }

        return null;
    }
}
