<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Vehicle maintenance record')</title>
    <link rel="icon" href="{{ asset('images/harent-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- jQuery (Required for Select2, DateRangePicker, DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/v/dt/dt-2.0.2/b-3.0.1/b-colvis-3.0.1/b-html5-3.0.1/r-3.0.0/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/dt/dt-2.0.2/b-3.0.1/b-colvis-3.0.1/b-html5-3.0.1/r-3.0.0/datatables.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        [x-cloak] { display: none !important; }
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        .dark .glass {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.1) 0%, rgba(99, 102, 241, 0.05) 100%);
            color: #4f46e5;
            border-right: 3px solid #4f46e5;
        }
        .dark .sidebar-link.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            color: #818cf8;
            border-right: 3px solid #818cf8;
        }

        /* Dark Mode Transitions */
        .theme-transition {
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }
    </style>
    <script>
        // On page load or when changing themes, best to add inline in `head` to avoid FOUC
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased theme-transition" 
      x-data="{ 
        sidebarOpen: false, 
        sidebarCollapsed: false,
        showUploadModal: false,
        isUploading: false,
        isDark: document.documentElement.classList.contains('dark'),
        toggleTheme() {
            if (this.isDark) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
                this.isDark = false;
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
                this.isDark = true;
            }
        },
        init() {
            this.$watch('isDark', value => {
                // Ensure consistency
                if (value) document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');
            });
        }
      }"
      x-init="init()">
    
    <!-- Mobile Header -->
    <header class="lg:hidden flex items-center justify-between p-4 glass sticky top-0 z-50">
        <a href="{{ \App\Models\Setting::get('app_logo_link', route('maintenance.dashboard')) }}" class="flex items-center gap-2">
            <img src="{{ \App\Models\Setting::get('app_logo_path', asset('images/harent-logo.png')) }}" alt="Vehicle maintenance record" class="h-10 w-auto object-contain">
        </a>
        <div class="flex items-center gap-2">
            <button @click="toggleTheme()" class="p-2 rounded-lg bg-white dark:bg-slate-800 shadow-sm text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none transition-colors">
                <svg x-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                <svg x-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9h-1m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </button>
            <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg bg-white dark:bg-slate-800 shadow-sm text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>
    </header>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside 
            :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full lg:translate-x-0': !sidebarOpen, 'lg:w-20': sidebarCollapsed, 'lg:w-64': !sidebarCollapsed }"
            class="fixed inset-y-0 left-0 z-40 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 transition-all duration-300 lg:static transform flex flex-col w-64 h-screen lg:h-auto overflow-hidden">
            
            <!-- Sidebar Header -->
            <a href="{{ \App\Models\Setting::get('app_logo_link', route('maintenance.dashboard')) }}" 
               class="h-20 flex items-center justify-center border-b border-slate-100 flex-shrink-0 hover:opacity-80 transition-opacity" 
               :class="sidebarCollapsed ? 'px-0' : 'px-6 justify-start'">
                <img src="{{ \App\Models\Setting::get('app_logo_path', asset('images/harent-logo.png')) }}" alt="Vehicle maintenance record" 
                     class="transition-all duration-300 object-contain"
                     :class="sidebarCollapsed ? 'h-8 w-8' : 'h-12 w-auto'" />
            </a>

            <!-- Scrollable Nav -->
            <nav class="p-4 space-y-1 flex-1 overflow-y-auto overflow-x-hidden custom-scrollbar">


                <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2 mt-6 px-2 whitespace-nowrap overflow-hidden transition-all duration-300"
                     :class="sidebarCollapsed ? 'text-center' : 'px-4'">
                     <span x-show="!sidebarCollapsed">Operations</span>
                     <span x-show="sidebarCollapsed" class="block w-full border-b border-slate-200 dark:border-slate-800"></span>
                </div>

                <a href="{{ route('maintenance.vehicle.transactions') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all group {{ request()->routeIs('maintenance.dashboard') || request()->routeIs('maintenance.vehicle.transactions') ? 'active' : '' }}"
                   title="Maintenance History">
                   <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" 
                          :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Maintenance History</span>
                </a>

                <a href="{{ route('maintenance.repair.jobs') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all group {{ request()->routeIs('maintenance.repair.jobs') ? 'active' : '' }}"
                   title="Repair Jobs">
                   <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" 
                          :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Repair Jobs</span>
                </a>

                <a href="{{ route('maintenance.batch.export') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all group {{ request()->routeIs('maintenance.batch.export') ? 'active' : '' }}"
                   title="Batch Export">
                   <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" 
                          :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Batch Export</span>
                </a>

                <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2 mt-6 px-2 whitespace-nowrap overflow-hidden transition-all duration-300"
                     :class="sidebarCollapsed ? 'text-center' : 'px-4'">
                     <span x-show="!sidebarCollapsed">Utilities</span>
                     <span x-show="sidebarCollapsed" class="block w-full border-b border-slate-200 dark:border-slate-800"></span>
                </div>



                <a href="{{ route('2fa.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all group {{ request()->routeIs('2fa.*') ? 'active' : '' }}"
                   title="Account Security">
                   <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" 
                          :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Account Security</span>
                </a>

                @if(auth()->check() && auth()->user()->hasRole('admin'))
                <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2 mt-6 px-2 whitespace-nowrap overflow-hidden transition-all duration-300"
                     :class="sidebarCollapsed ? 'text-center' : 'px-4'">
                     <span x-show="!sidebarCollapsed">Admin</span>
                     <span x-show="sidebarCollapsed" class="block w-full border-b border-slate-200 dark:border-slate-800"></span>
                </div>

                <a href="{{ route('maintenance.odoo.settings') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all group {{ request()->routeIs('maintenance.odoo.settings') ? 'active' : '' }}"
                   title="Maintenance Config">
                   <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" 
                          :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Maintenance Config</span>
                </a>

                <a href="{{ route('admin.users.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all group {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" title="Users">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Users</span>
                </a>

                <a href="{{ route('admin.sessions.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all group {{ request()->routeIs('admin.sessions.*') ? 'active' : '' }}" title="Sessions">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Sessions</span>
                </a>

                <a href="{{ route('admin.backups.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all group {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}" title="Backups">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Backups</span>
                </a>
                @endif

            </nav>

            <!-- Sidebar Footer -->
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 whitespace-nowrap overflow-hidden transition-all duration-300 flex-shrink-0"
                 :class="sidebarCollapsed ? 'items-center justify-center p-2' : ''">
                <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'justify-center' : ''">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold text-xs flex-shrink-0">
                        @if(auth()->check())
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        @else
                            ?
                        @endif
                    </div>
                    <div :class="sidebarCollapsed ? 'hidden' : 'block flex-1'">
                        @if(auth()->check())
                            <div class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 capitalize">{{ auth()->user()->role ?? 'User' }}</div>
                            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                                    @csrf
                                    <button type="submit" class="text-xs text-red-500 hover:text-red-600 transition-colors">Logout</button>
                            </form>
                        @else
                            <div class="text-sm font-bold text-slate-700 dark:text-slate-200">Guest</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">Not logged in</div>
                            <div class="mt-1 flex gap-2">
                                <a href="{{ route('login') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Login</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </aside>

        <!-- Overlay -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-black/20 backdrop-blur-sm lg:hidden" style="display: none;"></div>

        <!-- Main Content -->
        <main class="flex-1 min-w-0 overflow-hidden bg-slate-50/50 dark:bg-slate-950 flex flex-col theme-transition">
            <!-- Desktop Top Bar -->
            <div class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 p-4 hidden lg:flex items-center gap-4 sticky top-0 z-20">
                <button @click="sidebarCollapsed = !sidebarCollapsed" class="p-2 text-slate-500 hover:text-indigo-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                </button>
                <div class="flex-1 px-4">
                    <form action="{{ route('maintenance.search') }}" method="GET" class="max-w-md w-full relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" name="q" class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all bg-slate-50 dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-700 placeholder-slate-400 dark:placeholder-slate-500" placeholder="Global Search (Lot, Product, Location)...">
                    </form>
                </div>

                <!-- Theme Toggle -->
                <button @click="toggleTheme()" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:border-indigo-100 dark:hover:border-indigo-900 transition-all shadow-sm">
                    <svg x-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    <svg x-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9h-1m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto">
                <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="font-medium">{{ session('success') }}</span>
                        </div>
                        <button @click="show = false" class="text-green-500 hover:text-green-700"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                    </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </main>
    </div>
    
    
    <!-- Upload Modal Removed -->

    @yield('scripts')
    
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
    </script>
</body>
</html>
