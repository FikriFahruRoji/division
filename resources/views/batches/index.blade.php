@extends('layouts.main')

@section('title', 'Batch Dokumen')

@section('content')
<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
        <h1 class="text-text-main dark:text-white text-2xl font-bold">Batch Dokumen</h1>
        <p class="text-sm text-text-secondary mt-1">Kelola batch dokumen dengan 1 QR code bersama</p>
    </div>
    <a href="{{ route('batches.create') }}" class="flex items-center justify-center gap-2 rounded-lg bg-primary hover:bg-primary-hover transition-colors h-10 px-5 text-text-main text-sm font-bold shadow-sm">
        <span class="material-symbols-outlined text-lg">add</span>
        Buat Batch Baru
    </a>
</div>

<!-- Search & Filter -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3 p-4">
        <div class="flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama batch..." 
                class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
        </div>
        <select name="status" class="px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="">Semua Status</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Diarsipkan</option>
        </select>
        <button type="submit" class="flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
            <span class="material-symbols-outlined text-lg">search</span>
            Cari
        </button>
    </form>
</div>

<!-- Batch List -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
    @if($batches->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-border-color dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/50">
                    <th class="text-left px-6 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Nama Batch</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Jenis</th>
                    <th class="text-center px-6 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Dokumen</th>
                    <th class="text-center px-6 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Tanggal</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-color dark:divide-zinc-700">
                @foreach($batches as $batch)
                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/50 transition-colors">
                    <td class="px-6 py-4">
                        <a href="{{ route('batches.show', $batch) }}" class="font-medium text-text-main dark:text-white hover:text-primary transition-colors">
                            {{ $batch->name }}
                        </a>
                        @if($batch->description)
                        <p class="text-xs text-text-secondary mt-0.5 truncate max-w-xs">{{ $batch->description }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-text-secondary">{{ $batch->doc_type }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-xs font-medium">
                            <span class="material-symbols-outlined text-sm">description</span>
                            {{ $batch->documents_count }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($batch->status === 'active')
                            <span class="inline-flex px-2.5 py-1 rounded-full bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-xs font-medium">Aktif</span>
                        @elseif($batch->status === 'draft')
                            <span class="inline-flex px-2.5 py-1 rounded-full bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 text-xs font-medium">Draft</span>
                        @else
                            <span class="inline-flex px-2.5 py-1 rounded-full bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-gray-300 text-xs font-medium">Arsip</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-text-secondary text-xs">{{ $batch->created_at->format('d M Y') }}</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('batches.show', $batch) }}" class="p-2 hover:bg-gray-100 dark:hover:bg-zinc-600 rounded-lg transition-colors text-text-secondary hover:text-primary" title="Detail">
                                <span class="material-symbols-outlined text-lg">visibility</span>
                            </a>
                            <a href="{{ route('batches.qr', $batch) }}" class="p-2 hover:bg-gray-100 dark:hover:bg-zinc-600 rounded-lg transition-colors text-text-secondary hover:text-primary" title="Download QR">
                                <span class="material-symbols-outlined text-lg">qr_code</span>
                            </a>
                            <form id="delete-form-{{ $batch->id }}" action="{{ route('batches.destroy', $batch) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="button" onclick="showDeleteModal('{{ $batch->id }}', '{{ $batch->name }}', {{ $batch->documents_count }})" class="p-2 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors text-text-secondary hover:text-red-500" title="Hapus">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="px-6 py-4 border-t border-border-color dark:border-zinc-700">
        {{ $batches->links() }}
    </div>
    @else
    <div class="px-6 py-16 text-center">
        <span class="material-symbols-outlined text-5xl text-text-secondary mb-3 opacity-50">inventory_2</span>
        <p class="text-text-secondary text-sm">Belum ada batch dokumen</p>
        <a href="{{ route('batches.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-bold transition-colors">
            <span class="material-symbols-outlined text-lg">add</span>
            Buat Batch Pertama
        </a>
    </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-xl max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center gap-4 mb-4">
            <div class="size-12 rounded-full bg-red-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-red-600">delete_forever</span>
            </div>
            <h3 class="text-lg font-bold text-text-main dark:text-white">Hapus Batch</h3>
        </div>
        <div class="mb-6">
            <p class="text-sm text-text-secondary mb-3">Apakah Anda yakin ingin menghapus batch ini?</p>
            <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-sm text-red-800 dark:text-red-200">
                    <strong id="delete-batch-name"></strong><br>
                    <span class="text-xs opacity-75" id="delete-batch-docs"></span>
                </p>
            </div>
            <p class="mt-3 text-xs text-red-600 dark:text-red-400 flex items-start gap-1.5">
                <span class="material-symbols-outlined text-sm flex-shrink-0">warning</span>
                Tindakan ini akan menghapus batch beserta <strong>semua dokumen</strong> di dalamnya secara permanen dan tidak dapat dibatalkan.
            </p>
        </div>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="hideDeleteModal()" 
                class="px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600 transition-colors">
                Batal
            </button>
            <button type="button" id="confirm-delete-btn"
                class="px-4 py-2.5 bg-red-500 hover:bg-red-600 rounded-lg text-sm font-semibold text-white transition-colors">
                Ya, Hapus Batch
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let deleteBatchId = null;

    function showDeleteModal(batchId, batchName, docCount) {
        deleteBatchId = batchId;
        document.getElementById('delete-batch-name').textContent = batchName;
        document.getElementById('delete-batch-docs').textContent = docCount + ' dokumen akan ikut dihapus';
        document.getElementById('delete-modal').classList.remove('hidden');
    }

    function hideDeleteModal() {
        document.getElementById('delete-modal').classList.add('hidden');
        deleteBatchId = null;
    }

    document.getElementById('confirm-delete-btn').addEventListener('click', function() {
        if (deleteBatchId) {
            document.getElementById('delete-form-' + deleteBatchId).submit();
        }
    });

    // Close modal on backdrop click
    document.getElementById('delete-modal').addEventListener('click', function(e) {
        if (e.target === this) hideDeleteModal();
    });
</script>
@endpush
