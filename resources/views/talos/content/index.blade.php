@extends('talos.layouts.app')

@php
    /* @var \Illuminate\Pagination\LengthAwarePaginator $entries */
@endphp

@section('title', $contentType['info']['displayName'] . ' — Content Manager')
@section('header', $contentType['info']['displayName'])

@section('header-actions')
    @if($i18n)
        <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1">
            @foreach($locales as $loc)
                <a href="{{ route('talos.content.index', ['uid' => $uid, 'locale' => $loc]) }}"
                   class="px-3 py-1 rounded text-xs font-medium transition-colors {{ $locale === $loc ? 'bg-blue-600 text-white' : 'text-slate-500 hover:text-slate-900' }}">
                    {{ strtoupper($loc) }}
                </a>
            @endforeach
        </div>
    @endif
    <a href="{{ route('talos.content-type-builder.api-settings', ['uid' => $uid]) }}"
       class="p-2 sm:px-3 sm:py-2 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-lg text-sm transition-colors flex items-center gap-1.5"
       title="API Fields">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span class="hidden sm:inline">API Fields</span>
    </a>
    <a href="{{ route('talos.content-type-builder.edit', ['uid' => $uid]) }}"
       class="p-2 sm:px-3 sm:py-2 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-lg text-sm transition-colors flex items-center gap-1.5"
       title="Edit schema">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span class="hidden sm:inline">Edit schema</span>
    </a>
    <a href="{{ route('talos.content.create', array_filter(['uid' => $uid, 'locale' => $i18n ? $locale : null])) }}"
       class="px-3 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span class="hidden xs:inline sm:inline">Create{{ $i18n ? ' (' . strtoupper($locale) . ')' : '' }}</span>
    </a>
@endsection

@section('content')
@php
    $attributes = $contentType['attributes'] ?? [];
    $draftable  = $contentType['options']['draftAndPublish'] ?? false;

    $displayCols = collect($attributes)->filter(fn($f) => in_array($f['type'], ['string','text','email','uid','integer','boolean','enumeration']))->take(4)->keys()->toArray();
    $multiPage   = $entries->hasPages();
    $pageIds     = $entries->pluck('id')->toArray();
