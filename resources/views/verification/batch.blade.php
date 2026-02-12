<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Batch Dokumen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .material-symbols-outlined {
            font-family: 'Material Icons';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-smoothing: antialiased;
        }
        body { font-family: 'Inter', sans-serif; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#f5c619",
                        "primary-hover": "#e6b800",
                        "text-main": "#181611",
                        "text-secondary": "#8a8160",
                        "border-color": "#e6e4db",
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="min-h-screen flex items-start justify-center p-4 pt-8 sm:pt-16">
        <div class="max-w-lg w-full">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-primary/20 mb-3">
                    <span class="material-symbols-outlined text-primary text-3xl">verified</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Verifikasi Dokumen</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $batch->name }}</p>
            </div>

            <!-- Batch Info Card -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                <div class="p-5 bg-gradient-to-r from-emerald-500 to-teal-600 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/20 mb-2">
                        <span class="material-symbols-outlined text-white text-2xl">inventory_2</span>
                    </div>
                    <h2 class="text-lg font-bold text-white">Batch Terverifikasi</h2>
                    <p class="text-white/80 text-sm">{{ $batch->doc_type }} • {{ $batch->document_count }} dokumen</p>
                </div>

                <!-- Search Section -->
                <div class="p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cari Dokumen berdasarkan NIM/NPM</label>
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Masukkan NIM/NPM..." 
                            class="w-full px-4 py-3 pl-11 border border-gray-200 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
                            autocomplete="off">
                        <span class="material-symbols-outlined absolute left-3 top-3 text-gray-400 text-xl">search</span>
                        <div id="loadingSpinner" class="hidden absolute right-3 top-3">
                            <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Ketik NIM/NPM untuk mencari dokumen Anda</p>
                </div>
            </div>

            <!-- Search Results -->
            <div id="results" class="space-y-3">
                <!-- Initial State -->
                <div id="initialState" class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                    <span class="material-symbols-outlined text-4xl text-gray-300 mb-2">manage_search</span>
                    <p class="text-sm text-gray-500">Masukkan NIM/NPM untuk mencari dokumen Anda</p>
                </div>

                <!-- No Results -->
                <div id="noResults" class="hidden bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                    <span class="material-symbols-outlined text-4xl text-red-300 mb-2">search_off</span>
                    <p class="text-sm text-gray-500">Dokumen tidak ditemukan</p>
                    <p class="text-xs text-gray-400 mt-1">Pastikan NIM/NPM yang dimasukkan benar</p>
                </div>

                <!-- Document Items (will be populated by JS) -->
                <div id="documentList" class="hidden space-y-3"></div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-xs text-gray-400">
                    Verifikasi dokumen digital • {{ $batch->name }}
                </p>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const initialState = document.getElementById('initialState');
        const noResults = document.getElementById('noResults');
        const documentList = document.getElementById('documentList');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const token = '{{ $token }}';
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length === 0) {
                initialState.classList.remove('hidden');
                noResults.classList.add('hidden');
                documentList.classList.add('hidden');
                return;
            }

            if (query.length < 2) return;

            loadingSpinner.classList.remove('hidden');

            searchTimeout = setTimeout(() => {
                fetch(`/verify-batch/${token}/search?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        loadingSpinner.classList.add('hidden');
                        initialState.classList.add('hidden');

                        if (data.documents && data.documents.length > 0) {
                            noResults.classList.add('hidden');
                            documentList.classList.remove('hidden');
                            documentList.innerHTML = '';

                            data.documents.forEach(doc => {
                                const card = document.createElement('div');
                                card.className = 'bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow';
                                card.innerHTML = `
                                    <div class="p-4">
                                        <div class="flex items-start gap-3">
                                            <div class="p-2 bg-emerald-50 rounded-lg shrink-0">
                                                <span class="material-symbols-outlined text-emerald-600">description</span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-gray-900 text-sm">${doc.title}</p>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="inline-flex px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-mono font-medium">${doc.identifier}</span>
                                                    <span class="text-xs text-gray-400">${doc.doc_number}</span>
                                                </div>
                                            </div>
                                            <span class="inline-flex px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-medium shrink-0">
                                                ✓ Valid
                                            </span>
                                        </div>
                                        <div class="flex gap-2 mt-3 pt-3 border-t border-gray-100">
                                            <a href="/verify-batch/${token}/${doc.uuid}/preview" target="_blank"
                                               class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                                                <span class="material-symbols-outlined text-sm">visibility</span>
                                                Lihat
                                            </a>
                                            <a href="/verify-batch/${token}/${doc.uuid}/download"
                                               class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-medium rounded-lg transition-colors">
                                                <span class="material-symbols-outlined text-sm">download</span>
                                                Unduh
                                            </a>
                                        </div>
                                    </div>
                                `;
                                documentList.appendChild(card);
                            });
                        } else {
                            noResults.classList.remove('hidden');
                            documentList.classList.add('hidden');
                        }
                    })
                    .catch(err => {
                        loadingSpinner.classList.add('hidden');
                        console.error('Search error:', err);
                    });
            }, 300);
        });
    </script>
</body>
</html>
