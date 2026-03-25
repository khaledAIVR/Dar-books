<?php

namespace App\Models;

use App\Services\BorrowQuotaService;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $appends = ['valid'];
    protected $dates = ['created_at', 'updated_at', 'start', 'end', 'transaction_date'];
    protected $casts = ['quote' => 'array'];

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }

    public function user()
    {

        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Remaining books the authenticated user may borrow now: min(per-period cap, annual cap), no rollover.
     * Plans 4–5 (no period fields / books_quota 0) resolve to 0 here — borrow API stays blocked until product adds agreed limits.
     */
    public function getValidAttribute()
    {
        if (! auth()->check()) {
            return 0;
        }

        return app(BorrowQuotaService::class)->remainingBorrowSlotsForSubscription($this, (int) auth()->id());
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
