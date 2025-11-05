<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\Support\Renderable;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['has.author.preference', 'has.category.preference']);
    }

    /**
     * Show the application home.
     *
     * @return Renderable
     */
    public function index()
    {
        if ($user = auth()->user()) {
            $categories = $user->categories()
                ->select('category_id', 'name', 'slug')
                ->limit(5)
                ->withCount('books')
                ->get()->toArray();
        } else {
            $categories = Category::select('id', 'name','slug')
                ->withCount('books')
                ->inRandomOrder()
                ->limit(5)
                ->get()->toArray();

        }
        return view('home', compact('categories'));
    }

    /**
     * Show my books mage.
     *
     * @return Renderable
     */
    public function booksForYou()
    {
        if ($user = auth()->user()) {
            $categories = $user->categories()
                ->select('category_id', 'name', 'slug')
                ->limit(5)
                ->withCount('books')
                ->get()->toArray();
        } else {
            $categories = Category::select('id', 'name', 'slug')
                ->withCount('books')
                ->inRandomOrder()
                ->limit(5)
                ->get()->toArray();

        }
        return view('booksForYou', compact('categories'));
    }
}
