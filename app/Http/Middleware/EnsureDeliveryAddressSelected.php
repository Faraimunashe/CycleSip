<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeliveryAddressSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('customer')) {
            return $next($request);
        }

        if ($request->routeIs('addresses.*') || $request->routeIs('logout') || $request->routeIs('compliance.*')) {
            return $next($request);
        }

        if ((bool) $request->session()->get('address_selection_required', false)) {
            return to_route('addresses.select')->with('error', 'Please choose a delivery address before continuing.');
        }

        return $next($request);
    }
}
