@php
    $relOpts    = $relationOptions[$name] ?? null;
    $isMultiple = $relOpts['multiple'] ?? false;
    $selected   = $isEdit ? ($value ?? ($isMultiple ? [] : null)) : ($isMultiple ? [] : null);
    if ($isMultiple) $selected = (array) $selected;
@endphp

@if($relOpts && count($relOpts['entries']) > 0)
    @php
        $lf         = $relOpts['labelField'];
        $entryLabel = fn($e) => $lf
            ? (' — ' . \Illuminate\Support\Str::limit(strip_tags($e->$lf ?? ''), 60))
            : '';
    @endphp

    @if($isMultiple)
        @php
            $relEntriesJson  = json_encode(collect($relOpts['entries'])->map(fn($e) => ['id' => $e->id, 'label' => '#' . $e->id . $entryLabel($e)])->values()->all());
            $relSelectedJson = json_encode(array_values(array_map('intval', $selected)));
        @endphp
        <div x-data="relPicker({{ $relEntriesJson }}, {{ $relSelectedJson }})">

            <template x-for="id in selected" :key="id">
                <input type="hidden" name="{{ $name }}[]" :value="id">
            </template>

            <div class="flex flex-wrap gap-1.5 mb-3" x-show="selected.length > 0">
                <template x-for="id in selected" :key="'chip-' + id">
                    <span class="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">
                        <span x-text="labelFor(id)"></span>
                        <button type="button" @click.stop="toggle(id)"
                                class="w-4 h-4 flex items-center justify-center rounded-full hover:bg-blue-200 transition-colors flex-shrink-0">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </span>
                </template>
            </div>

            <div class="relative mb-2">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model="search" placeholder="Search…"
                       class="w-full pl-9 pr-4 py-2 bg-slate-100 border border-slate-200 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 transition-all">
                <button type="button" x-show="search" @click.stop="search = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="border border-slate-200 rounded-xl overflow-hidden divide-y divide-slate-100 max-h-56 overflow-y-auto">
                <template x-if="filtered().length === 0">
                    <div class="px-4 py-4 text-sm text-slate-400 text-center">No results</div>
                </template>
                <template x-for="entry in filtered()" :key="entry.id">
                    <div class="flex items-center gap-3 px-4 py-2.5 cursor-pointer transition-colors select-none"
                         :class="selected.includes(entry.id) ? 'bg-blue-50 hover:bg-blue-50/80' : 'bg-white hover:bg-slate-50'"
                         @click="toggle(entry.id)">
                        <div class="w-4 h-4 rounded flex items-center justify-center flex-shrink-0 border transition-all duration-100"
                             :class="selected.includes(entry.id) ? 'bg-blue-600 border-blue-600' : 'border-slate-300 bg-white'">
                            <svg x-show="selected.includes(entry.id)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="text-sm text-slate-700" x-text="entry.label"></span>
                    </div>
                </template>
            </div>

            <p class="text-xs text-slate-400 mt-2"
               x-text="selected.length ? selected.length + ' selected of ' + entries.length : entries.length + ' available'"></p>
        </div>
    @else
        <select name="{{ $name }}"
                class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 transition-all">
            <option value="">— None —</option>
            @foreach($relOpts['entries'] as $relEntry)
                <option value="{{ $relEntry->id }}"
                        {{ $selected == $relEntry->id ? 'selected' : '' }}>
                    #{{ $relEntry->id }}{{ $entryLabel($relEntry) }}
                </option>
            @endforeach
        </select>
    @endif
@else
    <p class="text-sm text-slate-400 italic">
        {{ $relOpts ? 'No entries in the target content type yet.' : 'Target content type not found.' }}
    </p>
@endif
