@extends('layouts.main')

@section('title', 'Dashboard')

@section('content')
<!-- User Stats Section (Super Admin & Admin) -->
@if(auth()->user()->isSuperAdmin() || (auth()->user()->role === 'admin' && auth()->user()->department_id))
<section class="mb-6 sm:mb-10">
    @if(auth()->user()->isSuperAdmin())
    <!-- Super Admin Stats -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        <!-- Departments -->
        <div class="flex flex-col gap-2 rounded-xl bg-white dark:bg-zinc-800 p-4 sm:p-5 shadow-sm border border-border-color dark:border-zinc-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between gap-2">
                <p class="text-text-secondary dark:text-gray-400 text-[10px] sm:text-sm font-medium uppercase tracking-wider leading-tight">Departemen</p>
                <div class="p-1.5 sm:p-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                    <span class="material-symbols-outlined text-lg sm:text-2xl">corporate_fare</span>
                </div>
            </div>
            <p class="text-text-main dark:text-white text-2xl sm:text-3xl font-bold leading-tight">{{ $userStats['departments'] ?? 0 }}</p>
        </div>
        
        <!-- Total Users -->
        <div class="flex flex-col gap-2 rounded-xl bg-white dark:bg-zinc-800 p-4 sm:p-5 shadow-sm border border-border-color dark:border-zinc-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between gap-2">
                <p class="text-text-secondary dark:text-gray-400 text-[10px] sm:text-sm font-medium uppercase tracking-wider leading-tight">Total Pengguna</p>
                <div class="p-1.5 sm:p-2 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                    <span class="material-symbols-outlined text-lg sm:text-2xl">groups</span>
                </div>
            </div>
            <p class="text-text-main dark:text-white text-2xl sm:text-3xl font-bold leading-tight">{{ $userStats['total_users'] ?? 0 }}</p>
        </div>
        
        <!-- Admins -->
        <div class="flex flex-col gap-2 rounded-xl bg-white dark:bg-zinc-800 p-4 sm:p-5 shadow-sm border border-border-color dark:border-zinc-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between gap-2">
                <p class="text-text-secondary dark:text-gray-400 text-[10px] sm:text-sm font-medium uppercase tracking-wider leading-tight">Admin</p>
                <div class="p-1.5 sm:p-2 rounded-lg bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400">
                    <span class="material-symbols-outlined text-lg sm:text-2xl">admin_panel_settings</span>
                </div>
            </div>
            <p class="text-text-main dark:text-white text-2xl sm:text-3xl font-bold leading-tight">{{ $userStats['admins'] ?? 0 }}</p>
        </div>
        
        <!-- Operators -->
        <div class="flex flex-col gap-2 rounded-xl bg-white dark:bg-zinc-800 p-4 sm:p-5 shadow-sm border border-border-color dark:border-zinc-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between gap-2">
                <p class="text-text-secondary dark:text-gray-400 text-[10px] sm:text-sm font-medium uppercase tracking-wider leading-tight">Operator</p>
                <div class="p-1.5 sm:p-2 rounded-lg bg-cyan-50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400">
                    <span class="material-symbols-outlined text-lg sm:text-2xl">manage_accounts</span>
                </div>
            </div>
            <p class="text-text-main dark:text-white text-2xl sm:text-3xl font-bold leading-tight">{{ $userStats['operators'] ?? 0 }}</p>
        </div>
        
        <!-- Signers -->
        <div class="flex flex-col gap-2 rounded-xl bg-white dark:bg-zinc-800 p-4 sm:p-5 shadow-sm border border-border-color dark:border-zinc-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between gap-2">
                <p class="text-text-secondary dark:text-gray-400 text-[10px] sm:text-sm font-medium uppercase tracking-wider leading-tight">Signer</p>
                <div class="p-1.5 sm:p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                    <span class="material-symbols-outlined text-lg sm:text-2xl">draw</span>
                </div>
            </div>
            <p class="text-text-main dark:text-white text-2xl sm:text-3xl font-bold leading-tight">{{ $userStats['signers'] ?? 0 }}</p>
        </div>
    </div>
    @else
    <!-- Admin Stats (Only Operators & Signers in their department) -->
    <div class="flex gap-3 sm:gap-4 mb-4">
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-border-color dark:border-zinc-700 flex items-center gap-4">
            <div class="p-3 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg">
                <span class="material-symbols-outlined text-cyan-600 text-xl">manage_accounts</span>
            </div>
            <div>
                <p class="text-2xl font-bold text-text-main dark:text-white">{{ $userStats['operators'] ?? 0 }}</p>
                <p class="text-xs text-text-secondary">Operator</p>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-border-color dark:border-zinc-700 flex items-center gap-4">
            <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                <span class="material-symbols-outlined text-emerald-600 text-xl">draw</span>
            </div>
            <div>
                <p class="text-2xl font-bold text-text-main dark:text-white">{{ $userStats['signers'] ?? 0 }}</p>
                <p class="text-xs text-text-secondary">Signer</p>
            </div>
        </div>
        @if(auth()->user()->department)
        <div class="flex-1 bg-white dark:bg-zinc-800 rounded-xl p-4 border border-border-color dark:border-zinc-700 flex items-center gap-4">
            <div class="p-3 bg-primary/20 rounded-lg">
                <span class="material-symbols-outlined text-primary text-xl">corporate_fare</span>
            </div>
            <div>
                <p class="text-sm font-bold text-text-main dark:text-white">{{ auth()->user()->department->name }}</p>
                <p class="text-xs text-text-secondary">Departemen Anda</p>
            </div>
        </div>
        @endif
    </div>
    @endif
