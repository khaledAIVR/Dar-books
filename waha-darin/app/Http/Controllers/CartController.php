<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Cart;
use App\Models\Subscription;

class CartController extends Controller
{

    public function index()
    {
        $user = auth()->user();
        $books = [];
        $available = 0;
        $cart = Cart::where('user_id', $user->id)->where('checkout', 0)->first();
        if ($cart) $books = json_decode($cart->books, TRUE);

        $subscription = Subscription::where('user_id', $user->id)->first();
        if($subscription) $available = $subscription->valid;

        return response()->json(['books'=>$books, 'available'=> $available], 200);
    }

    public function update(Book $book)
    {
        $user = auth()->user();
        $currentBookAuthor = $book->author()->get()->toArray();
        $currentBookAuthor = $currentBookAuthor[0]['name'];

        $cart = Cart::where('user_id', $user->id)->where('checkout', 0)->first();
        if (!$cart) {
            $cart = new Cart();
            $cart->user_id = $user->id;
            $books = [
                [
                    'id' => $book->id,
                    'title' => $book->title,
                    'cover_photo' => $book->cover_photo,
                    'slug' => $book->slug,
                    'author' => $currentBookAuthor,
                ]
            ];
            $cart->books = json_encode($books);
            $cart->save();
            return response()->json(['message' => 'Book Added to cart', 'status' => 200], 200);
        } else {
            // Make a PHP array from the JSON string.
            $books = json_decode($cart->books, TRUE);

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

            $cart->books = array_values(array_unique($books, SORT_REGULAR));

            // Make a JSON string from the array.
            $cart->books = json_encode($cart->books);
            $cart->save();
        }
        return response()->json(['message' => 'Book Added to cart', 'status' => 200], 200);
    }




    public function delete(Book $book)
    {
        $user = auth()->user();
        $cart = Cart::where('user_id', $user->id)->where('checkout', 0)->first();
        $books = json_decode($cart->books, TRUE);


        //  Remove the book
        foreach ($books as $key => $bookItem) {
            if ($bookItem['id'] == $book->id) {
                \array_splice($books, $key, 1);
                break;
            }
        }
        $cart->books = json_encode($books);
        $cart->save();
        return   response()->json(['message' => 'Book deleted from cart', 'status' => 200], 200);
    }


}
