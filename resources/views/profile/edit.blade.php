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

    <!-- Delete Account -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-red-200 dark:border-red-900/50 overflow-hidden">
        <div class="p-6">
            <h3 class="text-lg font-bold text-red-600 dark:text-red-400 mb-1">Hapus Akun</h3>
            <p class="text-sm text-text-secondary mb-6">Setelah akun Anda dihapus, semua sumber daya dan data akan dihapus secara permanen.</p>
            
            <button type="button" onclick="document.getElementById('delete-modal').classList.remove('hidden')" 
                class="flex items-center gap-2 px-4 py-2.5 bg-red-500 hover:bg-red-600 rounded-lg text-white text-sm font-semibold transition-colors">
                <span class="material-symbols-outlined text-lg">delete</span>
                Hapus Akun
            </button>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div id="delete-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-zinc-800 rounded-xl max-w-md w-full mx-4 p-6 shadow-2xl">
        <div class="flex items-center gap-4 mb-4">
            <div class="size-12 rounded-full bg-red-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-red-600">warning</span>
            </div>
            <h3 class="text-lg font-bold text-text-main dark:text-white">Hapus Akun</h3>
        </div>
        <p class="text-sm text-text-secondary mb-4">Apakah Anda yakin ingin menghapus akun? Tindakan ini tidak dapat dibatalkan.</p>
        
        <form method="post" action="{{ route('profile.destroy') }}">
            @csrf
            @method('delete')
            
            <div class="mb-4">
                <label for="delete_password" class="block text-sm font-medium text-text-main dark:text-white mb-2">Password</label>
                <input id="delete_password" name="password" type="password" placeholder="Masukkan password untuk konfirmasi"
                    class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                @error('password', 'userDeletion')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('delete-modal').classList.add('hidden')" 
                    class="px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2.5 bg-red-500 hover:bg-red-600 rounded-lg text-white text-sm font-semibold">
                    Hapus Akun
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
