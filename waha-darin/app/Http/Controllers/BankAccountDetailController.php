<?php

namespace App\Http\Controllers;

use App\Models\BankAccountDetail;
use Illuminate\Http\JsonResponse;

class BankAccountDetailController extends Controller
{
    /**
     * Return bank account details for the subscription checkout page.
     * Public so the checkout page can fetch without auth.
     *
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $bank = BankAccountDetail::forCheckout();
        $data = $bank ? [
            'name' => $bank->name,
            'account_number' => $bank->account_number,
            'iban' => $bank->iban,
            'swift_code' => $bank->swift_code,
        ] : [
            'name' => null,
            'account_number' => null,
            'iban' => null,
            'swift_code' => null,
        ];
        return response()->json($data);
    }
}
