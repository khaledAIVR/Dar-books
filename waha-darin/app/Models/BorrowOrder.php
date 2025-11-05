<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property mixed user_id
 * @property mixed use_zipCode
 * @property mixed use_region
 * @property mixed use_country
 * @property mixed user_address_line_two
 * @property mixed user_address_line_one
 * @property mixed phone
 * @property mixed user_name
 * @property mixed|string status
 * @property Carbon|mixed end_date
 * @property Carbon|mixed start_date
 * @property false|mixed|string books
 * @property mixed user_phone
 */
class BorrowOrder extends Model
{
    protected $casts = ['books' => 'array'];
    protected $appends = ['completed'];

    /**
     * @return BelongsTo
     * @var mixed
     */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function books()
    {
        return $this->belongsToMany(Book::class, 'pivot_order_books', 'borrow_order_id', 'book_id')->withTimestamps();
    }

    public function getCompletedAttribute()
    {
        if(now() > $this->end_date &&  $this->status == 'Completed'){
            return true;
        }
        return false;
    }
}
