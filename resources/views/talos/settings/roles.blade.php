@extends('talos.layouts.app')

@section('title', 'Roles & Permissions — Talos')
@section('header', 'Roles & Permissions')

@section('content')
@php
    $sectionDefs = [
        'content-type-builder' => 'Content-Type Builder',
        'components'           => 'Components',
        'media'                => 'Media Library',
        'settings'             => 'Settings',
    ];
    $cmActions = ['read' => 'Read', 'create' => 'Create', 'update' => 'Update', 'delete' => 'Delete', 'publish' => 'Publish'];
@endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- ── Role list with inline permission editors (2/3) ──────────── --}}
    <div class="xl:col-span-2 space-y-3">

        @forelse($roles as $role)
            <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden"
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
                        <p class="text-sm font-medium text-white">{{ $role->name }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $role->description ? $role->description . ' · ' : '' }}{{ $role->users_count }} user(s)
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="open = !open"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs border rounded-lg transition-colors"
                                :class="open
                                    ? 'border-blue-700 text-blue-400 bg-blue-900/20'
                                    : 'border-gray-700 text-gray-400 hover:text-white hover:border-gray-600'">
                            <svg class="w-3 h-3 transition-transform" :class="open && 'rotate-90'"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span x-text="open ? 'Close' : 'Edit permissions'"></span>
                        </button>

                        <form action="{{ route('talos.settings.roles.destroy', $role->id) }}"
                              method="POST" onsubmit="return confirm('Delete role {{ addslashes($role->name) }}? Users with this role will lose all permissions.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 text-gray-600 hover:text-red-400 transition-colors" title="Delete role">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Permission editor --}}
                <div x-show="open" x-cloak class="border-t border-gray-800">

                    {{-- Admin sections --}}
                    <div class="px-5 py-4 border-b border-gray-800">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Admin Sections</p>
                        <div class="grid grid-cols-2 gap-y-2 gap-x-4">
                            @foreach($sectionDefs as $key => $label)
                                <label class="flex items-center gap-2.5 cursor-pointer group">
                                    <input type="checkbox"
                                           x-model="sections['{{ $key }}']"
                                           class="w-4 h-4 rounded border-gray-600 bg-gray-800 text-blue-500 focus:ring-blue-500 focus:ring-offset-gray-900 cursor-pointer">
                                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Content Manager permissions --}}
                    <div class="px-5 py-4 border-b border-gray-800">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Content Manager</p>
                            <div class="flex gap-3">
                                <button type="button" @click="selectAll(true)"
                                        class="text-xs text-blue-400 hover:text-blue-300 transition-colors">Select all</button>
                                <span class="text-gray-700">·</span>
                                <button type="button" @click="selectAll(false)"
                                        class="text-xs text-gray-500 hover:text-gray-400 transition-colors">Clear all</button>
                            </div>
                        </div>

                        @if(empty($contentTypes))
                            <p class="text-xs text-gray-600 italic">No content types defined yet.</p>
                        @else
                            <div class="overflow-x-auto rounded-lg border border-gray-700">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="border-b border-gray-700 bg-gray-800/60">
                                            <th class="text-left px-3 py-2 text-gray-400 font-medium">Collection</th>
                                            @foreach($cmActions as $action => $actionLabel)
                                                <th class="px-3 py-2 text-center text-gray-400 font-medium">
                                                    <div class="flex flex-col items-center gap-1">
                                                        <span>{{ $actionLabel }}</span>
                                                        <button type="button"
                                                                @click="toggleCol('{{ $action }}')"
                                                                class="text-gray-600 hover:text-blue-400 transition-colors"
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
                                    <tbody class="divide-y divide-gray-800">
                                        <template x-for="type in types" :key="type.__uid">
                                            <tr class="hover:bg-gray-800/40 transition-colors">
                                                <td class="px-3 py-2.5">
                                                    <p class="text-white font-medium" x-text="type.info.displayName"></p>
                                                    <p class="text-gray-600 text-[10px]"
                                                       x-text="(type.kind ?? 'collectionType') === 'singleType' ? 'Single' : 'Collection'"></p>
                                                </td>
                                                <template x-for="action in ['read', 'create', 'update', 'delete', 'publish']" :key="action">
                                                    <td class="px-3 py-2.5 text-center">
                                                        <input type="checkbox"
                                                               x-model="contentManager[type.__uid][action]"
                                                               class="w-4 h-4 rounded border-gray-600 bg-gray-800 text-blue-500 focus:ring-blue-500 focus:ring-offset-gray-900 cursor-pointer">
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
                        <span x-show="saved" x-cloak class="text-xs text-green-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Saved
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-gray-900 border border-gray-800 rounded-xl px-5 py-10 text-center text-gray-600 text-sm">
                No roles yet. Create one to get started.
            </div>
        @endforelse
    </div>

    {{-- ── Create role (1/3) ─────────────────────────────────────────── --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 h-fit">
        <h2 class="text-white font-semibold mb-4">Create Role</h2>
        <form action="{{ route('talos.settings.roles.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Name</label>
                <input name="name" type="text" required placeholder="e.g. Editor"
                       class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Description <span class="text-gray-600">(optional)</span></label>
                <input name="description" type="text" placeholder="What can this role do?"
                       class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
            </div>
            <button type="submit"
                    class="w-full py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors">
                Create role
            </button>
        </form>

        <div class="mt-5 pt-5 border-t border-gray-800 space-y-2">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">How it works</p>
            <p class="text-xs text-gray-500">Create a role, then expand it to set which admin sections and content types users with that role can access.</p>
            <p class="text-xs text-gray-500">Super admins always have full access regardless of role.</p>
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