@endphp

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden"
     x-data="tableManager('{{ $uid }}', {{ $entries->currentPage() }}, {{ $entries->perPage() }}, {{ $manualOrder ? 'true' : 'false' }}, {{ json_encode($pageIds) }})">

    {{-- Bulk actions bar --}}
    <div x-show="selected.length > 0" x-cloak
         class="px-5 py-3 bg-blue-50 border-b border-blue-200 flex items-center gap-3 flex-wrap">
        <span class="text-sm font-medium text-blue-700" x-text="selected.length + ' ' + (selected.length === 1 ? 'entry' : 'entries') + ' selected'"></span>

        @if($draftable)
            {{-- Bulk Publish --}}
            <form action="{{ route('talos.content.bulk-publish', ['uid' => $uid]) }}"
                  method="POST"
                  :data-confirm="'Publish ' + selected.length + ' ' + (selected.length === 1 ? 'entry' : 'entries') + '?'">
                @csrf
                <input type="hidden" name="ids" x-bind:value="JSON.stringify(selected)">
                <button type="submit"
                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-medium rounded-lg transition-colors">
                    Publish selected
                </button>
            </form>

            {{-- Bulk Unpublish --}}
            <form action="{{ route('talos.content.bulk-unpublish', ['uid' => $uid]) }}"
                  method="POST"
                  :data-confirm="'Unpublish ' + selected.length + ' ' + (selected.length === 1 ? 'entry' : 'entries') + '?'">
                @csrf
                <input type="hidden" name="ids" x-bind:value="JSON.stringify(selected)">
                <button type="submit"
                        class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-white text-xs font-medium rounded-lg transition-colors">
                    Unpublish selected
                </button>
            </form>
        @endif

        {{-- Bulk Delete --}}
        <form action="{{ route('talos.content.bulk-destroy', ['uid' => $uid]) }}"
              method="POST"
              :data-confirm="'Permanently delete ' + selected.length + ' ' + (selected.length === 1 ? 'entry' : 'entries') + '? This cannot be undone.'">
            @csrf
            <input type="hidden" name="ids" x-bind:value="JSON.stringify(selected)">
            <button type="submit"
                    class="px-3 py-1.5 bg-red-600 hover:bg-red-500 text-white text-xs font-medium rounded-lg transition-colors">
                Delete selected
            </button>
        </form>

        <button type="button" @click="clearAll()"
                class="ml-auto text-xs text-blue-500 hover:text-blue-700 transition-colors">
            Clear selection
        </button>
    </div>

    {{-- Table header --}}
    <div class="px-4 sm:px-5 py-3 sm:py-3.5 border-b border-slate-200 flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3 flex-wrap">
            <p class="text-sm text-slate-500">
                {{ $entries->total() }} {{ $entries->total() === 1 ? 'entry' : 'entries' }}
                @if($draftable)
                    <span class="hidden sm:inline text-slate-400 ml-2 text-xs">(drafts visible — public API only exposes published)</span>
                @endif
            </p>
            @if($manualOrder)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-600 text-xs rounded-full border border-blue-100">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    Manual order — drag or type position
                </span>
                <span x-show="saving" class="text-xs text-slate-400 animate-pulse">Saving…</span>
                <span x-show="saved" x-transition class="text-xs text-emerald-600">Order saved</span>
            @endif
        </div>
        <div class="text-xs text-slate-400 font-mono">
            {{ $contentType['info']['pluralName'] }} · {{ count($attributes) }} fields
        </div>
    </div>

    @if($entries->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-slate-400 gap-4">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-sm">No entries yet.</p>
            <a href="{{ route('talos.content.create', ['uid' => $uid]) }}"
               class="text-blue-600 hover:underline text-sm">Create the first entry</a>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200">
                        {{-- Select-all checkbox --}}
                        <th class="w-10 px-4 py-3" data-no-dirty>
                            <input type="checkbox"
                                   x-ref="headerCheck"
                                   :checked="allSelected"
                                   x-effect="$refs.headerCheck.indeterminate = someSelected"
                                   @change="toggleAll()"
                                   class="w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer accent-blue-600">
                        </th>
                        @if($manualOrder)
                            <th class="w-8 px-3 py-3"></th>{{-- drag handle --}}
                            @if($multiPage)
                                <th class="text-left px-2 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider w-16">#</th>
                            @endif
                        @endif
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider w-12">ID</th>
                        @foreach($displayCols as $i => $col)
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider {{ $i > 0 ? 'hidden sm:table-cell' : '' }}">
                                {{ str_replace('_', ' ', $col) }}
                            </th>
                        @endforeach
                        @if($i18n)
                            <th class="hidden sm:table-cell text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Locale</th>
                        @endif
                        @if($draftable)
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                        @endif
                        <th class="hidden sm:table-cell text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Created</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200" x-ref="tbody" @if($manualOrder) data-no-dirty @endif>
                    @foreach($entries as $entry)
                        @php
                            /* @var \stdClass $loop */
                            $rowPos = ($entries->currentPage() - 1) * $entries->perPage() + $loop->index + 1;
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors"
                            :class="isSelected({{ $entry->id }}) ? 'bg-blue-50' : ''"
                            @if($manualOrder) data-id="{{ $entry->id }}" @endif>

                            {{-- Row checkbox --}}
                            <td class="px-4 py-3" data-no-dirty>
                                <input type="checkbox"
                                       :checked="isSelected({{ $entry->id }})"
                                       @change="toggle({{ $entry->id }})"
                                       class="w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer accent-blue-600">
                            </td>

                            @if($manualOrder)
                                <td class="px-3 py-3 text-slate-300 hover:text-slate-500 cursor-grab active:cursor-grabbing sort-handle" title="Drag to reorder">
                                    <svg class="w-4 h-4" viewBox="0 0 16 16" fill="currentColor">
                                        <circle cx="5" cy="4" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="5" cy="12" r="1.5"/>
                                        <circle cx="11" cy="4" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="11" cy="12" r="1.5"/>
                                    </svg>
                                </td>
                                @if($multiPage)
                                <td class="px-2 py-3"
                                    x-data="positionCell({{ $entry->id }}, {{ $rowPos }}, '{{ $uid }}', {{ $entries->total() }})">
                                    <div class="relative">
                                        <input type="number"
                                               x-model="pos"
                                               min="1"
                                               max="{{ $entries->total() }}"
                                               class="pos-input w-14 text-center text-xs border border-slate-200 rounded px-1 py-1 text-slate-500 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                               @mousedown.stop
                                               @blur="save()">
                                        <span x-show="saved" x-transition class="absolute -top-5 left-0 text-xs text-emerald-600 whitespace-nowrap">Saved</span>
                                    </div>
                                </td>
                                @endif
                            @endif

                            <td class="px-4 py-3 text-slate-400 text-xs font-mono">{{ $entry->id }}</td>
                            @foreach($displayCols as $i => $col)
                                <td class="px-4 py-3 text-slate-600 {{ $i > 0 ? 'hidden sm:table-cell' : '' }}">
                                    @php $val = $entry->$col; @endphp
                                    @if(is_bool($val))
                                        <span class="{{ $val ? 'text-emerald-700' : 'text-slate-400' }}">{{ $val ? 'Yes' : 'No' }}</span>
                                    @elseif(is_array($val))
                                        <span class="text-slate-500">{{ implode(', ', $val) }}</span>
                                    @elseif(is_string($val) && strlen($val) > 60)
                                        {{ Str::limit($val, 60) }}
                                    @else
                                        {{ $val }}
                                    @endif
                                </td>
                            @endforeach
                            @if($i18n)
                                <td class="hidden sm:table-cell px-4 py-3">
                                    <span class="text-xs bg-slate-100 text-slate-500 border border-slate-300 px-2 py-0.5 rounded font-mono">
                                        {{ strtoupper($entry->locale ?? '?') }}
                                    </span>
                                </td>
                            @endif
                            @if($draftable)
                                <td class="px-4 py-3">
                                    @if($entry->published_at)
                                        <span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded">Published</span>
                                    @else
                                        <span class="text-xs bg-slate-100 text-slate-400 border border-slate-300 px-2 py-0.5 rounded">Draft</span>
                                    @endif
                                </td>
                            @endif
                            <td class="hidden sm:table-cell px-4 py-3 text-slate-400 text-xs">{{ \Carbon\Carbon::parse($entry->created_at)->diffForHumans() }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1 justify-end">
                                    @if($draftable)
                                        @if($entry->published_at)
                                            <form action="{{ route('talos.content.unpublish', ['uid' => $uid, 'id' => $entry->id]) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-xs text-amber-600 hover:text-amber-700 px-1 py-1">Unpublish</button>
                                            </form>
                                        @else
                                            <form action="{{ route('talos.content.publish', ['uid' => $uid, 'id' => $entry->id]) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-xs text-emerald-600 hover:text-emerald-700 px-1 py-1">Publish</button>
                                            </form>
                                        @endif
                                    @endif

                                    {{-- Duplicate --}}
                                    <form action="{{ route('talos.content.duplicate', ['uid' => $uid, 'id' => $entry->id]) }}" method="POST"
                                          title="Duplicate entry">
                                        @csrf
                                        <button type="submit" class="text-slate-400 hover:text-violet-600 transition-colors p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    </form>

                                    {{-- Edit --}}
                                    <a href="{{ route('talos.content.edit', ['uid' => $uid, 'id' => $entry->id]) }}"
                                       class="text-slate-400 hover:text-blue-600 transition-colors p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </a>

                                    {{-- Delete --}}
                                    <form action="{{ route('talos.content.destroy', ['uid' => $uid, 'id' => $entry->id]) }}"
                                          method="POST" data-confirm="Delete this entry?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-slate-400 hover:text-red-600 transition-colors p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($entries->hasPages())
            <div class="px-5 py-4 border-t border-slate-200">
                {{ $entries->links() }}
            </div>
        @endif
    @endif
</div>

<script>
function tableManager(uid, page, perPage, manualOrder, pageIds) {
    return {
        // ── Bulk selection ────────────────────────────────────────────────────
        pageIds:  pageIds || [],
        selected: [],

        toggle(id) {
            const i = this.selected.indexOf(id);
            i === -1 ? this.selected.push(id) : this.selected.splice(i, 1);
        },

        isSelected(id) {
            return this.selected.includes(id);
        },

        get allSelected() {
            return this.pageIds.length > 0 && this.selected.length === this.pageIds.length;
        },

        get someSelected() {
            return this.selected.length > 0 && this.selected.length < this.pageIds.length;
        },

        toggleAll() {
            this.selected = this.allSelected ? [] : [...this.pageIds];
        },

        clearAll() {
            this.selected = [];
        },

        // ── Manual ordering ───────────────────────────────────────────────────
        saving: false,
        saved:  false,
        _savedTimer: null,

        init() {
            if (! manualOrder) return;
            this.$nextTick(() => {
                if (typeof Sortable === 'undefined' || ! this.$refs.tbody) return;
                Sortable.create(this.$refs.tbody, {
                    handle:     '.sort-handle',
                    animation:  150,
                    ghostClass: 'opacity-40',
                    onEnd: () => this.persist(),
                });
            });
        },

        async persist() {
            const rows = Array.from(this.$refs.tbody.querySelectorAll('tr[data-id]'));
            const ids  = rows.map(r => r.dataset.id);

            const offset   = (page - 1) * perPage;
            const newOrder = ids.map((rowId, idx) => ({ id: parseInt(rowId), pos: offset + idx + 1 }));

            this.saving = true;
            this.saved  = false;
            clearTimeout(this._savedTimer);

            try {
                await fetch(`/{{ config('talos.admin_prefix', 'talos') }}/content-manager/${uid}/reorder`, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ ids, page, per_page: perPage }),
                });
                window.dispatchEvent(new CustomEvent('talos:page-reordered', { detail: newOrder }));
                this.saved = true;
                this._savedTimer = setTimeout(() => this.saved = false, 2500);
            } finally {
                this.saving = false;
            }
        },
    };
}

