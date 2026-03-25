<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use App\User;
use Closure;

class HasValidSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        $subscription = Subscription::where('user_id', $user->id)->first();
        $booksCount = is_array($request->books) ? count($request->books) : (is_countable($request->books) ? count($request->books) : 0);

        // Subscription must exist, be activated, and have enough quota for this order.
        // (Users start as "pending" until bank transfer is reviewed in admin.)
        if (
            !$subscription ||
            strtolower((string) $subscription->status) !== 'active' ||
            !$subscription->end ||
            now()->greaterThan($subscription->end) ||
            $subscription->valid < $booksCount
        ) {
            return response()->json(['subscription' => false, 'status' => 403], 403);
        }
        return $next($request);
    }
}
