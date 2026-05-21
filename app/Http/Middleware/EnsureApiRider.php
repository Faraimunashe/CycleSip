<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiRider
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->hasRole('rider')) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Only riders can access this resource.',
            'code' => 'rider_role_required',
        ], 403);
    }
}
