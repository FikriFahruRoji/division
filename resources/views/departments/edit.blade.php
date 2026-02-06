@extends('layouts.main')

@section('title', 'Edit Departemen')

@section('content')
<!-- Page Header -->
<div class="flex items-center gap-4 mb-8">
    <a href="{{ route('departments.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
        <span class="material-symbols-outlined text-text-secondary">arrow_back</span>
    </a>
    <h1 class="text-text-main dark:text-white text-2xl font-bold">Edit Departemen</h1>
</div>

<!-- Form Card -->
<div class="max-w-2xl">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
        <div class="p-6">
            <form action="{{ route('departments.update', $department) }}" method="POST">
                @csrf @method('PUT')
                <div class="space-y-5">
                    <!-- Code -->
                    <div>
                        <label for="code" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Kode Departemen <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="code" id="code" value="{{ old('code', $department->code) }}" required
                            class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary uppercase">
                        @error('code')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Nama Departemen <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $department->name) }}" required
                            class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary">
                        @error('name')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    
                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Deskripsi
                        </label>
                        <textarea name="description" id="description" rows="3"
                            class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary resize-none">{{ old('description', $department->description) }}</textarea>
                    </div>
                    
                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Status
                        </label>
                        <select name="status" id="status"
                            class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="active" {{ old('status', $department->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ old('status', $department->status) == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    
                    <!-- Assign Admins -->
                    @if(count($admins) > 0)
                    <div>
                        <label class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Admin yang Mengelola
                        </label>
                        <div class="space-y-2 max-h-48 overflow-y-auto p-3 bg-background-light dark:bg-zinc-700 rounded-lg">
                            @foreach($admins as $admin)
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="admin_ids[]" value="{{ $admin->id }}"
                                    {{ in_array($admin->id, $assignedAdminIds) ? 'checked' : '' }}
                                    class="w-4 h-4 text-primary bg-white dark:bg-zinc-600 border-gray-300 dark:border-zinc-500 rounded focus:ring-primary">
                                <span class="text-text-main dark:text-white">{{ $admin->name }}</span>
                                <span class="text-text-secondary text-sm">({{ $admin->email }})</span>
                            </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-text-secondary">Admin yang dipilih dapat mengelola user di departemen ini</p>
                    </div>
                    @endif
                </div>
                
                <!-- Actions -->
                <div class="mt-8 flex justify-end gap-3">
                    <a href="{{ route('departments.index') }}" 
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
