<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\Borrow\OrderDelivered;
use App\Mail\Borrow\OrderShipped;
use App\Models\BorrowOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class WeeklyOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $from = Carbon::now()->subDays(7);

        // Received: only orders from the last 7 days (weekly view)
        $receivedOrders = BorrowOrder::where('created_at', '>=', $from)
            ->where('status', 'Received')
            ->with('user:id,name,email,phone')
            ->orderByDesc('created_at')
            ->get();

        // Shipped: all shipped orders regardless of date
        $shippedOrders = BorrowOrder::where('status', 'Shipped')
            ->with('user:id,name,email,phone')
            ->orderByDesc('created_at')
            ->get();

        // Delivered: all delivered-phase orders (excluding ReturnedBack)
        $deliveredOrders = BorrowOrder::whereIn('status', ['Delivered', 'WaitingReturnShipment'])
            ->with('user:id,name,email,phone')
            ->orderByDesc('created_at')
            ->get();

        // Returned: all returned orders regardless of date
        $returnedOrders = BorrowOrder::where('status', 'ReturnedBack')
            ->with('user:id,name,email,phone')
            ->orderByDesc('created_at')
            ->get();

        $format = function ($orders) {
            return $orders->map(function (BorrowOrder $order) {
                return $this->formatOrder($order);
            })->values();
        };

        return response()->json([
            'received' => $format($receivedOrders),
            'shipped' => $format($shippedOrders),
            'delivered' => $format($deliveredOrders),
            'returned' => $format($returnedOrders),
        ]);
    }

    public function confirm(Request $request, BorrowOrder $order): JsonResponse
    {
        $data = $request->validate([
            'shipment_number' => ['required', 'string', 'max:190'],
        ]);

        if (!$this->isActionable($order)) {
            return response()->json(['message' => 'Order already processed.'], 409);
        }

        if (!$this->isWithinWindow($order)) {
            return response()->json(['message' => 'Order is outside the processing window.'], 422);
        }

        $order->shipment_number = $data['shipment_number'];
        $order->shipment_status = 'confirmed';
        $order->shipment_confirmed_at = Carbon::now();
        $order->status = 'Shipped';
        $order->save();

        // Reload user relationship
        $order->loadMissing(['user:id,name,email,phone', 'books']);
        Mail::to($order->user)->send(new OrderShipped($order));

        return response()->json([
            'message' => 'Order shipment confirmed.',
            'order' => $this->formatOrder($order),
        ]);
    }

    public function deliver(Request $request, BorrowOrder $order): JsonResponse
    {
        if ($order->status !== 'Shipped') {
            return response()->json(['message' => 'Only shipped orders can be marked as delivered.'], 409);
        }

        $order->status = 'Delivered';
        $order->delivered_at = Carbon::now();
        $order->save();

        $order->loadMissing(['user:id,name,email,phone', 'books']);
        Mail::to($order->user)->send(new OrderDelivered($order));

        return response()->json([
            'message' => 'Order marked as delivered.',
            'order' => $this->formatOrder($order),
        ]);
    }

    public function markReturned(Request $request, BorrowOrder $order): JsonResponse
    {
        if (!in_array($order->status, ['Delivered', 'WaitingReturnShipment'], true)) {
            return response()->json(['message' => 'Only delivered orders can be marked as returned.'], 409);
        }

        if (!$order->return_shipment_number) {
            return response()->json(['message' => 'Return shipment number is missing.'], 422);
        }

        $order->status = 'ReturnedBack';
        $order->return_confirmed_at = Carbon::now();
        $order->save();

        $order->loadMissing('user:id,name,email,phone');

        return response()->json([
            'message' => 'Order marked as returned.',
            'order' => $this->formatOrder($order),
        ]);
    }

    public function cancel(Request $request, BorrowOrder $order): JsonResponse
    {
        $data = $request->validate([
            'cancellation_note' => ['nullable', 'string', 'max:500'],
        ]);

        if (!$this->isActionable($order)) {
            return response()->json(['message' => 'Order already processed.'], 409);
        }

        if (!$this->isWithinWindow($order)) {
            return response()->json(['message' => 'Order is outside the processing window.'], 422);
        }

        $order->shipment_status = 'cancelled';
        $order->status = 'Cancelled';
        $order->shipment_number = null;
        $order->shipment_confirmed_at = null;
        $order->cancellation_note = $data['cancellation_note'] ?? null;
        $order->save();

        // Reload user relationship
        $order->loadMissing('user:id,name,email,phone');

        return response()->json([
            'message' => 'Order cancelled.',
            'order' => $this->formatOrder($order),
        ]);
    }

    protected function isWithinWindow(BorrowOrder $order): bool
    {
        return $order->created_at && $order->created_at->greaterThanOrEqualTo(Carbon::now()->subDays(7));
    }

    protected function isActionable(BorrowOrder $order): bool
    {
        return $order->shipment_status === 'pending';
    }

    protected function formatOrder(BorrowOrder $order): array
    {
        // Ensure user is loaded
        if (!$order->relationLoaded('user')) {
            $order->load('user:id,name,email,phone');
        }

        $user = $order->user;
        
        // Get books using the relationship query builder - this works reliably
        // Use get() to fetch the books directly from the relationship
        $booksCollection = $order->books()
            ->select('books.id', 'books.title', 'books.image', 'books.author_id')
            ->with('author:id,name')
            ->get();
        
        // Format books
        $books = [];
        if ($booksCollection && $booksCollection->count() > 0) {
            $books = $booksCollection->map(function ($book) {
                // Author should be loaded via eager loading
                $authorData = null;
                if ($book->author) {
                    $authorData = [
                        'id' => $book->author->id,
                        'name' => $book->author->name,
                    ];
                }
                
                return [
                    'id' => $book->id,
                    'title' => $book->title ?? 'Untitled',
                    'image' => $book->image ?? null,
                    'cover_photo' => $book->cover_photo ?? null,
                    'author' => $authorData,
                    'author_id' => optional($book->author)->id ?? $book->author_id ?? null,
                ];
            })->values()->all();
        }

        return [
            'id' => $order->id,
            'status' => $order->status,
            'shipment_status' => $order->shipment_status,
            'shipment_number' => $order->shipment_number,
            'shipment_confirmed_at' => optional($order->shipment_confirmed_at)->toIso8601String(),
            'delivered_at' => optional($order->delivered_at)->toIso8601String(),
            'return_shipment_number' => $order->return_shipment_number,
            'return_shipment_added_at' => optional($order->return_shipment_added_at)->toIso8601String(),
            'return_confirmed_at' => optional($order->return_confirmed_at)->toIso8601String(),
            'start_date' => $order->start_date ? \Carbon\Carbon::parse($order->start_date)->toDateString() : null,
            'end_date' => $order->end_date ? \Carbon\Carbon::parse($order->end_date)->toDateString() : null,
            'created_at' => optional($order->created_at)->toIso8601String(),
            'tracking_url' => $order->tracking_url,
            'cancellation_note' => $order->cancellation_note,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ] : null,
            'shipping' => [
                'name' => $order->user_name,
                'phone' => $order->user_phone,
                'address_line_one' => $order->user_address_line_one,
                'address_line_two' => $order->user_address_line_two,
                'country' => $order->use_country,
                'region' => $order->use_region,
                'zip_code' => $order->use_zipCode,
            ],
            'books' => $books,
        ];
    }
}
