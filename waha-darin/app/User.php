<?php

namespace App;

use App\Models\BorrowOrder;
use App\Models\Category;
use App\Models\Author;
use App\Models\Cart;
use App\Models\FavList;
use App\Models\Subscription;
use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends \TCG\Voyager\Models\User implements JWTSubject, MustVerifyEmail
{
    use Notifiable;

    public function isSuperAdmin(): bool
    {
        $superAdminEmail = (string) config('superadmin.email', env('SUPER_ADMIN_EMAIL', ''));
        if ($superAdminEmail !== '') {
            return strtolower((string) $this->email) === strtolower($superAdminEmail);
        }

        $superAdminId = (int) config('superadmin.id', (int) env('SUPER_ADMIN_ID', 1));
        return (int) $this->id === $superAdminId;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'photo_url',
    ];

    /**
     * Get the profile photo URL attribute.
     *
     * @return string
     */
    public function getPhotoUrlAttribute()
    {
        return 'https://www.gravatar.com/avatar/'.md5(strtolower($this->email)).'.jpg?s=200&d=mm';
    }


    /**
     * Get the oauth providers.
     *
     * @return HasMany
     */
    public function oauthProviders()
    {
        return $this->hasMany(OAuthProvider::class);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }

    /**
     * @return int
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'user_categories', 'user_id', 'category_id')->withTimestamps();
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'user_authors', 'user_id', 'author_id')->withTimestamps();
    }

    public function cart()
    {
        return $this->hasOne(Cart::class, 'user_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(BorrowOrder::class, 'user_id', 'id');
    }

    public function favList()
    {
        return $this->hasOne(FavList::class, 'user_id', 'id');
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'user_id', 'id');
    }
}
