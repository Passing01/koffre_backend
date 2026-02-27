<?php

namespace App\Services\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthOtpService
{
    public function sendOtp(string $phone, ?string $fullname = null, ?string $countryCode = null): void
    {
        $expiresAt = now()->addMinutes((int) config('otp.expires_minutes'));

        // En environnement de test, on garde l'ancien comportement (code fixe local)
        if (App::environment(['local', 'testing']) && (bool) config('otp.test_mode')) {
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

            return;
        }

        // Production : déléguer l'envoi à Ikoddi
        $identity = $this->buildIkoddiIdentity($phone, $countryCode);

        $orgId = config('services.ikoddi.organization_id');
        $appId = config('services.ikoddi.otp_app_id');
        $type = config('services.ikoddi.type', 'sms');
        $apiKey = config('services.ikoddi.api_key');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-api-key' => $apiKey,
        ])->post("https://api.ikoddi.com/api/v1/groups/{$orgId}/otp/{$appId}/{$type}/{$identity}");

        if ($response->failed() || $response->json('status') !== 0) {
            throw ValidationException::withMessages([
                'phone' => ['Impossible d’envoyer le code OTP.'],
            ]);
        }

        $otpToken = $response->json('otpToken');

        DB::transaction(function () use ($phone, $fullname, $countryCode, $otpToken, $expiresAt) {
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

            // On stocke le otpToken (verificationKey Ikoddi)
            $user->otp_code = $otpToken;
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

        // En environnement de test, on continue de vérifier localement
        if (App::environment(['local', 'testing']) && (bool) config('otp.test_mode')) {
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

        // Production : déléguer la vérification à Ikoddi
        $orgId = config('services.ikoddi.organization_id');
        $appId = config('services.ikoddi.otp_app_id');
        $apiKey = config('services.ikoddi.api_key');

        $identity = $this->buildIkoddiIdentity($user->phone, $user->country_code);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-api-key' => $apiKey,
        ])->post("https://api.ikoddi.com/api/v1/groups/{$orgId}/otp/{$appId}/verify", [
            'verificationKey' => $user->otp_code,
            'otp' => $otpCode,
            'identity' => $identity,
        ]);

        if ($response->failed() || $response->json('status') !== 0) {
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

    /**
     * Construit l'identity au format attendu par Ikoddi (numérique, sans préfixe BF+).
     * - Nettoie tout sauf les chiffres
     * - Si le téléphone commence déjà par l'indicatif, on ne le duplique pas
     */
    private function buildIkoddiIdentity(string $phone, ?string $countryCode): string
    {
        $digitsPhone = preg_replace('/\D+/', '', $phone) ?? '';
        $digitsCode = preg_replace('/\D+/', (string) $countryCode) ?: '226';

        if (str_starts_with($digitsPhone, $digitsCode)) {
            return $digitsPhone;
        }

        return $digitsCode . $digitsPhone;
    }
}
