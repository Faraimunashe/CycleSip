<?php

namespace App\Services;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class EmailVerificationService
{
    public function issueCode(User $user, string $context = 'issue'): string
    {
        EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->delete();

        $plainCode = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(15);

        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($plainCode),
            'expires_at' => $expiresAt,
        ]);

        if (! app()->isProduction()) {
            Log::info('Email verification OTP issued.', [
                'context' => $context,
                'user_id' => $user->id,
                'email' => $user->email,
                'code' => $plainCode,
                'expires_at' => $expiresAt->toIso8601String(),
            ]);
        }

        $user->notify(new EmailVerificationOtpNotification($plainCode));

        return $plainCode;
    }

    public function verify(User $user, string $code): bool
    {
        $record = EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $record || ! Hash::check($code, $record->code_hash)) {
            return false;
        }

        $record->update(['consumed_at' => now()]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        return true;
    }

    public function resend(User $user): void
    {
        if ($user->email_verified_at !== null) {
            throw new InvalidArgumentException('Email is already verified.');
        }

        $this->issueCode($user, 'resend');
    }
}
