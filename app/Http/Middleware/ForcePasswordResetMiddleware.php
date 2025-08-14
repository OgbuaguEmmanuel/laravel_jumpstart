<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordResetMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('user')->user();

        if (! $user) {
            abort(Response::HTTP_UNAUTHORIZED, 'User not authenticated');
        }

        if ($user->force_password_reset) {
            abort(Response::HTTP_FORBIDDEN, 'You must reset your password to continue');
        }

        return $next($request);
    }
}
