<?php

namespace App\Widgets;

use App\Models\Publisher;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Widgets\BaseDimmer;

class PublishersDimmer extends BaseDimmer
{
    protected $config = [];

    public function run()
    {
        $count = Publisher::count();
        $countFormatted = number_format($count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon' => 'voyager-company',
            'title' => "{$countFormatted} Publishers",
            'text' => "Total publishers.",
            'button' => [
                'text' => 'View Publishers',
                'link' => route('voyager.publishers.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/05.jpg'),
        ]));
    }

    public function shouldBeDisplayed()
    {
        return Auth::user()->can('browse', app(Publisher::class));
    }
}

