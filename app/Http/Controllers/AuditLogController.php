<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = AuditLog::with(['user', 'document'])->latest();
        
        // Scope logs to department for regular admins
        if (!$user->isSuperAdmin()) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }
        
        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        
        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        
        // Search by description
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }
        
        $logs = $query->paginate(25);
        
        // Get unique actions for filter
        $actions = AuditLog::distinct()->pluck('action');
        
        return view('audit-logs.index', compact('logs', 'actions'));
    }
}
