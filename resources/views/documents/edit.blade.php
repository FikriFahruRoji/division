@extends('layouts.main')

@section('title', 'Edit Dokumen')

@section('content')
<!-- Page Header -->
<div class="flex items-center gap-4 mb-8">
    <a href="{{ route('documents.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
        <span class="material-symbols-outlined text-text-secondary">arrow_back</span>
    </a>
    <h1 class="text-text-main dark:text-white text-2xl font-bold">Edit Dokumen</h1>
</div>

<!-- Form Card -->
<div class="max-w-4xl">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
        <div class="p-6">
            <form action="{{ route('documents.update', $document) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="space-y-6">
                    <!-- File Upload -->
                    <div>
                        <label class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            File PDF (Opsional)
                        </label>
                        <div class="border-2 border-dashed border-border-color dark:border-zinc-600 rounded-xl p-8 text-center hover:border-primary transition-colors" id="dropzone">
                            <span class="material-symbols-outlined text-5xl text-text-secondary mb-3">upload_file</span>
                            <div class="text-sm text-text-secondary mb-2">
                                <label for="file" class="text-primary font-medium cursor-pointer hover:underline">
                                    Pilih file baru
                                </label>
                                atau drag and drop
                            </div>
                            <p class="text-xs text-text-secondary">Biarkan kosong jika tidak ingin mengubah file (Max 10MB)</p>
                            <input id="file" name="file" type="file" class="hidden" accept=".pdf">
                        </div>
                        <p id="file-name" class="mt-2 text-sm text-primary font-medium"></p>
                        @if($document->file_path)
                            @php
                                $safeDocNumber = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($document->doc_number ?? ''));
                                $docPdfName = ($safeDocNumber ?: 'dokumen') . '_' . ($document->signed_file_path ? 'signed' : 'original') . '.pdf';
                            @endphp
                            <p class="mt-2 text-xs text-text-secondary">File saat ini: <a href="{{ route('documents.preview', ['document' => $document, 'filename' => $docPdfName]) }}" target="_blank" class="text-primary hover:underline">Lihat Dokumen</a></p>
                        @endif
                        @error('file')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Judul Dokumen <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" id="title" value="{{ old('title', $document->title) }}" required
                            class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        @error('title')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    
                    <!-- Doc Number & Date -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="doc_number" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                                Nomor Dokumen <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="doc_number" id="doc_number" value="{{ old('doc_number', $document->doc_number) }}" placeholder="Contoh: SK/001/2024" required
                                class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            @error('doc_number')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="doc_date" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                                Tanggal Dokumen <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="doc_date" id="doc_date" value="{{ old('doc_date', $document->doc_date->format('Y-m-d')) }}" required
                                class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            @error('doc_date')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    
                    <!-- Doc Type & Unit -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="doc_type" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                                Jenis Dokumen <span class="text-red-500">*</span>
                            </label>
                            <select name="doc_type" id="doc_type" required
                                class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">Pilih Jenis</option>
                                @foreach(['Surat Keputusan', 'Surat Keterangan', 'Surat Tugas', 'Memo', 'Perjanjian', 'Kontrak', 'Lainnya'] as $type)
                                    <option value="{{ $type }}" {{ old('doc_type', $document->doc_type) == $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                            @error('doc_type')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="unit" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                                Unit/Bagian <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="unit" id="unit" value="{{ old('unit', $document->unit) }}" placeholder="Contoh: Bagian Umum" required
                                class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            @error('unit')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    
                    <!-- Sign Mode -->
                    <div>
                        <label for="sign_mode" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Mode Tanda Tangan <span class="text-red-500">*</span>
                        </label>
                        <select name="sign_mode" id="sign_mode" required
                            class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="single" {{ old('sign_mode', $document->sign_mode) == 'single' ? 'selected' : '' }}>Single (1 penandatangan)</option>
                            <option value="sequential" {{ old('sign_mode', $document->sign_mode) == 'sequential' ? 'selected' : '' }}>Sequential (berurutan)</option>
                            <option value="parallel" {{ old('sign_mode', $document->sign_mode) == 'parallel' ? 'selected' : '' }}>Parallel (bersamaan)</option>
                        </select>
                        <p class="mt-1 text-xs text-text-secondary">Sequential: tanda tangan berurutan. Parallel: semua bisa tanda tangan bersamaan.</p>
                    </div>
                    
                    <!-- Signers -->
                    <div x-data="{ selectedDept: '' }">
                        <label class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Penandatangan <span class="text-red-500">*</span>
                        </label>
                        
                        <!-- Department Filter -->
                        <div class="mb-4">
                            <select x-model="selectedDept" 
                                class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">-- Pilih Departemen --</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <p class="text-xs text-text-secondary mb-3">Pilih satu atau lebih penandatangan. Gunakan filter departemen untuk mencari nama.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-60 overflow-y-auto bg-background-light dark:bg-zinc-700 rounded-lg p-3">
                            @foreach($signers as $signer)
                            <label x-show="selectedDept === '' || selectedDept == '{{ $signer->department_id }}'" 
                                class="flex items-center p-3 rounded-lg bg-white dark:bg-zinc-800 border border-border-color dark:border-zinc-600 hover:border-primary cursor-pointer transition-colors">
                                <input type="checkbox" name="signers[]" value="{{ $signer->id }}" 
                                    class="rounded border-border-color text-primary focus:ring-primary" 
                                    {{ in_array($signer->id, old('signers', $document->signerAssignments->pluck('signer_id')->toArray())) ? 'checked' : '' }}>
                                <div class="ml-3">
                                    <span class="text-sm font-medium text-text-main dark:text-white">{{ $signer->name }}</span>
                                    <span class="text-xs text-text-secondary block">
                                        {{ $signer->position }} 
                                        @if($signer->department)
                                        <span class="text-primary/70"> • {{ $signer->department->code }}</span>
                                        @endif
                                    </span>
                                </div>
                            </label>
                            @endforeach
                            <!-- Empty State -->
                            <div x-show="selectedDept !== '' && $el.closest('.grid').querySelectorAll('label[style*=\'display: none\']').length === {{ count($signers) }}" class="col-span-2 text-center py-4 text-text-secondary text-sm hidden">
                                Tidak ada penandatangan di departemen ini.
                            </div>
                        </div>
                        @error('signers')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    
                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Catatan
                        </label>
                        <textarea name="notes" id="notes" rows="3" placeholder="Catatan atau keterangan tambahan..."
                            class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary resize-none">{{ old('notes', $document->notes) }}</textarea>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="mt-8 flex justify-end gap-3">
                    <a href="{{ route('documents.index') }}" 
                        class="px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600 transition-colors">
                        Batal
                    </a>
                    <button type="submit" 
                        class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
                        <span class="material-symbols-outlined text-lg">save</span>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('file').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        document.getElementById('file-name').textContent = fileName ? 'File dipilih: ' + fileName : '';
    });
    
    // Click on dropzone to trigger file input
    document.getElementById('dropzone').addEventListener('click', function() {
        document.getElementById('file').click();
    });
</script>
@endpush
