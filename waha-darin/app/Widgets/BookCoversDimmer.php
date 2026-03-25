<?php

namespace App\Widgets;

use App\Models\Book;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Widgets\BaseDimmer;

class BookCoversDimmer extends BaseDimmer
{
    protected $config = [];

    public function run()
    {
        $count = Book::whereNotNull('image')
            ->where('image', '!=', '')
            ->count();

        $countFormatted = number_format($count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon' => 'voyager-photo',
            'title' => "{$countFormatted} Covers",
            'text' => "Books that have a cover image.",
            'button' => [
                'text' => 'View Books',
                'link' => route('voyager.books.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/08.jpg'),
        ]));
    }

    public function shouldBeDisplayed()
    {
        return Auth::user()->can('browse', app(Book::class));
    }
}

