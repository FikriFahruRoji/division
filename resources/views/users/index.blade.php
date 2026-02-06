@extends('layouts.main')

@section('title', 'Manajemen User')

@section('content')
<!-- Page Header -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
    <h1 class="text-text-main dark:text-white text-2xl font-bold">Manajemen User</h1>
    <a href="{{ route('users.create') }}" class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
        <span class="material-symbols-outlined text-lg">person_add</span>
        Tambah User
    </a>
</div>

<!-- Users Table -->
<div x-data="{ deleteModalOpen: false, deleteUrl: '', userName: '' }" class="w-full bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
    <!-- Filters -->
    <div class="p-6 border-b border-border-color dark:border-zinc-700">
        <form action="{{ route('users.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary text-lg">search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..." 
                        class="w-full pl-10 pr-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                </div>
            </div>
            @if(auth()->user()->isSuperAdmin() && $departments->count() > 0)
            <div class="w-44">
                <select name="department_id" class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                    <option value="">Semua Departemen</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="w-40">
                <select name="role" class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                    <option value="">Semua Role</option>
                    @if(auth()->user()->isSuperAdmin())
                        <option value="super_admin" {{ request('role') == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    @endif
                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="operator" {{ request('role') == 'operator' ? 'selected' : '' }}>Operator</option>
                    <option value="signer" {{ request('role') == 'signer' ? 'selected' : '' }}>Signer</option>
                </select>
            </div>
            <button type="submit" class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
                <span class="material-symbols-outlined text-lg">filter_list</span>
                Filter
            </button>
            @if(request()->hasAny(['search', 'department_id', 'role']))
            <a href="{{ route('users.index') }}" class="flex items-center gap-1 px-3 py-2.5 text-text-secondary hover:text-text-main text-sm transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
                Reset
            </a>
            @endif
        </form>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full min-w-[800px]">
            <thead>
                <tr class="bg-primary/5 dark:bg-primary/10 border-b border-border-color dark:border-zinc-700">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">User</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Departemen</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Jabatan</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Role</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-text-secondary uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-color dark:divide-zinc-700">
                @forelse($users as $user)
                <tr class="group hover:bg-gray-50 dark:hover:bg-zinc-700/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="size-10 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-text-main dark:text-white">{{ $user->name }}</div>
                                <div class="text-xs text-text-secondary">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($user->department)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 dark:bg-zinc-700 dark:text-gray-300">
                                {{ $user->department->code }}
                            </span>
                        @else
                            <span class="text-text-secondary text-sm">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-main dark:text-gray-300">
                        {{ $user->position ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($user->role === 'super_admin')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800">Super Admin</span>
                        @elseif($user->role === 'admin')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300 border border-purple-200 dark:border-purple-800">Admin</span>
                        @elseif($user->role === 'operator')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800">Operator</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 border border-green-200 dark:border-green-800">Signer</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($user->status === 'active')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                <span class="size-1.5 rounded-full bg-green-500"></span>
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                <span class="size-1.5 rounded-full bg-red-500"></span>
                                Nonaktif
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('users.edit', $user) }}" class="text-text-secondary hover:text-primary transition-colors" title="Edit">
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </a>
                            @can('delete', $user)
                                <button @click="deleteModalOpen = true; deleteUrl = '{{ route('users.destroy', $user) }}'; userName = '{{ $user->name }}'" class="text-text-secondary hover:text-red-500 transition-colors" title="Hapus">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <span class="material-symbols-outlined text-5xl text-gray-300 mb-3">group</span>
                        <p class="text-text-secondary">Belum ada user</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($users->hasPages())
    <div class="bg-white dark:bg-zinc-800 px-6 py-4 border-t border-border-color dark:border-zinc-700">
        {{ $users->withQueryString()->links() }}
    </div>
    @endif

    <!-- Delete Confirmation Modal -->
    <div x-show="deleteModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="deleteModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/50 transition-opacity" aria-hidden="true" @click="deleteModalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="deleteModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="bg-white dark:bg-zinc-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <span class="material-symbols-outlined text-red-600">warning</span>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-text-main dark:text-white" id="modal-title">
                                Hapus User
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-text-secondary">
                                    Apakah Anda yakin ingin menghapus user <span x-text="userName" class="font-bold text-text-main dark:text-gray-200"></span>? 
                                    <br><br>
                                    Data dokumen dan tanda tangan user ini akan tetap tersimpan untuk keperluan audit, namun user tidak akan bisa login lagi.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-zinc-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form :action="deleteUrl" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Hapus
                        </button>
                    </form>
                    <button @click="deleteModalOpen = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-zinc-600 shadow-sm px-4 py-2 bg-white dark:bg-zinc-700 text-base font-medium text-text-main dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
