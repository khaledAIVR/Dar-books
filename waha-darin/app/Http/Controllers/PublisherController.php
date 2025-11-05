<?php

namespace App\Http\Controllers;

use App\Models\Publisher;
use Illuminate\Http\Request;

class PublisherController extends Controller
{
    public function index()
    {
        $publisher = Publisher::select('id', 'name', 'avatar', 'slug', 'description')->inRandomOrder()->paginate(15);
        return response()->json($publisher, 200);
    }

    public function show(Publisher $publisher)
    {
        return response()->json($publisher, 200);
    }
}
