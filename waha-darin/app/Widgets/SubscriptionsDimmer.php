<?php

namespace App\Widgets;

use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Widgets\BaseDimmer;

class SubscriptionsDimmer extends BaseDimmer
{
    protected $config = [];

    public function run()
    {
        $count = Subscription::count();
        $countFormatted = number_format($count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon' => 'voyager-receipt',
            'title' => "{$countFormatted} Subscriptions",
            'text' => "Total subscriptions.",
            'button' => [
                'text' => 'View Subscriptions',
                'link' => route('voyager.subscriptions.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/07.jpg'),
        ]));
    }

    public function shouldBeDisplayed()
    {
        return Auth::user()->can('browse', app(Subscription::class));
    }
}

