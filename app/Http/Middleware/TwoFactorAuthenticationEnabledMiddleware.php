<?php

namespace App\Http\Middleware;

use App\Facades\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuthenticationEnabledMiddleware
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

        if (! Settings::get('two_factor_enabled')) {
            abort(Response::HTTP_FORBIDDEN, 'Two Factor Authentication is not allowed');
        }

        return $next($request);
    }
}
