<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Invalid credentials.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        /** @var User $user */
        $user = $request->user();

        if ($user->hasRole('customer')) {
            if ($user->email_verified_at === null) {
                return to_route('verification.notice')->with('success', 'Welcome back. Verify your email to continue.');
            }

            $request->session()->put('address_selection_required', true);
            $request->session()->forget('selected_delivery_address_id');

            return to_route('addresses.select')->with('success', 'Welcome back. Choose your delivery address to continue.');
        }

        return to_route($this->redirectRouteFor($user))->with('success', 'Welcome back.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('success', 'Signed out successfully.');
    }

    private function redirectRouteFor(User $user): string
    {
        if ($user->hasRole('rider')) {
            return 'rider.orders.available';
        }

        if ($user->can('view-analytics')) {
            return 'admin.dashboard';
        }

        return match (true) {
            $user->can('manage-orders') => 'admin.orders.index',
            $user->can('manage-riders') => 'admin.riders.index',
            $user->can('manage-stores') => 'admin.stores.index',
            $user->can('manage-products') => 'admin.products.index',
            $user->can('manage-zones') => 'admin.zones.index',
            $user->can('manage-customers') => 'admin.customers.index',
            $user->can('manage-payments') => 'admin.finance.index',
            default => 'products.index',
        };
    }
}
