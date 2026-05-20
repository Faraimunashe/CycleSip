<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Authentication required.');
        }

        $isOpsRole = $user->hasRole(['super-admin', 'admin', 'operations-manager', 'support-staff', 'finance-officer']);
        $hasOpsPermission = collect(['manage-orders', 'view-analytics'])
            ->some(fn (string $permission): bool => $user->hasPermission($permission));

        if (! $isOpsRole && ! $hasOpsPermission) {
            abort(403, 'Only operations staff can access this area.');
        }

        return $next($request);
    }
}
