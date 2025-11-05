<?php

namespace App\Models;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $appends = ['valid'];
    protected $dates = ['created_at', 'updated_at', 'start', 'end'];
    protected $casts = ['quote' => 'array'];

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }

    public function user()
    {

        return $this->belongsTo(User::class, 'user_id', 'id');
    }


    public function getValidAttribute()
    {
        $month = Carbon::now()->month;
        $count = BorrowOrder::where('user_id', auth()->id())->whereMonth("created_at", $month)->count();
        $monthQuota = $this->plan->books_quota;
        return $monthQuota >= $count? $monthQuota - $count : 0;
    }


    public function getMonthAttribute()
    {
        return $this->getCurrentMonth();
    }

    public function getAvailableAttribute()
    {
        $currentMonth = $this->getCurrentMonth();
        return $this->currentMonthQuote($currentMonth);
    }

    private function getCurrentMonth()
    {
        $start = $this->start;
        $now = now();
        return $start->diffInMonths($now);
    }

    private function currentMonthQuote($currentMonth)
    {
//        $quote = json_decode($this->quote);
//        return $quote[$currentMonth];
    }
}
