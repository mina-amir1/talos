<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', config('talos.admin_title', 'Talos CMS'))</title>
    <link rel="icon" type="image/png" sizes="any" href="/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300;0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;1,14..32,400&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', 'system-ui', 'sans-serif'],
                },
            }
        }
    }
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            background: #f1f5f9;
            color: #1e293b;
            font-family: 'Inter', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        [x-cloak] { display: none !important; }

        /* ── Scrollbar ─────────────────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* ── Sidebar nav link ──────────────────────────── */
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.4375rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #64748b;
            text-decoration: none;
            transition: background 0.12s ease, color 0.12s ease;
            position: relative;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-link:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        .sidebar-link.active {
            background: #eff6ff;
            color: #2563eb;
        }
        .sidebar-link.active::before {
            content: '';
            position: absolute;
            left: 0; top: 20%; bottom: 20%;
            width: 2.5px;
            background: #2563eb;
            border-radius: 0 2px 2px 0;
        }
        .sidebar-link svg { flex-shrink: 0; opacity: 0.7; }
        .sidebar-link.active svg { opacity: 1; color: #2563eb; }

        /* ── Section label ─────────────────────────────── */
        .nav-section-label {
            padding: 0.5rem 0.75rem 0.25rem;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94a3b8;
            user-select: none;
        }

        /* ── Card ──────────────────────────────────────── */
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        /* ── Focus ring ────────────────────────────────── */
        input:focus, select:focus, textarea:focus, button:focus-visible {
            outline: 2px solid rgba(37, 99, 235, 0.4);
            outline-offset: 2px;
        }

    </style>
    @stack('styles')
</head>

<body x-data="{ sidebarOpen: true }">
<div class="flex h-full">

    {{-- ══════════════════════════════════════════════════════════════
         SIDEBAR
    ══════════════════════════════════════════════════════════════ --}}
    <aside x-show="sidebarOpen" x-cloak
           class="flex flex-col flex-shrink-0 h-full overflow-hidden transition-all"
           style="width:248px; background:#ffffff; border-right:1px solid #e2e8f0">

        {{-- Logo --}}
        <a href="{{ route('talos.dashboard') }}" class="flex items-center gap-3 px-4 py-4 hover:bg-slate-50 transition-colors" style="border-bottom:1px solid #e2e8f0">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-slate-800 leading-tight">{{ config('talos.admin_title', 'Talos CMS') }}</p>
                <p class="text-xs text-slate-400">v{{ config('talos.version', '1.0') }}</p>
            </div>
        </a>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto py-3 px-2.5 space-y-0.5">
            @php
                $types      = app(\App\Services\ContentTypeService::class)->all();
                $navUser    = $talosUser ?? null;
                $isSA       = $navUser?->is_super_admin ?? true;
                $navPerms   = $navUser?->role?->permissions ?? [];
                $canSection = fn(string $s) => $isSA || (bool)($navPerms['sections'][$s] ?? false);
                $canContent = fn(string $uid) => $isSA || !empty($navPerms['content-manager'][$uid] ?? []);
                $visibleTypes = array_filter($types, fn($t) => $canContent($t['__uid']));
            @endphp

            <p class="nav-section-label">Content</p>

            @php
                $iconPalette = [
                    ['bg' => '#dbeafe', 'text' => '#1d4ed8'], // blue
                    ['bg' => '#d1fae5', 'text' => '#065f46'], // emerald
                    ['bg' => '#ede9fe', 'text' => '#5b21b6'], // violet
                    ['bg' => '#fce7f3', 'text' => '#9d174d'], // pink
                    ['bg' => '#fef3c7', 'text' => '#92400e'], // amber
                    ['bg' => '#ccfbf1', 'text' => '#115e59'], // teal
                    ['bg' => '#e0e7ff', 'text' => '#3730a3'], // indigo
                    ['bg' => '#fee2e2', 'text' => '#991b1b'], // red
                    ['bg' => '#ffedd5', 'text' => '#9a3412'], // orange
                    ['bg' => '#cffafe', 'text' => '#155e75'], // cyan
                ];
            @endphp
            @forelse($visibleTypes as $type)
                @php
                    $palette  = $iconPalette[abs(crc32($type['__uid'])) % count($iconPalette)];
                    $initial  = strtoupper(mb_substr($type['info']['displayName'], 0, 1));
                @endphp
                <a href="{{ route('talos.content.index', ['uid' => $type['__uid']]) }}"
                   class="sidebar-link {{ request()->is('*content-manager/' . $type['__uid'] . '*') ? 'active' : '' }}">
                    <span class="inline-flex items-center justify-center w-4 h-4 rounded text-[9px] font-bold flex-shrink-0"
                          style="background:{{ $palette['bg'] }}; color:{{ $palette['text'] }}">{{ $initial }}</span>
                    <span class="truncate">{{ $type['info']['displayName'] }}</span>
                </a>
            @empty
                <p class="px-3 py-1.5 text-xs italic text-slate-400">No content types yet</p>
            @endforelse

            {{-- Builder --}}
            @if($canSection('content-type-builder') || $canSection('components'))
                <div class="pt-3 mt-2" style="border-top:1px solid #e2e8f0">
                    <p class="nav-section-label">Builder</p>
                    @if($canSection('content-type-builder'))
                        <a href="{{ route('talos.content-type-builder.index') }}"
                           class="sidebar-link {{ request()->is('*content-type-builder*') ? 'active' : '' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                            </svg>
                            Content-Type Builder
                        </a>
                    @endif
                    @if($canSection('components'))
                        <a href="{{ route('talos.components.index') }}"
                           class="sidebar-link {{ request()->is('*components*') ? 'active' : '' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Components
                        </a>
                    @endif
                </div>
            @endif

            {{-- Assets & Config --}}
            @if($canSection('media') || $canSection('settings') || $canSection('locales') || $canSection('api-tokens') || $canSection('storage') || $canSection('backup') || $canSection('webhooks'))
                <div class="pt-3 mt-2" style="border-top:1px solid #e2e8f0">
                    <p class="nav-section-label">Assets & Config</p>
                    @if($canSection('media'))
                        <a href="{{ route('talos.media.index') }}"
                           class="sidebar-link {{ request()->is('*media*') ? 'active' : '' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Media Library
                        </a>
                    @endif
                    @if($canSection('locales'))
                        <a href="{{ route('talos.settings.locales') }}"
                           class="sidebar-link {{ request()->is('*settings/locales*') ? 'active' : '' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                            </svg>
                            Locales
                        </a>
                    @endif
                    @if($canSection('settings'))
                        <a href="{{ route('talos.settings.roles') }}"
                           class="sidebar-link {{ request()->is('*settings/roles*') ? 'active' : '' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Roles & Permissions
                        </a>
                        <a href="{{ route('talos.settings.users') }}"
                           class="sidebar-link {{ request()->is('*settings/users*') ? 'active' : '' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            Admin Users
                        </a>
                    @endif
                    @if($canSection('api-tokens'))
                        <a href="{{ route('talos.settings.api-tokens') }}"
                           class="sidebar-link {{ request()->is('*settings/api-tokens*') ? 'active' : '' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            API Tokens
                        </a>
                    @endif
                    @if($canSection('storage'))
                        <a href="{{ route('talos.settings.storage') }}"
                           class="sidebar-link {{ request()->is('*settings/storage*') ? 'active' : '' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                            </svg>
                            Storage
                        </a>
                    @endif
                    @if($canSection('backup'))
                        <a href="{{ route('talos.settings.backup') }}"
                           class="sidebar-link {{ request()->is('*settings/backup*') ? 'active' : '' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                            </svg>
                            Backup
                        </a>
                    @endif
                    @if($canSection('webhooks'))
                        <a href="{{ route('talos.settings.webhooks') }}"
                           class="sidebar-link {{ request()->is('*settings/webhooks*') ? 'active' : '' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Webhooks
                        </a>
                    @endif
                </div>
            @endif
        </nav>

        {{-- User footer --}}
        <div class="px-3 py-3" style="border-top:1px solid #e2e8f0">
            <div class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl bg-slate-50">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold flex-shrink-0 text-white bg-blue-600">
                    {{ strtoupper(substr(session('talos_user_name', 'A'), 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium truncate text-slate-700">{{ session('talos_user_name', 'Admin') }}</p>
                    <p class="text-xs truncate text-slate-400">{{ session('talos_user_email') }}</p>
                </div>
                <a href="{{ route('talos.settings.profile') }}" title="My profile"
                   class="p-1 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a4 4 0 01-2.828 1.172H7v-2a4 4 0 011.172-2.828z"/>
                    </svg>
                </a>
                <form action="{{ route('talos.logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Sign out"
                            class="p-1 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ══════════════════════════════════════════════════════════════
         MAIN AREA
    ══════════════════════════════════════════════════════════════ --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Top bar --}}
        <header class="flex items-center gap-3 px-6 flex-shrink-0"
                style="height:52px; background:#ffffff; border-bottom:1px solid #e2e8f0">
            <button @click="sidebarOpen = !sidebarOpen"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <h1 class="text-sm font-semibold text-slate-700 flex-1">@yield('header', 'Dashboard')</h1>

            <div class="flex items-center gap-2">
                @yield('header-actions')
            </div>
        </header>

        {{-- Content --}}
        <main class="flex-1 overflow-y-auto p-6 bg-slate-50" id="talos-main">

            {{-- Flash: errors --}}
            @if($errors->any())
                <div class="mb-5 flex items-start gap-2.5 px-4 py-3 rounded-xl text-sm bg-red-50 border border-red-200 text-red-700">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <ul class="space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Flash: new API token --}}
            @if(session('new_token'))
                <div class="mb-5 px-4 py-3 rounded-xl text-sm bg-amber-50 border border-amber-200 text-amber-800">
                    <p class="font-semibold mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Copy your token now — it won't be shown again
                    </p>
                    <div class="flex items-center gap-2 mt-2" x-data>
                        <code class="flex-1 px-3 py-2 rounded-lg text-xs break-all bg-amber-100 text-amber-900 font-mono">{{ session('new_token') }}</code>
                        <button @click="navigator.clipboard.writeText('{{ session('new_token') }}')"
                                class="px-3 py-2 rounded-lg text-xs font-medium bg-amber-600 hover:bg-amber-700 text-white transition-colors">
                            Copy
                        </button>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')

{{-- Toast container --}}
<div id="talos-toasts" class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none" style="min-width:280px;max-width:360px"></div>

{{-- Confirm modal --}}
<div id="talos-confirm-backdrop" data-no-dirty
     class="fixed inset-0 z-[9998] flex items-center justify-center bg-black/40 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4">
        <p id="talos-confirm-message" class="text-sm text-slate-700 leading-relaxed mb-5"></p>
        <div class="flex justify-end gap-2">
            <button id="talos-confirm-cancel"
                    class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200">
                Cancel
            </button>
            <button id="talos-confirm-ok"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600">
                Confirm
            </button>
        </div>
    </div>
</div>

<script>
window.talos = (() => {
    // ── Toast ────────────────────────────────────────────────────────────────
    function toast(message, type = 'info') {
        const colors = {
            success: 'border-emerald-400 bg-emerald-50 text-emerald-800',
            error:   'border-red-400 bg-red-50 text-red-800',
            info:    'border-blue-400 bg-blue-50 text-blue-800',
            warning: 'border-amber-400 bg-amber-50 text-amber-800',
        };
        const icons = {
            success: `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`,
            error:   `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`,
            info:    `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z"/></svg>`,
            warning: `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>`,
        };

        const el = document.createElement('div');
        el.className = `pointer-events-auto flex items-start gap-2.5 border rounded-xl px-4 py-3 shadow-lg text-sm font-medium
                        transition-all duration-300 translate-x-0 opacity-100 ${colors[type] ?? colors.info}`;
        el.innerHTML = (icons[type] ?? icons.info) + `<span>${message}</span>`;

        const container = document.getElementById('talos-toasts');
        container.appendChild(el);

        // Animate out after 4s
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transform = 'translateX(20px)';
            setTimeout(() => el.remove(), 300);
        }, 4000);
    }

    // ── Confirm ──────────────────────────────────────────────────────────────
    let _confirmResolve = null;

    const backdrop = document.getElementById('talos-confirm-backdrop');
    const msgEl    = document.getElementById('talos-confirm-message');
    const okBtn    = document.getElementById('talos-confirm-ok');
    const cancelBtn = document.getElementById('talos-confirm-cancel');

    function closeConfirm(result) {
        backdrop.classList.add('hidden');
        if (_confirmResolve) { _confirmResolve(result); _confirmResolve = null; }
    }

    okBtn.addEventListener('click',     () => closeConfirm(true));
    cancelBtn.addEventListener('click', () => closeConfirm(false));
    backdrop.addEventListener('click',  (e) => { if (e.target === backdrop) closeConfirm(false); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeConfirm(false); });

    function confirm(message, { confirmLabel = 'Confirm', danger = true } = {}) {
        msgEl.textContent = message;
        okBtn.textContent = confirmLabel;
        okBtn.className = `px-4 py-2 text-sm font-medium text-white rounded-lg ${danger ? 'bg-red-500 hover:bg-red-600' : 'bg-blue-600 hover:bg-blue-700'}`;
        backdrop.classList.remove('hidden');
        return new Promise(resolve => { _confirmResolve = resolve; });
    }

    // ── Form data-confirm interception ───────────────────────────────────────
    document.addEventListener('submit', async function (e) {
        const msg = e.target.dataset.confirm;
        if (!msg) return;
        e.preventDefault();
        const ok = await confirm(msg);
        if (ok) { e.target.dataset.confirm = ''; e.target.submit(); }
    });

    // ── Unsaved changes tracking ──────────────────────────────────────────────
    let _dirty = false;

    function markDirty() { _dirty = true; }
    function markClean() { _dirty = false; }
    function isDirty()   { return _dirty; }

    // Any input/change on the page marks it dirty (opt-out with data-no-dirty)
    document.addEventListener('input',  (e) => { if (!e.target.closest('[data-no-dirty]')) markDirty(); });
    document.addEventListener('change', (e) => { if (!e.target.closest('[data-no-dirty]')) markDirty(); });

    // Clean up on any form submit (save action)
    document.addEventListener('submit', () => markClean(), true);

    // Native browser warning (refresh, close tab, back/forward)
    window.addEventListener('beforeunload', (e) => {
        if (!_dirty) return;
        e.preventDefault();
        e.returnValue = '';
    });

    // Intercept internal link clicks when dirty
    document.addEventListener('click', async (e) => {
        if (!_dirty) return;
        const link = e.target.closest('a[href]');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
        // Allow target="_blank" — new tab, no navigation loss
        if (link.target === '_blank') return;

        e.preventDefault();
        const leave = await confirm('You have unsaved changes that will be lost. Leave this page?', { confirmLabel: 'Leave', danger: true });
        if (leave) {
            markClean();
            window.location.href = link.href;
        }
    });

    return { toast, confirm, markDirty, markClean, isDirty };
})();

@if(session('success'))
talos.toast('{{ session('success') }}', 'success');
@endif
</script>
</body>
</html>
