@extends('talos.layouts.app')

@section('title', 'Roles & Permissions — Talos')
@section('header', 'Roles & Permissions')

@section('content')
@php
    $sectionDefs = [
        'content-type-builder' => 'Content-Type Builder',
        'components'           => 'Components',
        'media'                => 'Media Library',
        'settings'             => 'Settings (Roles & Users)',
        'locales'              => 'Locales',
        'api-tokens'           => 'API Tokens',
        'storage'              => 'Storage',
        'backup'               => 'Backup',
        'webhooks'             => 'Webhooks',
    ];
    $cmActions = ['read' => 'Read', 'create' => 'Create', 'update' => 'Update', 'delete' => 'Delete', 'publish' => 'Publish'];
@endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- ── Role list with inline permission editors (2/3) ──────────── --}}
    <div class="xl:col-span-2 space-y-3">

        @forelse($roles as $role)
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden"
                 x-data="roleEditor(
                     {{ $role->id }},
                     @js($role->permissions ?? []),
                     @js($contentTypes),
                     '{{ route('talos.settings.roles.update', $role->id) }}'
                 )"
                 x-init="init()">

                {{-- Role header --}}
                <div class="flex items-center justify-between px-5 py-4">
                    <div>
                        <p class="text-sm font-medium text-slate-800">{{ $role->name }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ $role->description ? $role->description . ' · ' : '' }}{{ $role->users_count }} user(s)
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="open = !open"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs border rounded-lg transition-colors"
                                :class="open
                                    ? 'border-blue-300 text-blue-600 bg-blue-50'
                                    : 'border-slate-300 text-slate-500 hover:text-slate-900 hover:border-slate-300'">
                            <svg class="w-3 h-3 transition-transform" :class="open && 'rotate-90'"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span x-text="open ? 'Close' : 'Edit permissions'"></span>
                        </button>

                        <form action="{{ route('talos.settings.roles.destroy', $role->id) }}"
                              method="POST" data-confirm="Delete role {{ addslashes($role->name) }}? Users with this role will lose all permissions.">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 transition-colors" title="Delete role">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Permission editor --}}
                <div x-show="open" x-cloak class="border-t border-slate-200">

                    {{-- Admin sections --}}
                    <div class="px-5 py-4 border-b border-slate-200">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Admin Sections</p>
                        <div class="grid grid-cols-2 gap-y-2 gap-x-4">
                            @foreach($sectionDefs as $key => $label)
                                <label class="flex items-center gap-2.5 cursor-pointer group">
                                    <input type="checkbox"
                                           x-model="sections['{{ $key }}']"
                                           class="w-4 h-4 rounded border-slate-300 bg-white text-blue-600 focus:ring-blue-500 focus:ring-offset-white cursor-pointer">
                                    <span class="text-sm text-slate-600 group-hover:text-slate-900 transition-colors">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Content Manager permissions --}}
                    <div class="px-5 py-4 border-b border-slate-200">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Content Manager</p>
                            <div class="flex gap-3">
                                <button type="button" @click="selectAll(true)"
                                        class="text-xs text-blue-600 hover:text-blue-500 transition-colors">Select all</button>
                                <span class="text-gray-700">·</span>
                                <button type="button" @click="selectAll(false)"
                                        class="text-xs text-slate-400 hover:text-slate-500 transition-colors">Clear all</button>
                            </div>
                        </div>

                        @if(empty($contentTypes))
                            <p class="text-xs text-slate-400 italic">No content types defined yet.</p>
                        @else
                            <div class="overflow-x-auto rounded-lg border border-slate-300">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="border-b border-slate-300 bg-slate-100">
                                            <th class="text-left px-3 py-2 text-slate-500 font-medium">Collection</th>
                                            @foreach($cmActions as $action => $actionLabel)
                                                <th class="px-3 py-2 text-center text-slate-500 font-medium">
                                                    <div class="flex flex-col items-center gap-1">
                                                        <span>{{ $actionLabel }}</span>
                                                        <button type="button"
                                                                @click="toggleCol('{{ $action }}')"
                                                                class="text-slate-400 hover:text-blue-600 transition-colors"
                                                                title="Toggle all {{ $actionLabel }}">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        <template x-for="type in types" :key="type.__uid">
                                            <tr class="hover:bg-slate-50 transition-colors">
                                                <td class="px-3 py-2.5">
                                                    <p class="text-slate-800 font-medium" x-text="type.info.displayName"></p>
                                                    <p class="text-slate-400 text-[10px]"
                                                       x-text="(type.kind ?? 'collectionType') === 'singleType' ? 'Single' : 'Collection'"></p>
                                                </td>
                                                <template x-for="action in ['read', 'create', 'update', 'delete', 'publish']" :key="action">
                                                    <td class="px-3 py-2.5 text-center">
                                                        <input type="checkbox"
                                                               x-model="contentManager[type.__uid][action]"
                                                               class="w-4 h-4 rounded border-slate-300 bg-white text-blue-600 focus:ring-blue-500 focus:ring-offset-white cursor-pointer">
                                                    </td>
                                                </template>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- Save --}}
                    <div class="px-5 py-3 flex items-center gap-3">
                        <button @click="save()"
                                :disabled="saving"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white rounded-lg text-sm font-semibold transition-colors">
                            <span x-text="saving ? 'Saving…' : 'Save permissions'"></span>
                        </button>
                        <span x-show="saved" x-cloak class="text-xs text-emerald-700 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Saved
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white border border-slate-200 rounded-xl px-5 py-10 text-center text-slate-400 text-sm">
                No roles yet. Create one to get started.
            </div>
        @endforelse
    </div>

    {{-- ── Create role (1/3) ─────────────────────────────────────────── --}}
    <div class="bg-white border border-slate-200 rounded-xl p-5 h-fit">
        <h2 class="text-slate-800 font-semibold mb-4">Create Role</h2>
        <form action="{{ route('talos.settings.roles.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1.5">Name</label>
                <input name="name" type="text" required placeholder="e.g. Editor"
                       class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1.5">Description <span class="text-slate-400">(optional)</span></label>
                <input name="description" type="text" placeholder="What can this role do?"
                       class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
            </div>
            <button type="submit"
                    class="w-full py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors">
                Create role
            </button>
        </form>

        <div class="mt-5 pt-5 border-t border-slate-200 space-y-2">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">How it works</p>
            <p class="text-xs text-slate-400">Create a role, then expand it to set which admin sections and content types users with that role can access.</p>
            <p class="text-xs text-slate-400">Super admins always have full access regardless of role.</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function roleEditor(id, existingPerms, types, updateUrl) {
    return {
        id, types, updateUrl,
        open: false,
        saving: false,
        saved: false,
        sections: {
            'content-type-builder': existingPerms?.sections?.['content-type-builder'] ?? false,
            'components':           existingPerms?.sections?.['components'] ?? false,
            'media':                existingPerms?.sections?.['media'] ?? false,
            'settings':             existingPerms?.sections?.['settings'] ?? false,
            'locales':              existingPerms?.sections?.['locales'] ?? false,
            'api-tokens':           existingPerms?.sections?.['api-tokens'] ?? false,
            'storage':              existingPerms?.sections?.['storage'] ?? false,
            'backup':               existingPerms?.sections?.['backup'] ?? false,
            'webhooks':             existingPerms?.sections?.['webhooks'] ?? false,
        },
        contentManager: {},

        init() {
            const actions = ['read', 'create', 'update', 'delete', 'publish'];
            this.types.forEach(t => {
                const uid     = t.__uid;
                const allowed = existingPerms?.['content-manager']?.[uid] ?? [];
                this.contentManager[uid] = {};
                actions.forEach(a => {
                    this.contentManager[uid][a] = allowed.includes(a);
                });
            });
        },

        selectAll(value) {
            Object.keys(this.contentManager).forEach(uid => {
                Object.keys(this.contentManager[uid]).forEach(a => {
                    this.contentManager[uid][a] = value;
                });
            });
        },

        toggleCol(action) {
            const allChecked = Object.values(this.contentManager).every(row => row[action]);
            Object.keys(this.contentManager).forEach(uid => {
                this.contentManager[uid][action] = !allChecked;
            });
        },

        async save() {
            this.saving = true;
            this.saved  = false;

            const permissions = {
                sections: { ...this.sections },
                'content-manager': {},
            };

            Object.entries(this.contentManager).forEach(([uid, ops]) => {
                const allowed = Object.entries(ops).filter(([, v]) => v).map(([k]) => k);
                if (allowed.length > 0) {
                    permissions['content-manager'][uid] = allowed;
                }
            });

            try {
                const res = await fetch(this.updateUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ permissions }),
                });

                if (res.ok) {
                    this.saved = true;
                    setTimeout(() => this.saved = false, 3000);
                }
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endpush

@endsection
