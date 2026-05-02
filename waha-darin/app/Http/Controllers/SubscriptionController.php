<?php

namespace App\Http\Controllers;

use App\Http\Requests\createSubscription;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    //


    public function create(createSubscription $createSubscription){
        $plan = Plan::find($createSubscription['plan_id']);
        $subscription = Subscription::where("user_id", auth()->id())->first()?? new Subscription();
        $subscription->user_id = auth()->id();
        $subscription->plan_id = $createSubscription['plan_id'];
        $subscription->transaction_amount = $createSubscription['transaction_amount'];
        $subscription->transaction_date = $createSubscription['transaction_date'];
        $subscription->quote = $plan->books_quota *12;
        $subscription->start = Carbon::now();
        $subscription->end   = Carbon::now()->addYear();
        $subscription->status = "pending";
        $subscription->save();

        return $subscription->load('plan')->appendValidForApi();
    }
}
