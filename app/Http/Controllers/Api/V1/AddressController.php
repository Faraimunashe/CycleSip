<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserAddressResource;
use App\Models\UserAddress;
use App\Services\Mobile\DeliveryAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private readonly DeliveryAddressService $deliveryAddressService,
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
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $address = $this->deliveryAddressService->create($request->user(), $validated);
        $request->user()->refresh();

        return $this->created([
            'address' => UserAddressResource::make($address)->resolve(),
            'selected_address_id' => $request->user()->selected_delivery_address_id,
        ], 'Delivery address saved and selected.');
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
