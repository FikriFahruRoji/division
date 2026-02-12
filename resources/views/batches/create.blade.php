@extends('layouts.main')

@section('title', 'Buat Batch Baru')

@section('content')
<!-- Page Header -->
<div class="flex items-center gap-4 mb-8">
    <a href="{{ route('batches.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
        <span class="material-symbols-outlined text-text-secondary">arrow_back</span>
    </a>
    <h1 class="text-text-main dark:text-white text-2xl font-bold">Buat Batch Dokumen Baru</h1>
</div>

<!-- Form Card -->
<div class="max-w-4xl">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
        <div class="p-6">
            <form action="{{ route('batches.store') }}" method="POST" enctype="multipart/form-data" id="batchForm">
                @csrf
                
                <div class="space-y-6">
                    <!-- Batch Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Nama Batch <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" 
                            placeholder="Contoh: Kartu Ujian Semester Ganjil 2025" required
                            class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        @error('name')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    
                    <!-- Doc Type -->
                    <div>
                        <label for="doc_type" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Jenis Dokumen <span class="text-red-500">*</span>
                        </label>
                        <select name="doc_type" id="doc_type" required
                            class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Pilih Jenis</option>
                            <option value="Kartu Ujian" {{ old('doc_type') == 'Kartu Ujian' ? 'selected' : '' }}>Kartu Ujian</option>
                            <option value="Sertifikat" {{ old('doc_type') == 'Sertifikat' ? 'selected' : '' }}>Sertifikat</option>
                            <option value="Transkrip" {{ old('doc_type') == 'Transkrip' ? 'selected' : '' }}>Transkrip</option>
                            <option value="Surat Keterangan" {{ old('doc_type') == 'Surat Keterangan' ? 'selected' : '' }}>Surat Keterangan</option>
                            <option value="Ijazah" {{ old('doc_type') == 'Ijazah' ? 'selected' : '' }}>Ijazah</option>
                            <option value="Lainnya" {{ old('doc_type') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                        @error('doc_type')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    
                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Deskripsi
                        </label>
                        <textarea name="description" id="description" rows="3" placeholder="Keterangan tambahan tentang batch ini..."
                            class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary resize-none">{{ old('description') }}</textarea>
                        @error('description')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    
                    <!-- File Upload -->
                    <div>
                        <label class="block text-sm font-medium text-text-main dark:text-white mb-2">
                            Upload File PDF <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-text-secondary mb-3">
                            Upload multiple file PDF atau 1 file ZIP berisi PDF. <strong>Nama file = NIM/NPM</strong> (contoh: <code class="bg-gray-100 dark:bg-zinc-700 px-1 rounded">2210001.pdf</code>)
                        </p>
                        
                        <div class="border-2 border-dashed border-border-color dark:border-zinc-600 rounded-xl p-8 text-center hover:border-primary transition-colors cursor-pointer" 
                             id="dropzone" onclick="document.getElementById('files').click()">
                            <span class="material-symbols-outlined text-5xl text-text-secondary mb-3">cloud_upload</span>
                            <div class="text-sm text-text-secondary mb-2">
                                <span class="text-primary font-medium">Klik untuk pilih file</span> atau drag & drop
                            </div>
                            <p class="text-xs text-text-secondary">PDF atau ZIP (maks 10MB per file)</p>
                            <input id="files" name="files[]" type="file" class="hidden" accept=".pdf,.zip" multiple required>
                        </div>
                        @error('files')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                        @error('files.*')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                        
                        <!-- File Preview List -->
                        <div id="fileList" class="mt-4 space-y-2 hidden">
                            <p class="text-sm font-medium text-text-main dark:text-white">File yang akan diupload:</p>
                            <div id="fileItems" class="space-y-1"></div>
                            <p id="fileCount" class="text-xs text-text-secondary mt-2"></p>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="mt-8 flex justify-end gap-3">
                    <a href="{{ route('batches.index') }}" 
                        class="px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600 transition-colors">
                        Batal
                    </a>
                    <button type="submit" id="submitBtn"
                        class="flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
                        <span class="material-symbols-outlined text-lg">upload</span>
                        Buat Batch & Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const filesInput = document.getElementById('files');
    const fileList = document.getElementById('fileList');
    const fileItems = document.getElementById('fileItems');
    const fileCount = document.getElementById('fileCount');
    const dropzone = document.getElementById('dropzone');
    const submitBtn = document.getElementById('submitBtn');
    const batchForm = document.getElementById('batchForm');

    filesInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        if (files.length > 0) {
            fileList.classList.remove('hidden');
            fileItems.innerHTML = '';
            
            files.forEach(file => {
                const nim = file.name.replace(/\.[^/.]+$/, '');
                const size = (file.size / 1024).toFixed(1);
                const item = document.createElement('div');
                item.className = 'flex items-center gap-3 px-3 py-2 bg-background-light dark:bg-zinc-700 rounded-lg text-sm';
                item.innerHTML = `
                    <span class="material-symbols-outlined text-primary text-lg">description</span>
                    <span class="font-medium text-text-main dark:text-white flex-1">${file.name}</span>
                    <span class="text-xs text-text-secondary bg-gray-200 dark:bg-zinc-600 px-2 py-0.5 rounded">NIM: ${nim}</span>
                    <span class="text-xs text-text-secondary">${size} KB</span>
                `;
                fileItems.appendChild(item);
            });
            
            fileCount.textContent = `Total: ${files.length} file`;
        } else {
            fileList.classList.add('hidden');
        }
    });

    // Drag and drop
    ['dragenter', 'dragover'].forEach(event => {
        dropzone.addEventListener(event, function(e) {
            e.preventDefault();
            dropzone.classList.add('border-primary', 'bg-primary/5');
        });
    });

    ['dragleave', 'drop'].forEach(event => {
        dropzone.addEventListener(event, function(e) {
            e.preventDefault();
            dropzone.classList.remove('border-primary', 'bg-primary/5');
        });
    });

    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        filesInput.files = e.dataTransfer.files;
        filesInput.dispatchEvent(new Event('change'));
    });

    // Show loading state on submit
    batchForm.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Mengupload...
        `;
    });
</script>
@endpush
