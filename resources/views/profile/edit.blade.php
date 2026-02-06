@extends('layouts.main')

@section('title', 'Profile')

@section('content')
<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-text-main dark:text-white text-2xl font-bold">Profile</h1>
    <p class="text-text-secondary">Kelola informasi akun Anda</p>
</div>

<div class="max-w-3xl space-y-6">
    <!-- Update Profile Information -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
        <div class="p-6">
            <h3 class="text-lg font-bold text-text-main dark:text-white mb-1">Informasi Profil</h3>
            <p class="text-sm text-text-secondary mb-6">Perbarui informasi profil dan alamat email akun Anda.</p>
            
            <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('patch')
                
                <div>
                    <label for="name" class="block text-sm font-medium text-text-main dark:text-white mb-2">Nama</label>
                    <input id="name" name="name" type="text" value="{{ old('name', auth()->user()->name) }}" required
                        class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    @error('name')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-text-main dark:text-white mb-2">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required
                        class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    @error('email')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>
                
                <div class="flex items-center gap-4">
                    <button type="submit" class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
                        <span class="material-symbols-outlined text-lg">save</span>
                        Simpan
                    </button>
                    @if (session('status') === 'profile-updated')
                    <p class="text-sm text-green-600">Tersimpan.</p>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Update Password -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
        <div class="p-6">
            <h3 class="text-lg font-bold text-text-main dark:text-white mb-1">Ubah Password</h3>
            <p class="text-sm text-text-secondary mb-6">Pastikan akun Anda menggunakan password yang panjang dan acak untuk keamanan.</p>
            
            <form method="post" action="{{ route('password.update') }}" class="space-y-5">
                @csrf
                @method('put')
                
                <div>
                    <label for="current_password" class="block text-sm font-medium text-text-main dark:text-white mb-2">Password Saat Ini</label>
                    <input id="current_password" name="current_password" type="password"
                        class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    @error('current_password', 'updatePassword')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-text-main dark:text-white mb-2">Password Baru</label>
                    <input id="password" name="password" type="password"
                        class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    @error('password', 'updatePassword')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>
                
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-text-main dark:text-white mb-2">Konfirmasi Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                        class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="flex items-center gap-4">
                    <button type="submit" class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
                        <span class="material-symbols-outlined text-lg">lock</span>
                        Ubah Password
                    </button>
                    @if (session('status') === 'password-updated')
                    <p class="text-sm text-green-600">Tersimpan.</p>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
