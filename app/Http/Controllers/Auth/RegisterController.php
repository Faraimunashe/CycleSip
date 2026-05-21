<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserIdentityDocument;
use App\Rules\MinimumAge;
use App\Rules\ZimbabwePhone;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $emailVerificationService,
    ) {
    }

    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'documentTypes' => UserIdentityDocument::DOCUMENT_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', new ZimbabwePhone()],
            'date_of_birth' => ['required', 'date', 'before:today', new MinimumAge(18)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'document_type' => ['nullable', Rule::in(UserIdentityDocument::DOCUMENT_TYPES), 'required_with:id_document'],
            'id_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120', 'required_with:document_type'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => ZimbabwePhone::normalize($validated['phone']),
            'date_of_birth' => $validated['date_of_birth'],
            'age_verified_at' => now(),
            'password' => $validated['password'],
        ]);
        $user->addRole('customer');

        if ($request->hasFile('id_document') && ! empty($validated['document_type'])) {
            $path = $request->file('id_document')->store('identity-documents', 'public');

            $user->identityDocuments()->create([
                'document_type' => $validated['document_type'],
                'file_url' => Storage::url($path),
                'status' => UserIdentityDocument::STATUS_PENDING,
            ]);
        }

        $this->emailVerificationService->issueCode($user);

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('verification.notice')->with('success', 'Account created. Enter the verification code sent to your email.');
    }
}
