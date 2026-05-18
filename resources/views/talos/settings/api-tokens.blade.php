@extends('talos.layouts.app')

@section('title', 'API Tokens — Talos')
@section('header', 'API Tokens')

@section('content')
@php
    $ops     = ['find' => 'Find', 'findOne' => 'Find One', 'create' => 'Create', 'update' => 'Update', 'delete' => 'Delete'];
    $typeMap = collect($contentTypes)->keyBy('__uid');
@endphp

<div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

    {{-- ── Token list (3/5) ──────────────────────────────────────────── --}}
    <div class="xl:col-span-3 bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-800">
            <h2 class="text-white font-semibold">Active Tokens</h2>
            <p class="text-xs text-gray-500 mt-0.5">Use as Bearer tokens for the Talos REST API.</p>
        </div>

        @if($tokens->isEmpty())
            <div class="px-5 py-10 text-center text-gray-600 text-sm">No tokens yet.</div>
        @else
            <div class="divide-y divide-gray-800">
                @foreach($tokens as $token)
                    <div class="px-5 py-4" x-data="{ open: false }">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-medium text-white">{{ $token->name }}</p>

                                    {{-- Type badge --}}
                                    @if($token->type === 'full-access')
                                        <span class="text-xs px-1.5 py-0.5 rounded border bg-red-900/40 text-red-400 border-red-800">Full access</span>
                                    @elseif($token->type === 'read-only')
                                        <span class="text-xs px-1.5 py-0.5 rounded border bg-blue-900/40 text-blue-400 border-blue-800">Read only</span>
                                    @else
                                        <span class="text-xs px-1.5 py-0.5 rounded border bg-gray-800 text-gray-300 border-gray-700">Custom</span>
                                        @if(!empty($token->permissions))
                                            <span class="text-xs text-gray-500">{{ count($token->permissions) }} collection(s)</span>
                                        @endif
                                    @endif

                                    @if($token->isExpired())
                                        <span class="text-xs text-red-400 font-medium">Expired</span>
                                    @endif
                                </div>

                                <p class="text-xs text-gray-500 mt-0.5">
                                    Created by {{ $token->creator?->full_name ?? '—' }}
                                    · Last used: {{ $token->last_used_at?->diffForHumans() ?? 'never' }}
                                    @if($token->expires_at) · Expires: {{ $token->expires_at->format('M d, Y') }} @endif
                                </p>

                                {{-- Custom permissions summary --}}
                                @if($token->type === 'custom' && !empty($token->permissions))
                                    <button @click="open = !open"
                                            class="mt-1.5 text-xs text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-1">
                                        <svg class="w-3 h-3 transition-transform" :class="open && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        <span x-text="open ? 'Hide permissions' : 'View permissions'"></span>
                                    </button>

                                    <div x-show="open" x-cloak class="mt-2 space-y-1">
                                        @foreach($token->permissions as $uid => $allowedOps)
                                            <div class="flex items-center gap-2 text-xs">
                                                <span class="text-gray-400 font-medium w-32 truncate">
                                                    {{ $typeMap->get($uid)['info']['displayName'] ?? $uid }}
                                                </span>
                                                <div class="flex gap-1 flex-wrap">
                                                    @foreach($ops as $op => $label)
                                                        @if(in_array($op, $allowedOps))
                                                            <span class="px-1.5 py-0.5 rounded bg-green-900/40 text-green-400 border border-green-800/60">{{ $label }}</span>
                                                        @else
                                                            <span class="px-1.5 py-0.5 rounded bg-gray-800 text-gray-600 border border-gray-700">{{ $label }}</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <form action="{{ route('talos.settings.api-tokens.destroy', $token->id) }}"
                                  method="POST" onsubmit="return confirm('Revoke this token?')" class="flex-shrink-0">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="text-gray-600 hover:text-red-400 transition-colors px-3 py-1 text-xs border border-gray-700 hover:border-red-800 rounded-lg">
                                    Revoke
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── Create token (2/5) ────────────────────────────────────────── --}}
    <div class="xl:col-span-2" x-data="tokenForm()" x-init="init()">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h2 class="text-white font-semibold mb-4">Create Token</h2>

            <form action="{{ route('talos.settings.api-tokens.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Token name</label>
                    <input name="name" type="text" required placeholder="e.g. Frontend App"
                           class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Token type</label>
                    <select name="type" x-model="type"
                            class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-gray-300 text-sm focus:outline-none focus:border-blue-500">
                        <option value="full-access">Full access — all operations on all collections</option>
                        <option value="read-only">Read only — find &amp; findOne on all collections</option>
                        <option value="custom">Custom — choose per collection &amp; operation</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Expires at <span class="text-gray-600">(optional)</span></label>
                    <input name="expires_at" type="datetime-local"
                           class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-gray-300 text-sm focus:outline-none focus:border-blue-500">
                </div>

                {{-- ── Permission matrix (custom only) ────────────────── --}}
                <div x-show="type === 'custom'" x-cloak class="space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Permissions</p>
                        <div class="flex gap-2">
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
                                        <th class="text-left px-3 py-2 text-gray-400 font-medium w-32">Collection</th>
                                        @foreach($ops as $op => $label)
                                            <th class="px-2 py-2 text-center text-gray-400 font-medium">
                                                <div class="flex flex-col items-center gap-1">
                                                    <span>{{ $label }}</span>
                                                    <button type="button"
                                                            @click="toggleColumn('{{ $op }}')"
                                                            class="text-gray-600 hover:text-blue-400 transition-colors leading-none"
                                                            title="Toggle column">
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
                                    @foreach($contentTypes as $type)
                                        @php $uid = $type['__uid']; @endphp
                                        <tr class="hover:bg-gray-800/40 transition-colors">
                                            <td class="px-3 py-2.5">
                                                <div>
                                                    <p class="text-white font-medium truncate max-w-[7rem]">{{ $type['info']['displayName'] }}</p>
                                                    <p class="text-gray-600 text-[10px] truncate max-w-[7rem]">{{ ($type['kind'] ?? 'collectionType') === 'singleType' ? 'Single' : 'Collection' }}</p>
                                                </div>
                                            </td>
                                            @foreach($ops as $op => $label)
                                                <td class="px-2 py-2.5 text-center">
                                                    <input type="checkbox"
                                                           name="permissions[{{ $uid }}][{{ $op }}]"
                                                           value="1"
                                                           data-op="{{ $op }}"
                                                           x-model="perms['{{ $uid }}']['{{ $op }}']"
                                                           class="w-4 h-4 rounded border-gray-600 bg-gray-800 text-blue-500 focus:ring-blue-500 focus:ring-offset-gray-900 cursor-pointer">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <button type="submit"
                        class="w-full py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors">
                    Generate token
                </button>
            </form>
        </div>

        {{-- API usage reference --}}
        <div class="mt-4 bg-gray-900 border border-gray-800 rounded-xl p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">API Usage</p>
            <div class="bg-gray-950 rounded-lg p-3 font-mono text-xs text-gray-400 space-y-1">
                <p><span class="text-blue-400">GET</span>    /api/articles</p>
                <p><span class="text-blue-400">GET</span>    /api/articles/{id}</p>
                <p><span class="text-green-400">POST</span>   /api/articles</p>
                <p><span class="text-yellow-400">PUT</span>    /api/articles/{id}</p>
                <p><span class="text-red-400">DELETE</span> /api/articles/{id}</p>
            </div>
            <p class="text-xs text-gray-600 mt-2">Header: <code class="text-gray-500">Authorization: Bearer {token}</code></p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function tokenForm() {
    return {
        type: 'full-access',
        perms: {},

        init() {
            @foreach($contentTypes as $ct)
            this.perms['{{ $ct['__uid'] }}'] = { find: false, findOne: false, create: false, update: false, delete: false };
            @endforeach
        },

        selectAll(value) {
            for (const uid in this.perms) {
                for (const op in this.perms[uid]) {
                    this.perms[uid][op] = value;
                }
            }
        },

        toggleColumn(op) {
            const allChecked = Object.values(this.perms).every(row => row[op]);
            for (const uid in this.perms) {
                this.perms[uid][op] = !allChecked;
            }
        },
    };
}
</script>
@endpush

@endsection
