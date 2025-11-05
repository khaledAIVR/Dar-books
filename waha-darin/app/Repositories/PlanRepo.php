<?php


namespace App\Repositories;


use App\Models\Subscription;
use http\Exception;

class PlanRepo
{
    private $stripe;
    private $paymentInfo;
    private $plan;
    private $user;

    public function __construct($paymentInfo, $plan)
    {
        $this->stripe = \Stripe::setApiKey(config('app.stripe_secret_key'));
        $this->paymentInfo = $paymentInfo;
        $this->plan = $plan;
        $this->user = auth()->user();
    }

    public function requestPayment()
    {
        return $this->getPay();
    }

    private function getPay()
    {
        try {

            $token = $this->getCardToken();
            $charge = $this->chargePayment($token);

            if ($charge['status'] == 'succeeded') {
                return [
                    'code' => 200,
                    'status' => 'success',
                    'icon' => 'checkPayment',
                    'message' => __('internal.Payment Done!'),
                    'subMessage' => __('internal.You can borrow books now.')
                ];
            } elseif ($charge['status'] == 'requires_source_action') {
                // Requires 3DS
                return [
                    'status' => 'requires_source_action',
                    'client_secret' => $charge['client_secret'],
                    'pk' => config('app.stripe_publishable_key'),
                ];
            }

        } catch (Exception $e) {
            return [
                'code' => $e->getCode(),
                'status' => 'danger',
                'icon' => 'cross',
                'message' => $e->getMessage(),
                'subMessage' => __('internal.Please reload the page and try again.')
            ];
        } catch (\Cartalyst\Stripe\Exception\CardErrorException $e) {
            return [
                'code' => $e->getCode(),
                'status' => 'danger',
                'icon' => 'cross',
                'message' => $e->getMessage(),
                'subMessage' => __('internal.Please reload the page and try again.')

            ];
        } catch (\Cartalyst\Stripe\Exception\MissingParameterException $e) {
            return [
                'code' => $e->getCode(),
                'status' => 'danger',
                'icon' => 'cross',
                'message' => $e->getMessage(),
                'subMessage' => __('internal.Please reload the page and try again.')
            ];

        }
    }

    private function getCardToken()
    {
        $info = $this->paymentInfo;
        $token = $this->stripe->paymentMethods()->create([
            'type' => 'card',
            'card' => [
                'number' => $info['cardNumber'],
                'exp_month' => $info['cardMonth'],
                'exp_year' => $info['cardYear'],
                'cvc' => $info['cardCVC'],
            ],
        ]);

        if (!isset($token['id'])) {
            return [
                'code' => 402, 'status' => 'danger', 'icon' => 'cross',
                'message' => __('internal.Your card number is incorrect!'),
                'subMessage' => __('internal.Please reload the page and try again.')
            ];
        } else {
            return $token;
        }
    }

    private function chargePayment($token)
    {
        $duration = $this->paymentInfo['subscriptionPeriod'];
        $price = $this->plan['price'];

        return $this->stripe->paymentIntents()->create([
            'currency' => 'EUR',
            'amount' => $price * $duration,
            'payment_method' => $token['id'],
            'confirm'=> true,
            'metadata'=>[
                'user_id' => $this->user->id,
                'plan_id' => $this->plan->id,
                'months' => $this->paymentInfo['subscriptionPeriod']
            ],
            'description' => 'Subscribe to Waha Dar-in bookstore.',
        ]);
    }

    public function createUserSubscription($user_id, $plan_id, $months)
    {
        $subscription = Subscription::where('user_id', $user_id)->first();
        if (!$subscription) {
            $subscription = new Subscription();
        }
        $subscription->user_id = $user_id;
        $subscription->plan_id = $plan_id;

        $subscription->quote = $this->initSubscriptionQuote($months);

        $subscription->start = now();
        $subscription->end = now()->addMonths($months);
        $subscription->save();
    }

    private function initSubscriptionQuote($months)
    {
        $months = (int)$months;
        $quote = [];
        for ($i = 0; $i <= $months; $i++) {
            array_push($quote,
                $this->plan->books_quota
            );
        }
        return json_encode($quote);
    }
}