</section>
@endif

<!-- Document Stats Section -->
<section class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 mb-6 sm:mb-10">
    <!-- Card 1: Action Required / Menunggu TTD -->
    <div class="flex flex-col gap-2 sm:gap-3 rounded-xl bg-white dark:bg-zinc-800 p-4 sm:p-6 shadow-sm border border-border-color dark:border-zinc-700 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between gap-2">
            <p class="text-text-secondary dark:text-gray-400 text-[10px] sm:text-sm font-medium uppercase tracking-wider leading-tight">Menunggu TTD</p>
            <span class="material-symbols-outlined text-primary bg-primary/10 p-1.5 sm:p-2 rounded-lg text-lg sm:text-2xl">priority_high</span>
        </div>
        <p class="text-text-main dark:text-white text-2xl sm:text-3xl font-bold leading-tight">{{ $stats['pending'] ?? 0 }}</p>
        <div class="h-1 w-full bg-gray-100 dark:bg-zinc-700 rounded-full overflow-hidden">
            @php $pendingPercent = ($stats['total'] > 0) ? min(100, ($stats['pending'] / $stats['total']) * 100) : 0; @endphp
            <div class="h-full bg-primary rounded-full" style="width: {{ $pendingPercent }}%"></div>
        </div>
    </div>
    
    <!-- Card 2: Completed / Sudah TTD -->
    <div class="flex flex-col gap-2 sm:gap-3 rounded-xl bg-white dark:bg-zinc-800 p-4 sm:p-6 shadow-sm border border-border-color dark:border-zinc-700 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between gap-2">
            <p class="text-text-secondary dark:text-gray-400 text-[10px] sm:text-sm font-medium uppercase tracking-wider leading-tight">Selesai TTD</p>
            <span class="material-symbols-outlined text-green-600 bg-green-50 p-1.5 sm:p-2 rounded-lg text-lg sm:text-2xl">check_circle</span>
        </div>
        <p class="text-text-main dark:text-white text-2xl sm:text-3xl font-bold leading-tight">{{ $stats['signed'] ?? 0 }}</p>
        <p class="text-text-secondary dark:text-gray-500 text-[10px] sm:text-xs hidden sm:block">dari {{ $stats['total'] ?? 0 }} total</p>
    </div>
    
    <!-- Card 3: Total Documents (Admin Only) -->
    @if(auth()->user()->isAdmin())
    <div class="flex flex-col gap-2 sm:gap-3 rounded-xl bg-white dark:bg-zinc-800 p-4 sm:p-6 shadow-sm border border-border-color dark:border-zinc-700 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between gap-2">
            <p class="text-text-secondary dark:text-gray-400 text-[10px] sm:text-sm font-medium uppercase tracking-wider leading-tight">Total Dokumen</p>
            <span class="material-symbols-outlined text-blue-500 bg-blue-50 p-1.5 sm:p-2 rounded-lg text-lg sm:text-2xl">description</span>
        </div>
        <p class="text-text-main dark:text-white text-2xl sm:text-3xl font-bold leading-tight">{{ $stats['total'] ?? 0 }}</p>
        <p class="text-text-secondary dark:text-gray-500 text-[10px] sm:text-xs hidden sm:block">
            @if(auth()->user()->isSuperAdmin())
                Seluruh dokumen
            @else
                Departemen Anda
            @endif
        </p>
    </div>
    @endif
    
    <!-- Card 4: Revoked / Dicabut -->
    <div class="flex flex-col gap-2 sm:gap-3 rounded-xl bg-white dark:bg-zinc-800 p-4 sm:p-6 shadow-sm border border-border-color dark:border-zinc-700 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between gap-2">
            <p class="text-text-secondary dark:text-gray-400 text-[10px] sm:text-sm font-medium uppercase tracking-wider leading-tight">Dicabut</p>
            <span class="material-symbols-outlined text-red-500 bg-red-50 p-1.5 sm:p-2 rounded-lg text-lg sm:text-2xl">cancel</span>
        </div>
        <p class="text-text-main dark:text-white text-2xl sm:text-3xl font-bold leading-tight">{{ $stats['revoked'] ?? 0 }}</p>
        <p class="text-text-secondary dark:text-gray-500 text-[10px] sm:text-xs hidden sm:block">Tidak berlaku</p>
    </div>
