<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Dokumen</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-lg w-full">
            <!-- Header -->
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Verifikasi Dokumen Digital</h1>
                <p class="text-sm text-gray-500 mt-1">Hasil pemindaian QR Code</p>
            </div>

            <!-- Status Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Status Banner -->
                <div class="p-6 text-center 
                    @if($statusColor === 'green') bg-green-500 @elseif($statusColor === 'yellow') bg-yellow-500 @else bg-red-500 @endif">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/20 mb-3">
                        @if($statusColor === 'green')
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @elseif($statusColor === 'yellow')
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        @else
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                            </svg>
                        @endif
                    </div>
                    <h2 class="text-3xl font-bold text-white">{{ $statusLabel }}</h2>
                    @if($result['status'] === 'valid')
                        <p class="text-white/80 mt-1">Dokumen ini sah dan berlaku</p>
                    @elseif($result['status'] === 'superseded')
                        <p class="text-white/80 mt-1">Dokumen ini telah digantikan versi baru</p>
                    @else
                        <p class="text-white/80 mt-1">Dokumen ini telah dicabut</p>
                    @endif
                </div>

                <!-- Document Info -->
                <div class="p-6">
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Informasi Dokumen</h3>
                        <dl class="space-y-2">
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <dt class="text-sm text-gray-500">Nomor Dokumen</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $result['document']['doc_number'] }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <dt class="text-sm text-gray-500">Judul</dt>
                                <dd class="text-sm font-medium text-gray-900 text-right max-w-[60%]">{{ $result['document']['title'] }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <dt class="text-sm text-gray-500">Tanggal Dokumen</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $result['document']['doc_date'] }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <dt class="text-sm text-gray-500">Jenis</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $result['document']['doc_type'] }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <dt class="text-sm text-gray-500">Unit</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $result['document']['unit'] }}</dd>
                            </div>
                            @if($result['document']['signed_at'])
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <dt class="text-sm text-gray-500">Ditandatangani</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $result['document']['signed_at'] }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>

                    <!-- Signers -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Penandatangan</h3>
                        <div class="space-y-2">
                            @foreach($result['signers'] as $signer)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $signer['name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $signer['position'] }}</p>
                                </div>
                                <div class="text-right">
                                    @if($signer['status'] === 'signed')
                                        <span class="inline-flex items-center text-xs text-green-600">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            Signed
                                        </span>
                                        <p class="text-xs text-gray-400">{{ $signer['signed_at'] }}</p>
                                    @else
                                        <span class="text-xs text-gray-400">{{ ucfirst($signer['status']) }}</span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Fingerprint -->
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">Fingerprint Dokumen</p>
                        <p class="font-mono text-sm font-medium text-gray-900 tracking-wider">{{ $result['fingerprint'] }}</p>
                    </div>

                    <!-- View/Download Document Button -->
                    @if($result['status'] === 'valid')
                    <div class="mt-4 flex flex-col sm:flex-row gap-2">
                        <a href="{{ route('verify.preview', $token) }}" 
                           target="_blank"
                           class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Lihat Dokumen
                        </a>
                        <a href="{{ route('verify.download', $token) }}" 
                           class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-green-500 hover:bg-green-600 text-white font-medium rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Unduh PDF
                        </a>
                    </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t text-center">
                    <p class="text-xs text-gray-400">
                        Diverifikasi {{ $result['access_count'] }} kali
                        @if($result['last_accessed_at'])
                            • Terakhir: {{ $result['last_accessed_at'] }}
                        @endif
                    </p>
                </div>
            </div>

            <!-- Warning for non-valid -->
            @if($result['status'] !== 'valid')
            <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Perhatian</h3>
                        <p class="mt-1 text-sm text-yellow-700">
                            @if($result['status'] === 'superseded')
                                Dokumen ini sudah tidak berlaku karena telah digantikan dengan versi yang lebih baru: v{{ $result['document']['version'] }}.
                            @else
                                Dokumen ini sudah tidak berlaku karena telah dicabut oleh pihak yang berwenang.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
