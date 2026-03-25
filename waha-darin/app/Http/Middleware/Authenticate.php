<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // API routes: no web "login" route in this SPA — return null for 401 JSON.
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }

        return '/';
    }
}
