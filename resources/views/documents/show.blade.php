@extends('layouts.main')

@section('title', 'Detail Dokumen')

@section('content')
@php
    $safeDocNumber = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($document->doc_number ?? ''));
    $docPdfName = ($safeDocNumber ?: 'dokumen') . '_' . ($document->signed_file_path ? 'signed' : 'original') . '.pdf';
@endphp
<!-- Page Header -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
    <div class="flex items-center gap-4">
        @if(auth()->user()->isSigner() && !auth()->user()->isAdmin() && !auth()->user()->isOperator())
        <a href="{{ route('signer-documents.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-text-secondary">arrow_back</span>
        </a>
        @else
        <a href="{{ route('documents.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-text-secondary">arrow_back</span>
        </a>
        @endif
        <div>
            <h1 class="text-text-main dark:text-white text-2xl font-bold">Detail Dokumen</h1>
            <p class="text-sm text-text-secondary">{{ $document->doc_number }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        @php
            $isRejected = $document->status === 'rejected';
            $lastRejection = $document->signerAssignments->where('status', 'rejected')->sortByDesc('updated_at')->first();
            $isFixed = $isRejected && $lastRejection && $document->updated_at->timestamp > ($lastRejection->updated_at->timestamp + 5);
        @endphp

        @if($document->isDraft() || $isRejected)
        <a href="{{ route('documents.edit', $document) }}" class="flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600 transition-colors">
            <span class="material-symbols-outlined text-lg">edit</span>
            Edit
        </a>
        
        @if($isRejected)
            <button type="button" 
                onclick="document.getElementById('finalize-modal').classList.remove('hidden')" 
                class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                {{ !$isFixed ? 'disabled' : '' }}
                title="{{ !$isFixed ? 'Lakukan perbaikan dokumen (Edit) terlebih dahulu sebelum mengajukan kembali' : 'Ajukan kembali dokumen' }}">
                <span class="material-symbols-outlined text-lg">replay</span>
                Ajukan Kembali
            </button>
        @else
            <button type="button" onclick="document.getElementById('finalize-modal').classList.remove('hidden')" class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
                <span class="material-symbols-outlined text-lg">send</span>
                Finalisasi
            </button>
        @endif
        @endif

        @php
            $hasSigned = $document->signerAssignments->where('signer_id', auth()->id())->where('status', 'signed')->isNotEmpty();
        @endphp
        @if($document->isSignedValid() && (auth()->user()->isAdmin() || auth()->user()->isOperator() || $document->creator_id === auth()->id() || $hasSigned))
        <button onclick="document.getElementById('revoke-modal').classList.remove('hidden')" class="flex items-center gap-2 px-4 py-2.5 bg-red-500 hover:bg-red-600 rounded-lg text-white text-sm font-semibold transition-colors">
            <span class="material-symbols-outlined text-lg">cancel</span>
            Cabut
        </button>
        @endif
        @php
            $isSafeToDelete = $document->status === 'draft' || $document->status === 'pending_signature' || $document->status === 'rejected';
            $canDelete = (auth()->user()->isAdmin() || auth()->user()->isOperator() || $document->creator_id === auth()->id()) && $isSafeToDelete;
        @endphp
        @if($canDelete)
        <button type="button" onclick="document.getElementById('delete-modal').classList.remove('hidden')" class="flex items-center gap-2 px-4 py-2.5 bg-white border border-red-200 text-red-600 hover:bg-red-50 rounded-lg text-sm font-semibold transition-colors">
            <span class="material-symbols-outlined text-lg">delete</span>
            Hapus
        </button>
        @endif
        <a href="{{ route('documents.preview', ['document' => $document, 'filename' => $docPdfName]) }}" target="_blank" class="flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600 transition-colors" title="Buka PDF di Tab Baru">
            <span class="material-symbols-outlined text-lg">open_in_new</span>
            Buka PDF
        </a>
        <button type="button" onclick="document.getElementById('download-modal').classList.remove('hidden')" class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
            <span class="material-symbols-outlined text-lg">download</span>
            Download
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Document Info -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-text-main dark:text-white">{{ $document->title }}</h3>
                        <p class="text-sm text-text-secondary">{{ $document->doc_number }} • v{{ $document->version }}</p>
                    </div>
                    @if($document->status === 'draft')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Draft</span>
                    @elseif($document->status === 'pending_signature')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary/20 text-yellow-800 dark:text-yellow-200">Menunggu TTD</span>
                    @elseif($document->status === 'signed_valid')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Valid</span>
                    @elseif($document->status === 'revoked')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">Dicabut</span>
                    @elseif($document->status === 'rejected')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">Ditolak</span>
                    @endif
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-sm">
                    <div class="bg-background-light dark:bg-zinc-700 rounded-lg p-4">
                        <dt class="font-medium text-text-secondary text-xs uppercase tracking-wider mb-1">Tanggal Dokumen</dt>
                        <dd class="text-text-main dark:text-white font-medium">{{ $document->doc_date->format('d F Y') }}</dd>
                    </div>
                    <div class="bg-background-light dark:bg-zinc-700 rounded-lg p-4">
                        <dt class="font-medium text-text-secondary text-xs uppercase tracking-wider mb-1">Jenis Dokumen</dt>
                        <dd class="text-text-main dark:text-white font-medium">{{ $document->doc_type }}</dd>
                    </div>
                    <div class="bg-background-light dark:bg-zinc-700 rounded-lg p-4">
                        <dt class="font-medium text-text-secondary text-xs uppercase tracking-wider mb-1">Unit/Bagian</dt>
                        <dd class="text-text-main dark:text-white font-medium">{{ $document->unit }}</dd>
                    </div>
                    <div class="bg-background-light dark:bg-zinc-700 rounded-lg p-4">
                        <dt class="font-medium text-text-secondary text-xs uppercase tracking-wider mb-1">Klasifikasi</dt>
                        <dd class="text-text-main dark:text-white font-medium capitalize">{{ $document->classification }}</dd>
                    </div>
                    <div class="bg-background-light dark:bg-zinc-700 rounded-lg p-4">
                        <dt class="font-medium text-text-secondary text-xs uppercase tracking-wider mb-1">Mode TTD</dt>
                        <dd class="text-text-main dark:text-white font-medium capitalize">{{ $document->sign_mode }}</dd>
                    </div>
                    <div class="bg-background-light dark:bg-zinc-700 rounded-lg p-4">
                        <dt class="font-medium text-text-secondary text-xs uppercase tracking-wider mb-1">Pembuat</dt>
                        <dd class="text-text-main dark:text-white font-medium">{{ $document->creator->name }}</dd>
                    </div>
                    <div class="md:col-span-2 bg-background-light dark:bg-zinc-700 rounded-lg p-4">
                        <dt class="font-medium text-text-secondary text-xs uppercase tracking-wider mb-1">Fingerprint</dt>
                        <dd class="text-text-main dark:text-white font-mono text-xs bg-white dark:bg-zinc-800 px-3 py-2 rounded inline-block">{{ $document->fingerprint }}</dd>
                    </div>
                    @if($document->notes)
                    <div class="md:col-span-2 bg-background-light dark:bg-zinc-700 rounded-lg p-4">
                        <dt class="font-medium text-text-secondary text-xs uppercase tracking-wider mb-1">Catatan</dt>
                        <dd class="text-text-main dark:text-white">{{ $document->notes }}</dd>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- PDF Preview -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="font-bold text-text-main dark:text-white">Preview Dokumen</h4>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('documents.preview', ['document' => $document, 'filename' => $docPdfName]) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-zinc-700 hover:bg-gray-200 dark:hover:bg-zinc-600 rounded-lg text-xs font-medium text-text-main dark:text-white transition-colors" title="Buka di Tab Baru">
                            <span class="material-symbols-outlined text-sm">open_in_new</span>
                            Buka di Tab Baru
                        </a>
                        <a href="{{ route('documents.download', ['document' => $document, 'filename' => $docPdfName]) }}" download="{{ $docPdfName }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary hover:bg-primary-hover rounded-lg text-xs font-semibold text-text-main transition-colors" title="Download File PDF">
                            <span class="material-symbols-outlined text-sm">download</span>
                            Download PDF
                        </a>
                    </div>
                </div>
                <div class="aspect-[3/4] bg-background-light dark:bg-zinc-700 rounded-lg overflow-hidden border border-border-color dark:border-zinc-600">
                    <iframe src="{{ route('documents.preview', ['document' => $document, 'filename' => $docPdfName]) }}" class="w-full h-full" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Signers Status -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
            <div class="p-6">
                <h4 class="font-bold text-text-main dark:text-white mb-4">Status Penandatangan</h4>
                <div class="space-y-3">
                    @foreach($document->signerAssignments as $assignment)
                    <div class="flex items-center justify-between p-3 bg-background-light dark:bg-zinc-700 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                @if($assignment->status === 'signed')
                                <div class="size-8 rounded-full bg-green-100 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-green-600 text-sm">check</span>
                                </div>
                                @elseif($assignment->status === 'rejected')
                                <div class="size-8 rounded-full bg-red-100 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-red-600 text-sm">close</span>
                                </div>
                                @elseif($assignment->status === 'notified')
                                <div class="size-8 rounded-full bg-primary/20 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary text-sm">schedule</span>
                                </div>
                                @else
                                <div class="size-8 rounded-full bg-gray-200 flex items-center justify-center">
                                    <span class="text-xs font-medium text-gray-600">{{ $assignment->order_index }}</span>
                                </div>
                                @endif
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-text-main dark:text-white">{{ $assignment->signer->name }}</p>
                                <p class="text-xs text-text-secondary">{{ $assignment->signer->position }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            @if($assignment->signed_at)
                            <p class="text-xs text-green-600 font-medium">Signed</p>
                            <p class="text-xs text-text-secondary">{{ $assignment->signed_at->format('d/m/Y H:i') }}</p>
                            @elseif($assignment->status === 'rejected')
                            <p class="text-xs text-red-600 font-medium">Ditolak</p>
                            @elseif($assignment->status === 'notified')
                            <p class="text-xs text-primary font-medium">Menunggu</p>
                            @else
                            <p class="text-xs text-text-secondary">Pending</p>
                            @endif
                        </div>
                    </div>
                    @if($assignment->status === 'rejected' && $assignment->rejection_reason)
                    <div class="ml-11 p-2 bg-red-50 dark:bg-red-900/20 rounded text-xs text-red-700 dark:text-red-300">
                        <strong>Alasan:</strong> {{ $assignment->rejection_reason }}
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>

        <!-- QR Code -->
        @if($document->signed_file_path)
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
            <div class="p-6 text-center">
                <h4 class="font-bold text-text-main dark:text-white mb-4">Verifikasi</h4>
                <div class="inline-block p-4 bg-white rounded-lg shadow-sm">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode(route('verify', $document->qr_token)) }}" alt="QR Code" class="mx-auto">
                </div>
                <p class="mt-3 text-xs text-text-secondary">Scan untuk verifikasi</p>
                <p class="mt-1 text-xs font-mono bg-background-light dark:bg-zinc-700 px-2 py-1 rounded inline-block">{{ $document->fingerprint }}</p>
            </div>
        </div>
        @endif

        <!-- Audit Log -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
            <div class="p-6">
                <h4 class="font-bold text-text-main dark:text-white mb-4">Riwayat Aktivitas</h4>
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($document->auditLogs->take(10) as $log)
                    <div class="flex items-start text-xs">
                        <div class="flex-shrink-0 size-2 rounded-full bg-primary mt-1.5"></div>
                        <div class="ml-3">
                            <p class="text-text-main dark:text-gray-300">{{ $log->description }}</p>
                            <p class="text-text-secondary">{{ $log->created_at->format('d/m/Y H:i') }} • {{ $log->user?->name ?? 'System' }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Finalize Modal -->
<div id="finalize-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-zinc-800 rounded-xl max-w-md w-full mx-4 p-6 shadow-2xl">
        <div class="flex items-center gap-4 mb-4">
            <div class="size-12 rounded-full bg-primary/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-primary">send</span>
            </div>
            <h3 class="text-lg font-bold text-text-main dark:text-white">Konfirmasi Finalisasi</h3>
        </div>
        <div class="mb-6">
            <p class="text-sm text-text-secondary mb-3">Apakah Anda yakin ingin memfinalisasi dokumen ini?</p>
            <div class="p-3 bg-primary/10 border border-primary/20 rounded-lg">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    <strong>Perhatian:</strong> Setelah difinalisasi, dokumen tidak dapat diedit lagi dan akan dikirimkan ke penandatangan.
                </p>
            </div>
        </div>
        <form action="{{ route('documents.finalize', $document) }}" method="POST">
            @csrf
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('finalize-modal').classList.add('hidden')" class="px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold">
                    Ya, Finalisasi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Revoke Modal -->
<div id="revoke-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-zinc-800 rounded-xl max-w-md w-full mx-4 p-6 shadow-2xl">
        <div class="flex items-center gap-4 mb-4">
            <div class="size-12 rounded-full bg-red-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-red-600">cancel</span>
            </div>
            <h3 class="text-lg font-bold text-text-main dark:text-white">Cabut Dokumen</h3>
        </div>
        <form action="{{ route('documents.revoke', $document) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="reason" class="block text-sm font-medium text-text-main dark:text-white mb-2">Alasan Pencabutan</label>
                <textarea name="reason" id="reason" rows="3" required
                    class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('revoke-modal').classList.add('hidden')" class="px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2.5 bg-red-500 hover:bg-red-600 rounded-lg text-white text-sm font-semibold">
                    Cabut Dokumen
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="delete-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-zinc-800 rounded-xl max-w-md w-full mx-4 p-6 shadow-2xl">
        <div class="flex items-center gap-4 mb-4">
            <div class="size-12 rounded-full bg-red-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-red-600">delete</span>
            </div>
            <h3 class="text-lg font-bold text-text-main dark:text-white">Hapus Dokumen</h3>
        </div>
        <div class="mb-6">
            <p class="text-sm text-text-secondary mb-3">Apakah Anda yakin ingin menghapus dokumen ini?</p>
            <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-sm text-red-800 dark:text-red-200">
                    <strong>Perhatian:</strong> Dokumen yang dihapus tidak dapat 
                    dikembalikan. Tindakan ini akan membatalkan proses tanda tangan yang sedang berjalan.
                </p>
            </div>
        </div>
        <form action="{{ route('documents.destroy', $document) }}" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('delete-modal').classList.add('hidden')" class="px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2.5 bg-red-500 hover:bg-red-600 rounded-lg text-white text-sm font-semibold">
                    Hapus Dokumen
                </button>
            </div>
        </form>
    </div>
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
                    <strong>{{ $docPdfName }}</strong>
                </p>
            </div>
        </div>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="document.getElementById('download-modal').classList.add('hidden')" class="px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600">
                Batal
            </button>
            <a href="{{ route('documents.preview', ['document' => $document, 'filename' => $docPdfName]) }}" 
               target="_blank"
               onclick="document.getElementById('download-modal').classList.add('hidden')" 
               class="px-4 py-2.5 bg-gray-100 dark:bg-zinc-700 hover:bg-gray-200 dark:hover:bg-zinc-600 rounded-lg text-text-main dark:text-white text-sm font-medium transition-colors">
                Buka Tab Baru
            </a>
            <a href="{{ route('documents.download', ['document' => $document, 'filename' => $docPdfName]) }}" 
               download="{{ $docPdfName }}"
               onclick="document.getElementById('download-modal').classList.add('hidden')" 
               class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 rounded-lg text-white text-sm font-semibold transition-colors">
                Ya, Download
            </a>
        </div>
    </div>
</div>
@endsection
