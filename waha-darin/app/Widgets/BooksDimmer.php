<?php

namespace App\Widgets;

use App\Models\Book;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Widgets\BaseDimmer;

class BooksDimmer extends BaseDimmer
{
    protected $config = [];

    public function run()
    {
        $count = Book::count();
        $countFormatted = number_format($count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon' => 'voyager-book',
            'title' => "{$countFormatted} Books",
            'text' => "Total books in your catalog.",
            'button' => [
                'text' => 'View Books',
                'link' => route('voyager.books.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/04.jpg'),
        ]));
    }

    public function shouldBeDisplayed()
    {
        return Auth::user()->can('browse', app(Book::class));
    }
}

