{{--
    Shared row-list UI for anything backed by the repeaterField() Alpine factory:
    top-level repeaters, repeaters nested inside a component/repeater, and repeatable
    components. The parent include sets up x-data="repeaterField(rows, emptyRow)";
    this partial just renders the list chrome (reorder/remove/collapse/add) and, per
    row, delegates each attribute to _field_subfield for full recursive field support.

    Inputs: $attrs (subName => subField map for one row), $depth (current nesting depth).
--}}
@php $depth = $depth ?? 0; @endphp

<template x-if="rows.length === 0">
    <div class="rounded-lg border-2 border-dashed border-slate-300 py-10 flex flex-col items-center gap-2">
        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm text-slate-400">No entry yet.</p>
        <p class="text-xs text-slate-400">Click on "Add an entry" to add your first entry.</p>
    </div>
</template>

<template x-if="rows.length > 0">
    <div class="rounded-lg border border-slate-300 overflow-hidden divide-y divide-slate-200">
        <template x-for="(row, idx) in rows" :key="idx">
            <div>
                {{-- Row header --}}
                <div class="flex items-center gap-3 px-4 py-3 bg-slate-100 hover:bg-slate-100/80 cursor-pointer select-none"
                     @click="toggle(idx)">
                    <div class="flex flex-col gap-0.5 flex-shrink-0">
                        <button type="button" @click.stop="moveUp(idx)" :disabled="idx === 0"
                                class="text-slate-400 hover:text-slate-600 disabled:opacity-20 disabled:cursor-not-allowed p-0.5 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
                            </svg>
                        </button>
                        <button type="button" @click.stop="moveDown(idx)" :disabled="idx === rows.length - 1"
                                class="text-slate-400 hover:text-slate-600 disabled:opacity-20 disabled:cursor-not-allowed p-0.5 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>
                    <span class="text-xs text-slate-400 font-mono flex-shrink-0 w-5" x-text="idx + 1"></span>
                    <span class="flex-1 text-sm text-slate-500 truncate" x-text="preview(row)"></span>
                    <button type="button" @click.stop="removeRow(idx)"
                            class="flex-shrink-0 p-1.5 rounded text-slate-400 hover:text-red-600 hover:bg-red-900/20 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                    <span class="flex-shrink-0 text-slate-400 transition-transform duration-200"
                          :class="isOpen(idx) ? 'rotate-180' : ''">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </span>
                </div>

                {{-- Row body --}}
                <div x-show="isOpen(idx)"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="p-5 bg-slate-100 border-t border-slate-300 space-y-5">
                    @foreach($attrs as $subName => $subField)
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-2">
                                {{ $subField['displayName'] ?? ucwords(str_replace('_', ' ', $subName)) }}
                                <span class="text-slate-400 text-xs font-normal ml-1">({{ $subField['type'] }})</span>
                            </label>
                            @include('talos.content.form._field_subfield', [
                                'subName'  => $subName,
                                'subField' => $subField,
                                'bind'     => "row['{$subName}']",
                                'depth'    => $depth + 1,
                                'openExpr' => 'isOpen(idx)',
                            ])
                        </div>
                    @endforeach
                </div>
            </div>
        </template>
    </div>
</template>

<button type="button" @click="addRow()"
        class="mt-3 w-full py-3 flex items-center justify-center gap-2 rounded-lg border border-dashed border-slate-300 hover:border-blue-500 bg-white hover:bg-slate-100/50 text-slate-400 hover:text-blue-600 text-sm font-medium transition-all">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    Add an entry
</button>
