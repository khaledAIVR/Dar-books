<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{

    public function index(Request $request)
    {
        $per_page = 8 ;
        if($request->per_page) $per_page = $request->per_page;

        $categories = Category::select('id', 'name', 'image', 'slug', 'color')
            ->withCount('books')
            ->orderBy('books_count', 'DESC')
            ->paginate($per_page);
        return response()->json($categories, 200);
    }

    public function show(Category $category)
    {
        $category = Category::where('id', $category->id)
            ->select('id', 'name', 'image', 'slug', 'color')
            ->withCount('books')
            ->get();
        return response()->json($category, 200);
    }


    public function loadCategoryBooks(Category $category)
    {
        $books = $category->books()
            ->select('book_id', 'title', 'slug', 'image', 'description', 'author_id')
            ->with('author:id,name')
            ->get()->toArray();
        return response()->json($books, 200);
    }
}
