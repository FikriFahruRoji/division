@extends('layouts.main')

@section('title', 'Edit User')

@section('content')
<!-- Page Header -->
<div class="flex items-center gap-4 mb-8">
    <a href="{{ route('users.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
        <span class="material-symbols-outlined text-text-secondary">arrow_back</span>
    </a>
    <h1 class="text-text-main dark:text-white text-2xl font-bold">Edit User</h1>
</div>

<!-- Form Card -->
<div class="max-w-2xl">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
        <div class="p-6">
            <form action="{{ route('users.update', $user) }}" method="POST">
                @csrf @method('PUT')
                <div class="space-y-5">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Nama <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                            class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary">
                        @error('name')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                            class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary">
                        @error('email')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    
                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Password
                        </label>
                        <input type="password" name="password" id="password"
                            class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="mt-1 text-xs text-text-secondary">Kosongkan jika tidak ingin mengubah password</p>
                        @error('password')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    
                    <!-- Role & Status Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="role" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                                Role <span class="text-red-500">*</span>
                            </label>
                            <select name="role" id="role" required
                                class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="signer" {{ old('role', $user->role) == 'signer' ? 'selected' : '' }}>Signer</option>
                                <option value="operator" {{ old('role', $user->role) == 'operator' ? 'selected' : '' }}>Operator</option>
                                @if(auth()->user()->isSuperAdmin())
                                    <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="super_admin" {{ old('role', $user->role) == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                                @endif
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                                Status
                            </label>
                            <select name="status" id="status"
                                class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="inactive" {{ old('status', $user->status) == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Department -->
                    @if(auth()->user()->isSuperAdmin())
                        <!-- Super Admin can choose any department -->
                        <div>
                            <label for="department_id" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                                Departemen
                            </label>
                            <select name="department_id" id="department_id"
                                class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">-- Pilih Departemen --</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ old('department_id', $user->department_id) == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }} ({{ $department->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                        </div>
                    @else
                        <!-- Admin - Show their department (read-only) -->
                        @if(auth()->user()->department)
                        <div>
                            <label class="block text-sm font-medium text-text-main dark:text-white mb-2">
                                Departemen
                            </label>
                            <div class="w-full px-4 py-2.5 rounded-lg bg-gray-100 dark:bg-zinc-600 text-text-main dark:text-white">
                                {{ auth()->user()->department->name }} ({{ auth()->user()->department->code }})
                            </div>
                            <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                        </div>
                        @endif
                    @endif
                    
                    <!-- Position -->
                    <div>
                        <label for="position" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Jabatan
                        </label>
                        <input type="text" name="position" id="position" value="{{ old('position', $user->position) }}"
                            class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Contoh: Kepala Bagian, Staff">
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="mt-8 flex justify-end gap-3">
                    <a href="{{ route('users.index') }}" 
                        class="px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600 transition-colors">
                        Batal
                    </a>
                    <button type="submit" 
                        class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
                        <span class="material-symbols-outlined text-lg">save</span>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
