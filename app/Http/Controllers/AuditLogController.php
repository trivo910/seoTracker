<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->user_id, fn($q, $id) => $q->where('user_id', $id))
            ->when($request->action,  fn($q, $a)  => $q->where('action', $a))
            ->when($request->from,    fn($q, $d)   => $q->whereDate('created_at', '>=', $d))
            ->when($request->to,      fn($q, $d)   => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $users = User::orderBy('name')->get();

        return view('audit.index', compact('logs', 'users'));
    }
}
