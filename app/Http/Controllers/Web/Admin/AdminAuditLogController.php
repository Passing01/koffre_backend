<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('actor');

        // Filtre par action
        if ($request->has('action') && $request->action !== '') {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        // Filtre par utilisateur
        if ($request->has('user_id') && $request->user_id !== '') {
            $query->where('actor_user_id', $request->user_id);
        }

        // Filtre par date
        if ($request->has('date_from') && $request->date_from !== '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to !== '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.audit.index', compact('logs'));
    }

    public function show($id)
    {
        $log = AuditLog::with('actor')->findOrFail($id);
        return view('admin.audit.show', compact('log'));
    }
}
