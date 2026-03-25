<?php

namespace App\Widgets;

use App\Models\BorrowOrder;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Widgets\BaseDimmer;

class BorrowOrdersDimmer extends BaseDimmer
{
    protected $config = [];

    public function run()
    {
        $count = BorrowOrder::count();
        $countFormatted = number_format($count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon' => 'voyager-basket',
            'title' => "{$countFormatted} Orders",
            'text' => "Total borrow orders.",
            'button' => [
                'text' => 'View Orders',
                'link' => route('voyager.borrow-orders.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/06.jpg'),
        ]));
    }

    public function shouldBeDisplayed()
    {
        return Auth::user()->can('browse', app(BorrowOrder::class));
    }
}

