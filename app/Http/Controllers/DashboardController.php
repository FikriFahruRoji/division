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
                $q->where('creator_id', $user->id)
                  ->orWhereHas('signerAssignments', fn($sq) => $sq->where('signer_id', $user->id));
            });
        }
        
        // Document stats for dashboard cards (1 single GROUP BY query instead of 5 count queries)
        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $stats = [
            'total' => (int) $statusCounts->sum(),
            'pending' => (int) $statusCounts->get('pending_signature', 0),
            'signed' => (int) $statusCounts->get('signed_valid', 0),
            'revoked' => (int) $statusCounts->get('revoked', 0),
            'draft' => (int) $statusCounts->get('draft', 0),
        ];
        
        // User stats - different for super admin vs admin (1 single GROUP BY query instead of 4-5 queries)
        $userStats = [];
        
        if ($user->isSuperAdmin()) {
            $roleCounts = User::selectRaw('role, count(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role');

            $userStats = [
                'departments' => Department::count(),
                'total_users' => (int) $roleCounts->sum(),
                'admins' => (int) $roleCounts->get('admin', 0),
                'operators' => (int) $roleCounts->get('operator', 0),
                'signers' => (int) $roleCounts->get('signer', 0),
            ];
        } elseif ($user->role === 'admin' && $user->department_id) {
            $roleCounts = User::where('department_id', $user->department_id)
                ->selectRaw('role, count(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role');

            $userStats = [
                'operators' => (int) $roleCounts->get('operator', 0),
                'signers' => (int) $roleCounts->get('signer', 0),
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
                $q->where('creator_id', $user->id)
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
        
        return view('dashboard', compact('stats', 'userStats', 'documents', 'pendingSignatures'));
    }
}
