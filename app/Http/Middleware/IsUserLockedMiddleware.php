<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsUserLockedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $user = auth('api')->user();

        if (!$user) {
            abort(Response::HTTP_FORBIDDEN, 'User not authenticated');
        }

        if ($user->isLocked()) {
            abort(Response::HTTP_FORBIDDEN, 'Your account is on locked. Try again later or contact support');
        }

        return $next($request);

    }
}
