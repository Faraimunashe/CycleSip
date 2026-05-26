<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserAddressResource;
use App\Models\UserAddress;
use App\Services\DeliveryZoneService;
use App\Services\Mobile\DeliveryAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private readonly DeliveryAddressService $deliveryAddressService,
        private readonly DeliveryZoneService $deliveryZoneService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $addresses = $this->deliveryAddressService->list($request->user());

        return $this->ok([
            'addresses' => UserAddressResource::collection($addresses)->resolve(),
            'selected_address_id' => $request->user()->selected_delivery_address_id,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:40'],
            'address_line' => ['required', 'string', 'max:255'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $coverage = $this->deliveryZoneService->coverageForPoint(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
        );

        if (! $coverage['is_serviceable']) {
            return response()->json([
                'message' => 'Delivery is not available at this location yet. Move the pin to a supported zone.',
                'code' => 'delivery_location_unserviceable',
                'errors' => [
                    'coverage' => [$coverage],
                ],
            ], 422);
        }

        $address = $this->deliveryAddressService->create($request->user(), $validated);
        $request->user()->refresh();

        return $this->created([
            'address' => UserAddressResource::make($address)->resolve(),
            'selected_address_id' => $request->user()->selected_delivery_address_id,
            'coverage' => $coverage,
        ], 'Delivery address saved and selected.');
    }

    public function coverage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        return $this->ok(
            $this->deliveryZoneService->coverageForPoint(
                (float) $validated['latitude'],
                (float) $validated['longitude'],
            ),
        );
    }

    public function select(Request $request, UserAddress $address): JsonResponse
    {
        $this->deliveryAddressService->select($request->user(), $address);
        $request->user()->refresh();

        return $this->ok([
            'address' => UserAddressResource::make($address)->resolve(),
            'selected_address_id' => $request->user()->selected_delivery_address_id,
        ], 'Delivery address selected.');
    }
}
