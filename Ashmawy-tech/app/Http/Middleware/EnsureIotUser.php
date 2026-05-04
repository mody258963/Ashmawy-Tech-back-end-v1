<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIotUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('iot-web');

        if ($user === null || ! $user->is_active) {
            abort(403, 'IoT account inactive or unavailable.');
        }

        return $next($request);
    }
}
