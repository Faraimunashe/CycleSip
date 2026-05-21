<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerified
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('customer')) {
            return $next($request);
        }

        if ($request->routeIs([
            'verification.*',
            'compliance.*',
            'logout',
        ])) {
            return $next($request);
        }

        if ($user->email_verified_at === null) {
            return to_route('verification.notice')->with('error', 'Please verify your email before continuing.');
        }

        return $next($request);
    }
}
