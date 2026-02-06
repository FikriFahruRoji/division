@extends('layouts.main')

@section('title', 'Audit Logs')

@section('content')
<!-- Page Header -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
    <h1 class="text-text-main dark:text-white text-2xl font-bold">Audit Logs</h1>
</div>

<!-- Logs Table -->
<div class="w-full bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
    <!-- Filters -->
    <div class="p-6 border-b border-border-color dark:border-zinc-700">
        <form action="{{ route('audit-logs.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary text-lg">search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari deskripsi..." 
                        class="w-full pl-10 pr-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                </div>
            </div>
            <div class="w-40">
                <select name="action" class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                    <option value="">Semua Aksi</option>
                    @foreach($actions as $action)
                    <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
            </div>
            <div class="w-40">
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
                    <span class="material-symbols-outlined text-lg">filter_list</span>
                    Filter
                </button>
                <a href="{{ route('audit-logs.index') }}" class="px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600 transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full min-w-[800px]">
            <thead>
                <tr class="bg-primary/5 dark:bg-primary/10 border-b border-border-color dark:border-zinc-700">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Waktu</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">User</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Aksi</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Deskripsi</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-color dark:divide-zinc-700">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-text-main dark:text-gray-300">{{ $log->created_at->format('d/m/Y') }}</div>
                        <div class="text-xs text-text-secondary">{{ $log->created_at->format('H:i:s') }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($log->user)
                        <div class="flex items-center gap-2">
                            <div class="size-7 rounded-full bg-primary/20 flex items-center justify-center text-primary text-xs font-bold">
                                {{ substr($log->user->name, 0, 1) }}
                            </div>
                            <span class="text-sm text-text-main dark:text-gray-300">{{ $log->user->name }}</span>
                        </div>
                        @else
                        <span class="text-sm text-text-secondary">System</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/20 text-yellow-800 dark:text-yellow-200">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-text-main dark:text-gray-300">{{ Str::limit($log->description, 60) }}</div>
                        @if($log->document)
                        <div class="text-xs text-primary mt-1">{{ $log->document->doc_number }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                        {{ $log->ip_address }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <span class="material-symbols-outlined text-5xl text-gray-300 mb-3">history</span>
                        <p class="text-text-secondary">Belum ada log aktivitas</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($logs->hasPages())
    <div class="bg-white dark:bg-zinc-800 px-6 py-4 border-t border-border-color dark:border-zinc-700">
        {{ $logs->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
