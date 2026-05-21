<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiDeliveryAddressSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('customer')) {
            return $next($request);
        }

        if ($user->selected_delivery_address_id !== null) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Please select a delivery address before continuing.',
            'code' => 'delivery_address_required',
        ], 403);
    }
}
