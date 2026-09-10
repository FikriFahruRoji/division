@extends('layouts.main')

@section('title', 'Dokumen Saya')

@section('content')
<!-- Page Header -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-text-main dark:text-white text-xl sm:text-2xl font-bold">Dokumen Saya</h1>
        <p class="text-sm text-text-secondary">Kelola dokumen yang Anda upload dan tandatangani</p>
    </div>
    <a href="{{ route('signer-documents.create') }}" class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors shadow-sm">
        <span class="material-symbols-outlined text-lg">add</span>
        Unggah Dokumen
    </a>
</div>

<!-- Tabs -->
<div class="mb-6 overflow-x-auto">
    <nav class="flex gap-2 min-w-max" aria-label="Tabs">
        <a href="{{ route('signer-documents.index', ['tab' => 'all']) }}" 
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg transition-colors {{ $tab === 'all' ? 'bg-primary text-text-main' : 'bg-white dark:bg-zinc-800 text-text-secondary hover:bg-gray-50 dark:hover:bg-zinc-700 border border-border-color dark:border-zinc-700' }}">
            <span class="material-symbols-outlined text-lg">folder</span>
            Semua
            <span class="px-2 py-0.5 text-xs rounded-full {{ $tab === 'all' ? 'bg-white/30' : 'bg-gray-100 dark:bg-zinc-700' }}">{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('signer-documents.index', ['tab' => 'uploaded']) }}" 
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg transition-colors {{ $tab === 'uploaded' ? 'bg-primary text-text-main' : 'bg-white dark:bg-zinc-800 text-text-secondary hover:bg-gray-50 dark:hover:bg-zinc-700 border border-border-color dark:border-zinc-700' }}">
            <span class="material-symbols-outlined text-lg">upload_file</span>
            Diunggah
            <span class="px-2 py-0.5 text-xs rounded-full {{ $tab === 'uploaded' ? 'bg-white/30' : 'bg-gray-100 dark:bg-zinc-700' }}">{{ $counts['uploaded'] }}</span>
        </a>
        <a href="{{ route('signer-documents.index', ['tab' => 'signed']) }}" 
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg transition-colors {{ $tab === 'signed' ? 'bg-green-500 text-white' : 'bg-white dark:bg-zinc-800 text-text-secondary hover:bg-gray-50 dark:hover:bg-zinc-700 border border-border-color dark:border-zinc-700' }}">
            <span class="material-symbols-outlined text-lg">check_circle</span>
            Sudah TTD
            <span class="px-2 py-0.5 text-xs rounded-full {{ $tab === 'signed' ? 'bg-white/30' : 'bg-gray-100 dark:bg-zinc-700' }}">{{ $counts['signed'] }}</span>
        </a>
        <a href="{{ route('signer-documents.index', ['tab' => 'pending']) }}" 
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg transition-colors {{ $tab === 'pending' ? 'bg-yellow-500 text-white' : 'bg-white dark:bg-zinc-800 text-text-secondary hover:bg-gray-50 dark:hover:bg-zinc-700 border border-border-color dark:border-zinc-700' }}">
            <span class="material-symbols-outlined text-lg">pending</span>
            Menunggu TTD
            <span class="px-2 py-0.5 text-xs rounded-full {{ $tab === 'pending' ? 'bg-white/30' : 'bg-gray-100 dark:bg-zinc-700' }}">{{ $counts['pending'] }}</span>
        </a>
    </nav>
</div>

