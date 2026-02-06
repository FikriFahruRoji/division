@extends('layouts.main')

@section('title', 'Unggah Dokumen')

@section('content')
<!-- Page Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('signer-documents.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
        <span class="material-symbols-outlined text-text-secondary">arrow_back</span>
    </a>
    <div>
        <h1 class="text-text-main dark:text-white text-xl sm:text-2xl font-bold">Unggah Dokumen</h1>
        <p class="text-sm text-text-secondary">Upload dokumen untuk ditandatangani</p>
    </div>
</div>

<div class="max-w-3xl">
    <!-- Info Alert -->
    <div class="mb-6 flex items-start gap-3 rounded-xl bg-primary/10 border border-primary/20 p-4">
        <span class="material-symbols-outlined text-primary flex-shrink-0">info</span>
        <p class="text-sm text-yellow-800 dark:text-yellow-200">
            Dokumen akan langsung disiapkan untuk Anda tandatangani setelah diunggah.
        </p>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
        <form action="{{ route('signer-documents.store') }}" method="POST" enctype="multipart/form-data" class="p-4 sm:p-6">
            @csrf



            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                <!-- Title -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-2">
                        Judul Dokumen <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="Masukkan judul dokumen"
                        class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    @error('title')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>

                <!-- Document Number -->
                <div>
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-2">
                        Nomor Dokumen <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="doc_number" value="{{ old('doc_number') }}" required placeholder="Contoh: SK-001/2026"
                        class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    @error('doc_number')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>

                <!-- Document Date -->
                <div>
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-2">
                        Tanggal Dokumen <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="doc_date" value="{{ old('doc_date', date('Y-m-d')) }}" required
                        class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    @error('doc_date')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>

                <!-- Document Type -->
                <div>
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-2">
                        Jenis Dokumen <span class="text-red-500">*</span>
                    </label>
                    <select name="doc_type" required
                        class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">Pilih Jenis</option>
                        <option value="Surat Keputusan" {{ old('doc_type') == 'Surat Keputusan' ? 'selected' : '' }}>Surat Keputusan</option>
                        <option value="Surat Keterangan" {{ old('doc_type') == 'Surat Keterangan' ? 'selected' : '' }}>Surat Keterangan</option>
                        <option value="Surat Tugas" {{ old('doc_type') == 'Surat Tugas' ? 'selected' : '' }}>Surat Tugas</option>
                        <option value="Memo" {{ old('doc_type') == 'Memo' ? 'selected' : '' }}>Memo</option>
                        <option value="Kontrak" {{ old('doc_type') == 'Kontrak' ? 'selected' : '' }}>Kontrak</option>
                        <option value="Lainnya" {{ old('doc_type') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                    @error('doc_type')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>

                <!-- Unit -->
                <div>
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-2">
                        Unit/Bagian <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="unit" value="{{ old('unit', auth()->user()->position ?? '') }}" required placeholder="Contoh: IT Department"
                        class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    @error('unit')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>

                <!-- Classification -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-3">Klasifikasi</label>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="classification" value="biasa" {{ old('classification', 'biasa') == 'biasa' ? 'checked' : '' }}
                                class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="text-sm text-text-main dark:text-white">Biasa</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="classification" value="rahasia" {{ old('classification') == 'rahasia' ? 'checked' : '' }}
                                class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="text-sm text-text-main dark:text-white">Rahasia</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="classification" value="sangat_rahasia" {{ old('classification') == 'sangat_rahasia' ? 'checked' : '' }}
                                class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="text-sm text-text-main dark:text-white">Sangat Rahasia</span>
                        </label>
                    </div>
                </div>

                <!-- Notes -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Catatan</label>
                    <textarea name="notes" rows="3" placeholder="Catatan tambahan (opsional)"
                        class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary resize-none">{{ old('notes') }}</textarea>
                </div>
            </div>

            <!-- Actions -->
            <!-- File Upload -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-text-main dark:text-white mb-2">
                    File PDF <span class="text-red-500">*</span>
                </label>
                <div class="relative border-2 border-dashed border-border-color dark:border-zinc-600 rounded-xl p-6 sm:p-8 text-center hover:border-primary transition-colors cursor-pointer" id="dropzone">
                    <input type="file" name="file" id="file" accept=".pdf" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <div class="space-y-3">
                        <div class="size-16 mx-auto bg-primary/10 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary text-3xl">upload_file</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-text-main dark:text-white">
                                <span class="text-primary">Pilih file</span> atau drag & drop
                            </p>
                            <p class="text-xs text-text-secondary mt-1">PDF hingga 10MB</p>
                        </div>
                        <p id="file-name" class="text-sm font-semibold text-primary hidden"></p>
                    </div>
                </div>
                @error('file')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>

            <div class="mt-6 flex flex-col sm:flex-row justify-center items-center gap-3">
                <a href="{{ route('signer-documents.index') }}" 
                    class="px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600 text-center transition-colors">
                    Batal
                </a>
                <button type="submit" class="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
                    <span class="material-symbols-outlined text-lg">upload</span>
                    Unggah & Siapkan TTD
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // File input handling
    document.getElementById('file').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        const fileNameEl = document.getElementById('file-name');
        if (fileName) {
            fileNameEl.textContent = fileName;
            fileNameEl.classList.remove('hidden');
        }
    });

    // Drag and drop
    const dropzone = document.getElementById('dropzone');
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
    });
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => dropzone.classList.add('border-primary', 'bg-primary/5'), false);
    });
    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => dropzone.classList.remove('border-primary', 'bg-primary/5'), false);
    });
    dropzone.addEventListener('drop', function(e) {
        const dt = e.dataTransfer;
        const file = dt.files[0];
        if (file && file.type === 'application/pdf') {
            document.getElementById('file').files = dt.files;
            document.getElementById('file-name').textContent = file.name;
            document.getElementById('file-name').classList.remove('hidden');
        }
    });
</script>
@endpush
