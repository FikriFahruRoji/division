<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        $user = auth()->user();
        
        // Initialize base query for document stats
        $baseQuery = Document::query();
        
        // Filter documents based on user role
        if ($user->isSuperAdmin()) {
            // Super admin sees all documents
        } elseif ($user->role === 'admin' && $user->department_id) {
            // Admin sees only documents from their department
            $baseQuery->whereHas('creator', fn($q) => $q->where('department_id', $user->department_id));
        } elseif ($user->isOperator()) {
            // Operator sees all documents (or could be filtered by department)
        } else {
            // Signer sees only their documents
            $baseQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('signerAssignments', fn($sq) => $sq->where('signer_id', $user->id));
            });
        }
        
        // Document stats for dashboard cards
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending_signature')->count(),
            'signed' => (clone $baseQuery)->where('status', 'signed_valid')->count(),
            'revoked' => (clone $baseQuery)->where('status', 'revoked')->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
        ];
        
        // User stats - different for super admin vs admin
        $userStats = [];
        
        if ($user->isSuperAdmin()) {
            // Super admin sees all user counts
            $userStats = [
                'departments' => Department::count(),
                'total_users' => User::count(),
                'admins' => User::where('role', 'admin')->count(),
                'operators' => User::where('role', 'operator')->count(),
                'signers' => User::where('role', 'signer')->count(),
            ];
        } elseif ($user->role === 'admin' && $user->department_id) {
            // Admin sees only users in their department
            $userStats = [
                'operators' => User::where('department_id', $user->department_id)->where('role', 'operator')->count(),
                'signers' => User::where('department_id', $user->department_id)->where('role', 'signer')->count(),
            ];
        }
        
        // Recent documents - filtered by role
        if ($user->isSuperAdmin() || $user->isOperator()) {
            $documents = Document::with(['creator', 'signerAssignments.signer'])
                ->latest()
                ->paginate(10);
        } elseif ($user->role === 'admin' && $user->department_id) {
            // Admin sees documents from their department
            $documents = Document::whereHas('creator', fn($q) => $q->where('department_id', $user->department_id))
                ->with(['creator', 'signerAssignments.signer'])
                ->latest()
                ->paginate(10);
        } else {
            // For signers - show documents related to them
            $documents = Document::where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('signerAssignments', fn($sq) => $sq->where('signer_id', $user->id));
            })
            ->with(['creator', 'signerAssignments.signer'])
            ->latest()
            ->paginate(10);
        }
        
        // Pending signatures for signer quick actions
        $pendingSignatures = null;
        if ($user->isSigner()) {
            $pendingSignatures = Document::whereHas('signerAssignments', function ($q) use ($user) {
                $q->where('signer_id', $user->id)
                  ->whereIn('status', ['pending', 'notified']);
            })
            ->where('status', 'pending_signature')
            ->with('creator')
            ->latest()
            ->take(5)
            ->get();
        }
        
        return view('dashboard-new', compact('stats', 'userStats', 'documents', 'pendingSignatures'));
    }
}
