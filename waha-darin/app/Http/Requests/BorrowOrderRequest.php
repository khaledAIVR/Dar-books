<?php

namespace App\Http\Requests;

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
}
