{{--
    Generic recursive field-editor partial. Renders ONE field's input given:
    - $subName  : field key (used for label/context, not required to be unique across the page)
    - $subField : the field definition (['type' => ..., ...])
    - $bind     : a JS lvalue expression pointing at this field's value in whatever Alpine
                  scope is currently active, e.g. "row['name']", "d['title']", "nr['icon']"
    - $depth    : nesting depth so far (caps recursion for component/repeater fields)
    - $openExpr : optional Alpine boolean expression for "is this field's container visible
                  right now" (used to lazily init Quill). Defaults to "true".

    Used by _repeater_rows_ui.blade.php (repeater rows, at any depth) and by itself
    recursively for nested component/repeater fields, so every field type works no matter
    how deeply it's nested inside repeaters and components.
--}}
@php
    $type     = $subField['type'] ?? 'string';
    $depth    = $depth ?? 0;
    $openExpr = $openExpr ?? 'true';
    $maxDepth = 4;
@endphp

@if(in_array($type, ['string', 'email', 'url', 'uid']))
    <input type="{{ $type === 'email' ? 'email' : ($type === 'url' ? 'url' : 'text') }}"
           x-model="{{ $bind }}"
           class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">

@elseif($type === 'text')
    <textarea x-model="{{ $bind }}" rows="4"
              class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500 resize-y"></textarea>

@elseif(in_array($type, ['integer', 'biginteger', 'decimal', 'float']))
    <input type="number"
           x-model.number="{{ $bind }}"
           step="{{ in_array($type, ['decimal', 'float']) ? 'any' : '1' }}"
           class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">

@elseif($type === 'boolean')
    <button type="button" @click="{{ $bind }} = !({{ $bind }})" class="flex items-center gap-3">
        <div class="relative w-12 h-6 rounded-full transition-colors duration-200" :class="({{ $bind }}) ? 'bg-blue-600' : 'bg-slate-200'">
            <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200" :class="({{ $bind }}) ? 'translate-x-6' : 'translate-x-0'"></div>
        </div>
        <span class="text-sm font-semibold" :class="({{ $bind }}) ? 'text-blue-600' : 'text-slate-400'" x-text="({{ $bind }}) ? 'True' : 'False'"></span>
    </button>

@elseif(in_array($type, ['date', 'datetime', 'time']))
    <input type="{{ $type }}" x-model="{{ $bind }}"
           class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">

@elseif($type === 'enumeration')
    @php $_sfOpts = $enumOpts($subField); $_sfMulti = !empty($subField['multiple']); @endphp
    @if($_sfMulti)
        <div x-data="{ _opts: {{ json_encode($_sfOpts) }} }">
            <div class="flex flex-wrap gap-1 mb-1.5" x-show="enumArr({{ $bind }}).length > 0">
                <template x-for="_ev in enumArr({{ $bind }})" :key="'e'+_ev">
                    <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                        <span x-text="_ev"></span>
                        <button type="button" @click.stop="{{ $bind }} = enumToggle({{ $bind }}, _ev)"
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
                         :class="enumArr({{ $bind }}).includes(_eo) ? 'bg-purple-50' : 'bg-white hover:bg-slate-50'"
                         @click="{{ $bind }} = enumToggle({{ $bind }}, _eo)">
                        <div class="w-3.5 h-3.5 rounded flex items-center justify-center flex-shrink-0 border transition-all"
                             :class="enumArr({{ $bind }}).includes(_eo) ? 'bg-purple-600 border-purple-600' : 'border-slate-300 bg-white'">
                            <svg x-show="enumArr({{ $bind }}).includes(_eo)" class="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="text-slate-700" x-text="_eo"></span>
                    </div>
                </template>
            </div>
        </div>
    @else
        <select x-model="{{ $bind }}"
                class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
            <option value="">— Select —</option>
            @foreach($_sfOpts as $eOpt)
                @if($eOpt)<option value="{{ $eOpt }}">{{ $eOpt }}</option>@endif
            @endforeach
        </select>
    @endif

