<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
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

        // Générer un code OTP
        $otpCode = config('otp.test_mode') ? config('otp.fixed_code') : rand(100000, 999999);

        $user->update([
            'otp_code' => $otpCode,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // En mode test, afficher le code
        if (config('otp.test_mode')) {
            session()->flash('otp_code', $otpCode);
        }

        session(['phone' => $request->phone]);

        return redirect()->route('admin.verify-otp')->with('success', 'Code OTP envoyé.');
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

        $user = User::where('phone', $phone)
            ->where('is_admin', true)
            ->first();

        if (!$user) {
            return back()->withErrors(['otp_code' => 'Utilisateur non trouvé.']);
        }

        if ($user->otp_code !== $request->otp_code) {
            return back()->withErrors(['otp_code' => 'Code OTP invalide.']);
        }

        if ($user->otp_expires_at < now()) {
            return back()->withErrors(['otp_code' => 'Code OTP expiré.']);
        }

        // Connexion de l'utilisateur
        Auth::login($user);

        // Nettoyer l'OTP
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

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
