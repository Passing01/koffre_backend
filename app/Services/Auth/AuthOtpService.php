<?php

namespace App\Services\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthOtpService
{
    public function sendOtp(string $phone, ?string $fullname = null, ?string $countryCode = null): void
    {
        $expiresAt = now()->addMinutes((int) config('otp.expires_minutes'));

        $otpCode = $this->generateOtpCode();

        DB::transaction(function () use ($phone, $fullname, $countryCode, $otpCode, $expiresAt) {
            $user = User::query()->firstOrCreate(
                ['phone' => $phone],
                [
                    'fullname' => $fullname,
                    'country_code' => $countryCode ?? 'BF',
                ]
            );

            if ($fullname !== null && $user->fullname !== $fullname) {
                $user->fullname = $fullname;
            }

            if ($countryCode !== null && $user->country_code !== $countryCode) {
                $user->country_code = $countryCode;
            }

            $user->otp_code = $otpCode;
            $user->otp_expires_at = $expiresAt;
            $user->save();
        });
    }

    public function verifyOtp(string $phone, string $otpCode): array
    {
        $user = User::query()->where('phone', $phone)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'phone' => ['Numéro invalide.'],
            ]);
        }

        if (!$user->otp_code || !$user->otp_expires_at) {
            throw ValidationException::withMessages([
                'otp_code' => ['Aucun code en attente.'],
            ]);
        }

        $expiresAt = Carbon::parse($user->otp_expires_at);
        if ($expiresAt->isPast()) {
            throw ValidationException::withMessages([
                'otp_code' => ['Code expiré.'],
            ]);
        }

        if (!hash_equals((string) $user->otp_code, (string) $otpCode)) {
            throw ValidationException::withMessages([
                'otp_code' => ['Code incorrect.'],
            ]);
        }

        return DB::transaction(function () use ($user) {
            $user->is_verified = true;
            $user->otp_code = null;
            $user->otp_expires_at = null;
            $user->save();

            $token = $user->createToken('mobile')->plainTextToken;

            return [
                'token' => $token,
                'user' => $user->fresh(),
            ];
        });
    }

    private function generateOtpCode(): string
    {
        if ((bool) config('otp.test_mode')) {
            return (string) config('otp.fixed_code');
        }

        return (string) random_int(100000, 999999);
    }
}
