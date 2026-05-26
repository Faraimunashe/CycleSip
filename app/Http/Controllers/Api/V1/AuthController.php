<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserIdentityDocument;
use App\Rules\MinimumAge;
use App\Rules\ZimbabwePhone;
use App\Services\EmailVerificationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AuthController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private readonly EmailVerificationService $emailVerificationService,
    ) {
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', new ZimbabwePhone()],
            'date_of_birth' => ['required', 'date', 'before:today', new MinimumAge(18)],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
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

        $this->emailVerificationService->issueCode($user, 'register');

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->created([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => UserResource::make($this->loadUserRelations($user))->resolve(),
        ], 'Account created. Verify your email with the code sent to your inbox.');
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->isActive()) {
            return $this->error('Your account is not active.', 403);
        }

        if (! $user->hasVerifiedEmail()) {
            $this->emailVerificationService->issueCode($user, 'login');
        }

        $tokenName = $validated['device_name'] ?? 'mobile';
        $token = $user->createToken($tokenName)->plainTextToken;

        $message = $user->hasVerifiedEmail()
            ? 'Signed in successfully.'
            : 'Signed in successfully. Verify your email with the code sent to your inbox.';

        return $this->ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => UserResource::make($this->loadUserRelations($user))->resolve(),
        ], $message);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->ok(null, 'Signed out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok([
            'user' => UserResource::make($this->loadUserRelations($request->user()))->resolve(),
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->ok([
                'user' => UserResource::make($this->loadUserRelations($user))->resolve(),
            ], 'Email already verified.');
        }

        if (! $this->emailVerificationService->verify($user, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired verification code.'],
            ]);
        }

        $user->refresh();

        return $this->ok([
            'user' => UserResource::make($this->loadUserRelations($user))->resolve(),
        ], 'Email verified successfully.');
    }

    public function resendEmailCode(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->ok(null, 'Email already verified.');
        }

        try {
            $this->emailVerificationService->resend($user);
        } catch (InvalidArgumentException) {
            return $this->error('Please wait before requesting another code.', 429);
        }

        return $this->ok(null, 'A new verification code has been sent to your email.');
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink(['email' => $validated['email']]);

        return $this->ok(null, 'If that email exists in our system, a reset link has been sent.');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $this->ok(null, 'Password reset successfully.');
    }

    private function loadUserRelations(User $user): User
    {
        return $user->load([
            'roles',
            'selectedDeliveryAddress',
            'identityDocuments' => fn ($query) => $query->latest()->limit(1),
            'riderProfile',
        ]);
    }
}
