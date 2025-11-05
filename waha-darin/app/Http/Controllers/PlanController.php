<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlacePlanOrder;
use App\Models\Plan;
use App\Models\Subscription;
use App\Repositories\PlanRepo;
use App\User;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    private $user;
    private $subscription;

    public function index()
    {
        $plans = Plan::all();
        return response()->json(['status' => 200, 'plans' => $plans], 200);
    }

    public function show(Plan $plan)
    {
        return response()->json(['status' => 200, 'plan' => $plan], 200);
    }


    public function create(PlacePlanOrder $request, Plan $plan)
    {
//        $subscription = Subscription::where('user_id', auth()->user()->id)->first();

//        if (!$subscription || !$subscription->valid) {
        $planSubscription = new PlanRepo($request, $plan);
        $payment = $planSubscription->requestPayment();


        return response()->json($payment, 200);
//        }
//        return [
//            'code' => 200, 'status' => 'success', 'icon' => 'check',
//            'message' => __('internal.Already Subscribed!'),
//            'subMessage' => __('internal.You can borrow books now.')
//        ];
    }

    public function handleStripeConfirmation(Request $request)
    {
        $stripe = \Stripe::setApiKey(config('app.stripe_secret_key'));
        $event = $stripe->events()->find($request['id']);
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        try {
            if ($event['type'] == 'payment_intent.succeeded') {
                $intent = $event['data']['object']['metadata'];
                $plan = Plan::find($intent['plan_id']);
                $user = User::find($intent['user_id']);
                $months = $intent['months'];

                $planSubscription = new PlanRepo(null, $plan);
                $planSubscription->createUserSubscription($user->id, $plan['id'], $months);
                $output->writeln('WOOOOOOOOOOOORKS');
            }
        }catch (\Exception $e){
//            $output->writeln($e);
        }



        return response('', 200);
    }
}
