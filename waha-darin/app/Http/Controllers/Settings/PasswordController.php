<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $this->validate($request, [
            'password' => 'required|confirmed|min:8',
        ]);

        $request->user()->update([
            'password' => bcrypt($request->password),
        ]);
        return response()->json(['status' => 200, 'user' => $user], 200);
    }
}
