<?php

namespace App\Widgets;

use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Widgets\BaseDimmer;

class CategoriesDimmer extends BaseDimmer
{
    protected $config = [];

    public function run()
    {
        $count = Category::count();
        $countFormatted = number_format($count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon' => 'voyager-categories',
            'title' => "{$countFormatted} Categories",
            'text' => "Total book categories.",
            'button' => [
                'text' => 'View Categories',
                'link' => route('voyager.categories.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/03.jpg'),
        ]));
    }

    public function shouldBeDisplayed()
    {
        return Auth::user()->can('browse', app(Category::class));
    }
}