function positionCell(id, initialPos, uid, total) {
    return {
        pos:  initialPos,
        orig: initialPos,
        saved: false,
        _timer: null,

        init() {
            window.addEventListener('talos:page-reordered', (e) => {
                const item = e.detail.find(i => i.id === id);
                if (item) {
                    this.pos  = item.pos;
                    this.orig = item.pos;
                }
            });

            window.addEventListener('talos:entry-moved', (e) => {
                const { movedId, oldPos, newPos } = e.detail;
                if (movedId === id) return;

                if (newPos < oldPos) {
                    if (this.pos >= newPos && this.pos <= oldPos - 1) { this.pos++; this.orig++; }
                } else {
                    if (this.pos >= oldPos + 1 && this.pos <= newPos) { this.pos--; this.orig--; }
                }
            });
        },

        async save() {
            const p = Math.max(1, Math.min(parseInt(this.pos) || 1, total));
            this.pos = p;
            if (p === this.orig) return;

            const oldPos = this.orig;

            try {
                await fetch(`/{{ config('talos.admin_prefix', 'talos') }}/content-manager/${uid}/move`, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ id, position: p }),
                });
                this.orig = p;
                window.dispatchEvent(new CustomEvent('talos:entry-moved', {
                    detail: { movedId: id, oldPos, newPos: p },
                }));
                this.saved = true;
                clearTimeout(this._timer);
                this._timer = setTimeout(() => this.saved = false, 2000);
            } catch (e) {
                this.pos = this.orig;
            }
        },
    };
}
</script>
@if($manualOrder)
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endif
@endsection
