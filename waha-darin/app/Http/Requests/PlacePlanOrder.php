<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlacePlanOrder extends FormRequest
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
            'cardName' => 'required|string|max:255',
            'cardNumber' => 'required|numeric|digits_between:14,16',
            'cardMonth' => 'required|numeric|digits_between:1,2|gt:0|lte:12',
            'cardYear' => 'required|numeric|digits:4|gte:2020|lte:2040',
            'cardCVC' => 'required|numeric|digits_between:3,4|gte:0',
            'subscriptionPeriod' => 'required|numeric|digits_between:1,2|gt:0|lte:12',
        ];
    }
}
