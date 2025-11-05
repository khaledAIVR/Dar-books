<?php

namespace App\Http\Controllers;

use App\Http\Requests\BorrowOrderRequest;
use App\Models\BorrowOrder;
use App\Models\Subscription;
use Illuminate\Support\Carbon;

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
        $this->middleware('has.valid.subscription')->except('index');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            $this->userSubscription = Subscription::where('user_id', $user->id)->first();
            return $next($request);
        });
    }

    public function index()
    {
        // group by completed
        $user = auth()->user();
        $orders = $user->orders()->select('id', 'start_date', 'end_date', 'end_date', 'status')->with('books:image')->get()->groupBy('completed');
        return response()->json($orders, 200);
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
        $subscription->quote = $subscription->quote - count($request->books);
        $subscription->save();
        $this->updateUserInfo($request);
        $this->deleteUserCart();

        return [
            'code' => 200, 'status' => 'success', 'icon' => 'check',
            'message' => __('internal.Order Placed!'),
            'subMessage' => __('internal.You can track your orders from my orders page.')
        ];
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
