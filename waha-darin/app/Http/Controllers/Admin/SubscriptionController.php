<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $base = Subscription::query()
            ->with([
                'user:id,name,email,phone',
                'plan:id,name,price,books_quota',
            ])
            ->orderByDesc('created_at');

        $pending = (clone $base)
            ->whereRaw('LOWER(status) = ?', ['pending'])
            ->get()
            ->map(fn (Subscription $s) => $this->formatSubscription($s))
            ->values();

        $active = (clone $base)
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->get()
            ->map(fn (Subscription $s) => $this->formatSubscription($s))
            ->values();

        $deactivated = (clone $base)
            ->whereRaw('LOWER(status) IN (?, ?, ?)', ['deactivated', 'inactive', 'expired'])
            ->get()
            ->map(fn (Subscription $s) => $this->formatSubscription($s))
            ->values();

        return response()->json([
            'pending' => $pending,
            'active' => $active,
            'deactivated' => $deactivated,
        ]);
    }

    public function activate(Request $request, Subscription $subscription): JsonResponse
    {
        $subscription->status = 'active';

        // Start/end should reflect activation time (bank transfer review may take days).
        $subscription->start = now();
        $subscription->end = now()->addYear();

        $subscription->save();
        $subscription->loadMissing(['user:id,name,email,phone', 'plan:id,name,price,books_quota']);

        return response()->json([
            'message' => 'Subscription activated.',
            'subscription' => $this->formatSubscription($subscription),
        ]);
    }

    public function deactivate(Request $request, Subscription $subscription): JsonResponse
    {
        $subscription->status = 'deactivated';
        $subscription->save();
        $subscription->loadMissing(['user:id,name,email,phone', 'plan:id,name,price,books_quota']);

        return response()->json([
            'message' => 'Subscription deactivated.',
            'subscription' => $this->formatSubscription($subscription),
        ]);
    }

    protected function formatSubscription(Subscription $subscription): array
    {
        $user = $subscription->user;
        $plan = $subscription->plan;

        $status = strtolower((string) $subscription->status);
        if ($status === 'inactive') {
            $status = 'deactivated';
        }

        return [
            'id' => $subscription->id,
            'status' => $status,
            'transaction_amount' => $subscription->transaction_amount,
            'transaction_date' => optional($subscription->transaction_date)->toIso8601String(),
            'start' => optional($subscription->start)->toIso8601String(),
            'end' => optional($subscription->end)->toIso8601String(),
            'created_at' => optional($subscription->created_at)->toIso8601String(),
            'updated_at' => optional($subscription->updated_at)->toIso8601String(),
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ] : null,
            'plan' => $plan ? [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $plan->price,
                'books_quota' => $plan->books_quota,
            ] : null,
        ];
    }
}

