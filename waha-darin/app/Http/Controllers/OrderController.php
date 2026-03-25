<?php

namespace App\Http\Controllers;

use App\Mail\Borrow\OrderReceived;
use App\Http\Requests\BorrowOrderRequest;
use App\Models\BorrowOrder;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    private $userSubscription;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Only require a valid subscription when placing a borrow order.
        // Other order actions (list, return shipment, etc.) should not depend on `books` payload.
        $this->middleware('has.valid.subscription')->only('create');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            $this->userSubscription = Subscription::where('user_id', $user->id)->first();
            return $next($request);
        });
    }

    public function index()
    {
        $user = auth()->user();
        $orders = $user->orders()
            ->select(
                'id',
                'start_date',
                'end_date',
                'status',
                'shipment_number',
                'shipment_status',
                'shipment_confirmed_at',
                'delivered_at',
                'return_shipment_number',
                'return_shipment_added_at',
                'return_confirmed_at',
                'created_at'
            )
            ->where(function ($query) {
                $query->where('shipment_status', '!=', 'cancelled')
                      ->orWhereNull('shipment_status');
            })
            ->where('status', '!=', 'Cancelled')
            ->with(['books' => function ($query) {
                $query->select('books.id', 'books.title', 'books.image', 'books.author_id')
                      ->with('author:id,name');
            }])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($order) {
                // When using select(), appended attributes aren't automatically included in JSON
                // Manually calculate and add tracking_url to the response
                if ($order->shipment_number) {
                    $encodedNumber = urlencode($order->shipment_number);
                    $order->tracking_url = "https://www.dhl.com/en/express/tracking.html?AWB={$encodedNumber}&brand=DHL";
                } else {
                    $order->tracking_url = null;
                }

                // Keep status consistent for the customer flow:
                // - If delivered and past due with no return shipment => WaitingReturnShipment
                // (We derive this at response time so it stays correct even if a scheduled job hasn't run yet.)
                if (!in_array($order->status, ['Cancelled', 'Completed'], true)) {
                    if (
                        $order->status === 'Delivered' &&
                        $order->end_date &&
                        now()->greaterThan(Carbon::parse($order->end_date)->endOfDay())
                    ) {
                        $order->status = 'WaitingReturnShipment';
                    }
                }

                // Make sure these fields are visible in JSON response
                $order->makeVisible([
                    'tracking_url',
                    'shipment_number',
                    'shipment_status',
                    'shipment_confirmed_at',
                    'delivered_at',
                    'return_shipment_number',
                    'return_shipment_added_at',
                    'return_confirmed_at',
                ]);
                return $order;
            })
            ->values();

        // Current vs Completed:
        // - Completed: only orders confirmed returned by admin (ReturnedBack)
        // - Current: everything else
        [$completed, $current] = $orders->partition(function ($order) {
            return $order->status === 'ReturnedBack';
        });

        return response()->json([
            $current->values(),
            $completed->values(),
        ], 200);
    }

    public function showBorrow()
    {
        $available = $this->userSubscription->Available;
        return view('books.borrow', compact('available'));
    }


    public function create(BorrowOrderRequest $request)
    {
        $user = auth()->id();
        $subscription = Subscription::where('user_id', $user)->first();
        $order = new BorrowOrder();
        $order->user_id = $user;
        $order->user_name = $request->name;
        $order->user_phone = $request->phone;
        $order->user_address_line_one = $request->addressLineOne;
        $order->user_address_line_two = $request->addressLineTwo;
        $order->use_country = $request->country;
        $order->use_region = $request->region;
        $order->use_zipCode = $request->zipCode;

        $order->start_date = Carbon::parse($request->startDate);
        $order->end_date = Carbon::parse($request->startDate)->addMonth();
        $order->status = 'Received';

        $order->save();
        $order->books()->sync($request->books);
        $order->loadMissing(['user', 'books']);
        Mail::to($order->user)->send(new OrderReceived($order));
        // Quota enforcement is handled by the `has.valid.subscription` middleware via
        // Subscription::valid (based on plan quota and monthly borrow count).
        // `quote` is stored as JSON/array and should not be decremented as a scalar.
        $this->updateUserInfo($request);
        $this->deleteUserCart();

        return [
            'code' => 200, 'status' => 'success', 'icon' => 'check',
            'message' => __('internal.Order Placed!'),
            'subMessage' => __('internal.You can track your orders from my orders page.')
        ];
    }

    public function addReturnShipment(Request $request, BorrowOrder $order)
    {
        $data = $request->validate([
            'return_shipment_number' => ['required', 'string', 'max:190'],
        ]);

        if ($order->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!in_array($order->status, ['Delivered', 'WaitingReturnShipment'], true)) {
            return response()->json(['message' => 'Return shipment can only be added for delivered orders.'], 409);
        }

        if ($order->return_shipment_number) {
            return response()->json(['message' => 'Return shipment number already set.'], 409);
        }

        $order->return_shipment_number = $data['return_shipment_number'];
        $order->return_shipment_added_at = now();
        // Do NOT mark returned here. Admin must confirm the book return.
        if (
            $order->status === 'Delivered' &&
            $order->end_date &&
            now()->greaterThan(Carbon::parse($order->end_date)->endOfDay())
        ) {
            $order->status = 'WaitingReturnShipment';
        }
        $order->save();

        return response()->json([
            'message' => 'Return shipment number saved.',
            'order' => $order->fresh(),
        ], 200);
    }

    private function updateUserInfo($request)
    {
        $user = auth()->user();
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->address_line_one = $request->addressLineOne;
        $user->address_line_two = $request->addressLineTwo;
//        $user->use_country = $request->country;
//        $user->use_region = $request->region;
        $user->postal_code = $request->zipCode;
        $user->save();
    }
    private function deleteUserCart()
    {
        $user = auth()->user();
        $user->cart()->delete();
        $user->save();
    }

    private function decrementMonthlyQuote($decrementAmount)
    {
        $monthIndex = $this->userSubscription->month;
        $quote = json_decode($this->userSubscription->quote);
        $quote[$monthIndex] -= $decrementAmount;
        $this->userSubscription->quote = json_encode($quote);
        $this->userSubscription->save();
    }
}