</section>

<!-- Quick Actions for Signer -->
@if(auth()->user()->isSigner() && isset($pendingSignatures) && $pendingSignatures->count() > 0)
<section class="mb-10">
    <h3 class="text-text-main dark:text-white text-xl font-bold mb-4">Dokumen Menunggu Tanda Tangan Anda</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($pendingSignatures->take(3) as $doc)
        <a href="{{ route('signatures.show', $doc) }}" class="flex items-center gap-4 p-4 bg-white dark:bg-zinc-800 rounded-xl border border-border-color dark:border-zinc-700 hover:border-primary hover:shadow-md transition-all">
            <div class="p-3 bg-primary/10 rounded-lg">
                <span class="material-symbols-outlined text-primary">gesture</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-text-main dark:text-white truncate">{{ $doc->title }}</p>
                <p class="text-xs text-text-secondary">{{ $doc->doc_number }}</p>
            </div>
            <span class="material-symbols-outlined text-text-secondary">chevron_right</span>
        </a>
        @endforeach
    </div>
</section>
@endif

<!-- Table Section Header & Controls -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <h3 class="text-text-main dark:text-white text-2xl font-bold leading-tight tracking-tight">Dokumen Terbaru</h3>
</div>

<!-- Documents Table -->
<div class="w-full bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[700px]">
            <thead>
                <tr class="bg-primary/5 dark:bg-primary/10 border-b border-border-color dark:border-zinc-700">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider w-[25%]">Nama Dokumen</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider w-[15%]">Pembuat</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider w-[10%]">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider w-[25%]">Penandatangan</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider w-[15%]">Tanggal</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-text-secondary uppercase tracking-wider w-[10%]">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-color dark:divide-zinc-700">
                @forelse($documents as $document)
                <tr class="group hover:bg-gray-50 dark:hover:bg-zinc-700/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-red-50 rounded-lg text-red-500">
                                <span class="material-symbols-outlined">picture_as_pdf</span>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-text-main dark:text-white">{{ Str::limit($document->title, 30) }}</div>
                                <div class="text-xs text-text-secondary">{{ $document->doc_number }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-text-main dark:text-white">{{ $document->creator->name }}</div>
                        <div class="text-xs text-text-secondary">{{ $document->creator->email }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($document->status === 'draft')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">Draft</span>
                        @elseif($document->status === 'pending_signature')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/20 text-yellow-800 dark:text-yellow-200 border border-primary/20">Pending</span>
                        @elseif($document->status === 'signed_valid')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 border border-green-200 dark:border-green-800">Valid</span>
                        @elseif($document->status === 'revoked')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800">Dicabut</span>
                        @elseif($document->status === 'rejected')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800">Ditolak</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ ucfirst($document->status) }}</span>
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
                        <div class="text-sm text-text-main dark:text-gray-300">{{ $document->created_at->format('d M Y') }}</div>
                        <div class="text-xs text-text-secondary">{{ $document->created_at->format('H:i') }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                            @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
                            <a href="{{ route('documents.show', $document) }}" class="text-text-secondary hover:text-primary transition-colors" title="Detail">
                                <span class="material-symbols-outlined text-[20px]">visibility</span>
                            </a>
                            @endif
                            @php
                                $safeDocNumber = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($document->doc_number ?? ''));
                                $docPdfName = ($safeDocNumber ?: 'dokumen') . '_' . ($document->signed_file_path ? 'signed' : 'original') . '.pdf';
                            @endphp
                            <a href="{{ route('documents.preview', ['document' => $document, 'filename' => $docPdfName]) }}" target="_blank" class="text-text-secondary hover:text-primary transition-colors" title="Preview">
                                <span class="material-symbols-outlined text-[20px]">open_in_new</span>
                            </a>
                            @if($document->status === 'signed_valid')
                            <button type="button" onclick="confirmDownload('{{ route('documents.download', ['document' => $document, 'filename' => $docPdfName]) }}', '{{ $docPdfName }}')" class="text-text-secondary hover:text-primary transition-colors" title="Download">
                                <span class="material-symbols-outlined text-[20px]">download</span>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <span class="material-symbols-outlined text-5xl text-gray-300 mb-3">folder_open</span>
                        <p class="text-text-secondary">Belum ada dokumen</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($documents instanceof \Illuminate\Pagination\LengthAwarePaginator && $documents->hasPages())
    <!-- Pagination -->
    <div class="bg-white dark:bg-zinc-800 px-6 py-4 border-t border-border-color dark:border-zinc-700 flex items-center justify-between">
        <div class="text-sm text-text-secondary dark:text-gray-400">
            Showing <span class="font-medium text-text-main dark:text-white">{{ $documents->firstItem() }}</span> to <span class="font-medium text-text-main dark:text-white">{{ $documents->lastItem() }}</span> of <span class="font-medium text-text-main dark:text-white">{{ $documents->total() }}</span> results
        </div>
        <div class="flex gap-2">
            @if($documents->onFirstPage())
            <span class="p-2 rounded-lg border border-border-color dark:border-zinc-700 text-gray-300 cursor-not-allowed">
                <span class="material-symbols-outlined text-sm">chevron_left</span>
            </span>
            @else
            <a href="{{ $documents->previousPageUrl() }}" class="p-2 rounded-lg border border-border-color dark:border-zinc-700 text-text-secondary hover:bg-gray-50 dark:hover:bg-zinc-700">
                <span class="material-symbols-outlined text-sm">chevron_left</span>
            </a>
            @endif
            
            @if($documents->hasMorePages())
            <a href="{{ $documents->nextPageUrl() }}" class="p-2 rounded-lg border border-border-color dark:border-zinc-700 text-text-secondary hover:bg-gray-50 dark:hover:bg-zinc-700">
                <span class="material-symbols-outlined text-sm">chevron_right</span>
            </a>
            @else
            <span class="p-2 rounded-lg border border-border-color dark:border-zinc-700 text-gray-300 cursor-not-allowed">
                <span class="material-symbols-outlined text-sm">chevron_right</span>
            </span>
            @endif
        </div>
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
