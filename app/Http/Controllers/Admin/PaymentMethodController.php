<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function index(): Response
    {
        $methods = PaymentMethod::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (PaymentMethod $method): array => [
                'id' => $method->id,
                'code' => $method->code,
                'name' => $method->name,
                'description' => $method->description,
                'timing' => $method->timing,
                'gateway' => $method->gateway,
                'is_enabled' => $method->is_enabled,
                'requires_phone' => $method->requires_phone,
                'sort_order' => $method->sort_order,
            ])
            ->values();

        return Inertia::render('Admin/PaymentMethods/Index', [
            'paymentMethods' => $methods,
            'timingOptions' => [
                PaymentMethod::TIMING_ON_DELIVERY => 'Pay on delivery',
                PaymentMethod::TIMING_PREPAY => 'Pay before checkout completes',
            ],
        ]);
    }

    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'timing' => ['required', 'in:'.PaymentMethod::TIMING_ON_DELIVERY.','.PaymentMethod::TIMING_PREPAY],
            'is_enabled' => ['required', 'boolean'],
            'requires_phone' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $paymentMethod->update($validated);

        return back()->with('success', "{$paymentMethod->name} updated.");
    }
}
