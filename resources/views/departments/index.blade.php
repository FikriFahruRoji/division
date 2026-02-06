@extends('layouts.main')

@section('title', 'Departemen')

@section('content')
<!-- Page Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
    <div>
        <h1 class="text-text-main dark:text-white text-2xl font-bold">Departemen</h1>
        <p class="text-text-secondary mt-1">Kelola master data departemen</p>
    </div>
    <a href="{{ route('departments.create') }}" 
        class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors w-fit">
        <span class="material-symbols-outlined text-lg">add</span>
        Tambah Departemen
    </a>
</div>

<!-- Search & Filter -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 p-4 mb-6">
    <form action="{{ route('departments.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari departemen..."
                class="w-full px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary">
        </div>
        <select name="status" class="px-4 py-2.5 border-none rounded-lg bg-background-light dark:bg-zinc-700 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="">Semua Status</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
        </select>
        <button type="submit" class="px-4 py-2.5 bg-primary hover:bg-primary-hover rounded-lg text-text-main text-sm font-semibold transition-colors">
            <span class="material-symbols-outlined text-lg">search</span>
        </button>
    </form>
</div>

<!-- Departments Table -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-border-color dark:border-zinc-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-background-light dark:bg-zinc-700/50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Kode</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Nama</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Jumlah User</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-text-secondary uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-color dark:divide-zinc-700">
                @forelse($departments as $department)
                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/30 transition-colors">
                    <td class="px-6 py-4">
                        <span class="font-mono text-sm text-text-main dark:text-white bg-gray-100 dark:bg-zinc-700 px-2 py-1 rounded">{{ $department->code }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-text-main dark:text-white font-medium">{{ $department->name }}</div>
                        @if($department->description)
                            <div class="text-text-secondary text-sm truncate max-w-xs">{{ $department->description }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-text-main dark:text-white">{{ $department->users_count }}</td>
                    <td class="px-6 py-4">
                        @if($department->status === 'active')
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Aktif</span>
                        @else
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-zinc-700 dark:text-gray-400">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('departments.edit', $department) }}" 
                                class="p-2 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition-colors" title="Edit">
                                <span class="material-symbols-outlined text-text-secondary">edit</span>
                            </a>
                            @if($department->users_count === 0)
                            <button type="button" 
                                onclick="showDeleteModal('{{ $department->id }}', '{{ $department->name }}')"
                                class="p-2 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Hapus">
                                <span class="material-symbols-outlined text-red-500">delete</span>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-text-secondary">
                        <span class="material-symbols-outlined text-4xl mb-2">corporate_fare</span>
                        <p>Belum ada departemen</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($departments->hasPages())
    <div class="px-6 py-4 border-t border-border-color dark:border-zinc-700">
        {{ $departments->links() }}
    </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="hideDeleteModal()"></div>
    
    <!-- Modal Content -->
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl max-w-md w-full transform transition-all">
            <div class="p-6">
                <!-- Icon -->
                <div class="mx-auto w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-3xl text-red-500">warning</span>
                </div>
                
                <!-- Title -->
                <h3 class="text-xl font-bold text-text-main dark:text-white text-center mb-2">
                    Hapus Departemen?
                </h3>
                
                <!-- Message -->
                <p class="text-text-secondary text-center mb-6">
                    Apakah Anda yakin ingin menghapus departemen <strong id="deleteDeptName" class="text-text-main dark:text-white"></strong>? 
                    Tindakan ini tidak dapat dibatalkan.
                </p>
                
                <!-- Actions -->
                <div class="flex gap-3">
                    <button type="button" onclick="hideDeleteModal()"
                        class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-zinc-700 hover:bg-gray-200 dark:hover:bg-zinc-600 rounded-lg text-text-main dark:text-white font-medium transition-colors">
                        Batal
                    </button>
                    <form id="deleteForm" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full px-4 py-2.5 bg-red-500 hover:bg-red-600 rounded-lg text-white font-medium transition-colors">
                            Ya, Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showDeleteModal(id, name) {
    document.getElementById('deleteDeptName').textContent = name;
    document.getElementById('deleteForm').action = '/departments/' + id;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function hideDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideDeleteModal();
    }
});
</script>
@endsection
