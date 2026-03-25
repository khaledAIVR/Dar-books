<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Cart;
use App\Models\Subscription;

class CartController extends Controller
{
    /**
     * Remaining borrow slots for this user (matches Subscription::valid / BorrowQuotaService).
     */
    private function borrowSlotsRemainingForUser($user): int
    {
        $subscription = Subscription::where('user_id', $user->id)->first();
        if ($subscription !== null) {
            // Same per-period + annual caps as everyone (BorrowQuotaService via Subscription::valid).
            // Do not inflate with superadmin.borrow_books_quota — that made the UI show 24 slots
            // for a 2-books/month plan when valid was 2.
            return (int) $subscription->valid;
        }

        if ($user->isSuperAdmin()) {
            return max(0, (int) config('superadmin.borrow_books_quota', 24));
        }

        return 0;
    }

    public function index()
    {
        $user = auth()->user();
        $books = [];
        $cart = Cart::where('user_id', $user->id)->where('checkout', 0)->first();
        if ($cart) {
            $books = json_decode($cart->books, true) ?: [];
        }

        $available = $this->borrowSlotsRemainingForUser($user);

        return response()->json(['books' => $books, 'available' => $available], 200);
    }

    public function update(Book $book)
    {
        $user = auth()->user();
        $available = $this->borrowSlotsRemainingForUser($user);

        $currentBookAuthor = $book->author()->get()->toArray();
        $currentBookAuthor = $currentBookAuthor[0]['name'];

        $cart = Cart::where('user_id', $user->id)->where('checkout', 0)->first();
        if (! $cart) {
            if ($available < 1) {
                return response()->json([
                    'message' => __('internal.Cart borrow limit reached'),
                    'status' => 409,
                    'available' => $available,
                ], 409);
            }
            $cart = new Cart();
            $cart->user_id = $user->id;
            $books = [
                [
                    'id' => $book->id,
                    'title' => $book->title,
                    'cover_photo' => $book->cover_photo,
                    'slug' => $book->slug,
                    'author' => $currentBookAuthor,
                ],
            ];
            $cart->books = json_encode($books);
            $cart->save();

            return response()->json(['message' => 'Book Added to cart', 'status' => 200, 'available' => $available], 200);
        }

        $books = json_decode($cart->books, true) ?: [];
        foreach ($books as $b) {
            if ((int) ($b['id'] ?? 0) === (int) $book->id) {
                return response()->json(['message' => 'Book Added to cart', 'status' => 200, 'available' => $available], 200);
            }
        }

        if (count($books) >= $available) {
            return response()->json([
                'message' => __('internal.Cart borrow limit reached'),
                'status' => 409,
                'available' => $available,
            ], 409);
        }

        $books[] = [
            'id' => $book->id,
            'title' => $book->title,
            'cover_photo' => $book->cover_photo,
            'slug' => $book->slug,
            'author' => $currentBookAuthor,
        ];

        $cart->books = json_encode(array_values(array_unique($books, SORT_REGULAR)));
        $cart->save();

        return response()->json(['message' => 'Book Added to cart', 'status' => 200, 'available' => $available], 200);
    }

    public function delete(Book $book)
    {
        $user = auth()->user();
        $cart = Cart::where('user_id', $user->id)->where('checkout', 0)->first();
        $books = json_decode($cart->books, true);

        foreach ($books as $key => $bookItem) {
            if ($bookItem['id'] == $book->id) {
                \array_splice($books, $key, 1);
                break;
            }
        }
        $cart->books = json_encode($books);
        $cart->save();

        return response()->json(['message' => 'Book deleted from cart', 'status' => 200], 200);
    }
}
