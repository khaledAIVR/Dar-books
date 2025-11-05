<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::select('id', 'title', 'slug', 'description', 'image', 'short_description')
            ->with('categories')
            ->inRandomOrder()
            ->Filter($request)
            ->paginate(20);
        return response()->json($events, 200);
    }


    public function show(Event $book)
    {
        $book = $book->load( 'categories');
        return response()->json($book, 200);
    }

}
