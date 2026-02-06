<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Flash Messages -->
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Dokumen</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_documents'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Menunggu Tanda Tangan</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['pending_signature'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Sudah Ditandatangani</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['signed_valid'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Dicabut</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['revoked'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @if(auth()->user()->isSigner() && isset($stats['my_pending']))
            <div class="mb-8">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Anda memiliki <strong>{{ $stats['my_pending'] }}</strong> dokumen yang menunggu tanda tangan Anda.
                                <a href="{{ route('signatures.pending') }}" class="font-medium underline">Lihat dokumen</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Menu Cepat</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
                    <a href="{{ route('documents.create') }}" class="bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition flex flex-col items-center text-center">
                        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mb-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Unggah Dokumen</span>
                    </a>
                    <a href="{{ route('documents.index') }}" class="bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition flex flex-col items-center text-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mb-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Daftar Dokumen</span>
                    </a>
                    @endif
                    @if(auth()->user()->isSigner() || auth()->user()->isAdmin())
                    <a href="{{ route('signatures.pending') }}" class="bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition flex flex-col items-center text-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mb-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Tanda Tangan Pending</span>
                    </a>
                    <a href="{{ route('signatures.history') }}" class="bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition flex flex-col items-center text-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mb-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Riwayat Tanda Tangan</span>
                    </a>
                    @endif
                </div>
            </div>

            <!-- Recent Documents -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        @if(auth()->user()->isSigner() && !auth()->user()->isAdmin() && !auth()->user()->isOperator())
                            Dokumen Menunggu Tanda Tangan Anda
                        @else
                            Dokumen Terbaru
                        @endif
                    </h3>
                    @if($recentDocuments->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Dokumen</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    @if(auth()->user()->isSigner())
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($recentDocuments as $document)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $document->doc_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ Str::limit($document->title, 40) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusColors = [
                                                'draft' => 'bg-gray-100 text-gray-800',
                                                'submitted' => 'bg-blue-100 text-blue-800',
                                                'pending_signature' => 'bg-yellow-100 text-yellow-800',
                                                'signed_valid' => 'bg-green-100 text-green-800',
                                                'superseded' => 'bg-orange-100 text-orange-800',
                                                'revoked' => 'bg-red-100 text-red-800',
                                            ];
                                            $statusLabels = [
                                                'draft' => 'Draft',
                                                'submitted' => 'Diajukan',
                                                'pending_signature' => 'Menunggu TTD',
                                                'signed_valid' => 'Valid',
                                                'superseded' => 'Digantikan',
                                                'revoked' => 'Dicabut',
                                            ];
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$document->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $statusLabels[$document->status] ?? $document->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $document->created_at->format('d M Y') }}
                                    </td>
                                    @if(auth()->user()->isSigner())
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('signatures.show', $document) }}" class="text-indigo-600 hover:text-indigo-900">Tandatangani</a>
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="mt-2 text-gray-500 text-sm">
                            @if(auth()->user()->isSigner() && !auth()->user()->isAdmin())
                                Tidak ada dokumen yang menunggu tanda tangan Anda saat ini.
                            @else
                                Belum ada dokumen.
                            @endif
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Admin: Recent Audit Logs -->
            @if(auth()->user()->isAdmin() && $recentLogs->count() > 0)
            <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Aktivitas Terbaru</h3>
                        <a href="{{ route('audit-logs.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">Lihat semua</a>
                    </div>
                    <div class="space-y-3">
                        @foreach($recentLogs->take(10) as $log)
                        <div class="flex items-start space-x-3 text-sm">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-gray-100">
                                    <span class="text-xs font-medium text-gray-600">{{ substr($log->user?->name ?? 'S', 0, 1) }}</span>
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-gray-900">{{ $log->description }}</p>
                                <p class="text-gray-500 text-xs">{{ $log->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
