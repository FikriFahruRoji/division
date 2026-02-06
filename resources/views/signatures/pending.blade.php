@extends('layouts.main')

@section('title', 'Dokumen Menunggu TTD')

@section('content')
<!-- Page Header -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
    <h1 class="text-text-main dark:text-white text-2xl font-bold">Dokumen Menunggu Tanda Tangan</h1>
</div>

<!-- Documents Table -->
<div class="w-full bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
    @if($documents->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full min-w-[700px]">
            <thead>
                <tr class="bg-primary/5 dark:bg-primary/10 border-b border-border-color dark:border-zinc-700">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Dokumen</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Dari</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Tanggal</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-text-secondary uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-color dark:divide-zinc-700">
                @foreach($documents as $document)
                <tr class="group hover:bg-gray-50 dark:hover:bg-zinc-700/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-primary/10 rounded-lg">
                                <span class="material-symbols-outlined text-primary">gesture</span>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-text-main dark:text-white">{{ $document->doc_number }}</div>
                                <div class="text-sm text-text-main dark:text-gray-300">{{ Str::limit($document->title, 40) }}</div>
                                <div class="text-xs text-text-secondary">{{ $document->doc_type }} • {{ $document->unit }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <div class="size-8 rounded-full bg-primary/20 flex items-center justify-center text-primary text-xs font-bold">
                                {{ substr($document->creator->name, 0, 1) }}
                            </div>
                            <span class="text-sm text-text-main dark:text-gray-300">{{ $document->creator->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-text-main dark:text-gray-300">{{ $document->doc_date->format('d M Y') }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <a href="{{ route('signatures.show', $document) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
                            <span class="material-symbols-outlined text-lg">draw</span>
                            Tinjau & Tandatangani
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    @if($documents->hasPages())
    <div class="bg-white dark:bg-zinc-800 px-6 py-4 border-t border-border-color dark:border-zinc-700">
        {{ $documents->links() }}
    </div>
    @endif
    @else
    <div class="py-16 text-center">
        <span class="material-symbols-outlined text-5xl text-green-400 mb-4">check_circle</span>
        <h3 class="text-lg font-medium text-text-main dark:text-white mb-2">Tidak ada dokumen menunggu</h3>
        <p class="text-sm text-text-secondary">Semua dokumen sudah ditandatangani atau belum ada yang diminta.</p>
    </div>
    @endif
</div>
@endsection
