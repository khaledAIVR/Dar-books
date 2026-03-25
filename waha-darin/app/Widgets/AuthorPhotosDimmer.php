<?php

namespace App\Widgets;

use App\Models\Author;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Widgets\BaseDimmer;

class AuthorPhotosDimmer extends BaseDimmer
{
    protected $config = [];

    public function run()
    {
        $count = Author::whereNotNull('avatar')
            ->where('avatar', '!=', '')
            ->count();

        $countFormatted = number_format($count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon' => 'voyager-camera',
            'title' => "{$countFormatted} Author Photos",
            'text' => "Authors that have a profile photo.",
            'button' => [
                'text' => 'View Authors',
                'link' => route('voyager.authors.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/09.jpg'),
        ]));
    }

    public function shouldBeDisplayed()
    {
        return Auth::user()->can('browse', app(Author::class));
    }
}

