<?php

namespace App\Http\Requests;

use App\Models\Subscription;
use App\Services\BorrowQuotaService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed zipCode
 * @property mixed region
 * @property mixed country
 * @property mixed addressLineTwo
 * @property mixed addressLineOne
 * @property mixed user_phone
 * @property mixed name
 * @property mixed startDate
 * @property mixed books
 * @property mixed phone
 */
class BorrowOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'books' => 'required',
            'startDate' => 'required|date',
            'name' => 'required|string',
            'phone' => 'required|string',
            'addressLineOne' => 'required|string',
            'addressLineTwo' => 'nullable|string',
            'country' => 'required|string',
            'region' => 'required|string',
            'zipCode' => 'required',

        ];
    }

    /**
     * Enforce the same remaining quota as {@see \App\Http\Middleware\HasValidSubscription} (books, not orders).
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = auth()->user();
            if (! $user || $user->isSuperAdmin()) {
                return;
            }
            $books = $this->input('books');
            if (! is_array($books)) {
                return;
            }
            $count = count($books);
            $subscription = Subscription::where('user_id', $user->id)->first();
            if (! $subscription) {
                $validator->errors()->add('books', __('internal.Borrow requires active subscription'));

                return;
            }
            $remaining = app(BorrowQuotaService::class)->remainingBorrowSlotsForSubscription($subscription, (int) $user->id);
            if ($count > $remaining) {
                $validator->errors()->add('books', __('internal.Borrow exceeds plan quota', ['max' => $remaining]));
            }
        });
    }
}
