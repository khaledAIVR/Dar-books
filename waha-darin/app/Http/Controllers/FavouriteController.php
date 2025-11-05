<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\FavList;

class FavouriteController extends Controller
{

    public function index()
    {
        $user = auth()->user();
        $books = [];
        $favlist = FavList::where('user_id', $user->id)->first();
        if ($favlist) $books = json_decode($favlist->books, TRUE);
        return response()->json($books, 200);
    }

    public function update(Book $book)
    {
        $user = auth()->user();
        $currentBookAuthor = $book->author()->get()->toArray();
        $currentBookAuthor = $currentBookAuthor[0]['name'];

        $favlist = FavList::where('user_id', $user->id)->first();
        if (!$favlist) {
            $favlist = new FavList();
            $favlist->user_id = $user->id;
            $books = [
                [
                    'id' => $book->id,
                    'title' => $book->title,
                    'cover_photo' => $book->cover_photo,
                    'slug' => $book->slug,
                    'author' => $currentBookAuthor,
                ]
            ];
            $favlist->books = json_encode($books);
            $favlist->save();
            return response()->json(['message' => 'Book Added to FavList', 'status' => 200], 200);
        } else {
            // Make a PHP array from the JSON string.
            $books = json_decode($favlist->books, TRUE);

            // Add request book to oder books array.
            array_push($books, [
                'id' => $book->id,
                'title' => $book->title,
                'cover_photo' => $book->cover_photo,
                'slug' => $book->slug,
                'author' => $currentBookAuthor,
            ]);


            // Only keep unique values, by using array_unique with SORT_REGULAR as flag.
            // We're using array_values here, to only retrieve the values and not the keys.
            // This way json_encode will give us a nicely formatted JSON string later on.

            $favlist->books = array_values(array_unique($books, SORT_REGULAR));

            // Make a JSON string from the array.
            $favlist->books = json_encode($favlist->books);
            $favlist->save();
        }
        return response()->json(['message' => 'Book Added to FavList', 'status' => 200], 200);
    }



    public function delete(Book $book)
    {
        $user = auth()->user();
        $favlist = FavList::where('user_id', $user->id)->first();
        $books = json_decode($favlist->books, TRUE);


        //  Remove the book
        foreach ($books as $key => $bookItem) {
            if ($bookItem['id'] == $book->id) {
                \array_splice($books, $key, 1);
                break;
            }
        }
        $favlist->books = json_encode($books);
        $favlist->save();
        return   response()->json(['message' => 'Book deleted from favlist', 'status' => 200], 200);
    }
}
