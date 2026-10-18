@extends('layouts.main')

@section('title', 'Detail User')

@section('content')
<!-- Page Header -->
<div class="flex items-center justify-between mb-8">
    <div class="flex items-center gap-4">
        <a href="{{ route('users.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-text-secondary">arrow_back</span>
        </a>
        <div>
            <h1 class="text-text-main dark:text-white text-2xl font-bold">Detail User</h1>
            <p class="text-text-secondary text-sm">Informasi lengkap akun pengguna</p>
        </div>
    </div>
    @can('update', $user)
    <a href="{{ route('users.edit', $user) }}" class="flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
        <span class="material-symbols-outlined text-lg">edit</span>
        Edit User
    </a>
    @endcan
</div>

<!-- Profile Overview Card -->
<div class="max-w-4xl space-y-6">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
        <div class="p-6">
            <div class="flex items-start gap-5 pb-6 border-b border-border-color dark:border-zinc-700">
                <div class="size-16 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold text-2xl">
                    {{ substr($user->name, 0, 1) }}
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-bold text-text-main dark:text-white">{{ $user->name }}</h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->isActive() ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </div>
                    <p class="text-text-secondary text-sm mt-1">{{ $user->email }}</p>
                    @if($user->email_verified_at)
                    <p class="text-xs text-green-600 dark:text-green-400 mt-0.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">verified</span> Email terverifikasi
                    </p>
                    @else
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">warning</span> Belum verifikasi email
                    </p>
                    @endif
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6">
                <div>
                    <label class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1">NIP / NIDN</label>
                    <p class="text-sm font-medium text-text-main dark:text-white font-mono">
                        {{ $user->nip_nidn ?: '-' }}
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1">Role</label>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-primary/10 text-primary">
                        {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                    </span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1">Departemen</label>
                    <p class="text-sm font-medium text-text-main dark:text-white">
                        {{ $user->department ? $user->department->name . ' (' . $user->department->code . ')' : 'Semua Departemen / Tidak Ada' }}
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1">Jabatan</label>
                    <p class="text-sm font-medium text-text-main dark:text-white">
                        {{ $user->position ?: '-' }}
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1">Autentikasi Dua Faktor</label>
                    <div class="flex items-center gap-3">
                        @if($user->hasTwoFactorEnabled())
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700">Aktif</span>
                            @can('resetTwoFactor', $user)
                            <form method="POST" action="{{ route('users.two-factor.reset', $user) }}" onsubmit="return confirm('Reset autentikasi dua faktor pengguna ini? Pengguna harus mendaftarkan ulang aplikasi authenticator sebelum dapat menandatangani.')">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Reset</button>
                            </form>
                            @endcan
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">Belum aktif</span>
                        @endif
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1">Terdaftar Sejak</label>
                    <p class="text-sm font-medium text-text-main dark:text-white">
                        {{ $user->created_at ? $user->created_at->format('d F Y H:i') : '-' }}
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1">Terakhir Diperbarui</label>
                    <p class="text-sm font-medium text-text-main dark:text-white">
                        {{ $user->updated_at ? $user->updated_at->format('d F Y H:i') : '-' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
