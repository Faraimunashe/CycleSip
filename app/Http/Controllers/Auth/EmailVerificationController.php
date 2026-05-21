<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $emailVerificationService,
    ) {
    }

    public function notice(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return to_route('login');
        }

        if ($user->email_verified_at !== null) {
            return to_route($this->postVerificationRoute($user));
        }

        return Inertia::render('Auth/VerifyEmail', [
            'email' => $user->email,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if (! $user) {
            return to_route('login');
        }

        if ($user->email_verified_at !== null) {
            return to_route($this->postVerificationRoute($user));
        }

        if (! $this->emailVerificationService->verify($user, $validated['code'])) {
            return back()->withErrors(['code' => 'Invalid or expired verification code.']);
        }

        if ($user->hasRole('customer')) {
            $request->session()->put('address_selection_required', true);
            $request->session()->forget('selected_delivery_address_id');

            return to_route('addresses.select')->with('success', 'Email verified. Choose your delivery address to continue.');
        }

        return to_route($this->postVerificationRoute($user))->with('success', 'Email verified successfully.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || $user->email_verified_at !== null) {
            return back();
        }

        try {
            $this->emailVerificationService->resend($user);
        } catch (InvalidArgumentException) {
            return back();
        }

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    private function postVerificationRoute($user): string
    {
        if ($user->hasRole('customer')) {
            return 'addresses.select';
        }

        if ($user->hasRole('rider')) {
            return 'rider.orders.available';
        }

        return 'admin.dashboard';
    }
}
