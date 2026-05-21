<?php

namespace App\Http\Middleware;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $selectedAddressId = $request->session()->get('selected_delivery_address_id');
        $selectedAddress = null;

        if ($request->user() && $selectedAddressId) {
            /** @var UserAddress|null $resolvedAddress */
            $resolvedAddress = $request->user()
                ->addresses()
                ->where('id', $selectedAddressId)
                ->first();

            if ($resolvedAddress) {
                $selectedAddress = [
                    'id' => $resolvedAddress->id,
                    'label' => $resolvedAddress->label,
                    'address_line' => $resolvedAddress->address_line,
                    'latitude' => $resolvedAddress->latitude,
                    'longitude' => $resolvedAddress->longitude,
                ];
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                        'status' => $request->user()->status,
                        'email_verified_at' => optional($request->user()->email_verified_at)?->toIso8601String(),
                        'age_verified_at' => optional($request->user()->age_verified_at)?->toIso8601String(),
                        'roles' => $request->user()->roles->pluck('name')->values(),
                        'permissions' => $request->user()->permissions->pluck('name')->values(),
                    ]
                    : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'delivery' => [
                'address_required' => (bool) $request->session()->get('address_selection_required', false),
                'selected_address' => $selectedAddress,
            ],
        ];
    }
}
