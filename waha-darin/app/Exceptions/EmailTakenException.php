<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailTakenException extends Exception
{
    /**
     * Render the exception as an HTTP response.
     *
     * @param  Request  $request
     * @return Response
     */
    public function render($request)
    {
        return response()->view('oauth.emailTaken', [], 400);
    }
}
