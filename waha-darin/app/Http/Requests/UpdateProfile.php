<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfile extends FormRequest
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
        $user = auth()->user();
        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'string',
            'age' => '',
            'address_line_one' => 'string',
            'address_line_two' => 'nullable|string',
            'country' => 'string',
            'region' => 'string',
            'zipCode' => 'string',
        ];
    }
}