@elseif($type === 'media')
    @php $subIsMultiple = !empty($subField['multiple']); @endphp
    @if($subIsMultiple)
        <div x-data="{
                 _mids: (() => { try { const v = {{ $bind }}; const a = Array.isArray(v) ? v : (v ? JSON.parse(v) : []); return JSON.parse(JSON.stringify(a)); } catch(e) { return []; } })(),
                 _mshow: false
             }"
             x-init="
                 $watch('_mids', v => {{ $bind }} = v);
                 $watch(() => {{ $bind }}, v => {
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
            <template x-if="_mshow">
            <div x-cloak
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
            </template>
        </div>
    @else
        <div x-data="{
                 _mid: {{ $bind }} ? parseInt({{ $bind }}) : null,
                 _mshow: false
             }"
             x-init="
                 $watch('_mid', v => {{ $bind }} = v);
                 $watch(() => {{ $bind }}, v => { const nv = v ? parseInt(v) : null; if (_mid !== nv) _mid = nv; });
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
            <template x-if="_mshow">
            <div x-cloak
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
            </template>
        </div>
    @endif

@elseif($type === 'richtext')
    <div x-effect="
        if(({{ $openExpr }}) && !$el.dataset.qlInit) {
            $el.dataset.qlInit = '1';
            requestAnimationFrame(() => {
                const _qel = $el.querySelector('[data-q]');
                const ql = new Quill(_qel, { theme: 'snow', modules: { toolbar: window._talosQuillToolbar }, placeholder: 'Write something…' });
                const _iv = {{ $bind }};
                if (_iv) { ql.clipboard.dangerouslyPasteHTML(_iv); ql.history.clear(); }
                ql.on('text-change', () => {
                    const _h = ql.root.innerHTML;
                    {{ $bind }} = _h === '<p><br></p>' ? '' : _h;
                    talos.markDirty();
                });
            });
        }
    "><div data-q></div></div>

@elseif($type === 'repeater' && $depth < $maxDepth)
    @php
        $nestedSubFields = $subField['subFields'] ?? [];
        $nrEmpty = json_encode(collect($nestedSubFields)->mapWithKeys(fn($sf, $sn) => [$sn => $sf['default'] ?? ''])->all());
    @endphp
    <div x-data="repeaterField((() => { try { const v = {{ $bind }}; const a = Array.isArray(v) ? v : (v ? JSON.parse(v) : []); return JSON.parse(JSON.stringify(a)); } catch(e) { return []; } })(), {{ $nrEmpty }})"
         x-init="$watch('rows', v => {{ $bind }} = v)">
        @include('talos.content.form._repeater_rows_ui', ['attrs' => $nestedSubFields, 'depth' => $depth])
    </div>

@elseif($type === 'component' && $depth < $maxDepth)
    @php
        $nestedUid    = $subField['components'][0] ?? null;
        $nestedSchema = $nestedUid ? ($componentMap[$nestedUid] ?? null) : null;
        $nestedRep    = !empty($subField['repeatable']);
    @endphp
    @if($nestedSchema)
        <p class="text-xs text-slate-400 mb-2 font-mono">{{ $nestedUid }}{{ $nestedRep ? ' · repeatable' : '' }}</p>
        @if($nestedRep)
            @php $ncEmpty = json_encode(collect($nestedSchema['attributes'] ?? [])->mapWithKeys(fn($sf, $sn) => [$sn => $sf['default'] ?? ''])->all()); @endphp
            <div x-data="repeaterField((() => { try { const v = {{ $bind }}; const a = Array.isArray(v) ? v : (v ? JSON.parse(v) : []); return JSON.parse(JSON.stringify(a)); } catch(e) { return []; } })(), {{ $ncEmpty }})"
                 x-init="$watch('rows', v => {{ $bind }} = v)">
                @include('talos.content.form._repeater_rows_ui', ['attrs' => $nestedSchema['attributes'] ?? [], 'depth' => $depth])
            </div>
        @else
            @php $dVar = 'd' . ($depth + 1); @endphp
            <div x-data="{ {{ $dVar }}: (() => { try { const v = {{ $bind }}; const o = (v && typeof v === 'object' && !Array.isArray(v)) ? v : {}; return JSON.parse(JSON.stringify(o)); } catch(e) { return {}; } })() }"
                 x-init="$watch('{{ $dVar }}', v => {{ $bind }} = v, { deep: true })">
                <div class="space-y-4 p-3 bg-white border border-slate-200 rounded-lg">
                    @foreach($nestedSchema['attributes'] ?? [] as $nnName => $nnField)
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">{{ $nnField['displayName'] ?? ucwords(str_replace('_', ' ', $nnName)) }}</label>
                            @include('talos.content.form._field_subfield', [
                                'subName'  => $nnName,
                                'subField' => $nnField,
                                'bind'     => "{$dVar}['{$nnName}']",
                                'depth'    => $depth + 1,
                                'openExpr' => 'true',
                            ])
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <p class="text-xs text-slate-400 italic">Component "{{ $nestedUid }}" not found.</p>
    @endif

@else
    <input type="text" x-model="{{ $bind }}"
           class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
@endif
