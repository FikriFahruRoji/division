@extends('layouts.main')

@section('title', 'Autentikasi Dua Faktor')

@section('content')
<!-- Page Header -->
<div class="mb-8">
    <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-1 text-sm text-text-secondary hover:text-text-main dark:hover:text-white mb-2">
        <span class="material-symbols-outlined text-lg">arrow_back</span>
        Profile
    </a>
    <h1 class="text-text-main dark:text-white text-2xl font-bold">Autentikasi Dua Faktor</h1>
    <p class="text-text-secondary">Kode dari aplikasi authenticator di ponsel Anda wajib dimasukkan setiap kali menandatangani atau menolak dokumen.</p>
</div>

<div class="max-w-3xl space-y-6">
    @if($user->hasTwoFactorEnabled())
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="size-10 rounded-full bg-green-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-green-600">verified_user</span>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-text-main dark:text-white">Aktif</h3>
                    <p class="text-sm text-text-secondary">Diaktifkan {{ $user->two_factor_confirmed_at->format('d M Y H:i') }}</p>
                </div>
            </div>

            <p class="text-sm text-text-secondary mb-4">Jika ponsel Anda hilang atau diganti, minta administrator untuk mereset autentikasi dua faktor Anda, lalu aktifkan kembali di halaman ini.</p>

            <form method="post" action="{{ route('two-factor.destroy') }}" class="space-y-4">
                @csrf
                @method('delete')
                <div>
                    <label for="code" class="block text-sm font-medium text-text-main dark:text-white mb-2">Kode authenticator untuk menonaktifkan</label>
                    <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required
                        class="w-48 px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white tracking-[0.3em] focus:outline-none focus:ring-2 focus:ring-red-500">
                    @error('code')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="flex items-center gap-2 px-4 py-2.5 bg-red-500 hover:bg-red-600 rounded-lg text-white text-sm font-semibold transition-colors">
                    <span class="material-symbols-outlined text-lg">lock_open</span>
                    Nonaktifkan
                </button>
            </form>
        </div>
    </div>
    @else
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
        <div class="p-6">
            <h3 class="text-lg font-bold text-text-main dark:text-white mb-1">Aktifkan</h3>
            <ol class="text-sm text-text-secondary mb-6 list-decimal list-inside space-y-1">
                <li>Pasang aplikasi authenticator (Google Authenticator, Microsoft Authenticator, atau Authy) di ponsel Anda.</li>
                <li>Pindai kode QR di bawah, atau masukkan kunci secara manual.</li>
                <li>Masukkan 6 digit kode yang muncul di aplikasi untuk konfirmasi.</li>
            </ol>

            <div class="flex flex-col sm:flex-row gap-6 items-start mb-6">
                <img src="{{ $qrCode }}" alt="Kode QR untuk aplikasi authenticator" class="size-44 rounded-lg border border-border-color dark:border-zinc-600 bg-white">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-text-secondary mb-1">Kunci manual</p>
                    <code class="block break-all text-sm font-mono text-text-main dark:text-white bg-background-light dark:bg-zinc-700 rounded px-3 py-2">{{ trim(chunk_split($secret, 4, ' ')) }}</code>
                    <p class="text-xs text-text-secondary mt-2">Jangan bagikan kode QR atau kunci ini kepada siapa pun.</p>
                </div>
            </div>

            <form method="post" action="{{ route('two-factor.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="code" class="block text-sm font-medium text-text-main dark:text-white mb-2">Kode dari aplikasi</label>
                    <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus
                        class="w-48 px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white tracking-[0.3em] focus:outline-none focus:ring-2 focus:ring-primary">
                    @error('code')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
                    <span class="material-symbols-outlined text-lg">verified_user</span>
                    Aktifkan
                </button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
