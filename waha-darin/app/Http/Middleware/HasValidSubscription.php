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
        $subscription = Subscription::where('user_id', auth()->user()->id)->first();

        if (!$subscription || $subscription->valid < count($request->books)) return response()->json(['subscription' => false, 'status' => 403], 403);
        return $next($request);
    }
}
