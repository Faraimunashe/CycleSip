<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AddressSelectionController extends Controller
{
    public function select(Request $request): Response
    {
        $addresses = $request->user()
            ->addresses()
            ->get()
            ->map(fn (UserAddress $address): array => [
                'id' => $address->id,
                'label' => $address->label,
                'address_line' => $address->address_line,
                'google_place_id' => $address->google_place_id,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'is_default' => $address->is_default,
            ])
            ->values();

        return Inertia::render('Addresses/Select', [
            'addresses' => $addresses,
            'selectedAddressId' => $request->session()->get('selected_delivery_address_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:40'],
            'address_line' => ['required', 'string', 'max:255'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        if (($validated['is_default'] ?? false) === true) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($validated);

        if ($user->addresses()->count() === 1) {
            $address->update(['is_default' => true]);
        }

        $request->session()->put('selected_delivery_address_id', $address->id);
        $request->session()->put('address_selection_required', false);

        return to_route('products.index')->with('success', 'Delivery address saved and selected.');
    }

    public function use(Request $request, UserAddress $address): RedirectResponse
    {
        abort_unless((int) $address->user_id === (int) $request->user()->id, 403);

        $request->session()->put('selected_delivery_address_id', $address->id);
        $request->session()->put('address_selection_required', false);

        return to_route('products.index')->with('success', 'Delivery address selected.');
    }
}
