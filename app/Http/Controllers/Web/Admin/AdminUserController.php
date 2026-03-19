<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->withCount(['cagnottes', 'contributions']);

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_blocked') && $request->is_blocked !== '') {
            $query->where(['is_blocked' => $request->is_blocked]);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function block(int $id)
    {
        $user = User::findOrFail($id);
        
        if ($user->is_admin) {
            return back()->withErrors(['error' => 'Impossible de bloquer un administrateur.']);
        }

        $user->update(['is_blocked' => true]);

        return back()->with('success', "L'utilisateur {$user->fullname} a été bloqué.");
    }

    public function unblock(int $id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_blocked' => false]);

        return back()->with('success', "L'utilisateur {$user->fullname} a été débloqué.");
    }

    public function show(int $id)
    {
        $user = User::withCount(['cagnottes', 'contributions'])->findOrFail($id);
        
        $activities = \App\Models\AuditLog::where('actor_user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return view('admin.users.show', compact('user', 'activities'));
    }
}
