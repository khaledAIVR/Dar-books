<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class createSubscription extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "plan_id" => "required|exists:plans,id",
            "transaction_amount"  => "required|numeric",
            "transaction_date"  => "required|date_format:Y-m-d H:i:s",
        ];
    }
}
