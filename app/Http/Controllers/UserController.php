<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);
        
        $user = auth()->user();
        $query = User::with('department');
        
        // Admin can only see users in their department
        if (!$user->isSuperAdmin() && $user->role === 'admin') {
            if ($user->department_id) {
                $query->where('department_id', $user->department_id);
            } else {
                // Admin without department can't see any users
                $query->whereRaw('1 = 0');
            }
        }
        
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        if ($request->filled('department_id') && $user->isSuperAdmin()) {
            $query->where('department_id', $request->department_id);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $users = $query->latest()->paginate(15);
        
        // Get departments for filter (super admin only)
        $departments = $user->isSuperAdmin() ? Department::active()->get() : collect();
        
        return view('users.index', compact('users', 'departments'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $this->authorize('create', User::class);
        
        $user = auth()->user();
        
        // Get available departments based on user role
        if ($user->isSuperAdmin()) {
            $departments = Department::active()->get();
        } else {
            // Admin can only create users in their own department
            $departments = $user->department_id 
                ? Department::where('id', $user->department_id)->active()->get()
                : collect();
        }
        
        return view('users.create', compact('departments'));
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        
        $validated['password'] = Hash::make($validated['password']);
        
        User::create($validated);
        
        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);
        
        $user->load(['documents', 'department', 'signerAssignments.document', 'signatures.document']);
        
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $this->authorize('update', $user);
        
        $authUser = auth()->user();
        
        // Get available departments based on user role
        if ($authUser->isSuperAdmin()) {
            $departments = Department::active()->get();
        } else {
            // Admin can only edit users to their own department
            $departments = $authUser->department_id 
                ? Department::where('id', $authUser->department_id)->active()->get()
                : collect();
        }
        
        return view('users.edit', compact('user', 'departments'));
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();
        
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        
        $user->update($validated);
        
        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        
        $user->delete();
        
        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil dihapus.');
    }
}
