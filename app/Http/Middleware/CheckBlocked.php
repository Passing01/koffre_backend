<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_blocked) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Votre compte a été suspendu par un administrateur.',
                ], 403);
            }

            auth()->logout();
            
            // Si c'est un admin, on renvoie vers login admin, sinon on peut renvoyer vers home ou autre
            if ($user->is_admin) {
                return redirect()->route('admin.login')->withErrors(['error' => 'Votre compte a été suspendu.']);
            }
            
            return redirect('/')->withErrors(['error' => 'Votre compte a été suspendu par un administrateur.']);
        }

        if ($user) {
            $user->update(['last_activity_at' => now()]);
        }

        return $next($request);
    }
}