<!-- Documents Table -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
    @if($documents->count() > 0)
    <!-- Desktop Table -->
    <div class="hidden sm:block overflow-x-auto">
        <table class="w-full min-w-[700px]">
            <thead>
                <tr class="bg-primary/5 dark:bg-primary/10 border-b border-border-color dark:border-zinc-700">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">No. Dokumen</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Judul</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Pembuat</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Tanggal</th>
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
                            <div class="p-2 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <span class="material-symbols-outlined text-red-500">picture_as_pdf</span>
                            </div>
                            <span class="text-sm font-medium text-text-main dark:text-white">{{ $document->doc_number }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-text-main dark:text-white">{{ Str::limit($document->title, 35) }}</div>
                        @if($document->created_by === auth()->id())
                        <span class="text-xs text-primary">Anda yang upload</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                        {{ $document->creator->name ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-wrap gap-1">
                            @if($document->status === 'draft')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Draft</span>
                            @elseif($document->status === 'pending_signature')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/20 text-yellow-800 dark:text-yellow-200">Menunggu TTD</span>
                            @elseif($document->status === 'signed_valid')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Valid</span>
                            @elseif($document->status === 'revoked')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">Dicabut</span>
                            @endif
                            @if($myAssignment)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $myAssignment->status === 'signed' ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300' }}">
                                {{ $myAssignment->status === 'signed' ? '✓ Sudah TTD' : 'Belum TTD' }}
                            </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                        {{ $document->created_at->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <div class="flex items-center justify-end gap-2">
                            @if($document->status === 'pending_signature' && $myAssignment && in_array($myAssignment->status, ['pending', 'notified']))
                            <a href="{{ route('signatures.show', $document) }}" class="flex items-center gap-1 px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded-lg transition-colors">
                                <span class="material-symbols-outlined text-sm">draw</span>
                                TTD
                            </a>
                            @endif
                            @php
                                $safeDocNumber = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($document->doc_number ?? ''));
                                $docPdfName = ($safeDocNumber ?: 'dokumen') . '_' . ($document->signed_file_path ? 'signed' : 'original') . '.pdf';
                            @endphp
                            @if($document->status === 'signed_valid')
                            <button type="button" onclick="confirmDownload('{{ route('documents.download', ['document' => $document, 'filename' => $docPdfName]) }}', '{{ $docPdfName }}')" class="text-text-secondary hover:text-primary transition-colors" title="Download">
                                <span class="material-symbols-outlined text-xl">download</span>
                            </button>
                            @endif
                            <a href="{{ route('documents.show', $document) }}" class="text-text-secondary hover:text-primary transition-colors" title="Lihat Detail">
                                <span class="material-symbols-outlined text-xl">visibility</span>
                            </a>
                            <a href="{{ route('documents.preview', ['document' => $document, 'filename' => $docPdfName]) }}" target="_blank" class="text-text-secondary hover:text-primary transition-colors" title="Preview PDF">
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
        @endphp
        <div class="p-4">
            <div class="flex items-start gap-3 mb-3">
                <div class="p-2 bg-red-50 dark:bg-red-900/20 rounded-lg flex-shrink-0">
                    <span class="material-symbols-outlined text-red-500">picture_as_pdf</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-text-main dark:text-white truncate">{{ $document->title }}</p>
                    <p class="text-xs text-text-secondary">{{ $document->doc_number }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-1 mb-3">
                @if($document->status === 'pending_signature')
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary/20 text-yellow-800">Menunggu TTD</span>
                @elseif($document->status === 'signed_valid')
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Valid</span>
                @endif
                @if($myAssignment)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $myAssignment->status === 'signed' ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' }}">
                    {{ $myAssignment->status === 'signed' ? '✓ Sudah' : 'Belum TTD' }}
                </span>
                @endif
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs text-text-secondary">{{ $document->created_at->format('d M Y') }}</span>
                <div class="flex items-center gap-2">
                    @if($document->status === 'pending_signature' && $myAssignment && in_array($myAssignment->status, ['pending', 'notified']))
                    <a href="{{ route('signatures.show', $document) }}" class="flex items-center gap-1 px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded-lg">
                        <span class="material-symbols-outlined text-sm">draw</span>
                        TTD
                    </a>
                    @endif
                    <a href="{{ route('documents.show', $document) }}" class="p-2 text-text-secondary hover:text-primary" title="Lihat Detail">
                        <span class="material-symbols-outlined text-xl">visibility</span>
                    </a>
                    @php
                        $safeDocNumber = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($document->doc_number ?? ''));
                        $docPdfName = ($safeDocNumber ?: 'dokumen') . '_' . ($document->signed_file_path ? 'signed' : 'original') . '.pdf';
                    @endphp
                    <a href="{{ route('documents.preview', ['document' => $document, 'filename' => $docPdfName]) }}" target="_blank" class="p-2 text-text-secondary hover:text-primary" title="Preview PDF">
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
        <span class="material-symbols-outlined text-5xl text-gray-300 mb-3">folder_off</span>
        <h3 class="text-sm font-medium text-text-main dark:text-white mb-1">
            @if($tab === 'uploaded')
                Belum ada dokumen yang Anda unggah
            @elseif($tab === 'signed')
                Belum ada dokumen yang sudah Anda tandatangani
            @elseif($tab === 'pending')
                Tidak ada dokumen yang menunggu tanda tangan Anda
            @else
                Belum ada dokumen
            @endif
        </h3>
        <p class="text-sm text-text-secondary mb-6">Unggah dokumen pertama Anda untuk ditandatangani.</p>
        <a href="{{ route('signer-documents.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
            <span class="material-symbols-outlined text-lg">upload_file</span>
            Unggah Dokumen
        </a>
    </div>
    @endif
</div>
<!-- Download Modal -->
<div id="download-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-zinc-800 rounded-xl max-w-sm w-full mx-4 p-6 shadow-2xl animate-fade-in-up">
        <div class="flex items-center gap-4 mb-4">
            <div class="size-12 rounded-full bg-blue-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-blue-600">download</span>
            </div>
            <h3 class="text-lg font-bold text-text-main dark:text-white">Download Dokumen</h3>
        </div>
        <div class="mb-6">
            <p class="text-sm text-text-secondary mb-3">Apakah Anda ingin mengunduh dokumen ini?</p>
            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <p class="text-sm text-blue-800 dark:text-blue-200 break-all">
                    <strong id="download-doc-number">document.pdf</strong>
                </p>
            </div>
        </div>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="document.getElementById('download-modal').classList.add('hidden')" class="px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600">
                Batal
            </button>
            <a id="download-confirm-btn" href="#" onclick="document.getElementById('download-modal').classList.add('hidden')" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 rounded-lg text-white text-sm font-semibold transition-colors">
                Ya, Download
            </a>
        </div>
    </div>
</div>

<script>
    function confirmDownload(url, filename) {
        const modal = document.getElementById('download-modal');
        const link = document.getElementById('download-confirm-btn');
        const msg = document.getElementById('download-doc-number');
        
        link.href = url;
        link.setAttribute('download', filename);
        msg.textContent = filename;
        modal.classList.remove('hidden');
    }
</script>
@endsection
