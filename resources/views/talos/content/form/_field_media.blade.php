@php
    $isMultiple = $field['multiple'] ?? false;
    if ($isMultiple) {
        $mInitIds = is_array($value)
            ? array_values(array_map('intval', array_filter($value)))
            : [];
    } else {
        $mInitId = is_array($value)
            ? (count($value) ? (int)$value[0] : null)
            : (is_numeric($value) ? (int)$value : null);
    }
@endphp

@if($isMultiple)
    {{-- Multiple files --}}
    <div x-data="{ ids: {{ json_encode($mInitIds) }}, show: false }">
        <input type="hidden" name="{{ $name }}_id" :value="JSON.stringify(ids)">

        <div class="flex flex-wrap gap-2 mb-3" x-show="ids.length > 0">
            <template x-for="_mi in $store._mlib.items.filter(i => ids.includes(i.id))" :key="_mi.id">
                <div class="relative group/thumb flex-shrink-0">
                    <template x-if="_mi.isImage">
                        <img :src="_mi.url" class="h-20 w-20 object-cover rounded-lg border border-slate-300">
                    </template>
                    <template x-if="!_mi.isImage">
                        <div class="h-20 w-20 bg-slate-100 rounded-lg border border-slate-300 flex items-center justify-center">
                            <span class="text-xs text-slate-400 font-mono uppercase" x-text="_mi.ext.toUpperCase()"></span>
                        </div>
                    </template>
                    <button type="button" @click.stop="ids = ids.filter(i => i !== _mi.id)"
                            class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-600 hover:bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover/thumb:opacity-100 transition-opacity">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </template>
        </div>

        <button type="button" @click="$store._mlib.refresh(); show = true"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-100 text-slate-600 rounded-lg text-sm font-medium transition-colors">
            <span x-text="ids.length ? ids.length + ' file(s) selected — edit' : 'Select files'"></span>
        </button>

        <div x-show="show" x-cloak
             class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
             @keydown.escape.window="show = false">
            <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <h3 class="text-slate-800 font-semibold">
                        Media Library
                        <span class="text-slate-400 text-sm font-normal" x-text="ids.length ? '(' + ids.length + ' selected)' : ''"></span>
                    </h3>
                    <button type="button" @click="show = false" class="text-slate-400 hover:text-slate-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="flex-1 min-h-0 flex overflow-hidden">
                    @include('talos.content.form._media_library_sidebar')
                    <div class="flex-1 min-h-0 overflow-y-auto">
                        <div class="p-4 grid grid-cols-3 gap-4">
                            <template x-for="_mi in $store._mlib.items" :key="_mi.id">
                                <button type="button"
                                        @click="ids.includes(_mi.id) ? ids = ids.filter(i => i !== _mi.id) : ids.push(_mi.id)"
                                        x-show="$store._mlib.folder===null||_mi.folder===$store._mlib.folder"
                                        :class="ids.includes(_mi.id) ? 'border-blue-500' : 'border-transparent'"
                                        class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500 relative">
                                    <template x-if="_mi.isImage"><img :src="_mi.url" class="w-full h-36 object-cover"></template>
                                    <template x-if="!_mi.isImage">
                                        <div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs" x-text="_mi.ext"></div>
                                    </template>
                                    <p class="text-xs text-slate-500 p-1 truncate" x-text="_mi.name"></p>
                                    <div x-show="ids.includes(_mi.id)"
                                         class="absolute top-1.5 right-1.5 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center pointer-events-none">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                    @include('talos.content.form._media_upload_label')
                    <div class="flex items-center gap-3">
                        <button type="button" @click="ids = []"
                                class="text-sm text-slate-400 hover:text-slate-600">Clear all</button>
                        <button type="button" @click="show = false"
                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-medium">Done</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@else
    {{-- Single file --}}
    <div x-data="{ selectedId: {{ $mInitId ?? 'null' }}, show: false }">
        <input type="hidden" name="{{ $name }}_id" :value="selectedId">

        <div x-show="selectedId" class="mb-3">
            <template x-for="_mi in $store._mlib.items.filter(i => i.id === selectedId)" :key="_mi.id">
                <div>
                    <template x-if="_mi.isImage"><img :src="_mi.url" class="h-32 object-cover rounded-lg"></template>
                    <template x-if="!_mi.isImage"><p class="text-sm text-slate-500" x-text="_mi.name"></p></template>
                </div>
            </template>
        </div>

        <button type="button" @click="$store._mlib.refresh(); show = true"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-100 text-slate-600 rounded-lg text-sm font-medium transition-colors">
            <span x-text="selectedId ? 'Change media' : 'Select from library'"></span>
        </button>

        <div x-show="show" x-cloak
             class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
             @keydown.escape.window="show = false">
            <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <h3 class="text-slate-800 font-semibold">Media Library</h3>
                    <button type="button" @click="show = false" class="text-slate-400 hover:text-slate-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="flex-1 min-h-0 flex overflow-hidden">
                    @include('talos.content.form._media_library_sidebar')
                    <div class="flex-1 min-h-0 overflow-y-auto">
                        <div class="p-4 grid grid-cols-3 gap-4">
                            <template x-for="_mi in $store._mlib.items" :key="_mi.id">
                                <button type="button"
                                        @click="selectedId = _mi.id; show = false"
                                        x-show="$store._mlib.folder===null||_mi.folder===$store._mlib.folder"
                                        :class="selectedId === _mi.id ? 'border-blue-500' : 'border-transparent'"
                                        class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500">
                                    <template x-if="_mi.isImage"><img :src="_mi.url" class="w-full h-36 object-cover"></template>
                                    <template x-if="!_mi.isImage">
                                        <div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs" x-text="_mi.ext"></div>
                                    </template>
                                    <p class="text-xs text-slate-500 p-1 truncate" x-text="_mi.name"></p>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                    @include('talos.content.form._media_upload_label')
                    <button type="button" @click="selectedId = null; show = false"
                            class="text-sm text-slate-400 hover:text-slate-600">Clear</button>
                </div>
            </div>
        </div>
    </div>
@endif
