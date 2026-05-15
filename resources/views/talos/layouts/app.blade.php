<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-950">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', config('talos.admin_title', 'Talos CMS'))</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50:  '#f0f4ff',
                            100: '#dce6ff',
                            200: '#b9ccff',
                            300: '#86a8ff',
                            400: '#4d7eff',
                            500: '#2563eb',
                            600: '#1d4ed8',
                            700: '#1e40af',
                            800: '#1e3a8a',
                            900: '#1e3060',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            font-weight: 500;
            color: rgb(156 163 175); /* gray-400 */
            text-decoration: none;
            transition: color 0.15s, background-color 0.15s;
        }
        .sidebar-link:hover {
            color: #fff;
            background-color: rgb(31 41 55); /* gray-800 */
        }
        .sidebar-link.active {
            background-color: rgb(29 78 216); /* blue-700 */
            color: #fff;
        }
    </style>
    @stack('styles')
</head>
<body class="h-full" x-data="{ sidebarOpen: true }">
<div class="flex h-full">

    {{-- ── Sidebar ──────────────────────────────────────────────── --}}
    <aside class="flex flex-col w-64 bg-gray-900 border-r border-gray-800 flex-shrink-0" x-show="sidebarOpen">
        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-800">
            <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18" />
                </svg>
            </div>
            <span class="text-white font-bold text-lg tracking-tight">Talos</span>
            <span class="text-gray-500 text-xs ml-auto">v{{ config('talos.version') }}</span>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            {{-- Content Manager --}}
            <p class="px-3 mb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Content</p>

            @php $types = app(\App\Services\ContentTypeService::class)->all(); @endphp
            @forelse($types as $type)
                <a href="{{ route('talos.content.index', ['uid' => $type['__uid']]) }}"
                   class="sidebar-link {{ request()->is('*content-manager/' . $type['__uid'] . '*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    {{ $type['info']['displayName'] }}
                </a>
            @empty
                <p class="px-3 py-2 text-xs text-gray-600 italic">No content types yet</p>
            @endforelse

            <div class="my-3 border-t border-gray-800"></div>

            {{-- Builder --}}
            <p class="px-3 mb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Builder</p>

            <a href="{{ route('talos.content-type-builder.index') }}"
               class="sidebar-link {{ request()->is('*content-type-builder*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                </svg>
                Content-Type Builder
            </a>

            <a href="{{ route('talos.components.index') }}"
               class="sidebar-link {{ request()->is('*components*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                Components
            </a>

            <div class="my-3 border-t border-gray-800"></div>

            {{-- Media & Settings --}}
            <p class="px-3 mb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Assets & Config</p>

            <a href="{{ route('talos.media.index') }}"
               class="sidebar-link {{ request()->is('*media*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Media Library
            </a>

            <a href="{{ route('talos.settings.roles') }}"
               class="sidebar-link {{ request()->is('*settings/roles*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Roles & Permissions
            </a>

            <a href="{{ route('talos.settings.users') }}"
               class="sidebar-link {{ request()->is('*settings/users*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Admin Users
            </a>

            <a href="{{ route('talos.settings.api-tokens') }}"
               class="sidebar-link {{ request()->is('*settings/api-tokens*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                API Tokens
            </a>
        </nav>

        {{-- User info --}}
        <div class="px-4 py-4 border-t border-gray-800">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-brand-600 rounded-full flex items-center justify-center text-white text-xs font-bold">
                    {{ strtoupper(substr(session('talos_user_name', 'A'), 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-white font-medium truncate">{{ session('talos_user_name') }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ session('talos_user_email') }}</p>
                </div>
                <form action="{{ route('talos.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-gray-500 hover:text-red-400 transition-colors" title="Logout">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ── Main ─────────────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Top bar --}}
        <header class="flex items-center gap-4 px-6 py-4 bg-gray-900 border-b border-gray-800">
            <button @click="sidebarOpen = !sidebarOpen" class="text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <h1 class="text-white font-semibold text-lg flex-1">@yield('header', 'Dashboard')</h1>
            @yield('header-actions')
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="mx-6 mt-4 p-4 bg-green-900/40 border border-green-700 rounded-lg text-green-300 text-sm flex items-center gap-2"
                 x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mx-6 mt-4 p-4 bg-red-900/40 border border-red-700 rounded-lg text-red-300 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- New API token reveal --}}
        @if(session('new_token'))
            <div class="mx-6 mt-4 p-4 bg-yellow-900/40 border border-yellow-600 rounded-lg text-yellow-300 text-sm"
                 x-data>
                <p class="font-semibold mb-2">⚠ Copy your API token now — it will not be shown again:</p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 bg-gray-900 rounded px-3 py-2 text-yellow-200 text-xs break-all">{{ session('new_token') }}</code>
                    <button @click="navigator.clipboard.writeText('{{ session('new_token') }}')"
                            class="px-3 py-2 bg-yellow-700 hover:bg-yellow-600 rounded text-xs text-white">Copy</button>
                </div>
            </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto bg-gray-950 p-6">
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
