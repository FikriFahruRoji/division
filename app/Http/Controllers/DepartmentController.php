<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments.
     */
    public function index(Request $request)
    {
        // Only super_admin can access department management
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat mengelola departemen.');
        }

        $query = Department::withCount('users');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $departments = $query->latest()->paginate(15);

        return view('departments.index', compact('departments'));
    }

    /**
     * Show the form for creating a new department.
     */
    public function create()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        return view('departments.create');
    }

    /**
     * Store a newly created department.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code|regex:/^[A-Z0-9\-]+$/',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
        ], [
            'code.regex' => 'Kode hanya boleh huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode departemen sudah digunakan.',
        ]);

        Department::create($validated);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Departemen berhasil ditambahkan.');
    }

    /**
     * Display the specified department.
     */
    public function show(Department $department)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $department->load(['users', 'admins']);

        return view('departments.show', compact('department'));
    }

    /**
     * Show the form for editing the specified department.
     */
    public function edit(Department $department)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        // Get admins to assign to this department
        $admins = User::where('role', 'admin')->get();
        $assignedAdminIds = $department->admins->pluck('id')->toArray();

        return view('departments.edit', compact('department', 'admins', 'assignedAdminIds'));
    }

    /**
     * Update the specified department.
     */
    public function update(Request $request, Department $department)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code,' . $department->id . '|regex:/^[A-Z0-9\-]+$/',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'admin_ids' => 'nullable|array',
            'admin_ids.*' => 'exists:users,id',
        ], [
            'code.regex' => 'Kode hanya boleh huruf kapital, angka, dan tanda hubung.',
        ]);

        $department->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'],
            'status' => $validated['status'],
        ]);

        // Sync admin assignments
        $department->admins()->sync($validated['admin_ids'] ?? []);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Departemen berhasil diperbarui.');
    }

    /**
     * Remove the specified department.
     */
    public function destroy(Department $department)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        // Check if department has users
        if ($department->users()->count() > 0) {
            return back()->with('error', 'Tidak dapat menghapus departemen yang masih memiliki user.');
        }

        $department->delete();

        return redirect()
            ->route('departments.index')
            ->with('success', 'Departemen berhasil dihapus.');
    }
}
