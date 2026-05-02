<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BorrowQuotaService
{
    /**
     * Books the user may still borrow now (min of per-period and annual remaining). No rollover.
     */
    public function remainingBorrowSlotsForSubscription(Subscription $subscription, int $userId): int
    {
        $subscription->loadMissing('plan');
        $plan = $subscription->plan;
        if (
            ! $plan
            || (int) $plan->books_quota <= 0
            || $plan->borrow_period_months === null
            || $plan->max_books_per_period === null
        ) {
            return 0;
        }

        if (! $subscription->start || ! $subscription->end) {
            return 0;
        }

        $tz = (string) config('app.timezone');
        $now = Carbon::now($tz);

        if ($now->greaterThan($subscription->end)) {
            return 0;
        }

        [$periodFrom, $periodEndExclusive] = $this->currentPeriodBounds($subscription, $plan, $now);

        $usedPeriod = $this->countBorrowedBooksHalfOpen($userId, $periodFrom, $periodEndExclusive);
        $remainingPeriod = max(0, (int) $plan->max_books_per_period - $usedPeriod);

        $yearFrom = $subscription->start->copy()->timezone($tz)->startOfDay();
        $yearToInclusive = $now->lessThan($subscription->end)
            ? $now->copy()
            : $subscription->end->copy()->timezone($tz)->endOfDay();

        $usedYear = $this->countBorrowedBooksInclusiveEnd($userId, $yearFrom, $yearToInclusive);
        $remainingYear = max(0, (int) $plan->books_quota - $usedYear);

        return min($remainingPeriod, $remainingYear);
    }

    /**
     * @return array{0: Carbon, 1: Carbon} [from, toExclusive)
     */
    public function currentPeriodBounds(Subscription $subscription, Plan $plan, Carbon $now): array
    {
        $tz = (string) config('app.timezone');
        $now = $now->copy()->timezone($tz);

        $periodMonths = (int) $plan->borrow_period_months;
        if ($periodMonths === 1 || $periodMonths === 2) {
            return $this->anchorPeriodBounds($subscription, $periodMonths, $now, $tz);
        }

        // Fallback: calendar month if misconfigured
        $from = $now->copy()->startOfMonth();
        $toExclusive = $now->copy()->startOfMonth()->addMonth();

        return [$from, $toExclusive];
    }

    /**
     * Repeating windows of length $periodMonths from subscription.start (half-open [from, toExclusive)).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function anchorPeriodBounds(Subscription $subscription, int $periodMonths, Carbon $now, string $tz): array
    {
        $anchor = $subscription->start->copy()->timezone($tz)->startOfDay();
        $cursor = $now->copy()->startOfDay();

        if ($cursor->lessThan($anchor)) {
            return [$anchor, $anchor->copy()->addMonths($periodMonths)];
        }

        $months = (int) $anchor->diffInMonths($cursor);
        $n = intdiv($months, $periodMonths);
        $periodFrom = $anchor->copy()->addMonths($n * $periodMonths);
        $periodEndExclusive = $anchor->copy()->addMonths(($n + 1) * $periodMonths);

        return [$periodFrom, $periodEndExclusive];
    }

    /**
     * Count book lines on non-cancelled borrow orders with created_at in [from, toExclusive).
     */
    public function countBorrowedBooksHalfOpen(int $userId, Carbon $from, Carbon $toExclusive): int
    {
        return (int) $this->borrowBooksBaseQuery($userId)
            ->where('borrow_orders.created_at', '>=', $from)
            ->where('borrow_orders.created_at', '<', $toExclusive)
            ->count();
    }

    /**
     * Count book lines on non-cancelled borrow orders with created_at in [from, toInclusive].
     */
    public function countBorrowedBooksInclusiveEnd(int $userId, Carbon $from, Carbon $toInclusive): int
    {
        return (int) $this->borrowBooksBaseQuery($userId)
            ->where('borrow_orders.created_at', '>=', $from)
            ->where('borrow_orders.created_at', '<=', $toInclusive)
            ->count();
    }

    private function borrowBooksBaseQuery(int $userId)
    {
        return DB::table('pivot_order_books')
            ->join('borrow_orders', 'pivot_order_books.borrow_order_id', '=', 'borrow_orders.id')
            ->where('borrow_orders.user_id', $userId)
            ->whereRaw("LOWER(COALESCE(borrow_orders.status, '')) != ?", ['cancelled']);
    }
}
