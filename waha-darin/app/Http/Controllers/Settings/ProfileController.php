<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Update the user's profile information.
     *
     * @param UpdateProfile $request
     * @return JsonResponse
     */
    public function update(UpdateProfile $request)
    {
        $user = auth()->user();
        if ($request->name) $user->name = $request->name;
        $user->phone = $request->phone;
        if ($request->age) $user->age = $request->age;
        if ($request->address_line_one) $user->address_line_one = $request->address_line_one;
        if ($request->address_line_two) $user->address_line_two = $request->address_line_two;
        if ($request->country) $user->country = $request->country;
        if ($request->region) $user->region = $request->region;
        if ($request->zipCode) $user->zipCode = $request->zipCode;
        $user->save();
        return response()->json(['status' => 200, 'user' => $user], 200);
    }

    /**
     * Update the user's categories preferences.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function categories(Request $request)
    {
        $user = auth()->user();
        $categories = $request['categories'];

        if (count($categories) > 0)
            $user->categories()->sync($categories);

        return response()->json(['status'=>200, 'message'=>'Categories updated'], 200);
    }

    /**
     * Update the user's authors preferences.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function authors(Request $request)
    {
        $user = auth()->user();
        $authors = $request['authors'];

        if (count($authors) > 0)
            $user->authors()->sync($authors);

        return response()->json(['status'=>200, 'message'=>'Authors updated'], 200);
    }
}
