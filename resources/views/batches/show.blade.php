@extends('layouts.main')

@section('title', 'Detail Batch')

@section('content')
<!-- Page Header -->
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('batches.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-text-secondary">arrow_back</span>
        </a>
        <div>
            <h1 class="text-text-main dark:text-white text-xl sm:text-2xl font-bold">{{ $batch->name }}</h1>
            <p class="text-sm text-text-secondary">{{ $batch->doc_type }} • {{ $batch->documents->count() }} dokumen</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('batches.qr', $batch) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600 transition-colors">
            <span class="material-symbols-outlined text-lg">download</span>
            Download QR
        </a>
    </div>
</div>

@if(session('success'))
<div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
    <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-green-600">check_circle</span>
        <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 sm:gap-6">
    <!-- Page Selector Sidebar -->
    <div class="lg:col-span-1 order-2 lg:order-1">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
            <div class="p-4 border-b border-border-color dark:border-zinc-700">
                <h4 class="font-bold text-text-main dark:text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">pages</span>
                    Halaman
                </h4>
                <p class="text-xs text-text-secondary mt-1">Pilih halaman untuk QR</p>
            </div>
            <div id="page-thumbnails" class="p-3 max-h-[400px] overflow-y-auto space-y-2">
                <div class="text-center text-sm text-text-secondary py-4">
                    <span class="material-symbols-outlined animate-spin">progress_activity</span>
                    <p>Memuat halaman...</p>
                </div>
            </div>
        </div>

        <!-- Batch Info -->
        <div class="mt-4 bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
            <div class="p-4">
                <h4 class="font-bold text-text-main dark:text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">info</span>
                    Info Batch
                </h4>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between py-1">
                        <dt class="text-text-secondary">Dibuat oleh</dt>
                        <dd class="text-text-main dark:text-white font-medium">{{ $batch->creator->name }}</dd>
                    </div>
                    <div class="flex justify-between py-1">
                        <dt class="text-text-secondary">Tanggal</dt>
                        <dd class="text-text-main dark:text-white font-medium">{{ $batch->created_at->format('d M Y') }}</dd>
                    </div>
                    <div class="flex justify-between py-1">
                        <dt class="text-text-secondary">Status QR</dt>
                        <dd>
                            @if($batch->documents->first()?->signed_file_path)
                                <span class="inline-flex items-center gap-1 text-green-600 text-xs font-medium">
                                    <span class="material-symbols-outlined text-sm">check_circle</span> Sudah diterapkan
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-yellow-600 text-xs font-medium">
                                    <span class="material-symbols-outlined text-sm">pending</span> Belum diterapkan
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- PDF Preview with QR Position Selector -->
    <div class="lg:col-span-2 order-1 lg:order-2">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
            <div class="p-4 border-b border-border-color dark:border-zinc-700">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-text-main dark:text-white">Preview Dokumen Pertama</h3>
                        <p class="text-sm text-text-secondary">Atur posisi QR code — akan diterapkan ke semua dokumen</p>
                    </div>
                </div>
            </div>
            
            <!-- PDF Canvas Container -->
            <div class="p-4">
                <div class="relative bg-gray-100 dark:bg-zinc-700 rounded-lg overflow-hidden" id="pdf-container">
                    <canvas id="pdf-canvas" class="w-full"></canvas>
                    
                    <!-- Draggable QR Placeholder -->
                    <div id="qr-placeholder" 
                         class="absolute cursor-move select-none shadow-lg z-10"
                         style="width: 80px; height: 80px; right: 40px; bottom: 60px;">
                        <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyAQAAAAA2RLUqAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAAmJLR0QA/4ePzL8AAAAHdElNRQfqAgEHGzNt8R4bAAAAbklEQVQcLM2Q0QnAIBADb6V0/yXY/VdjdYUkE/wQkP48eqiA10OskZ6a1lqrq/Su1t56j760R2d1aK2u0qN3t0dndWitrtK726OzOrRWV+kLBXw5z+31y7wAAAAASUVORK5CYII=" 
                             class="w-full h-full opacity-90" alt="QR Code">
                    </div>
                    
                    <!-- Page Navigation -->
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2 bg-white dark:bg-zinc-800 rounded-full shadow-lg px-4 py-2">
                        <button type="button" id="prev-page" class="p-1 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-full disabled:opacity-50" disabled>
                            <span class="material-symbols-outlined text-lg">chevron_left</span>
                        </button>
                        <span id="page-info" class="text-sm font-medium text-text-main dark:text-white">1 / 1</span>
                        <button type="button" id="next-page" class="p-1 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-full disabled:opacity-50" disabled>
                            <span class="material-symbols-outlined text-lg">chevron_right</span>
                        </button>
                    </div>
                </div>
                
                <div class="mt-3 p-3 bg-primary/10 rounded-lg">
                    <p class="text-xs text-yellow-800 dark:text-yellow-200 flex items-start gap-2">
                        <span class="material-symbols-outlined text-sm flex-shrink-0">info</span>
                        <span>Seret kotak QR ke posisi yang diinginkan pada dokumen. Gunakan panel halaman di kiri untuk memilih halaman. Posisi akan diterapkan ke <strong>semua dokumen</strong> dalam batch.</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions Sidebar -->
    <div class="lg:col-span-1 space-y-4 order-3">
        <!-- QR Actions -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
            <div class="p-4">
                <h4 class="font-bold text-text-main dark:text-white mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">qr_code_2</span>
                    Posisi QR Code
                </h4>
                <div class="space-y-4">
                    <!-- Position Info -->
                    <div class="p-3 bg-background-light dark:bg-zinc-700 rounded-lg">
                        <p class="text-xs text-text-secondary mb-2">Posisi QR Code:</p>
                        <div class="grid grid-cols-3 gap-2 text-xs">
                            <div class="bg-white dark:bg-zinc-600 rounded px-2 py-1 text-center">
                                <span class="text-text-secondary">Hal</span>
                                <p id="pos-page" class="font-bold text-text-main dark:text-white">1</p>
                            </div>
                            <div class="bg-white dark:bg-zinc-600 rounded px-2 py-1 text-center">
                                <span class="text-text-secondary">X</span>
                                <p id="pos-x" class="font-bold text-text-main dark:text-white">-</p>
                            </div>
                            <div class="bg-white dark:bg-zinc-600 rounded px-2 py-1 text-center">
                                <span class="text-text-secondary">Y</span>
                                <p id="pos-y" class="font-bold text-text-main dark:text-white">-</p>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="btn-apply-qr" 
                        class="w-full flex justify-center items-center gap-2 px-4 py-3 bg-green-500 hover:bg-green-600 rounded-lg font-semibold text-sm text-white transition-colors">
                        <span class="material-symbols-outlined">qr_code_2</span>
                        Terapkan QR ke Semua Dokumen
                    </button>
                </div>
            </div>
        </div>

        <!-- Document List -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
            <div class="p-4">
                <h4 class="font-bold text-text-main dark:text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">description</span>
                    Dokumen ({{ $batch->documents->count() }})
                </h4>
                <div class="space-y-1 max-h-[300px] overflow-y-auto">
                    @foreach($batch->documents as $doc)
                    <div class="flex items-center justify-between p-2 bg-background-light dark:bg-zinc-700 rounded-lg">
                        <div class="flex items-center gap-2 min-w-0">
                            @if($doc->signed_file_path)
                            <div class="size-6 rounded-full bg-green-100 flex-shrink-0 flex items-center justify-center">
                                <span class="material-symbols-outlined text-green-600 text-xs">check</span>
                            </div>
                            @else
                            <div class="size-6 rounded-full bg-gray-200 dark:bg-zinc-600 flex-shrink-0 flex items-center justify-center">
                                <span class="material-symbols-outlined text-gray-500 text-xs">description</span>
                            </div>
                            @endif
                            <span class="text-xs text-text-main dark:text-white truncate">{{ $doc->identifier }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- QR Code Preview -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
            <div class="p-4 text-center">
                <h4 class="font-bold text-text-main dark:text-white mb-3 flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-primary">qr_code</span>
                    QR Batch
                </h4>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($batch->getVerificationUrl()) }}" 
                     alt="QR Code" class="mx-auto rounded-lg" width="120" height="120">
                <p class="text-[10px] text-text-secondary mt-2 break-all">{{ $batch->getVerificationUrl() }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Apply QR Confirmation Modal -->
<div id="apply-qr-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-xl max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center gap-4 mb-4">
            <div class="size-12 rounded-full bg-green-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-green-600">qr_code_2</span>
            </div>
            <h3 class="text-lg font-bold text-text-main dark:text-white">Terapkan QR Code</h3>
        </div>
        <div class="mb-6">
            <p class="text-sm text-text-secondary mb-3">QR code akan diterapkan ke <strong>{{ $batch->documents->count() }} dokumen</strong> pada posisi yang telah ditentukan.</p>
            <div class="p-3 bg-primary/10 rounded-lg">
                <p class="text-xs text-yellow-800 dark:text-yellow-200">
                    <strong>Posisi QR Code:</strong> Halaman <span id="confirm-page">1</span>, X: <span id="confirm-x">-</span>, Y: <span id="confirm-y">-</span>
                </p>
            </div>
            <p class="mt-3 text-xs text-text-secondary">Proses ini mungkin memerlukan waktu. Harap tunggu hingga selesai.</p>
        </div>
        <form id="apply-qr-form" action="{{ route('batches.applyQr', $batch) }}" method="POST">
            @csrf
            <input type="hidden" name="qr_page" id="input-qr-page" value="1">
            <input type="hidden" name="qr_x" id="input-qr-x" value="">
            <input type="hidden" name="qr_y" id="input-qr-y" value="">
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('apply-qr-modal').classList.add('hidden')" 
                    class="px-4 py-2.5 bg-white dark:bg-zinc-700 border border-border-color dark:border-zinc-600 rounded-lg text-sm font-medium text-text-main dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-600 transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2.5 bg-green-500 hover:bg-green-600 rounded-lg text-sm font-semibold text-white transition-colors">
                    Ya, Terapkan QR
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    
    const pdfUrl = '{{ route("batches.previewFirst", $batch) }}';
    
    let pdfDoc = null;
    let currentPage = 1;
    let totalPages = 0;
    let scale = 1.5;
    let pdfPageData = {};
    
    // QR position state
    let qrPosition = {
        page: 1,
        x: null,
        y: null
    };
    
    // Load the PDF
    async function loadPDF() {
        try {
            pdfDoc = await pdfjsLib.getDocument(pdfUrl).promise;
            totalPages = pdfDoc.numPages;
            
            document.getElementById('page-info').textContent = `1 / ${totalPages}`;
            
            if (totalPages > 1) {
                document.getElementById('next-page').disabled = false;
            }
            
            await renderPage(1);
            await renderThumbnails();
        } catch (error) {
            console.error('Error loading PDF:', error);
            document.getElementById('pdf-container').innerHTML = `
                <div class="p-8 text-center text-red-500">
                    <span class="material-symbols-outlined text-4xl mb-2">error</span>
                    <p>Gagal memuat PDF</p>
                </div>
            `;
        }
    }
    
    // Render a specific page
    async function renderPage(pageNum) {
        const page = await pdfDoc.getPage(pageNum);
        const canvas = document.getElementById('pdf-canvas');
        const ctx = canvas.getContext('2d');
        
        const viewport = page.getViewport({ scale: scale });
        
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        
        // Store page data for coordinate conversion
        pdfPageData = {
            width: page.getViewport({ scale: 1 }).width,
            height: page.getViewport({ scale: 1 }).height,
            scale: scale
        };
        
        await page.render({
            canvasContext: ctx,
            viewport: viewport
        }).promise;
        
        currentPage = pageNum;
        document.getElementById('page-info').textContent = `${pageNum} / ${totalPages}`;
        
        // Update navigation buttons
        document.getElementById('prev-page').disabled = pageNum <= 1;
        document.getElementById('next-page').disabled = pageNum >= totalPages;
        
        // Update thumbnail selection
        document.querySelectorAll('.page-thumbnail').forEach((thumb, index) => {
            if (index + 1 === pageNum) {
                thumb.classList.add('ring-2', 'ring-primary');
            } else {
                thumb.classList.remove('ring-2', 'ring-primary');
            }
        });
        
        // Update QR page position
        qrPosition.page = pageNum;
        document.getElementById('pos-page').textContent = pageNum;
        updatePlaceholderSize();
        updatePositionFromPlaceholder();
    }
    
    // Render page thumbnails
    async function renderThumbnails() {
        const container = document.getElementById('page-thumbnails');
        container.innerHTML = '';
        
        for (let i = 1; i <= totalPages; i++) {
            const page = await pdfDoc.getPage(i);
            const viewport = page.getViewport({ scale: 0.2 });
            
            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            canvas.className = 'page-thumbnail w-full rounded cursor-pointer hover:opacity-80 transition-opacity ' + (i === 1 ? 'ring-2 ring-primary' : '');
            canvas.dataset.page = i;
            
            const ctx = canvas.getContext('2d');
            await page.render({ canvasContext: ctx, viewport: viewport }).promise;
            
            const wrapper = document.createElement('div');
            wrapper.className = 'relative';
            wrapper.innerHTML = `
                <span class="absolute top-1 left-1 bg-black/50 text-white text-[10px] px-1.5 py-0.5 rounded">${i}</span>
            `;
            wrapper.prepend(canvas);
            
            canvas.addEventListener('click', () => renderPage(i));
            
            container.appendChild(wrapper);
        }
    }
    
    // Page navigation
    document.getElementById('prev-page').addEventListener('click', () => {
        if (currentPage > 1) renderPage(currentPage - 1);
    });
    
    document.getElementById('next-page').addEventListener('click', () => {
        if (currentPage < totalPages) renderPage(currentPage + 1);
    });
    
    // Drag and drop for QR placeholder
    const qrPlaceholder = document.getElementById('qr-placeholder');
    const pdfContainer = document.getElementById('pdf-container');
    
    let isDragging = false;
    let dragOffsetX = 0;
    let dragOffsetY = 0;
    
    qrPlaceholder.addEventListener('mousedown', startDrag);
    qrPlaceholder.addEventListener('touchstart', startDrag, { passive: false });
    
    function startDrag(e) {
        isDragging = true;
        qrPlaceholder.style.cursor = 'grabbing';
        
        const clientX = e.type === 'touchstart' ? e.touches[0].clientX : e.clientX;
        const clientY = e.type === 'touchstart' ? e.touches[0].clientY : e.clientY;
        
        const rect = qrPlaceholder.getBoundingClientRect();
        dragOffsetX = clientX - rect.left;
        dragOffsetY = clientY - rect.top;
        
        e.preventDefault();
    }
    
    document.addEventListener('mousemove', drag);
    document.addEventListener('touchmove', drag, { passive: false });
    
    function drag(e) {
        if (!isDragging) return;
        
        const clientX = e.type === 'touchmove' ? e.touches[0].clientX : e.clientX;
        const clientY = e.type === 'touchmove' ? e.touches[0].clientY : e.clientY;
        
        const containerRect = pdfContainer.getBoundingClientRect();
        const qrRect = qrPlaceholder.getBoundingClientRect();
        
        let newX = clientX - containerRect.left - dragOffsetX;
        let newY = clientY - containerRect.top - dragOffsetY;
        
        // Constrain within container
        newX = Math.max(0, Math.min(newX, containerRect.width - qrRect.width));
        newY = Math.max(0, Math.min(newY, containerRect.height - qrRect.height - 50));
        
        qrPlaceholder.style.left = newX + 'px';
        qrPlaceholder.style.top = newY + 'px';
        qrPlaceholder.style.right = 'auto';
        qrPlaceholder.style.bottom = 'auto';
        
        updatePositionFromPlaceholder();
        e.preventDefault();
    }
    
    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchend', endDrag);
    
    function endDrag() {
        if (isDragging) {
            isDragging = false;
            qrPlaceholder.style.cursor = 'move';
        }
    }
    
    function updatePositionFromPlaceholder() {
        const canvas = document.getElementById('pdf-canvas');
        const canvasRect = canvas.getBoundingClientRect();
        const qrRect = qrPlaceholder.getBoundingClientRect();
        
        // Get QR center position relative to canvas
        const centerX = qrRect.left + qrRect.width / 2 - canvasRect.left;
        const centerY = qrRect.top + qrRect.height / 2 - canvasRect.top;
        
        // Convert to mm: screen -> PDF points -> mm
        const pointsToMm = 25.4 / 72;
        
        const pdfPointsX = (centerX / canvasRect.width) * pdfPageData.width;
        const pdfPointsY = (centerY / canvasRect.height) * pdfPageData.height;
        
        const pdfX = pdfPointsX * pointsToMm;
        const pdfY = pdfPointsY * pointsToMm;
        
        qrPosition.x = Math.round(pdfX * 10) / 10;
        qrPosition.y = Math.round(pdfY * 10) / 10;
        qrPosition.page = currentPage;
        
        // Update display
        document.getElementById('pos-x').textContent = qrPosition.x.toFixed(1);
        document.getElementById('pos-y').textContent = qrPosition.y.toFixed(1);
        document.getElementById('pos-page').textContent = qrPosition.page;
    }
    
    function updatePlaceholderSize() {
        const mm = 25; // Fixed QR size in mm
        const pixels = ((mm / 25.4) * 72) * scale;
        
        qrPlaceholder.style.width = pixels + 'px';
        qrPlaceholder.style.height = pixels + 'px';
    }
    
    // Initialize position on load
    setTimeout(updatePositionFromPlaceholder, 500);
    
    // Apply QR button click
    document.getElementById('btn-apply-qr').addEventListener('click', function() {
        // Update form fields
        document.getElementById('input-qr-page').value = qrPosition.page;
        document.getElementById('input-qr-x').value = qrPosition.x;
        document.getElementById('input-qr-y').value = qrPosition.y;
        
        // Update confirmation modal
        document.getElementById('confirm-page').textContent = qrPosition.page;
        document.getElementById('confirm-x').textContent = qrPosition.x?.toFixed(1) || '-';
        document.getElementById('confirm-y').textContent = qrPosition.y?.toFixed(1) || '-';
        
        // Show modal
        document.getElementById('apply-qr-modal').classList.remove('hidden');
    });
    
    // Load PDF on page load
    document.addEventListener('DOMContentLoaded', loadPDF);
</script>
@endpush
