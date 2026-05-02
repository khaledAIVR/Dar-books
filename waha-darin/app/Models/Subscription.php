<?php

namespace App\Models;

use App\Services\BorrowQuotaService;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    /**
     * Do not append `valid` globally: Voyager serialize() runs on every browse row and it is expensive.
     * Use {@see Subscription::appendValidForApi()} before returning subscriptions as JSON to clients.
     */
    protected $appends = [];

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

        try {
            return app(BorrowQuotaService::class)->remainingBorrowSlotsForSubscription($this, (int) auth()->id());
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }
    }

    public function appendValidForApi(): self
    {
        return $this->append('valid');
    }


    public function getMonthAttribute()
    {
        if (! $this->start) {
            return 0;
        }

        return $this->getCurrentMonth();
    }

    public function getAvailableAttribute()
    {
        if (! $this->start) {
            return null;
        }

        $currentMonth = $this->getCurrentMonth();

        return $this->currentMonthQuote($currentMonth);
    }

    private function getCurrentMonth()
    {
        return $this->start->diffInMonths(now());
    }

    private function currentMonthQuote($currentMonth)
    {
        //        $quote = json_decode($this->quote);
        //        return $quote[$currentMonth];
        return null;
    }
}
