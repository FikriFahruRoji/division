@extends('layouts.main')

@section('title', 'Riwayat Tanda Tangan')

@section('content')
<!-- Page Header -->
<div class="mb-6 sm:mb-8">
    <h1 class="text-text-main dark:text-white text-xl sm:text-2xl font-bold">Riwayat Tanda Tangan</h1>
    <p class="text-sm text-text-secondary">Dokumen yang sudah Anda tandatangani</p>
</div>

<!-- History Table -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
    @if($documents->count() > 0)
    <!-- Desktop Table -->
    <div class="hidden sm:block overflow-x-auto">
        <table class="w-full min-w-[600px]">
            <thead>
                <tr class="bg-primary/5 dark:bg-primary/10 border-b border-border-color dark:border-zinc-700">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">No. Dokumen</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Judul</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Ditandatangani Pada</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-text-secondary uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-color dark:divide-zinc-700">
                @foreach($documents as $document)
                @php
                    $myAssignment = $document->signerAssignments->where('signer_id', auth()->id())->first();
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <span class="material-symbols-outlined text-green-500">verified</span>
                            </div>
                            <span class="text-sm font-medium text-text-main dark:text-white">{{ $document->doc_number }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-text-main dark:text-white">{{ Str::limit($document->title, 40) }}</div>
                        <div class="text-xs text-text-secondary">{{ $document->doc_type }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($document->status === 'signed_valid')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                            <span class="material-symbols-outlined text-sm">check_circle</span>
                            Valid
                        </span>
                        @elseif($document->status === 'revoked')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                            <span class="material-symbols-outlined text-sm">cancel</span>
                            Dicabut
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-primary/20 text-yellow-800 dark:text-yellow-200">
                            <span class="material-symbols-outlined text-sm">pending</span>
                            Pending
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-text-main dark:text-white">{{ $myAssignment?->signed_at?->format('d M Y') ?? '-' }}</div>
                        <div class="text-xs text-text-secondary">{{ $myAssignment?->signed_at?->format('H:i') ?? '' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <div class="flex items-center justify-end gap-2">
                            @php
                                $safeDocNumber = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($document->doc_number ?? ''));
                                $docPdfName = ($safeDocNumber ?: 'dokumen') . '_' . ($document->signed_file_path ? 'signed' : 'original') . '.pdf';
                            @endphp
                            @if($document->status === 'signed_valid')
                            <a href="{{ route('documents.download', ['document' => $document, 'filename' => $docPdfName]) }}" download="{{ $docPdfName }}" class="text-text-secondary hover:text-primary transition-colors" title="Download">
                                <span class="material-symbols-outlined text-xl">download</span>
                            </a>
                            @endif
                            <a href="{{ route('documents.preview', ['document' => $document, 'filename' => $docPdfName]) }}" target="_blank" class="text-text-secondary hover:text-primary transition-colors" title="Lihat">
                                <span class="material-symbols-outlined text-xl">open_in_new</span>
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Mobile Cards -->
    <div class="sm:hidden divide-y divide-border-color dark:divide-zinc-700">
        @foreach($documents as $document)
        @php
            $myAssignment = $document->signerAssignments->where('signer_id', auth()->id())->first();
            $safeDocNumber = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($document->doc_number ?? ''));
            $docPdfName = ($safeDocNumber ?: 'dokumen') . '_' . ($document->signed_file_path ? 'signed' : 'original') . '.pdf';
        @endphp
        <div class="p-4">
            <div class="flex items-start gap-3 mb-3">
                <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-lg flex-shrink-0">
                    <span class="material-symbols-outlined text-green-500">verified</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-text-main dark:text-white truncate">{{ $document->title }}</p>
                    <p class="text-xs text-text-secondary">{{ $document->doc_number }}</p>
                </div>
                @if($document->status === 'signed_valid')
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 flex-shrink-0">Valid</span>
                @elseif($document->status === 'revoked')
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 flex-shrink-0">Dicabut</span>
                @endif
            </div>
            <div class="flex items-center justify-between">
                <div class="text-xs text-text-secondary">
                    <span class="material-symbols-outlined text-sm align-middle mr-1">schedule</span>
                    {{ $myAssignment?->signed_at?->format('d M Y, H:i') ?? '-' }}
                </div>
                <div class="flex items-center gap-2">
                    @if($document->status === 'signed_valid')
                    <a href="{{ route('documents.download', ['document' => $document, 'filename' => $docPdfName]) }}" download="{{ $docPdfName }}" class="p-2 text-text-secondary hover:text-primary">
                        <span class="material-symbols-outlined text-xl">download</span>
                    </a>
                    @endif
                    <a href="{{ route('documents.preview', ['document' => $document, 'filename' => $docPdfName]) }}" target="_blank" class="p-2 text-text-secondary hover:text-primary">
                        <span class="material-symbols-outlined text-xl">open_in_new</span>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    
    @if($documents->hasPages())
    <div class="bg-white dark:bg-zinc-800 px-4 sm:px-6 py-4 border-t border-border-color dark:border-zinc-700">
        {{ $documents->links() }}
    </div>
    @endif
    @else
    <!-- Empty State -->
    <div class="text-center py-12 px-4">
        <span class="material-symbols-outlined text-5xl text-gray-300 mb-3">history</span>
        <h3 class="text-sm font-medium text-text-main dark:text-white mb-1">Belum ada riwayat</h3>
        <p class="text-sm text-text-secondary">Anda belum menandatangani dokumen apapun.</p>
    </div>
    @endif
</div>
@endsection
