@extends('layouts.main')

@section('title', 'Daftar Dokumen')

@section('content')
<!-- Page Header -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
    <h1 class="text-text-main dark:text-white text-2xl font-bold">Daftar Dokumen</h1>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 p-6 mb-6">
    <form action="{{ route('documents.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label for="search" class="block text-sm font-medium text-text-secondary mb-2">Cari</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary text-lg">search</span>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nomor atau judul dokumen..." 
                    class="w-full pl-10 pr-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary text-sm">
            </div>
        </div>
        <div class="w-48">
            <label for="status" class="block text-sm font-medium text-text-secondary mb-2">Status</label>
            <select name="status" id="status" class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                <option value="">Semua Status</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="pending_signature" {{ request('status') == 'pending_signature' ? 'selected' : '' }}>Menunggu TTD</option>
                <option value="signed_valid" {{ request('status') == 'signed_valid' ? 'selected' : '' }}>Valid</option>
                <option value="superseded" {{ request('status') == 'superseded' ? 'selected' : '' }}>Digantikan</option>
                <option value="revoked" {{ request('status') == 'revoked' ? 'selected' : '' }}>Dicabut</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
                <span class="material-symbols-outlined text-lg">filter_list</span>
                Filter
            </button>
            <a href="{{ route('documents.index') }}" class="px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600 transition-colors">
                Reset
            </a>
        </div>
    </form>
</div>

<!-- Documents Table -->
<div class="w-full bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[800px]">
            <thead>
                <tr class="bg-primary/5 dark:bg-primary/10 border-b border-border-color dark:border-zinc-700">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">No. Dokumen</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Judul</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Pembuat</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Jenis</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Penandatangan</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Tanggal</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-text-secondary uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-color dark:divide-zinc-700">
                @forelse($documents as $document)
                <tr class="group hover:bg-gray-50 dark:hover:bg-zinc-700/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-text-main dark:text-white">{{ $document->doc_number }}</div>
                        <div class="text-xs text-text-secondary">v{{ $document->version }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-red-50 rounded-lg text-red-500">
                                <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-text-main dark:text-white">{{ Str::limit($document->title, 30) }}</div>
                                <div class="text-xs text-text-secondary">{{ $document->unit }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-text-main dark:text-white">{{ $document->creator->name }}</div>
                        <div class="text-xs text-text-secondary">{{ $document->creator->email }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                        {{ $document->doc_type }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($document->status === 'draft')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">Draft</span>
                        @elseif($document->status === 'pending_signature')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/20 text-yellow-800 dark:text-yellow-200 border border-primary/20">Menunggu TTD</span>
                        @elseif($document->status === 'signed_valid')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 border border-green-200 dark:border-green-800">Valid</span>
                        @elseif($document->status === 'superseded')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300 border border-orange-200 dark:border-orange-800">Digantikan</span>
                        @elseif($document->status === 'revoked')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800">Dicabut</span>
                        @elseif($document->status === 'rejected')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800">Ditolak</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($document->signerAssignments->count() > 0)
                        <div class="space-y-1">
                            @foreach($document->signerAssignments->take(2) as $assignment)
                            <div class="text-xs">
                                <span class="text-text-main dark:text-white font-medium">{{ $assignment->signer->name ?? 'User Dihapus' }}</span>
                                @if($assignment->signer && $assignment->signer->department)
                                <span class="text-text-secondary">({{ $assignment->signer->department->code }})</span>
                                @endif
                            </div>
                            @endforeach
                            @if($document->signerAssignments->count() > 2)
                            <div class="text-xs text-text-secondary">+{{ $document->signerAssignments->count() - 2 }} lainnya</div>
                            @endif
                        </div>
                        @else
                        <span class="text-xs text-text-secondary">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-text-main dark:text-gray-300">{{ $document->doc_date->format('d M Y') }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('documents.show', $document) }}" class="text-text-secondary hover:text-primary transition-colors" title="Detail">
                                <span class="material-symbols-outlined text-[20px]">visibility</span>
                            </a>
                            @if($document->isDraft())
                            <a href="{{ route('documents.edit', $document) }}" class="text-text-secondary hover:text-primary transition-colors" title="Edit">
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </a>
                            @endif
                            <a href="{{ route('documents.preview', $document) }}" target="_blank" class="text-text-secondary hover:text-primary transition-colors" title="Preview">
                                <span class="material-symbols-outlined text-[20px]">open_in_new</span>
                            </a>
                            @if($document->status === 'signed_valid')
                            <button type="button" onclick="confirmDownload('{{ route('documents.download', $document) }}', '{{ $document->doc_number }}')" class="text-text-secondary hover:text-primary transition-colors" title="Download">
                                <span class="material-symbols-outlined text-[20px]">download</span>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center">
                        <span class="material-symbols-outlined text-5xl text-gray-300 mb-3">folder_open</span>
                        <h3 class="mt-2 text-sm font-medium text-text-main dark:text-white">Belum ada dokumen</h3>
                        <p class="mt-1 text-sm text-text-secondary">Mulai dengan mengunggah dokumen baru.</p>
                        <div class="mt-6">
                            <a href="{{ route('documents.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
                                <span class="material-symbols-outlined text-lg">add</span>
                                Unggah Dokumen
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($documents->hasPages())
    <div class="bg-white dark:bg-zinc-800 px-6 py-4 border-t border-border-color dark:border-zinc-700">
        {{ $documents->withQueryString()->links() }}
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
    function confirmDownload(url, docNumber) {
        const modal = document.getElementById('download-modal');
        const link = document.getElementById('download-confirm-btn');
        const msg = document.getElementById('download-doc-number');
        
        link.href = url;
        msg.textContent = docNumber + '.pdf';
        modal.classList.remove('hidden');
    }
</script>
@endsection
