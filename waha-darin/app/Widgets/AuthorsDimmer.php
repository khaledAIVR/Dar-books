<?php

namespace App\Widgets;

use App\Models\Author;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Widgets\BaseDimmer;

class AuthorsDimmer extends BaseDimmer
{
    protected $config = [];

    public function run()
    {
        $count = Author::count();
        $countFormatted = number_format($count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon' => 'voyager-person',
            'title' => "{$countFormatted} Authors",
            'text' => "Total authors in your database.",
            'button' => [
                'text' => 'View Authors',
                'link' => route('voyager.authors.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/02.jpg'),
        ]));
    }

    public function shouldBeDisplayed()
    {
        return Auth::user()->can('browse', app(Author::class));
    }
}

