@php
    $compUids     = $field['components'] ?? [];
    $firstUid     = $compUids[0] ?? null;
    $compSchema   = $firstUid ? ($componentMap[$firstUid] ?? null) : null;
    $isRepeatable = $field['repeatable'] ?? false;
@endphp

{{-- ── Repeatable component ── --}}
@if($isRepeatable && $compSchema)
    @php
        $repRaw   = is_array($value) ? $value : (is_string($value) && $value ? json_decode($value, true) : []);
        $repRows  = json_encode($repRaw ?? []);
        $repEmpty = json_encode(collect($compSchema['attributes'] ?? [])->mapWithKeys(fn($sf, $sn) => [$sn => $sf['default'] ?? ''])->all());
    @endphp
    <p class="text-xs text-slate-400 mb-3 font-mono">{{ $firstUid }} · repeatable</p>

    <div x-data="repeaterField({{ $repRows }}, {{ $repEmpty }})">
        <input type="hidden" name="{{ $name }}" :value="JSON.stringify(rows)">

        <template x-if="rows.length === 0">
            <div class="rounded-lg border-2 border-dashed border-slate-300 py-10 flex flex-col items-center gap-2">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm text-slate-400">No entry yet.</p>
                <p class="text-xs text-slate-400">Click "Add an entry" to get started.</p>
            </div>
        </template>

        <template x-if="rows.length > 0">
            <div class="rounded-lg border border-slate-300 overflow-hidden divide-y divide-slate-200">
                <template x-for="(row, idx) in rows" :key="idx">
                    <div>
                        <div class="flex items-center gap-3 px-4 py-3 bg-slate-100 hover:bg-slate-100/80 cursor-pointer select-none"
                             @click="toggle(idx)">
                            <div class="flex flex-col gap-0.5 flex-shrink-0">
                                <button type="button" @click.stop="moveUp(idx)" :disabled="idx === 0"
                                        class="text-slate-400 hover:text-slate-600 disabled:opacity-20 disabled:cursor-not-allowed p-0.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
                                    </svg>
                                </button>
                                <button type="button" @click.stop="moveDown(idx)" :disabled="idx === rows.length - 1"
                                        class="text-slate-400 hover:text-slate-600 disabled:opacity-20 disabled:cursor-not-allowed p-0.5">
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

                        <div x-show="isOpen(idx)"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="p-5 bg-slate-100 border-t border-slate-300 space-y-5">

                            @foreach($compSchema['attributes'] ?? [] as $subName => $subField)
                                @php $subLabel = ucwords(str_replace('_', ' ', $subName)); @endphp
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-2">
                                        {{ $subLabel }}
                                        <span class="text-slate-400 text-xs font-normal ml-1">({{ $subField['type'] }})</span>
                                    </label>

                                    @if(in_array($subField['type'], ['string','email','url','uid']))
                                        <input type="{{ $subField['type'] === 'email' ? 'email' : ($subField['type'] === 'url' ? 'url' : 'text') }}"
                                               x-model="row['{{ $subName }}']"
                                               class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">

                                    @elseif($subField['type'] === 'text')
                                        <textarea x-model="row['{{ $subName }}']" rows="4"
                                                  class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500 resize-y"></textarea>

                                    @elseif(in_array($subField['type'], ['integer','biginteger','decimal','float']))
                                        <input type="number" x-model.number="row['{{ $subName }}']"
                                               step="{{ in_array($subField['type'], ['decimal','float']) ? 'any' : '1' }}"
                                               class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">

                                    @elseif($subField['type'] === 'boolean')
                                        <button type="button" @click="toggleBool(row, '{{ $subName }}')" class="flex items-center gap-3">
                                            <div class="relative w-12 h-6 rounded-full transition-colors duration-200"
                                                 :class="getBool(row, '{{ $subName }}') ? 'bg-blue-600' : 'bg-slate-200'">
                                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                                                     :class="getBool(row, '{{ $subName }}') ? 'translate-x-6' : 'translate-x-0'"></div>
                                            </div>
                                            <span class="text-sm font-semibold transition-colors"
                                                  :class="getBool(row, '{{ $subName }}') ? 'text-blue-600' : 'text-slate-400'"
                                                  x-text="getBool(row, '{{ $subName }}') ? 'True' : 'False'"></span>
                                        </button>

                                    @elseif(in_array($subField['type'], ['date','datetime','time']))
                                        <input type="{{ $subField['type'] }}" x-model="row['{{ $subName }}']"
                                               class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">

                                    @elseif($subField['type'] === 'enumeration')
                                        @php $_sfOpts = $enumOpts($subField); $_sfMulti = !empty($subField['multiple']); @endphp
                                        @if($_sfMulti)
                                            <div x-data="{ _opts: {{ json_encode($_sfOpts) }} }">
                                                <div class="flex flex-wrap gap-1 mb-1.5" x-show="enumArr(row['{{ $subName }}']).length > 0">
                                                    <template x-for="_ev in enumArr(row['{{ $subName }}'])" :key="'ce'+_ev">
                                                        <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                                                            <span x-text="_ev"></span>
                                                            <button type="button" @click.stop="row['{{ $subName }}'] = enumToggle(row['{{ $subName }}'], _ev)"
                                                                    class="w-3.5 h-3.5 flex items-center justify-center rounded-full hover:bg-purple-200">
                                                                <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                                                </svg>
                                                            </button>
                                                        </span>
                                                    </template>
                                                </div>
                                                <div class="border border-slate-200 rounded-lg overflow-hidden divide-y divide-slate-100">
                                                    <template x-for="_eo in _opts" :key="_eo">
                                                        <div class="flex items-center gap-2.5 px-3 py-2 cursor-pointer transition-colors select-none"
                                                             :class="enumArr(row['{{ $subName }}']).includes(_eo) ? 'bg-purple-50' : 'bg-white hover:bg-slate-50'"
                                                             @click="row['{{ $subName }}'] = enumToggle(row['{{ $subName }}'], _eo)">
                                                            <div class="w-3.5 h-3.5 rounded flex items-center justify-center flex-shrink-0 border transition-all"
                                                                 :class="enumArr(row['{{ $subName }}']).includes(_eo) ? 'bg-purple-600 border-purple-600' : 'border-slate-300 bg-white'">
                                                                <svg x-show="enumArr(row['{{ $subName }}']).includes(_eo)"
                                                                     class="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                                </svg>
                                                            </div>
                                                            <span class="text-sm text-slate-700" x-text="_eo"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        @else
                                            <select x-model="row['{{ $subName }}']"
                                                    class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                                                <option value="">— Select —</option>
                                                @foreach($_sfOpts as $eOpt)
                                                    @if($eOpt)<option value="{{ $eOpt }}">{{ $eOpt }}</option>@endif
                                                @endforeach
                                            </select>
                                        @endif

                                    @elseif($subField['type'] === 'media')
                                        @php $subIsMultiple = !empty($subField['multiple']); @endphp
                                        @if($subIsMultiple)
                                            <div x-data="{
                                                     _mids: (() => { try { const v = row['{{ $subName }}']; return Array.isArray(v) ? v : (v ? JSON.parse(v) : []); } catch(e) { return []; } })(),
                                                     _mshow: false
                                                 }"
                                                 x-init="
                                                     $watch('_mids', v => row['{{ $subName }}'] = v);
                                                     $watch(() => row['{{ $subName }}'], v => {
                                                         try { const nv = Array.isArray(v) ? v : (v ? JSON.parse(v) : []); if (JSON.stringify(_mids) !== JSON.stringify(nv)) _mids = nv; } catch(e) {}
                                                     });
                                                 ">
                                                <div x-show="_mids.length > 0" class="flex flex-wrap gap-2 mb-2">
                                                    <template x-for="_mi in $store._mlib.items.filter(i => _mids.includes(i.id))" :key="_mi.id">
                                                        <div class="relative group">
                                                            <template x-if="_mi.isImage"><img :src="_mi.url" class="h-16 w-16 object-cover rounded-lg"></template>
                                                            <template x-if="!_mi.isImage"><div class="h-16 w-16 bg-slate-100 flex items-center justify-center text-slate-500 text-xs rounded-lg" x-text="_mi.ext"></div></template>
                                                            <button type="button" @click="_mids = _mids.filter(id => id !== _mi.id); talos.markDirty()"
                                                                    class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-xs hidden group-hover:flex items-center justify-center">✕</button>
                                                        </div>
                                                    </template>
                                                </div>
                                                <button type="button" @click="$store._mlib.refresh(); _mshow = true"
                                                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-sm font-medium transition-colors">
                                                    <span x-text="_mids.length ? 'Add / change media' : 'Select from library'"></span>
                                                </button>
                                                <div x-show="_mshow" x-cloak
                                                     class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
                                                     @keydown.escape.window="_mshow = false">
                                                    <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                                                            <h3 class="text-slate-800 font-semibold">Media Library</h3>
                                                            <button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900">
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
                                                                                @click="_mids.includes(_mi.id) ? _mids = _mids.filter(id => id !== _mi.id) : _mids.push(_mi.id); talos.markDirty()"
                                                                                x-show="$store._mlib.folder===null||_mi.folder===$store._mlib.folder"
                                                                                :class="_mids.includes(_mi.id) ? 'border-blue-500' : 'border-transparent'"
                                                                                class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500">
                                                                            <template x-if="_mi.isImage"><img :src="_mi.url" class="w-full h-36 object-cover"></template>
                                                                            <template x-if="!_mi.isImage"><div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs" x-text="_mi.ext"></div></template>
                                                                            <p class="text-xs text-slate-500 p-1 truncate" x-text="_mi.name"></p>
                                                                        </button>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                            @include('talos.content.form._media_upload_label')
                                                            <div class="flex items-center gap-3">
                                                                <span class="text-sm text-slate-400" x-text="_mids.length + ' selected'"></span>
                                                                <button type="button" @click="_mids = []; talos.markDirty()" class="text-sm text-slate-400 hover:text-slate-600">Clear</button>
                                                                <button type="button" @click="_mshow = false" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Done</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div x-data="{
                                                     _mid: row['{{ $subName }}'] ? parseInt(row['{{ $subName }}']) : null,
                                                     _mshow: false
                                                 }"
                                                 x-init="
                                                     $watch('_mid', v => row['{{ $subName }}'] = v);
                                                     $watch(() => row['{{ $subName }}'], v => { const nv = v ? parseInt(v) : null; if (_mid !== nv) _mid = nv; });
                                                 ">
                                                <div x-show="_mid" class="mb-2">
                                                    <template x-for="_mi in $store._mlib.items.filter(i => i.id === _mid)" :key="_mi.id">
                                                        <div>
                                                            <template x-if="_mi.isImage"><img :src="_mi.url" class="h-20 w-auto object-cover rounded-lg"></template>
                                                            <template x-if="!_mi.isImage"><p class="text-sm text-slate-500" x-text="_mi.name"></p></template>
                                                        </div>
                                                    </template>
                                                </div>
                                                <button type="button" @click="$store._mlib.refresh(); _mshow = true"
                                                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-100 text-slate-600 rounded-lg text-sm font-medium transition-colors">
                                                    <span x-text="_mid ? 'Change media' : 'Select from library'"></span>
                                                </button>
                                                <div x-show="_mshow" x-cloak
                                                     class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
                                                     @keydown.escape.window="_mshow = false">
                                                    <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                                                            <h3 class="text-slate-800 font-semibold">Media Library</h3>
                                                            <button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900">
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
                                                                                @click="_mid = _mi.id; _mshow = false; talos.markDirty()"
                                                                                x-show="$store._mlib.folder===null||_mi.folder===$store._mlib.folder"
                                                                                :class="_mid === _mi.id ? 'border-blue-500' : 'border-transparent'"
                                                                                class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500">
                                                                            <template x-if="_mi.isImage"><img :src="_mi.url" class="w-full h-36 object-cover"></template>
                                                                            <template x-if="!_mi.isImage"><div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs" x-text="_mi.ext"></div></template>
                                                                            <p class="text-xs text-slate-500 p-1 truncate" x-text="_mi.name"></p>
                                                                        </button>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                            @include('talos.content.form._media_upload_label')
                                                            <button type="button" @click="_mid = null; _mshow = false; talos.markDirty()"
                                                                    class="text-sm text-slate-400 hover:text-slate-600">Clear</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                    @elseif($subField['type'] === 'richtext')
                                        <div x-effect="
                                            if(isOpen(idx) && !$el.dataset.qlInit) {
                                                $el.dataset.qlInit = '1';
                                                requestAnimationFrame(() => {
                                                    const _qel = $el.querySelector('[data-q]');
                                                    const ql = new Quill(_qel, { theme: 'snow', modules: { toolbar: window._talosQuillToolbar }, placeholder: 'Write something…' });
                                                    const _iv = row['{{ $subName }}'];
                                                    if (_iv) { ql.clipboard.dangerouslyPasteHTML(_iv); ql.history.clear(); }
                                                    ql.on('text-change', () => { const _h = ql.root.innerHTML; row['{{ $subName }}'] = _h === '<p><br></p>' ? '' : _h; talos.markDirty(); });
                                                });
                                            }
                                        "><div data-q></div></div>

                                    @elseif($subField['type'] === 'component')
                                        @php
                                            $nestedUid    = $subField['components'][0] ?? null;
                                            $nestedSchema = $nestedUid ? ($componentMap[$nestedUid] ?? null) : null;
                                            $nestedRep    = !empty($subField['repeatable']);
                                        @endphp
                                        @if($nestedSchema)
                                            @if($nestedRep)
                                                {{-- Nested repeatable inside repeatable --}}
                                                <div x-data="{
                                                         nestedRows: (() => { try { const v = row['{{ $subName }}']; return Array.isArray(v) ? v : (v ? JSON.parse(v) : []); } catch(e) { return []; } })(),
                                                         nestedOpen: {}
                                                     }"
                                                     x-init="$watch('nestedRows', v => row['{{ $subName }}'] = v)">
                                                    <div class="space-y-2">
                                                        <template x-for="(nr, ni) in nestedRows" :key="ni">
                                                            <div class="border border-slate-200 rounded-lg overflow-hidden bg-white">
                                                                <div class="flex items-center justify-between px-3 py-2 bg-slate-50 cursor-pointer"
                                                                     @click="nestedOpen[ni] = !nestedOpen[ni]">
                                                                    <span class="text-xs font-medium text-slate-600" x-text="'Entry #' + (ni + 1)"></span>
                                                                    <div class="flex items-center gap-2">
                                                                        <button type="button" @click.stop="nestedRows.splice(ni, 1); talos.markDirty()" class="text-slate-300 hover:text-red-500 text-xs">✕</button>
                                                                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="nestedOpen[ni] ? 'rotate-180' : ''"
                                                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                                        </svg>
                                                                    </div>
                                                                </div>
                                                                <div x-show="nestedOpen[ni]" class="p-3 space-y-3">
                                                                    @foreach($nestedSchema['attributes'] ?? [] as $nnName => $nnField)
                                                                        <div>
                                                                            <label class="block text-xs font-medium text-slate-500 mb-1">{{ ucwords(str_replace('_', ' ', $nnName)) }}</label>
                                                                            @if(in_array($nnField['type'], ['string','email','url']))
                                                                                <input type="text" x-model="nr['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                                            @elseif($nnField['type'] === 'text')
                                                                                <textarea x-model="nr['{{ $nnName }}']" rows="3" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500 resize-y"></textarea>
                                                                            @elseif(in_array($nnField['type'], ['integer','decimal','float']))
                                                                                <input type="number" x-model.number="nr['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                                            @elseif($nnField['type'] === 'boolean')
                                                                                <button type="button" @click="nr['{{ $nnName }}'] = !nr['{{ $nnName }}']" class="flex items-center gap-2">
                                                                                    <div class="relative w-10 h-5 rounded-full transition-colors" :class="nr['{{ $nnName }}'] ? 'bg-blue-600' : 'bg-slate-200'">
                                                                                        <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform" :class="nr['{{ $nnName }}'] ? 'translate-x-5' : ''"></div>
                                                                                    </div>
                                                                                </button>
                                                                            @elseif($nnField['type'] === 'enumeration')
                                                                                @php $_nnOpts = $enumOpts($nnField); @endphp
                                                                                <select x-model="nr['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                                                    <option value="">— Select —</option>
                                                                                    @foreach($_nnOpts as $nnOpt)
                                                                                        <option value="{{ $nnOpt }}">{{ $nnOpt }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            @elseif($nnField['type'] === 'richtext')
                                                                                <div x-effect="
                                                                                    if(nestedOpen[ni] && !$el.dataset.qlInit) { $el.dataset.qlInit='1'; requestAnimationFrame(() => {
                                                                                    const _qel = $el.querySelector('[data-q]');
                                                                                    const ql = new Quill(_qel, { theme: 'snow', modules: { toolbar: window._talosQuillToolbar }, placeholder: 'Write something…' });
                                                                                    const _iv = nr['{{ $nnName }}'];
                                                                                    if (_iv) { ql.clipboard.dangerouslyPasteHTML(_iv); ql.history.clear(); }
                                                                                    ql.on('text-change', () => { const _h = ql.root.innerHTML; nr['{{ $nnName }}'] = _h === '<p><br></p>' ? '' : _h; talos.markDirty(); });
                                                                                    }); }
                                                                                "><div data-q></div></div>
                                                                            @else
                                                                                <input type="text" x-model="nr['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <button type="button"
                                                            @click="nestedRows.push({}); nestedOpen[nestedRows.length-1] = true; talos.markDirty()"
                                                            class="mt-2 w-full py-2 flex items-center justify-center gap-1 rounded-lg border border-dashed border-slate-300 hover:border-blue-500 text-slate-400 hover:text-blue-600 text-xs font-medium transition-all">
                                                        + Add entry
                                                    </button>
                                                </div>
                                            @else
                                                {{-- Nested single inside repeatable --}}
                                                <div class="space-y-3 p-3 bg-white border border-slate-200 rounded-lg">
                                                    @foreach($nestedSchema['attributes'] ?? [] as $nnName => $nnField)
                                                        <div>
                                                            <label class="block text-xs font-medium text-slate-500 mb-1">{{ ucwords(str_replace('_', ' ', $nnName)) }}</label>
                                                            @if(in_array($nnField['type'], ['string','email','url']))
                                                                <input type="text" x-model="(row['{{ $subName }}'] ??= {})['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                            @elseif($nnField['type'] === 'text')
                                                                <textarea x-model="(row['{{ $subName }}'] ??= {})['{{ $nnName }}']" rows="3" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500 resize-y"></textarea>
                                                            @elseif(in_array($nnField['type'], ['integer','decimal','float']))
                                                                <input type="number" x-model.number="(row['{{ $subName }}'] ??= {})['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                            @elseif($nnField['type'] === 'boolean')
                                                                <button type="button" @click="const o = (row['{{ $subName }}'] ??= {}); o['{{ $nnName }}'] = !o['{{ $nnName }}']" class="flex items-center gap-2">
                                                                    <div class="relative w-10 h-5 rounded-full transition-colors" :class="row['{{ $subName }}']?.['{{ $nnName }}'] ? 'bg-blue-600' : 'bg-slate-200'">
                                                                        <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform" :class="row['{{ $subName }}']?.['{{ $nnName }}'] ? 'translate-x-5' : ''"></div>
                                                                    </div>
                                                                </button>
                                                            @elseif($nnField['type'] === 'enumeration')
                                                                @php $_nnOpts = $enumOpts($nnField); @endphp
                                                                <select x-model="(row['{{ $subName }}'] ??= {})['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                                    <option value="">— Select —</option>
                                                                    @foreach($_nnOpts as $nnOpt)
                                                                        <option value="{{ $nnOpt }}">{{ $nnOpt }}</option>
                                                                    @endforeach
                                                                </select>
                                                            @elseif($nnField['type'] === 'richtext')
                                                                <div x-effect="
                                                                    if(isOpen(idx) && !$el.dataset.qlInit) { $el.dataset.qlInit='1'; requestAnimationFrame(() => {
                                                                    const _qel = $el.querySelector('[data-q]');
                                                                    const ql = new Quill(_qel, { theme: 'snow', modules: { toolbar: window._talosQuillToolbar }, placeholder: 'Write something…' });
                                                                    const _iv = (row['{{ $subName }}'] ?? {})['{{ $nnName }}'];
                                                                    if (_iv) { ql.clipboard.dangerouslyPasteHTML(_iv); ql.history.clear(); }
                                                                    ql.on('text-change', () => { const _h = ql.root.innerHTML; const _o = (row['{{ $subName }}'] ??= {}); _o['{{ $nnName }}'] = _h === '<p><br></p>' ? '' : _h; talos.markDirty(); });
                                                                    }); }
                                                                "><div data-q></div></div>
                                                            @else
                                                                <input type="text" x-model="(row['{{ $subName }}'] ??= {})['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @else
                                            <p class="text-xs text-slate-400 italic">Component "{{ $nestedUid }}" not found.</p>
                                        @endif

                                    @else
                                        <input type="text" x-model="row['{{ $subName }}']"
                                               class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                                    @endif
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
    </div>

{{-- ── Single component ── --}}
@elseif(!$isRepeatable && $compSchema)
    @php
        $compRaw  = is_array($value) ? $value : (is_string($value) && $value ? json_decode($value, true) : null);
        $compJson = !empty($compRaw) ? json_encode($compRaw) : '{}';
    @endphp
    <p class="text-xs text-slate-400 mb-3 font-mono">{{ $firstUid }}</p>

    <div x-data="{ d: {{ $compJson }} }">
        <input type="hidden" name="{{ $name }}" :value="JSON.stringify(d)">
        <div class="space-y-4">
            @foreach($compSchema['attributes'] ?? [] as $subName => $subField)
                @php $subLabel = ucwords(str_replace('_', ' ', $subName)); @endphp
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-2">
                        {{ $subLabel }}
                        <span class="text-slate-400 text-xs font-normal ml-1">({{ $subField['type'] }})</span>
                    </label>

                    @if(in_array($subField['type'], ['string','email','url','uid']))
                        <input type="{{ $subField['type'] === 'email' ? 'email' : ($subField['type'] === 'url' ? 'url' : 'text') }}"
                               x-model="d.{{ $subName }}"
                               class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">

                    @elseif($subField['type'] === 'text')
                        <textarea x-model="d.{{ $subName }}" rows="4"
                                  class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500 resize-y"></textarea>

                    @elseif(in_array($subField['type'], ['integer','biginteger','decimal','float']))
                        <input type="number" x-model.number="d.{{ $subName }}"
                               step="{{ in_array($subField['type'], ['decimal','float']) ? 'any' : '1' }}"
                               class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">

                    @elseif($subField['type'] === 'boolean')
                        <div x-data="{ on: d.{{ $subName }} ?? {{ ($subField['default'] ?? false) ? 'true' : 'false' }} }"
                             x-init="$watch('on', v => d.{{ $subName }} = v)">
                            <button type="button" @click="on = !on" class="flex items-center gap-3">
                                <div class="relative w-12 h-6 rounded-full transition-colors duration-200"
                                     :class="on ? 'bg-blue-600' : 'bg-slate-200'">
                                    <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                                         :class="on ? 'translate-x-6' : 'translate-x-0'"></div>
                                </div>
                                <span class="text-sm font-semibold" :class="on ? 'text-blue-600' : 'text-slate-400'"
                                      x-text="on ? 'True' : 'False'"></span>
                            </button>
                        </div>

                    @elseif(in_array($subField['type'], ['date','datetime','time']))
                        <input type="{{ $subField['type'] }}" x-model="d.{{ $subName }}"
                               class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">

                    @elseif($subField['type'] === 'enumeration')
                        @php $_sfOpts = $enumOpts($subField); $_sfMulti = !empty($subField['multiple']); @endphp
                        @if($_sfMulti)
                            <div x-data="{ _opts: {{ json_encode($_sfOpts) }} }">
                                <div class="flex flex-wrap gap-1 mb-1.5" x-show="enumArr(d.{{ $subName }}).length > 0">
                                    <template x-for="_ev in enumArr(d.{{ $subName }})" :key="'ce'+_ev">
                                        <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                                            <span x-text="_ev"></span>
                                            <button type="button" @click.stop="d.{{ $subName }} = enumToggle(d.{{ $subName }}, _ev)"
                                                    class="w-3.5 h-3.5 flex items-center justify-center rounded-full hover:bg-purple-200">
                                                <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </span>
                                    </template>
                                </div>
                                <div class="border border-slate-200 rounded-lg overflow-hidden divide-y divide-slate-100">
                                    <template x-for="_eo in _opts" :key="_eo">
                                        <div class="flex items-center gap-2.5 px-3 py-2 cursor-pointer transition-colors select-none text-sm"
                                             :class="enumArr(d.{{ $subName }}).includes(_eo) ? 'bg-purple-50' : 'bg-white hover:bg-slate-50'"
                                             @click="d.{{ $subName }} = enumToggle(d.{{ $subName }}, _eo)">
                                            <div class="w-3.5 h-3.5 rounded flex items-center justify-center flex-shrink-0 border transition-all"
                                                 :class="enumArr(d.{{ $subName }}).includes(_eo) ? 'bg-purple-600 border-purple-600' : 'border-slate-300 bg-white'">
                                                <svg x-show="enumArr(d.{{ $subName }}).includes(_eo)"
                                                     class="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </div>
                                            <span class="text-slate-700" x-text="_eo"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        @else
                            <select x-model="d.{{ $subName }}"
                                    class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                                <option value="">— Select —</option>
                                @foreach($_sfOpts as $eOpt)
                                    @if($eOpt)<option value="{{ $eOpt }}">{{ $eOpt }}</option>@endif
                                @endforeach
                            </select>
                        @endif

                    @elseif($subField['type'] === 'media')
                        @php $subIsMultiple = !empty($subField['multiple']); @endphp
                        @if($subIsMultiple)
                            <div x-data="{
                                     _mids: (() => { try { const v = d.{{ $subName }}; return Array.isArray(v) ? v : (v ? JSON.parse(v) : []); } catch(e) { return []; } })(),
                                     _mshow: false
                                 }"
                                 x-init="$watch('_mids', v => d.{{ $subName }} = v)">
                                <div x-show="_mids.length > 0" class="flex flex-wrap gap-2 mb-2">
                                    <template x-for="_mi in $store._mlib.items.filter(i => _mids.includes(i.id))" :key="_mi.id">
                                        <div class="relative group">
                                            <template x-if="_mi.isImage"><img :src="_mi.url" class="h-16 w-16 object-cover rounded-lg"></template>
                                            <template x-if="!_mi.isImage"><div class="h-16 w-16 bg-slate-100 flex items-center justify-center text-slate-500 text-xs rounded-lg" x-text="_mi.ext"></div></template>
                                            <button type="button" @click="_mids = _mids.filter(id => id !== _mi.id); talos.markDirty()"
                                                    class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-xs hidden group-hover:flex items-center justify-center">✕</button>
                                        </div>
                                    </template>
                                </div>
                                <button type="button" @click="$store._mlib.refresh(); _mshow = true"
                                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-sm font-medium transition-colors">
                                    <span x-text="_mids.length ? 'Add / change media' : 'Select from library'"></span>
                                </button>
                                <div x-show="_mshow" x-cloak
                                     class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
                                     @keydown.escape.window="_mshow = false">
                                    <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                                            <h3 class="text-slate-800 font-semibold">Media Library</h3>
                                            <button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900">
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
                                                                @click="_mids.includes(_mi.id) ? _mids = _mids.filter(id => id !== _mi.id) : _mids.push(_mi.id); talos.markDirty()"
                                                                x-show="$store._mlib.folder===null||_mi.folder===$store._mlib.folder"
                                                                :class="_mids.includes(_mi.id) ? 'border-blue-500' : 'border-transparent'"
                                                                class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500">
                                                            <template x-if="_mi.isImage"><img :src="_mi.url" class="w-full h-36 object-cover"></template>
                                                            <template x-if="!_mi.isImage"><div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs" x-text="_mi.ext"></div></template>
                                                            <p class="text-xs text-slate-500 p-1 truncate" x-text="_mi.name"></p>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                            @include('talos.content.form._media_upload_label')
                                            <div class="flex items-center gap-3">
                                                <span class="text-sm text-slate-400" x-text="_mids.length + ' selected'"></span>
                                                <button type="button" @click="_mids = []; talos.markDirty()" class="text-sm text-slate-400 hover:text-slate-600">Clear</button>
                                                <button type="button" @click="_mshow = false" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Done</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div x-data="{ _mid: d.{{ $subName }} ? parseInt(d.{{ $subName }}) : null, _mshow: false }"
                                 x-init="$watch('_mid', v => d.{{ $subName }} = v)">
                                <div x-show="_mid" class="mb-2">
                                    <template x-for="_mi in $store._mlib.items.filter(i => i.id === _mid)" :key="_mi.id">
                                        <div>
                                            <template x-if="_mi.isImage"><img :src="_mi.url" class="h-20 w-auto object-cover rounded-lg"></template>
                                            <template x-if="!_mi.isImage"><p class="text-sm text-slate-500" x-text="_mi.name"></p></template>
                                        </div>
                                    </template>
                                </div>
                                <button type="button" @click="$store._mlib.refresh(); _mshow = true"
                                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-100 text-slate-600 rounded-lg text-sm font-medium transition-colors">
                                    <span x-text="_mid ? 'Change media' : 'Select from library'"></span>
                                </button>
                                <div x-show="_mshow" x-cloak
                                     class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
                                     @keydown.escape.window="_mshow = false">
                                    <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                                            <h3 class="text-slate-800 font-semibold">Media Library</h3>
                                            <button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900">
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
                                                                @click="_mid = _mi.id; _mshow = false; talos.markDirty()"
                                                                x-show="$store._mlib.folder===null||_mi.folder===$store._mlib.folder"
                                                                :class="_mid === _mi.id ? 'border-blue-500' : 'border-transparent'"
                                                                class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500">
                                                            <template x-if="_mi.isImage"><img :src="_mi.url" class="w-full h-36 object-cover"></template>
                                                            <template x-if="!_mi.isImage"><div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs" x-text="_mi.ext"></div></template>
                                                            <p class="text-xs text-slate-500 p-1 truncate" x-text="_mi.name"></p>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                            @include('talos.content.form._media_upload_label')
                                            <button type="button" @click="_mid = null; _mshow = false; talos.markDirty()"
                                                    class="text-sm text-slate-400 hover:text-slate-600">Clear</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    @elseif($subField['type'] === 'richtext')
                        <div x-init="
                            const _qel = $el.querySelector('[data-q]');
                            const ql = new Quill(_qel, { theme: 'snow', modules: { toolbar: window._talosQuillToolbar }, placeholder: 'Write something…' });
                            const _iv = d.{{ $subName }};
                            if (_iv) { ql.clipboard.dangerouslyPasteHTML(_iv); ql.history.clear(); }
                            ql.on('text-change', () => { const _h = ql.root.innerHTML; d.{{ $subName }} = _h === '<p><br></p>' ? '' : _h; talos.markDirty(); });
                        "><div data-q></div></div>

                    @elseif($subField['type'] === 'component')
                        @php
                            $nestedUid    = $subField['components'][0] ?? null;
                            $nestedSchema = $nestedUid ? ($componentMap[$nestedUid] ?? null) : null;
                            $nestedRep    = !empty($subField['repeatable']);
                        @endphp
                        @if($nestedSchema)
                            @if($nestedRep)
                                {{-- Nested repeatable inside single --}}
                                <div x-data="{
                                         nestedRows: (() => { try { const v = d.{{ $subName }}; return Array.isArray(v) ? v : (v ? JSON.parse(v) : []); } catch(e) { return []; } })(),
                                         nestedOpen: {}
                                     }"
                                     x-init="$watch('nestedRows', v => d.{{ $subName }} = v)">
                                    <div class="space-y-2">
                                        <template x-for="(nr, ni) in nestedRows" :key="ni">
                                            <div class="border border-slate-200 rounded-lg overflow-hidden bg-white">
                                                <div class="flex items-center justify-between px-3 py-2 bg-slate-50 cursor-pointer"
                                                     @click="nestedOpen[ni] = !nestedOpen[ni]">
                                                    <span class="text-xs font-medium text-slate-600" x-text="'Entry #' + (ni + 1)"></span>
                                                    <div class="flex items-center gap-2">
                                                        <button type="button" @click.stop="nestedRows.splice(ni, 1); talos.markDirty()" class="text-slate-300 hover:text-red-500 text-xs">✕</button>
                                                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="nestedOpen[ni] ? 'rotate-180' : ''"
                                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div x-show="nestedOpen[ni]" class="p-3 space-y-3">
                                                    @foreach($nestedSchema['attributes'] ?? [] as $nnName => $nnField)
                                                        <div>
                                                            <label class="block text-xs font-medium text-slate-500 mb-1">{{ ucwords(str_replace('_', ' ', $nnName)) }}</label>
                                                            @if(in_array($nnField['type'], ['string','email','url']))
                                                                <input type="text" x-model="nr['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                            @elseif($nnField['type'] === 'text')
                                                                <textarea x-model="nr['{{ $nnName }}']" rows="3" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500 resize-y"></textarea>
                                                            @elseif(in_array($nnField['type'], ['integer','decimal','float']))
                                                                <input type="number" x-model.number="nr['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                            @elseif($nnField['type'] === 'boolean')
                                                                <button type="button" @click="nr['{{ $nnName }}'] = !nr['{{ $nnName }}']" class="flex items-center gap-2">
                                                                    <div class="relative w-10 h-5 rounded-full transition-colors" :class="nr['{{ $nnName }}'] ? 'bg-blue-600' : 'bg-slate-200'">
                                                                        <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform" :class="nr['{{ $nnName }}'] ? 'translate-x-5' : ''"></div>
                                                                    </div>
                                                                </button>
                                                            @elseif($nnField['type'] === 'enumeration')
                                                                @php $_nnOpts = $enumOpts($nnField); @endphp
                                                                <select x-model="nr['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                                    <option value="">— Select —</option>
                                                                    @foreach($_nnOpts as $nnOpt)
                                                                        <option value="{{ $nnOpt }}">{{ $nnOpt }}</option>
                                                                    @endforeach
                                                                </select>
                                                            @elseif($nnField['type'] === 'richtext')
                                                                <div x-effect="
                                                                    if(nestedOpen[ni] && !$el.dataset.qlInit) { $el.dataset.qlInit='1'; requestAnimationFrame(() => {
                                                                    const _qel = $el.querySelector('[data-q]');
                                                                    const ql = new Quill(_qel, { theme: 'snow', modules: { toolbar: window._talosQuillToolbar }, placeholder: 'Write something…' });
                                                                    const _iv = nr['{{ $nnName }}'];
                                                                    if (_iv) { ql.clipboard.dangerouslyPasteHTML(_iv); ql.history.clear(); }
                                                                    ql.on('text-change', () => { const _h = ql.root.innerHTML; nr['{{ $nnName }}'] = _h === '<p><br></p>' ? '' : _h; talos.markDirty(); });
                                                                    }); }
                                                                "><div data-q></div></div>
                                                            @else
                                                                <input type="text" x-model="nr['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <button type="button"
                                            @click="nestedRows.push({}); nestedOpen[nestedRows.length-1] = true; talos.markDirty()"
                                            class="mt-2 w-full py-2 flex items-center justify-center gap-1 rounded-lg border border-dashed border-slate-300 hover:border-blue-500 text-slate-400 hover:text-blue-600 text-xs font-medium transition-all">
                                        + Add entry
                                    </button>
                                </div>
                            @else
                                {{-- Nested single inside single --}}
                                <div class="space-y-3 p-3 bg-white border border-slate-200 rounded-lg">
                                    @foreach($nestedSchema['attributes'] ?? [] as $nnName => $nnField)
                                        <div>
                                            <label class="block text-xs font-medium text-slate-500 mb-1">{{ ucwords(str_replace('_', ' ', $nnName)) }}</label>
                                            @if(in_array($nnField['type'], ['string','email','url']))
                                                <input type="text" x-model="(d.{{ $subName }} ??= {})['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                            @elseif($nnField['type'] === 'text')
                                                <textarea x-model="(d.{{ $subName }} ??= {})['{{ $nnName }}']" rows="3" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500 resize-y"></textarea>
                                            @elseif(in_array($nnField['type'], ['integer','decimal','float']))
                                                <input type="number" x-model.number="(d.{{ $subName }} ??= {})['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                            @elseif($nnField['type'] === 'boolean')
                                                <button type="button" @click="const o = (d.{{ $subName }} ??= {}); o['{{ $nnName }}'] = !o['{{ $nnName }}']" class="flex items-center gap-2">
                                                    <div class="relative w-10 h-5 rounded-full transition-colors" :class="d.{{ $subName }}?.['{{ $nnName }}'] ? 'bg-blue-600' : 'bg-slate-200'">
                                                        <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform" :class="d.{{ $subName }}?.['{{ $nnName }}'] ? 'translate-x-5' : ''"></div>
                                                    </div>
                                                </button>
                                            @elseif($nnField['type'] === 'enumeration')
                                                @php $_nnOpts = $enumOpts($nnField); @endphp
                                                <select x-model="(d.{{ $subName }} ??= {})['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                    <option value="">— Select —</option>
                                                    @foreach($_nnOpts as $nnOpt)
                                                        @if($nnOpt)<option value="{{ $nnOpt }}">{{ $nnOpt }}</option>@endif
                                                    @endforeach
                                                </select>
                                            @elseif($nnField['type'] === 'richtext')
                                                <div x-init="
                                                    const _qel = $el.querySelector('[data-q]');
                                                    const ql = new Quill(_qel, { theme: 'snow', modules: { toolbar: window._talosQuillToolbar }, placeholder: 'Write something…' });
                                                    const _iv = (d['{{ $subName }}'] ?? {})['{{ $nnName }}'];
                                                    if (_iv) { ql.clipboard.dangerouslyPasteHTML(_iv); ql.history.clear(); }
                                                    ql.on('text-change', () => { const _h = ql.root.innerHTML; const _o = (d['{{ $subName }}'] ??= {}); _o['{{ $nnName }}'] = _h === '<p><br></p>' ? '' : _h; talos.markDirty(); });
                                                "><div data-q></div></div>
                                            @else
                                                <input type="text" x-model="(d.{{ $subName }} ??= {})['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <p class="text-xs text-slate-400 italic">Component "{{ $nestedUid }}" not found.</p>
                        @endif

                    @else
                        <input type="text" x-model="d.{{ $subName }}"
                               class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                    @endif
                </div>
            @endforeach
        </div>
    </div>

{{-- ── Not found ── --}}
@else
    <div class="p-4 bg-slate-100 rounded-lg border border-dashed border-slate-300">
        <p class="text-sm text-slate-400">
            @if($firstUid) Component "{{ $firstUid }}" not found. @else No component assigned. @endif
        </p>
    </div>
@endif
