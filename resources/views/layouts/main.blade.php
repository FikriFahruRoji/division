<!DOCTYPE html>
<html class="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'DigitalSign') }} - @yield('title', 'Dashboard')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    
    <!-- Material Icons (using regular icons as fallback) -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Override to use Material Icons instead of Symbols */
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
            -webkit-font-feature-settings: 'liga';
            font-feature-settings: 'liga';
        }
        [x-cloak] { display: none !important; }
    </style>


    
    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f5c619",
                        "primary-hover": "#e6b800",
                        "background-light": "#f5f5f5", 
                        "background-dark": "#221e10",
                        "text-main": "#181611",
                        "text-secondary": "#8a8160",
                        "border-color": "#e6e4db",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: { 
                        "DEFAULT": "0.25rem", 
                        "lg": "0.5rem", 
                        "xl": "0.75rem", 
                        "full": "9999px" 
                    },
                },
            },
        }
    </script>
    
    @stack('styles')
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main font-display antialiased h-screen flex overflow-hidden" x-data="{ mobileMenuOpen: false }">
    
    <!-- Sidebar -->
    @include('components.sidebar')
    
    <!-- Mobile Backdrop -->
    <div x-cloak x-show="mobileMenuOpen" @click="mobileMenuOpen = false" x-transition.opacity class="fixed inset-0 z-40 bg-black/50 lg:hidden backdrop-blur-sm"></div>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        
        <!-- Top Header -->
        <header class="bg-white dark:bg-zinc-900 border-b border-border-color dark:border-zinc-700 h-16 flex items-center justify-between px-4 sm:px-6 z-30">
            <!-- Left: Mobile Toggle & Title -->
            <div class="flex items-center gap-4">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2 -ml-2 text-text-secondary hover:text-text-main hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                
                <div class="flex flex-col">
                    <h1 class="text-xl font-bold text-text-main dark:text-white leading-tight">
                        @yield('title', 'Dashboard')
                    </h1>
                    <!-- Optional Breadcrumbs can go here or be added dynamically -->
                    <p class="text-xs text-text-secondary hidden sm:block">
                        DigitalSign / @yield('title', 'Dashboard')
                    </p>
                </div>
            </div>

            <!-- Right: Actions (Notification, etc) -->
            <div class="flex items-center gap-3">
                <!-- Add Document Button (Short version for topbar) -->
                @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
                <a href="{{ route('documents.create') }}" class="hidden sm:flex items-center justify-center gap-2 rounded-lg bg-primary hover:bg-primary-hover transition-colors h-9 px-4 text-text-main text-sm font-bold shadow-sm">
                    <span class="material-symbols-outlined text-lg">add</span>
                    <span>Buat Dokumen</span>
                </a>
                
                <!-- Mobile Only Add Icon -->
                 <a href="{{ route('documents.create') }}" class="sm:hidden flex items-center justify-center size-9 rounded-lg bg-primary hover:bg-primary-hover transition-colors text-text-main shadow-sm">
                    <span class="material-symbols-outlined text-xl">add</span>
                </a>
                @endif
                
                <!-- Notification Bell (Placeholder) -->
                <button class="relative p-2 text-text-secondary hover:text-primary hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="absolute top-2 right-2 size-2 bg-red-500 rounded-full border border-white dark:border-zinc-900"></span>
                </button>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Flash Messages -->
                @if(session('success'))
                <div class="mb-4 sm:mb-6 flex items-center gap-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 shadow-sm animate-fade-in-down">
                    <div class="p-2 bg-green-100 dark:bg-green-800/30 rounded-lg text-green-600 dark:text-green-400">
                        <span class="material-symbols-outlined text-xl">check_circle</span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-green-800 dark:text-green-200">Berhasil</p>
                        <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                    </div>
                    <button onclick="this.parentElement.remove()" class="ml-auto text-green-600 hover:text-green-800">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                @endif
                
                @if(session('error'))
                <div class="mb-4 sm:mb-6 flex items-center gap-3 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 shadow-sm animate-fade-in-down">
                     <div class="p-2 bg-red-100 dark:bg-red-800/30 rounded-lg text-red-600 dark:text-red-400">
                        <span class="material-symbols-outlined text-xl">error</span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-red-800 dark:text-red-200">Gagal</p>
                        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
                    </div>
                    <button onclick="this.parentElement.remove()" class="ml-auto text-red-600 hover:text-red-800">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
    
    <!-- Alpine.js for dropdown -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @stack('scripts')
</body>
</html>
