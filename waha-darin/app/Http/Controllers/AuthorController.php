<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;

class AuthorController extends Controller
{

    public function index(Request $request)
    {
        $per_page = 15 ;
        if($request->per_page) $per_page = $request->per_page;

        $authors = Author::select('id', 'name', 'avatar', 'slug', 'description')
            ->withCount('books')
            ->orderBy('books_count', 'DESC')
            ->paginate($per_page);
        return response()->json($authors, 200);
    }

    public function show(Author $author)
    {
        return response()->json($author, 200);
    }

}
