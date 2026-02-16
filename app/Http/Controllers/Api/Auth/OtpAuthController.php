<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\Auth\AuthOtpService;
use Illuminate\Http\JsonResponse;

class OtpAuthController extends Controller
{
    public function __construct(private readonly AuthOtpService $authOtpService)
    {
    }

    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $this->authOtpService->sendOtp(
            phone: $request->string('phone')->toString(),
            fullname: $request->string('fullname')->toString() ?: null,
            countryCode: $request->string('country_code')->toString() ?: null,
        );

        $data = [];
        if (app()->environment(['local', 'testing']) && (bool) config('otp.test_mode')) {
            $data['otp'] = (string) config('otp.fixed_code');
        }

        return response()->json([
            'message' => 'OTP envoyé.',
            'data' => $data,
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->authOtpService->verifyOtp(
            phone: $request->string('phone')->toString(),
            otpCode: $request->string('otp_code')->toString(),
        );

        return response()->json([
            'message' => 'Authentification réussie.',
            'data' => $result,
        ]);
    }
}
