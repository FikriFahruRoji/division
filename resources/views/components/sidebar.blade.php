<aside 
    class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-zinc-900 border-r border-border-color dark:border-zinc-700 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 flex flex-col"
    :class="mobileMenuOpen ? 'translate-x-0 shadow-xl' : '-translate-x-full'"
>
    <!-- Logo -->
    <div class="flex items-center gap-3 px-6 py-5 border-b border-border-color dark:border-zinc-700 h-16">
        <div class="size-8 flex items-center justify-center text-primary">
            <span class="material-symbols-outlined text-4xl">edit</span>
        </div>
        <h2 class="text-text-main dark:text-white text-xl font-bold leading-tight tracking-tight">DigitalSign</h2>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-1">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-primary/10 text-text-main dark:text-white font-semibold' : 'text-text-secondary hover:bg-gray-50 dark:hover:bg-zinc-800' }} transition-colors group">
            <span class="material-symbols-outlined text-xl {{ request()->routeIs('dashboard') ? 'text-text-main dark:text-white' : 'text-text-secondary group-hover:text-text-main dark:group-hover:text-white' }}">dashboard</span>
            Dashboard
        </a>
        
        @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-text-secondary uppercase tracking-wider">Dokumen</p>
        </div>
        <a href="{{ route('documents.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('documents.*') ? 'bg-primary/10 text-text-main dark:text-white font-semibold' : 'text-text-secondary hover:bg-gray-50 dark:hover:bg-zinc-800' }} transition-colors group">
            <span class="material-symbols-outlined text-xl {{ request()->routeIs('documents.*') ? 'text-text-main dark:text-white' : 'text-text-secondary group-hover:text-text-main dark:group-hover:text-white' }}">description</span>
            Dokumen
        </a>
        <a href="{{ route('documents.create') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('documents.create') ? 'bg-primary/10 text-text-main dark:text-white font-semibold' : 'text-text-secondary hover:bg-gray-50 dark:hover:bg-zinc-800' }} transition-colors group">
            <span class="material-symbols-outlined text-xl {{ request()->routeIs('documents.create') ? 'text-text-main dark:text-white' : 'text-text-secondary group-hover:text-text-main dark:group-hover:text-white' }}">add_circle</span>
            Dokumen Baru
        </a>
        @endif
        
        @if(auth()->user()->isAdmin() || auth()->user()->isSigner())
        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-text-secondary uppercase tracking-wider">Tanda Tangan</p>
        </div>
        <a href="{{ route('signatures.pending') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('signatures.pending') || request()->routeIs('signatures.show') ? 'bg-primary/10 text-text-main dark:text-white font-semibold' : 'text-text-secondary hover:bg-gray-50 dark:hover:bg-zinc-800' }} transition-colors group">
            <span class="material-symbols-outlined text-xl {{ request()->routeIs('signatures.pending') || request()->routeIs('signatures.show') ? 'text-text-main dark:text-white' : 'text-text-secondary group-hover:text-text-main dark:group-hover:text-white' }}">draw</span>
            Perlu TTD
        </a>
        @endif
        
        @if(auth()->user()->isSigner())
        <a href="{{ route('signer-documents.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('signer-documents.*') ? 'bg-primary/10 text-text-main dark:text-white font-semibold' : 'text-text-secondary hover:bg-gray-50 dark:hover:bg-zinc-800' }} transition-colors group">
            <span class="material-symbols-outlined text-xl {{ request()->routeIs('signer-documents.*') ? 'text-text-main dark:text-white' : 'text-text-secondary group-hover:text-text-main dark:group-hover:text-white' }}">folder</span>
            Dokumen Saya
        </a>
        <a href="{{ route('signatures.history') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('signatures.history') ? 'bg-primary/10 text-text-main dark:text-white font-semibold' : 'text-text-secondary hover:bg-gray-50 dark:hover:bg-zinc-800' }} transition-colors group">
            <span class="material-symbols-outlined text-xl {{ request()->routeIs('signatures.history') ? 'text-text-main dark:text-white' : 'text-text-secondary group-hover:text-text-main dark:group-hover:text-white' }}">history</span>
            Riwayat
        </a>
        @endif
        
        @if(auth()->user()->isAdmin())
        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-text-secondary uppercase tracking-wider">Administrasi</p>
        </div>
        <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('users.*') ? 'bg-primary/10 text-text-main dark:text-white font-semibold' : 'text-text-secondary hover:bg-gray-50 dark:hover:bg-zinc-800' }} transition-colors group">
            <span class="material-symbols-outlined text-xl {{ request()->routeIs('users.*') ? 'text-text-main dark:text-white' : 'text-text-secondary group-hover:text-text-main dark:group-hover:text-white' }}">group</span>
            Users
        </a>
        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('departments.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('departments.*') ? 'bg-primary/10 text-text-main dark:text-white font-semibold' : 'text-text-secondary hover:bg-gray-50 dark:hover:bg-zinc-800' }} transition-colors group">
            <span class="material-symbols-outlined text-xl {{ request()->routeIs('departments.*') ? 'text-text-main dark:text-white' : 'text-text-secondary group-hover:text-text-main dark:group-hover:text-white' }}">corporate_fare</span>
            Departemen
        </a>
        @endif
        <a href="{{ route('audit-logs.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('audit-logs.*') ? 'bg-primary/10 text-text-main dark:text-white font-semibold' : 'text-text-secondary hover:bg-gray-50 dark:hover:bg-zinc-800' }} transition-colors group">
            <span class="material-symbols-outlined text-xl {{ request()->routeIs('audit-logs.*') ? 'text-text-main dark:text-white' : 'text-text-secondary group-hover:text-text-main dark:group-hover:text-white' }}">receipt_long</span>
            Audit Log
        </a>
        @endif
    </nav>

    <!-- Bottom Profile Section -->
    <div class="border-t border-border-color dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/50 p-4">
        <div class="relative" x-data="{ open: false }">
            <div class="flex items-center gap-3 cursor-pointer group" @click="open = !open">
                <div class="bg-primary/20 rounded-full size-10 flex items-center justify-center text-primary font-bold shadow-sm ring-2 ring-transparent group-hover:ring-primary/20 transition-all">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-text-main dark:text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-text-secondary truncate">{{ auth()->user()->email }}</p>
                </div>
                <span class="material-symbols-outlined text-text-secondary text-lg group-hover:text-primary transition-colors">expand_less</span>
            </div>

            <!-- Up-pop Menu -->
            <div x-cloak x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2" class="absolute bottom-full left-0 mb-3 w-full bg-white dark:bg-zinc-800 rounded-xl shadow-xl border border-border-color dark:border-zinc-700 py-1.5 overflow-hidden z-[60]">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-text-main dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                    <span class="material-symbols-outlined text-lg">person</span>
                    Profile
                </a>
                <div class="my-1 border-t border-border-color dark:border-zinc-700"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <span class="material-symbols-outlined text-lg">logout</span>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
