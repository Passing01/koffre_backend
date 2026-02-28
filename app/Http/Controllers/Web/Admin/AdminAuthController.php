<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AuthOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function __construct(private readonly AuthOtpService $authOtpService)
    {
    }

    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !$user->is_admin) {
            return back()->withErrors(['phone' => 'Utilisateur non autorisé.']);
        }

        $this->authOtpService->sendOtp(
            phone: $request->phone,
            fullname: null,
            countryCode: $user->country_code ?? '226',
        );

        session(['phone' => $request->phone]);

        return redirect()->route('admin.verify-otp')->with('success', 'Code OTP envoyé par SMS.');
    }

    public function showVerifyForm()
    {
        if (!session('phone')) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.verify-otp');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|string|size:6',
        ]);

        $phone = session('phone');
        if (!$phone) {
            return redirect()->route('admin.login')->withErrors(['error' => 'Session expirée.']);
        }

        $user = User::where('phone', $phone)->where('is_admin', true)->first();

        if (!$user) {
            return back()->withErrors(['otp_code' => 'Utilisateur non trouvé.']);
        }

        try {
            $user = $this->authOtpService->verifyOtpForWeb($phone, $request->otp_code);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        if (!$user->is_admin) {
            return back()->withErrors(['otp_code' => 'Utilisateur non autorisé.']);
        }

        Auth::login($user);
        session()->forget('phone');

        return redirect()->route('admin.dashboard')->with('success', 'Connexion réussie !');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Déconnexion réussie.');
    }
}
