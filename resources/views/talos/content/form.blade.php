@extends('talos.layouts.app')

@php
    $isEdit = isset($entry);
    $title  = $isEdit ? 'Edit entry' : 'Create entry';
@endphp

@section('title', $title . ' — ' . $contentType['info']['displayName'])
@section('header', $contentType['info']['displayName'] . ' — ' . ($isEdit ? 'Edit' : 'Create'))

@push('styles')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
    /* Light-theme overrides for Quill Snow */
    .ql-toolbar.ql-snow {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-bottom: none;
        border-radius: 0.5rem 0.5rem 0 0;
    }
    .ql-container.ql-snow {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0 0 0.5rem 0.5rem;
    }
    .ql-toolbar .ql-stroke { stroke: #64748b; }
    .ql-toolbar .ql-fill   { fill:   #64748b; }
    .ql-toolbar .ql-picker-label,
    .ql-toolbar .ql-picker-item { color: #64748b; }
    .ql-toolbar button:hover .ql-stroke,
    .ql-toolbar button.ql-active .ql-stroke { stroke: #2563eb; }
    .ql-toolbar button:hover .ql-fill,
    .ql-toolbar button.ql-active .ql-fill   { fill:   #2563eb; }
    .ql-toolbar .ql-picker-options { background: #ffffff; border-color: #e2e8f0; }
    .ql-editor { color: #1e293b; min-height: 220px; font-size: 0.9rem; line-height: 1.6; }
    .ql-editor.ql-blank::before { color: #94a3b8; font-style: normal; }
    .ql-editor h1, .ql-editor h2, .ql-editor h3 { color: #0f172a; }
    .ql-editor blockquote { border-left-color: #cbd5e1; color: #64748b; }
    .ql-editor code, .ql-editor pre { background: #f1f5f9; color: #2563eb; }
    .ql-snow .ql-tooltip { background: #ffffff; border-color: #e2e8f0; color: #1e293b; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .ql-snow .ql-tooltip input[type=text] { background: #f8fafc; border-color: #e2e8f0; color: #1e293b; }
</style>
@endpush

@section('content')
@php
    $attributes  = $contentType['attributes'] ?? [];
    $draftable   = $contentType['options']['draftAndPublish'] ?? false;
    $i18n        = $i18n ?? false;
    $locale      = $locale ?? config('talos.default_locale');
    $locales     = $locales ?? app(\App\Services\LocaleService::class)->all();
    $siblings    = $siblings ?? [];
    $mediaItems  = $mediaItems ?? collect();
    $components  = $components ?? [];

    // Shared helper: parse enum options from schema string
    $enumOpts = fn($f) => array_values(array_filter(array_map('trim', explode("\n", $f['enumValues'] ?? ''))));

    // Media folders for the picker sidebar
    $mediaFolders = $mediaItems->pluck('folder')->filter()->unique()->sort()->values();

    // Build uid → schema map for component fields
    $componentMap = [];
    foreach ($components as $comp) {
        $componentMap[$comp['__uid']] = $comp;
    }
@endphp

<div class="flex gap-6 items-start">

    {{-- ── Main form (fields only — sidebar lives outside to avoid nested-form bug) ── --}}
    <form action="{{ $isEdit ? route('talos.content.update', ['uid' => $uid, 'id' => $entry->id]) : route('talos.content.store', ['uid' => $uid]) }}"
          method="POST" enctype="multipart/form-data" id="content-form" class="flex-1">
        @csrf
        @if($isEdit) @method('PUT') @endif
        @if($i18n)
            <input type="hidden" name="locale" value="{{ $locale }}">
            @if(!$isEdit)
                <input type="hidden" name="localizations_id" value="{{ request('localizations_id') }}">
            @endif
        @endif

        <div class="space-y-5">
            {{-- ── Slug field (collection types only, not single types) ── --}}
            @php
                $isCollection  = ($contentType['kind'] ?? 'collectionType') === 'collectionType';
                $isTranslation = $isEdit && $i18n
                    && isset($entry->localizations_id)
                    && $entry->localizations_id
                    && $entry->id !== $entry->localizations_id;
                $slugValue     = $isEdit ? ($entry->slug ?? '') : old('slug', '');
            @endphp
            @if($isCollection)
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <label class="block text-sm font-medium text-slate-600 mb-2">
                    Slug
                    <span class="text-slate-400 text-xs font-normal ml-1">(URL identifier)</span>
                    @if($isTranslation)
                        <span class="ml-2 text-[10px] font-medium bg-slate-100 text-slate-400 px-2 py-0.5 rounded-full">shared across translations</span>
                    @endif
                </label>
                @if($isTranslation)
                    <div class="flex items-center gap-2">
                        <span class="text-slate-300 text-sm">/</span>
                        <input type="text" name="slug" value="{{ $slugValue }}" readonly
                               class="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-400 text-sm font-mono cursor-not-allowed">
                    </div>
                    <p class="text-xs text-slate-400 mt-1.5">Slug is set on the main entry and cannot be changed in translations.</p>
                @else
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400 text-sm">/</span>
                        <input type="text" id="talos-slug-input" name="slug" value="{{ $slugValue }}"
                               placeholder="auto-generated-from-first-field"
                               class="flex-1 px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500 font-mono">
                    </div>
                    <p class="text-xs text-slate-400 mt-1.5">
                        Fetch by slug or ID:
                        <code class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-500">/api/{{ $contentType['info']['pluralName'] ?? 'entries' }}/{slug-or-id}</code>
                    </p>
                @endif
            </div>
            @endif

            @foreach($attributes as $name => $field)
                @php
                    $value    = $isEdit ? ($entry->$name ?? null) : old($name, $field['default'] ?? null);
                    $label    = ucwords(str_replace('_', ' ', $name));
                    $required = $field['required'] ?? false;
                @endphp

                @if($field['private'] ?? false)
                    @continue
                @endif

                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <label class="block text-sm font-medium text-slate-600 mb-3">
                        {{ $label }}
                        @if($required)<span class="text-red-600 ml-0.5">*</span>@endif
                        <span class="text-slate-400 text-xs font-normal ml-1">({{ $field['type'] }})</span>
                    </label>

                    @switch($field['type'])

                        {{-- ── Short / typed text ── --}}
                        @case('string')
                        @case('email')
                        @case('url')
                        @case('uid')
                            <input type="{{ $field['type'] === 'email' ? 'email' : ($field['type'] === 'url' ? 'url' : 'text') }}"
                                   name="{{ $name }}" value="{{ $value }}"
                                   {{ $required ? 'required' : '' }}
                                   maxlength="{{ $field['maxLength'] ?? 255 }}"
                                   class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                            @break

                        {{-- ── Plain long text ── --}}
                        @case('text')
                            <textarea name="{{ $name }}" rows="4" {{ $required ? 'required' : '' }}
                                      class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500 resize-y">{{ $value }}</textarea>
                            @break

                        {{-- ── Rich text (Quill) ── --}}
                        @case('richtext')
                            <div data-quill-for="{{ $name }}">{!! $value !!}</div>
                            <input type="hidden" name="{{ $name }}" id="quill-input-{{ $name }}" value="{{ $value }}">
                            @break

                        {{-- ── Relation ── --}}
                        @case('relation')
                            @php
                                $relOpts    = $relationOptions[$name] ?? null;
                                $isMultiple = $relOpts['multiple'] ?? false;
                                $selected   = $isEdit ? ($value ?? ($isMultiple ? [] : null)) : ($isMultiple ? [] : null);
                                if ($isMultiple) $selected = (array) $selected;
                            @endphp
                            @if($relOpts && count($relOpts['entries']) > 0)
                                @php
                                    $lf = $relOpts['labelField'];
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

                                        {{-- Hidden inputs for form submission --}}
                                        <template x-for="id in selected" :key="id">
                                            <input type="hidden" name="{{ $name }}[]" :value="id">
                                        </template>

                                        {{-- Selected chips --}}
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

                                        {{-- Search --}}
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

                                        {{-- Entry list --}}
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

                                        {{-- Footer count --}}
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
                            @break

                        {{-- ── Numbers ── --}}
                        @case('integer')
                        @case('biginteger')
                        @case('decimal')
                        @case('float')
                            <input type="number" name="{{ $name }}" value="{{ $value }}"
                                   {{ $required ? 'required' : '' }}
                                   step="{{ in_array($field['type'], ['decimal','float']) ? 'any' : '1' }}"
                                   min="{{ $field['min'] ?? '' }}" max="{{ $field['max'] ?? '' }}"
                                   class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                            @break

                        {{-- ── Boolean toggle ── --}}
                        @case('boolean')
                            @php
                                $boolOn = $isEdit ? (bool)$value : (bool)($field['default'] ?? false);
                            @endphp
                            <div x-data="{ on: {{ $boolOn ? 'true' : 'false' }} }">
                                <input type="hidden" name="{{ $name }}" :value="on ? '1' : '0'">
                                <button type="button" @click="on = !on" class="flex items-center gap-3">
                                    <div class="relative w-12 h-6 rounded-full transition-colors duration-200"
                                         :class="on ? 'bg-blue-600' : 'bg-slate-200'">
                                        <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                                             :class="on ? 'translate-x-6' : 'translate-x-0'"></div>
                                    </div>
                                    <span class="text-sm font-semibold transition-colors"
                                          :class="on ? 'text-blue-600' : 'text-slate-400'"
                                          x-text="on ? 'True' : 'False'"></span>
                                </button>
                            </div>
                            @break

                        {{-- ── Date / time ── --}}
                        @case('datetime')
                            <input type="datetime-local" name="{{ $name }}"
                                   value="{{ $value }}" {{ $required ? 'required' : '' }}
                                   class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                            @break

                        @case('date')
                        @case('time')
                            <input type="{{ $field['type'] }}" name="{{ $name }}"
                                   value="{{ $value }}" {{ $required ? 'required' : '' }}
                                   class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                            @break

                        {{-- ── Enumeration ── --}}
                        @case('enumeration')
                            @php
                                $eOpts = $enumOpts($field);
                                $eMulti = !empty($field['multiple']);
                            @endphp
                            @if($eMulti)
                                @php
                                    $eSel = is_array($value) ? $value : ($value ? [$value] : []);
                                @endphp
                                <div x-data="enumPicker({{ json_encode($eOpts) }}, {{ json_encode($eSel) }})">
                                    <template x-for="v in selected" :key="v">
                                        <input type="hidden" name="{{ $name }}[]" :value="v">
                                    </template>
                                    <div class="flex flex-wrap gap-1.5 mb-2" x-show="selected.length > 0">
                                        <template x-for="v in selected" :key="'c'+v">
                                            <span class="inline-flex items-center gap-1 pl-2.5 pr-1.5 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                                                <span x-text="v"></span>
                                                <button type="button" @click.stop="toggle(v)" class="w-4 h-4 flex items-center justify-center rounded-full hover:bg-purple-200 transition-colors">
                                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </span>
                                        </template>
                                    </div>
                                    <div class="border border-slate-200 rounded-xl overflow-hidden divide-y divide-slate-100">
                                        <template x-for="opt in opts" :key="opt">
                                            <div class="flex items-center gap-3 px-4 py-2.5 cursor-pointer transition-colors select-none"
                                                 :class="selected.includes(opt) ? 'bg-purple-50 hover:bg-purple-50/80' : 'bg-white hover:bg-slate-50'"
                                                 @click="toggle(opt)">
                                                <div class="w-4 h-4 rounded flex items-center justify-center flex-shrink-0 border transition-all"
                                                     :class="selected.includes(opt) ? 'bg-purple-600 border-purple-600' : 'border-slate-300 bg-white'">
                                                    <svg x-show="selected.includes(opt)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-slate-700" x-text="opt"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-2" x-text="selected.length ? selected.length + ' selected' : 'None selected'"></p>
                                </div>
                            @else
                                <select name="{{ $name }}" {{ $required ? 'required' : '' }}
                                        class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                                    @if(!$required)<option value="">— Select —</option>@endif
                                    @foreach($eOpts as $opt)
                                        <option value="{{ $opt }}" {{ $value === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @break

                        {{-- ── Raw JSON ── --}}
                        @case('json')
                            <textarea name="{{ $name }}" rows="5" {{ $required ? 'required' : '' }}
                                      class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm font-mono focus:outline-none focus:border-blue-500 resize-y">{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value }}</textarea>
                            <p class="text-xs text-slate-400 mt-1">Must be valid JSON</p>
                            @break

                        {{-- ── Media picker ── --}}
                        @case('media')
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
                                {{-- ── Multiple files ── --}}
                                <div x-data="{ ids: {{ json_encode($mInitIds) }}, show: false }">
                                    <input type="hidden" name="{{ $name }}_id" :value="JSON.stringify(ids)">

                                    {{-- Selected thumbnails --}}
                                    <div class="flex flex-wrap gap-2 mb-3" x-show="ids.length > 0">
                                        @foreach($mediaItems as $m)
                                            <div x-show="ids.includes({{ $m->id }})" class="relative group/thumb flex-shrink-0">
                                                @if($m->isImage())
                                                    <img src="{{ $m->url }}" class="h-20 w-20 object-cover rounded-lg border border-slate-300">
                                                @else
                                                    <div class="h-20 w-20 bg-slate-100 rounded-lg border border-slate-300 flex items-center justify-center">
                                                        <span class="text-xs text-slate-400 font-mono uppercase">{{ $m->ext }}</span>
                                                    </div>
                                                @endif
                                                <button type="button" @click.stop="ids = ids.filter(i => i !== {{ $m->id }})"
                                                        class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-600 hover:bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover/thumb:opacity-100 transition-opacity">
                                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>

                                    <button type="button" @click="show = true"
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
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="flex-1 min-h-0 flex overflow-hidden"><div class="w-48 border-r border-slate-200 flex-shrink-0 overflow-y-auto py-2"><button type="button" @click="$store._mlib.folder = null" :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm transition-colors">All media</button>@foreach($mediaFolders as $_mfdr)<button type="button" data-folder="{{ $_mfdr }}" @click="$store._mlib.folder = $el.dataset.folder" :class="$el.dataset.folder === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><span>{{ basename($_mfdr) }}</span></button>@endforeach</div><div class="flex-1 min-h-0 overflow-y-auto"><div class="p-4 grid grid-cols-3 gap-4">
                                                @foreach($mediaItems as $m)
                                                    <button type="button"
                                                            @click="ids.includes({{ $m->id }}) ? ids = ids.filter(i => i !== {{ $m->id }}) : ids.push({{ $m->id }})"
                                                            data-folder="{{ $m->folder ?? '' }}" x-show="$store._mlib.folder===null||$el.dataset.folder===$store._mlib.folder" class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500 relative"
                                                            :class="ids.includes({{ $m->id }}) ? 'border-blue-500' : 'border-transparent'">
                                                        @if($m->isImage())
                                                            <img src="{{ $m->url }}" class="w-full h-36 object-cover">
                                                        @else
                                                            <div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs">{{ $m->ext }}</div>
                                                        @endif
                                                        <p class="text-xs text-slate-500 p-1 truncate">{{ $m->name }}</p>
                                                        <div x-show="ids.includes({{ $m->id }})"
                                                             class="absolute top-1.5 right-1.5 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center pointer-events-none">
                                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                            </div></div><div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                <a href="{{ route('talos.media.index') }}" target="_blank"
                                                   class="text-sm text-blue-600 hover:underline">Upload more →</a>
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
                                {{-- ── Single file ── --}}
                                <div x-data="{ selectedId: {{ $mInitId ?? 'null' }}, show: false }">
                                    <input type="hidden" name="{{ $name }}_id" :value="selectedId">

                                    <div x-show="selectedId" class="mb-3">
                                        @foreach($mediaItems as $m)
                                            <div x-show="selectedId === {{ $m->id }}">
                                                @if($m->isImage())
                                                    <img src="{{ $m->url }}" class="h-32 object-cover rounded-lg">
                                                @else
                                                    <p class="text-sm text-slate-500">{{ $m->name }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>

                                    <button type="button" @click="show = true"
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
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="flex-1 min-h-0 flex overflow-hidden"><div class="w-48 border-r border-slate-200 flex-shrink-0 overflow-y-auto py-2"><button type="button" @click="$store._mlib.folder = null" :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm transition-colors">All media</button>@foreach($mediaFolders as $_mfdr)<button type="button" data-folder="{{ $_mfdr }}" @click="$store._mlib.folder = $el.dataset.folder" :class="$el.dataset.folder === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><span>{{ basename($_mfdr) }}</span></button>@endforeach</div><div class="flex-1 min-h-0 overflow-y-auto"><div class="p-4 grid grid-cols-3 gap-4">
                                                @foreach($mediaItems as $m)
                                                    <button type="button"
                                                            @click="selectedId = {{ $m->id }}; show = false"
                                                            data-folder="{{ $m->folder ?? '' }}" x-show="$store._mlib.folder===null||$el.dataset.folder===$store._mlib.folder" class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500"
                                                            :class="selectedId === {{ $m->id }} ? 'border-blue-500' : 'border-transparent'">
                                                        @if($m->isImage())
                                                            <img src="{{ $m->url }}" class="w-full h-36 object-cover">
                                                        @else
                                                            <div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs">{{ $m->ext }}</div>
                                                        @endif
                                                        <p class="text-xs text-slate-500 p-1 truncate">{{ $m->name }}</p>
                                                    </button>
                                                @endforeach
                                            </div>
                                            </div></div><div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                <a href="{{ route('talos.media.index') }}" target="_blank"
                                                   class="text-sm text-blue-600 hover:underline">Upload more →</a>
                                                <button type="button" @click="selectedId = null; show = false"
                                                        class="text-sm text-slate-400 hover:text-slate-600">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @break

                        {{-- ── Component (single or repeatable) ── --}}
                        @case('component')
                            @php
                                $compUids     = $field['components'] ?? [];
                                $firstUid     = $compUids[0] ?? null;
                                $compSchema   = $firstUid ? ($componentMap[$firstUid] ?? null) : null;
                                $isRepeatable = $field['repeatable'] ?? false;
                            @endphp

                            @if($isRepeatable && $compSchema)
                                {{-- ── Repeatable component — accordion list ── --}}
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
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <p class="text-sm text-slate-400">No entry yet.</p>
                                            <p class="text-xs text-slate-400">Click on "Add an entry" to add your first entry.</p>
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
                                                                    class="text-slate-400 hover:text-slate-600 disabled:opacity-20 disabled:cursor-not-allowed p-0.5 transition-colors">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                                            </button>
                                                            <button type="button" @click.stop="moveDown(idx)" :disabled="idx === rows.length - 1"
                                                                    class="text-slate-400 hover:text-slate-600 disabled:opacity-20 disabled:cursor-not-allowed p-0.5 transition-colors">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                                            </button>
                                                        </div>
                                                        <span class="text-xs text-slate-400 font-mono flex-shrink-0 w-5" x-text="idx + 1"></span>
                                                        <span class="flex-1 text-sm text-slate-500 truncate" x-text="preview(row)"></span>
                                                        <button type="button" @click.stop="removeRow(idx)"
                                                                class="flex-shrink-0 p-1.5 rounded text-slate-400 hover:text-red-600 hover:bg-red-900/20 transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </button>
                                                        <span class="flex-shrink-0 text-slate-400 transition-transform duration-200" :class="isOpen(idx) ? 'rotate-180' : ''">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
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
                                                                    <input type="{{ $subField['type'] === 'email' ? 'email' : ($subField['type'] === 'url' ? 'url' : 'text') }}" x-model="row['{{ $subName }}']" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                                                                @elseif($subField['type'] === 'text')
                                                                    <textarea x-model="row['{{ $subName }}']" rows="4" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500 resize-y"></textarea>
                                                                @elseif(in_array($subField['type'], ['integer','biginteger','decimal','float']))
                                                                    <input type="number" x-model.number="row['{{ $subName }}']" step="{{ in_array($subField['type'], ['decimal','float']) ? 'any' : '1' }}" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                                                                @elseif($subField['type'] === 'boolean')
                                                                    <button type="button" @click="toggleBool(row, '{{ $subName }}')" class="flex items-center gap-3">
                                                                        <div class="relative w-12 h-6 rounded-full transition-colors duration-200" :class="getBool(row, '{{ $subName }}') ? 'bg-blue-600' : 'bg-slate-200'">
                                                                            <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200" :class="getBool(row, '{{ $subName }}') ? 'translate-x-6' : 'translate-x-0'"></div>
                                                                        </div>
                                                                        <span class="text-sm font-semibold" :class="getBool(row, '{{ $subName }}') ? 'text-blue-600' : 'text-slate-400'" x-text="getBool(row, '{{ $subName }}') ? 'True' : 'False'"></span>
                                                                    </button>
                                                                @elseif(in_array($subField['type'], ['date','datetime','time']))
                                                                    <input type="{{ $subField['type'] }}" x-model="row['{{ $subName }}']" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                                                                @elseif($subField['type'] === 'enumeration')
                                                                    @php $_sfOpts = $enumOpts($subField); $_sfMulti = !empty($subField['multiple']); @endphp
                                                                    @if($_sfMulti)
                                                                        <div x-data="{ _opts: {{ json_encode($_sfOpts) }} }">
                                                                            <div class="flex flex-wrap gap-1 mb-1.5" x-show="enumArr(row['{{ $subName }}']).length > 0">
                                                                                <template x-for="_ev in enumArr(row['{{ $subName }}'])" :key="'c'+_ev">
                                                                                    <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                                                                                        <span x-text="_ev"></span>
                                                                                        <button type="button" @click.stop="row['{{ $subName }}'] = enumToggle(row['{{ $subName }}'], _ev)" class="w-3.5 h-3.5 flex items-center justify-center rounded-full hover:bg-purple-200">
                                                                                            <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
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
                                                                                            <svg x-show="enumArr(row['{{ $subName }}']).includes(_eo)" class="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                                        </div>
                                                                                        <span class="text-sm text-slate-700" x-text="_eo"></span>
                                                                                    </div>
                                                                                </template>
                                                                            </div>
                                                                        </div>
                                                                    @else
                                                                        <select x-model="row['{{ $subName }}']" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                                                                            <option value="">— Select —</option>
                                                                            @foreach($_sfOpts as $eOpt)
                                                                                <option value="{{ $eOpt }}">{{ $eOpt }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    @endif
                                                                @elseif($subField['type'] === 'media')
                                                                    @php $subIsMultiple = !empty($subField['multiple']); @endphp
                                                                    @if($subIsMultiple)
                                                                    <div x-data="{ _mids: (() => { try { const v = row['{{ $subName }}']; return Array.isArray(v) ? v : (v ? JSON.parse(v) : []); } catch(e) { return []; } })(), _mshow: false }"
                                                                         x-init="$watch('_mids', v => row['{{ $subName }}'] = v)">
                                                                        <div x-show="_mids.length > 0" class="flex flex-wrap gap-2 mb-2">
                                                                            @foreach($mediaItems as $m)
                                                                                <div x-show="_mids.includes({{ $m->id }})" class="relative group">
                                                                                    @if($m->isImage())
                                                                                        <img src="{{ $m->url }}" class="h-16 w-16 object-cover rounded-lg">
                                                                                    @else
                                                                                        <div class="h-16 w-16 bg-slate-100 flex items-center justify-center text-slate-500 text-xs rounded-lg">{{ $m->ext }}</div>
                                                                                    @endif
                                                                                    <button type="button" @click="_mids = _mids.filter(id => id !== {{ $m->id }}); talos.markDirty()"
                                                                                            class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-xs hidden group-hover:flex items-center justify-center">✕</button>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                        <button type="button" @click="_mshow = true"
                                                                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-sm font-medium transition-colors">
                                                                            <span x-text="_mids.length ? 'Add / change media' : 'Select from library'"></span>
                                                                        </button>
                                                                        <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
                                                                             @keydown.escape.window="_mshow = false">
                                                                            <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                                                                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                                                                                    <h3 class="text-slate-800 font-semibold">Media Library</h3>
                                                                                    <button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900">
                                                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                                    </button>
                                                                                </div>
                                                                                <div class="flex-1 min-h-0 flex overflow-hidden"><div class="w-48 border-r border-slate-200 flex-shrink-0 overflow-y-auto py-2"><button type="button" @click="$store._mlib.folder = null" :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm transition-colors">All media</button>@foreach($mediaFolders as $_mfdr)<button type="button" data-folder="{{ $_mfdr }}" @click="$store._mlib.folder = $el.dataset.folder" :class="$el.dataset.folder === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><span>{{ basename($_mfdr) }}</span></button>@endforeach</div><div class="flex-1 min-h-0 overflow-y-auto"><div class="p-4 grid grid-cols-3 gap-4">
                                                                                    @foreach($mediaItems as $m)
                                                                                        <button type="button" @click="_mids.includes({{ $m->id }}) ? _mids = _mids.filter(id => id !== {{ $m->id }}) : _mids.push({{ $m->id }}); talos.markDirty()"
                                                                                                data-folder="{{ $m->folder ?? '' }}" x-show="$store._mlib.folder===null||$el.dataset.folder===$store._mlib.folder" class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500"
                                                                                                :class="_mids.includes({{ $m->id }}) ? 'border-blue-500' : 'border-transparent'">
                                                                                            @if($m->isImage())
                                                                                                <img src="{{ $m->url }}" class="w-full h-36 object-cover">
                                                                                            @else
                                                                                                <div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs">{{ $m->ext }}</div>
                                                                                            @endif
                                                                                            <p class="text-xs text-slate-500 p-1 truncate">{{ $m->name }}</p>
                                                                                        </button>
                                                                                    @endforeach
                                                                                </div>
                                                                                </div></div><div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                                                    <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-600 hover:underline">Upload more →</a>
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
                                                                    <div x-data="{ _mid: row['{{ $subName }}'] ? parseInt(row['{{ $subName }}']) : null, _mshow: false }"
                                                                         x-init="$watch('_mid', v => row['{{ $subName }}'] = v)">
                                                                        <div x-show="_mid" class="mb-2">
                                                                            @foreach($mediaItems as $m)
                                                                                <div x-show="_mid === {{ $m->id }}">
                                                                                    @if($m->isImage())
                                                                                        <img src="{{ $m->url }}" class="h-20 w-auto object-cover rounded-lg">
                                                                                    @else
                                                                                        <p class="text-sm text-slate-500">{{ $m->name }}</p>
                                                                                    @endif
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                        <button type="button" @click="_mshow = true"
                                                                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-100 text-slate-600 rounded-lg text-sm font-medium transition-colors">
                                                                            <span x-text="_mid ? 'Change media' : 'Select from library'"></span>
                                                                        </button>
                                                                        <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
                                                                             @keydown.escape.window="_mshow = false">
                                                                            <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                                                                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                                                                                    <h3 class="text-slate-800 font-semibold">Media Library</h3>
                                                                                    <button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900">
                                                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                                    </button>
                                                                                </div>
                                                                                <div class="flex-1 min-h-0 flex overflow-hidden"><div class="w-48 border-r border-slate-200 flex-shrink-0 overflow-y-auto py-2"><button type="button" @click="$store._mlib.folder = null" :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm transition-colors">All media</button>@foreach($mediaFolders as $_mfdr)<button type="button" data-folder="{{ $_mfdr }}" @click="$store._mlib.folder = $el.dataset.folder" :class="$el.dataset.folder === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><span>{{ basename($_mfdr) }}</span></button>@endforeach</div><div class="flex-1 min-h-0 overflow-y-auto"><div class="p-4 grid grid-cols-3 gap-4">
                                                                                    @foreach($mediaItems as $m)
                                                                                        <button type="button" @click="_mid = {{ $m->id }}; _mshow = false; talos.markDirty()"
                                                                                                data-folder="{{ $m->folder ?? '' }}" x-show="$store._mlib.folder===null||$el.dataset.folder===$store._mlib.folder" class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500"
                                                                                                :class="_mid === {{ $m->id }} ? 'border-blue-500' : 'border-transparent'">
                                                                                            @if($m->isImage())
                                                                                                <img src="{{ $m->url }}" class="w-full h-36 object-cover">
                                                                                            @else
                                                                                                <div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs">{{ $m->ext }}</div>
                                                                                            @endif
                                                                                            <p class="text-xs text-slate-500 p-1 truncate">{{ $m->name }}</p>
                                                                                        </button>
                                                                                    @endforeach
                                                                                </div>
                                                                                </div></div><div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                                                    <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-600 hover:underline">Upload more →</a>
                                                                                    <button @click="_mid = null; _mshow = false; talos.markDirty()" class="text-sm text-slate-400 hover:text-slate-600">Clear</button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    @endif
                                                                @elseif($subField['type'] === 'component')
                                                                    @php
                                                                        $nestedUid    = $subField['components'][0] ?? null;
                                                                        $nestedSchema = $nestedUid ? ($componentMap[$nestedUid] ?? null) : null;
                                                                        $nestedRep    = !empty($subField['repeatable']);
                                                                    @endphp
                                                                    @if($nestedSchema)
                                                                        @if($nestedRep)
                                                                            {{-- Nested repeatable component inside repeatable parent --}}
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
                                                                                                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="nestedOpen[ni] ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
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
                                                                                                            @php $_nnOpts = $enumOpts($nnField); $_nnMulti = !empty($nnField['multiple']); @endphp
                                                                                                            @if($_nnMulti)
                                                                                                                <div x-data="{ _opts: {{ json_encode($_nnOpts) }} }">
                                                                                                                    <div class="flex flex-wrap gap-1 mb-1" x-show="enumArr(nr['{{ $nnName }}']).length > 0">
                                                                                                                        <template x-for="_ev in enumArr(nr['{{ $nnName }}'])" :key="'c'+_ev">
                                                                                                                            <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                                                                                                                                <span x-text="_ev"></span>
                                                                                                                                <button type="button" @click.stop="nr['{{ $nnName }}'] = enumToggle(nr['{{ $nnName }}'], _ev)" class="w-3 h-3 flex items-center justify-center rounded-full hover:bg-purple-200"><svg class="w-1.5 h-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                                                                                                            </span>
                                                                                                                        </template>
                                                                                                                    </div>
                                                                                                                    <div class="border border-slate-200 rounded-lg overflow-hidden divide-y divide-slate-100">
                                                                                                                        <template x-for="_eo in _opts" :key="_eo">
                                                                                                                            <div class="flex items-center gap-2 px-3 py-2 cursor-pointer transition-colors select-none text-xs"
                                                                                                                                 :class="enumArr(nr['{{ $nnName }}']).includes(_eo) ? 'bg-purple-50' : 'bg-white hover:bg-slate-50'"
                                                                                                                                 @click="nr['{{ $nnName }}'] = enumToggle(nr['{{ $nnName }}'], _eo)">
                                                                                                                                <div class="w-3.5 h-3.5 rounded flex items-center justify-center flex-shrink-0 border transition-all" :class="enumArr(nr['{{ $nnName }}']).includes(_eo) ? 'bg-purple-600 border-purple-600' : 'border-slate-300 bg-white'">
                                                                                                                                    <svg x-show="enumArr(nr['{{ $nnName }}']).includes(_eo)" class="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                                                                                </div>
                                                                                                                                <span class="text-slate-700" x-text="_eo"></span>
                                                                                                                            </div>
                                                                                                                        </template>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            @else
                                                                                                                <select x-model="nr['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                                                                                    <option value="">— Select —</option>
                                                                                                                    @foreach($_nnOpts as $nnOpt)
                                                                                                                        <option value="{{ $nnOpt }}">{{ $nnOpt }}</option>
                                                                                                                    @endforeach
                                                                                                                </select>
                                                                                                            @endif
                                                                                                        @elseif($nnField['type'] === 'media')
                                                                                                            @php $nnMultiple = !empty($nnField['multiple']); @endphp
                                                                                                            @if($nnMultiple)
                                                                                                            <div x-data="{ _mids: (() => { try { const v = nr['{{ $nnName }}']; return Array.isArray(v) ? v : (v ? JSON.parse(v) : []); } catch(e) { return []; } })(), _mshow: false }"
                                                                                                                 x-init="$watch('_mids', v => nr['{{ $nnName }}'] = v)">
                                                                                                                <div x-show="_mids.length > 0" class="flex flex-wrap gap-1.5 mb-2">
                                                                                                                    @foreach($mediaItems as $m)
                                                                                                                        <div x-show="_mids.includes({{ $m->id }})" class="relative group">
                                                                                                                            @if($m->isImage())<img src="{{ $m->url }}" class="h-12 w-12 object-cover rounded">@else<div class="h-12 w-12 bg-slate-100 flex items-center justify-center text-slate-500 text-xs rounded">{{ $m->ext }}</div>@endif
                                                                                                                            <button type="button" @click="_mids = _mids.filter(id => id !== {{ $m->id }}); talos.markDirty()" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-xs hidden group-hover:flex items-center justify-center">✕</button>
                                                                                                                        </div>
                                                                                                                    @endforeach
                                                                                                                </div>
                                                                                                                <button type="button" @click="_mshow = true" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-xs font-medium transition-colors">
                                                                                                                    <span x-text="_mids.length ? 'Add / change' : 'Select from library'"></span>
                                                                                                                </button>
                                                                                                                <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6" @keydown.escape.window="_mshow = false">
                                                                                                                    <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                                                                                                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200"><h3 class="text-slate-800 font-semibold">Media Library</h3><button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
                                                                                                                        <div class="flex-1 min-h-0 flex overflow-hidden"><div class="w-48 border-r border-slate-200 flex-shrink-0 overflow-y-auto py-2"><button type="button" @click="$store._mlib.folder = null" :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm transition-colors">All media</button>@foreach($mediaFolders as $_mfdr)<button type="button" data-folder="{{ $_mfdr }}" @click="$store._mlib.folder = $el.dataset.folder" :class="$el.dataset.folder === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><span>{{ basename($_mfdr) }}</span></button>@endforeach</div><div class="flex-1 min-h-0 overflow-y-auto"><div class="p-4 grid grid-cols-3 gap-4">
                                                                                                                            @foreach($mediaItems as $m)
                                                                                                                                <button type="button" @click="_mids.includes({{ $m->id }}) ? _mids = _mids.filter(id => id !== {{ $m->id }}) : _mids.push({{ $m->id }}); talos.markDirty()" data-folder="{{ $m->folder ?? '' }}" x-show="$store._mlib.folder===null||$el.dataset.folder===$store._mlib.folder" class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500" :class="_mids.includes({{ $m->id }}) ? 'border-blue-500' : 'border-transparent'">
                                                                                                                                    @if($m->isImage())<img src="{{ $m->url }}" class="w-full h-36 object-cover">@else<div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs">{{ $m->ext }}</div>@endif
                                                                                                                                    <p class="text-xs text-slate-500 p-1 truncate">{{ $m->name }}</p>
                                                                                                                                </button>
                                                                                                                            @endforeach
                                                                                                                        </div>
                                                                                                                        </div></div><div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                                                                                            <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-600 hover:underline">Upload more →</a>
                                                                                                                            <div class="flex items-center gap-3"><span class="text-sm text-slate-400" x-text="_mids.length + ' selected'"></span><button type="button" @click="_mids = []; talos.markDirty()" class="text-sm text-slate-400 hover:text-slate-600">Clear</button><button type="button" @click="_mshow = false" class="text-sm text-blue-600 font-medium">Done</button></div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                            @else
                                                                                                            <div x-data="{ _mid: nr['{{ $nnName }}'] ? parseInt(nr['{{ $nnName }}']) : null, _mshow: false }"
                                                                                                                 x-init="$watch('_mid', v => nr['{{ $nnName }}'] = v)">
                                                                                                                <div x-show="_mid" class="mb-2">
                                                                                                                    @foreach($mediaItems as $m)
                                                                                                                        <div x-show="_mid === {{ $m->id }}">@if($m->isImage())<img src="{{ $m->url }}" class="h-14 w-auto object-cover rounded">@else<p class="text-xs text-slate-500">{{ $m->name }}</p>@endif</div>
                                                                                                                    @endforeach
                                                                                                                </div>
                                                                                                                <button type="button" @click="_mshow = true" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-xs font-medium transition-colors">
                                                                                                                    <span x-text="_mid ? 'Change' : 'Select from library'"></span>
                                                                                                                </button>
                                                                                                                <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6" @keydown.escape.window="_mshow = false">
                                                                                                                    <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                                                                                                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200"><h3 class="text-slate-800 font-semibold">Media Library</h3><button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
                                                                                                                        <div class="flex-1 min-h-0 flex overflow-hidden"><div class="w-48 border-r border-slate-200 flex-shrink-0 overflow-y-auto py-2"><button type="button" @click="$store._mlib.folder = null" :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm transition-colors">All media</button>@foreach($mediaFolders as $_mfdr)<button type="button" data-folder="{{ $_mfdr }}" @click="$store._mlib.folder = $el.dataset.folder" :class="$el.dataset.folder === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><span>{{ basename($_mfdr) }}</span></button>@endforeach</div><div class="flex-1 min-h-0 overflow-y-auto"><div class="p-4 grid grid-cols-3 gap-4">
                                                                                                                            @foreach($mediaItems as $m)
                                                                                                                                <button type="button" @click="_mid = {{ $m->id }}; _mshow = false; talos.markDirty()" data-folder="{{ $m->folder ?? '' }}" x-show="$store._mlib.folder===null||$el.dataset.folder===$store._mlib.folder" class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500" :class="_mid === {{ $m->id }} ? 'border-blue-500' : 'border-transparent'">
                                                                                                                                    @if($m->isImage())<img src="{{ $m->url }}" class="w-full h-36 object-cover">@else<div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs">{{ $m->ext }}</div>@endif
                                                                                                                                    <p class="text-xs text-slate-500 p-1 truncate">{{ $m->name }}</p>
                                                                                                                                </button>
                                                                                                                            @endforeach
                                                                                                                        </div>
                                                                                                                        </div></div><div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                                                                                            <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-600 hover:underline">Upload more →</a>
                                                                                                                            <button type="button" @click="_mid = null; _mshow = false; talos.markDirty()" class="text-sm text-slate-400 hover:text-slate-600">Clear</button>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                            @endif
                                                                                                        @else
                                                                                                            <input type="text" x-model="nr['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                                                                        @endif
                                                                                                    </div>
                                                                                                @endforeach
                                                                                            </div>
                                                                                        </div>
                                                                                    </template>
                                                                                </div>
                                                                                <button type="button" @click="nestedRows.push({}); nestedOpen[nestedRows.length-1] = true; talos.markDirty()"
                                                                                        class="mt-2 w-full py-2 flex items-center justify-center gap-1 rounded-lg border border-dashed border-slate-300 hover:border-blue-500 text-slate-400 hover:text-blue-600 text-xs font-medium transition-all">
                                                                                    + Add entry
                                                                                </button>
                                                                            </div>
                                                                        @else
                                                                            {{-- Nested single component inside repeatable parent --}}
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
                                                                                            @php $_nnOpts3 = $enumOpts($nnField); $_nnMulti3 = !empty($nnField['multiple']); @endphp
                                                                                            @if($_nnMulti3)
                                                                                                <div x-data="{ _opts: {{ json_encode($_nnOpts3) }} }">
                                                                                                    <div class="flex flex-wrap gap-1 mb-1" x-show="enumArr((row['{{ $subName }}'] ??= {})['{{ $nnName }}']).length > 0">
                                                                                                        <template x-for="_ev in enumArr((row['{{ $subName }}'] ?? {})['{{ $nnName }}'])" :key="'c'+_ev">
                                                                                                            <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                                                                                                                <span x-text="_ev"></span>
                                                                                                                <button type="button" @click.stop="const _o=(row['{{ $subName }}']??={}); _o['{{ $nnName }}']=enumToggle(_o['{{ $nnName }}'],_ev)" class="w-3 h-3 flex items-center justify-center rounded-full hover:bg-purple-200"><svg class="w-1.5 h-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                                                                                            </span>
                                                                                                        </template>
                                                                                                    </div>
                                                                                                    <div class="border border-slate-200 rounded-lg overflow-hidden divide-y divide-slate-100">
                                                                                                        <template x-for="_eo in _opts" :key="_eo">
                                                                                                            <div class="flex items-center gap-2 px-3 py-2 cursor-pointer transition-colors select-none text-xs"
                                                                                                                 :class="enumArr((row['{{ $subName }}'] ?? {})['{{ $nnName }}']).includes(_eo) ? 'bg-purple-50' : 'bg-white hover:bg-slate-50'"
                                                                                                                 @click="const _o=(row['{{ $subName }}']??={}); _o['{{ $nnName }}']=enumToggle(_o['{{ $nnName }}'],_eo)">
                                                                                                                <div class="w-3.5 h-3.5 rounded flex items-center justify-center flex-shrink-0 border transition-all" :class="enumArr((row['{{ $subName }}'] ?? {})['{{ $nnName }}']).includes(_eo) ? 'bg-purple-600 border-purple-600' : 'border-slate-300 bg-white'">
                                                                                                                    <svg x-show="enumArr((row['{{ $subName }}'] ?? {})['{{ $nnName }}']).includes(_eo)" class="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                                                                </div>
                                                                                                                <span class="text-slate-700" x-text="_eo"></span>
                                                                                                            </div>
                                                                                                        </template>
                                                                                                    </div>
                                                                                                </div>
                                                                                            @else
                                                                                                <select x-model="(row['{{ $subName }}'] ??= {})['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                                                                    <option value="">— Select —</option>
                                                                                                    @foreach($_nnOpts3 as $nnOpt)
                                                                                                        <option value="{{ $nnOpt }}">{{ $nnOpt }}</option>
                                                                                                    @endforeach
                                                                                                </select>
                                                                                            @endif
                                                                                        @elseif($nnField['type'] === 'media')
                                                                                            @php $nnMultiple = !empty($nnField['multiple']); @endphp
                                                                                            @if($nnMultiple)
                                                                                            <div x-data="{ _mids: (() => { try { const v = (row['{{ $subName }}'] ?? {})['{{ $nnName }}']; return Array.isArray(v) ? v : (v ? JSON.parse(v) : []); } catch(e) { return []; } })(), _mshow: false }"
                                                                                                 x-init="$watch('_mids', v => (row['{{ $subName }}'] ??= {})['{{ $nnName }}'] = v)">
                                                                                                <div x-show="_mids.length > 0" class="flex flex-wrap gap-1.5 mb-2">
                                                                                                    @foreach($mediaItems as $m)
                                                                                                        <div x-show="_mids.includes({{ $m->id }})" class="relative group">
                                                                                                            @if($m->isImage())<img src="{{ $m->url }}" class="h-12 w-12 object-cover rounded">@else<div class="h-12 w-12 bg-slate-100 flex items-center justify-center text-slate-500 text-xs rounded">{{ $m->ext }}</div>@endif
                                                                                                            <button type="button" @click="_mids = _mids.filter(id => id !== {{ $m->id }}); talos.markDirty()" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-xs hidden group-hover:flex items-center justify-center">✕</button>
                                                                                                        </div>
                                                                                                    @endforeach
                                                                                                </div>
                                                                                                <button type="button" @click="_mshow = true" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-xs font-medium transition-colors">
                                                                                                    <span x-text="_mids.length ? 'Add / change' : 'Select from library'"></span>
                                                                                                </button>
                                                                                                <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6" @keydown.escape.window="_mshow = false">
                                                                                                    <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                                                                                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200"><h3 class="text-slate-800 font-semibold">Media Library</h3><button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
                                                                                                        <div class="flex-1 min-h-0 flex overflow-hidden"><div class="w-48 border-r border-slate-200 flex-shrink-0 overflow-y-auto py-2"><button type="button" @click="$store._mlib.folder = null" :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm transition-colors">All media</button>@foreach($mediaFolders as $_mfdr)<button type="button" data-folder="{{ $_mfdr }}" @click="$store._mlib.folder = $el.dataset.folder" :class="$el.dataset.folder === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><span>{{ basename($_mfdr) }}</span></button>@endforeach</div><div class="flex-1 min-h-0 overflow-y-auto"><div class="p-4 grid grid-cols-3 gap-4">
                                                                                                            @foreach($mediaItems as $m)
                                                                                                                <button type="button" @click="_mids.includes({{ $m->id }}) ? _mids = _mids.filter(id => id !== {{ $m->id }}) : _mids.push({{ $m->id }}); talos.markDirty()" data-folder="{{ $m->folder ?? '' }}" x-show="$store._mlib.folder===null||$el.dataset.folder===$store._mlib.folder" class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500" :class="_mids.includes({{ $m->id }}) ? 'border-blue-500' : 'border-transparent'">
                                                                                                                    @if($m->isImage())<img src="{{ $m->url }}" class="w-full h-36 object-cover">@else<div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs">{{ $m->ext }}</div>@endif
                                                                                                                    <p class="text-xs text-slate-500 p-1 truncate">{{ $m->name }}</p>
                                                                                                                </button>
                                                                                                            @endforeach
                                                                                                        </div>
                                                                                                        </div></div><div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                                                                            <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-600 hover:underline">Upload more →</a>
                                                                                                            <div class="flex items-center gap-3"><span class="text-sm text-slate-400" x-text="_mids.length + ' selected'"></span><button type="button" @click="_mids = []; talos.markDirty()" class="text-sm text-slate-400 hover:text-slate-600">Clear</button><button type="button" @click="_mshow = false" class="text-sm text-blue-600 font-medium">Done</button></div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                            @else
                                                                                            <div x-data="{ _mid: (row['{{ $subName }}'] ?? {})['{{ $nnName }}'] ? parseInt((row['{{ $subName }}'] ?? {})['{{ $nnName }}']) : null, _mshow: false }"
                                                                                                 x-init="$watch('_mid', v => (row['{{ $subName }}'] ??= {})['{{ $nnName }}'] = v)">
                                                                                                <div x-show="_mid" class="mb-2">
                                                                                                    @foreach($mediaItems as $m)
                                                                                                        <div x-show="_mid === {{ $m->id }}">@if($m->isImage())<img src="{{ $m->url }}" class="h-14 w-auto object-cover rounded">@else<p class="text-xs text-slate-500">{{ $m->name }}</p>@endif</div>
                                                                                                    @endforeach
                                                                                                </div>
                                                                                                <button type="button" @click="_mshow = true" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-xs font-medium transition-colors">
                                                                                                    <span x-text="_mid ? 'Change' : 'Select from library'"></span>
                                                                                                </button>
                                                                                                <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6" @keydown.escape.window="_mshow = false">
                                                                                                    <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                                                                                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200"><h3 class="text-slate-800 font-semibold">Media Library</h3><button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
                                                                                                        <div class="flex-1 min-h-0 flex overflow-hidden"><div class="w-48 border-r border-slate-200 flex-shrink-0 overflow-y-auto py-2"><button type="button" @click="$store._mlib.folder = null" :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm transition-colors">All media</button>@foreach($mediaFolders as $_mfdr)<button type="button" data-folder="{{ $_mfdr }}" @click="$store._mlib.folder = $el.dataset.folder" :class="$el.dataset.folder === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><span>{{ basename($_mfdr) }}</span></button>@endforeach</div><div class="flex-1 min-h-0 overflow-y-auto"><div class="p-4 grid grid-cols-3 gap-4">
                                                                                                            @foreach($mediaItems as $m)
                                                                                                                <button type="button" @click="_mid = {{ $m->id }}; _mshow = false; talos.markDirty()" data-folder="{{ $m->folder ?? '' }}" x-show="$store._mlib.folder===null||$el.dataset.folder===$store._mlib.folder" class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500" :class="_mid === {{ $m->id }} ? 'border-blue-500' : 'border-transparent'">
                                                                                                                    @if($m->isImage())<img src="{{ $m->url }}" class="w-full h-36 object-cover">@else<div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs">{{ $m->ext }}</div>@endif
                                                                                                                    <p class="text-xs text-slate-500 p-1 truncate">{{ $m->name }}</p>
                                                                                                                </button>
                                                                                                            @endforeach
                                                                                                        </div>
                                                                                                        </div></div><div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                                                                            <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-600 hover:underline">Upload more →</a>
                                                                                                            <button type="button" @click="_mid = null; _mshow = false; talos.markDirty()" class="text-sm text-slate-400 hover:text-slate-600">Clear</button>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                            @endif
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
                                                                    <input type="text" x-model="row['{{ $subName }}']" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
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
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Add an entry
                                    </button>
                                </div>

                            @elseif(!$isRepeatable && $compSchema)
                                {{-- ── Single component — inline sub-fields ── --}}
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
                                                    <input type="{{ $subField['type'] === 'email' ? 'email' : ($subField['type'] === 'url' ? 'url' : 'text') }}" x-model="d.{{ $subName }}" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                                                @elseif($subField['type'] === 'text')
                                                    <textarea x-model="d.{{ $subName }}" rows="4" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500 resize-y"></textarea>
                                                @elseif(in_array($subField['type'], ['integer','biginteger','decimal','float']))
                                                    <input type="number" x-model.number="d.{{ $subName }}" step="{{ in_array($subField['type'], ['decimal','float']) ? 'any' : '1' }}" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                                                @elseif($subField['type'] === 'boolean')
                                                    <div x-data="{ on: d.{{ $subName }} ?? {{ ($subField['default'] ?? false) ? 'true' : 'false' }} }" x-init="$watch('on', v => d.{{ $subName }} = v)">
                                                        <button type="button" @click="on = !on" class="flex items-center gap-3">
                                                            <div class="relative w-12 h-6 rounded-full transition-colors duration-200" :class="on ? 'bg-blue-600' : 'bg-slate-200'">
                                                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200" :class="on ? 'translate-x-6' : 'translate-x-0'"></div>
                                                            </div>
                                                            <span class="text-sm font-semibold" :class="on ? 'text-blue-600' : 'text-slate-400'" x-text="on ? 'True' : 'False'"></span>
                                                        </button>
                                                    </div>
                                                @elseif(in_array($subField['type'], ['date','datetime','time']))
                                                    <input type="{{ $subField['type'] }}" x-model="d.{{ $subName }}" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                                                @elseif($subField['type'] === 'enumeration')
                                                    @php $_sfOpts5 = $enumOpts($subField); $_sfMulti5 = !empty($subField['multiple']); @endphp
                                                    @if($_sfMulti5)
                                                        <div x-data="{ _opts: {{ json_encode($_sfOpts5) }} }">
                                                            <div class="flex flex-wrap gap-1 mb-1" x-show="enumArr(d.{{ $subName }}).length > 0">
                                                                <template x-for="_ev in enumArr(d.{{ $subName }})" :key="'c5'+_ev">
                                                                    <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                                                                        <span x-text="_ev"></span>
                                                                        <button type="button" @click.stop="d.{{ $subName }} = enumToggle(d.{{ $subName }}, _ev)" class="w-3 h-3 flex items-center justify-center rounded-full hover:bg-purple-200"><svg class="w-1.5 h-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                                                    </span>
                                                                </template>
                                                            </div>
                                                            <div class="border border-slate-200 rounded-lg overflow-hidden divide-y divide-slate-100">
                                                                <template x-for="_eo in _opts" :key="_eo">
                                                                    <div class="flex items-center gap-2 px-3 py-2 cursor-pointer transition-colors select-none text-sm"
                                                                         :class="enumArr(d.{{ $subName }}).includes(_eo) ? 'bg-purple-50' : 'bg-white hover:bg-slate-50'"
                                                                         @click="d.{{ $subName }} = enumToggle(d.{{ $subName }}, _eo)">
                                                                        <div class="w-3.5 h-3.5 rounded flex items-center justify-center flex-shrink-0 border transition-all" :class="enumArr(d.{{ $subName }}).includes(_eo) ? 'bg-purple-600 border-purple-600' : 'border-slate-300 bg-white'">
                                                                            <svg x-show="enumArr(d.{{ $subName }}).includes(_eo)" class="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                        </div>
                                                                        <span class="text-slate-700" x-text="_eo"></span>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <select x-model="d.{{ $subName }}" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                                                            <option value="">— Select —</option>
                                                            @foreach($_sfOpts5 as $eOpt)
                                                                @if($eOpt)<option value="{{ $eOpt }}">{{ $eOpt }}</option>@endif
                                                            @endforeach
                                                        </select>
                                                    @endif
                                                @elseif($subField['type'] === 'media')
                                                    @php $subIsMultiple = !empty($subField['multiple']); @endphp
                                                    @if($subIsMultiple)
                                                    <div x-data="{ _mids: (() => { try { const v = d.{{ $subName }}; return Array.isArray(v) ? v : (v ? JSON.parse(v) : []); } catch(e) { return []; } })(), _mshow: false }"
                                                         x-init="$watch('_mids', v => d.{{ $subName }} = v)">
                                                        <div x-show="_mids.length > 0" class="flex flex-wrap gap-2 mb-2">
                                                            @foreach($mediaItems as $m)
                                                                <div x-show="_mids.includes({{ $m->id }})" class="relative group">
                                                                    @if($m->isImage())
                                                                        <img src="{{ $m->url }}" class="h-16 w-16 object-cover rounded-lg">
                                                                    @else
                                                                        <div class="h-16 w-16 bg-slate-100 flex items-center justify-center text-slate-500 text-xs rounded-lg">{{ $m->ext }}</div>
                                                                    @endif
                                                                    <button type="button" @click="_mids = _mids.filter(id => id !== {{ $m->id }}); talos.markDirty()"
                                                                            class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-xs hidden group-hover:flex items-center justify-center">✕</button>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <button type="button" @click="_mshow = true"
                                                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-sm font-medium transition-colors">
                                                            <span x-text="_mids.length ? 'Add / change media' : 'Select from library'"></span>
                                                        </button>
                                                        <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
                                                             @keydown.escape.window="_mshow = false">
                                                            <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                                                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                                                                    <h3 class="text-slate-800 font-semibold">Media Library</h3>
                                                                    <button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900">
                                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                    </button>
                                                                </div>
                                                                <div class="flex-1 min-h-0 flex overflow-hidden"><div class="w-48 border-r border-slate-200 flex-shrink-0 overflow-y-auto py-2"><button type="button" @click="$store._mlib.folder = null" :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm transition-colors">All media</button>@foreach($mediaFolders as $_mfdr)<button type="button" data-folder="{{ $_mfdr }}" @click="$store._mlib.folder = $el.dataset.folder" :class="$el.dataset.folder === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><span>{{ basename($_mfdr) }}</span></button>@endforeach</div><div class="flex-1 min-h-0 overflow-y-auto"><div class="p-4 grid grid-cols-3 gap-4">
                                                                    @foreach($mediaItems as $m)
                                                                        <button type="button" @click="_mids.includes({{ $m->id }}) ? _mids = _mids.filter(id => id !== {{ $m->id }}) : _mids.push({{ $m->id }}); talos.markDirty()"
                                                                                data-folder="{{ $m->folder ?? '' }}" x-show="$store._mlib.folder===null||$el.dataset.folder===$store._mlib.folder" class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500"
                                                                                :class="_mids.includes({{ $m->id }}) ? 'border-blue-500' : 'border-transparent'">
                                                                            @if($m->isImage())
                                                                                <img src="{{ $m->url }}" class="w-full h-36 object-cover">
                                                                            @else
                                                                                <div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs">{{ $m->ext }}</div>
                                                                            @endif
                                                                            <p class="text-xs text-slate-500 p-1 truncate">{{ $m->name }}</p>
                                                                        </button>
                                                                    @endforeach
                                                                </div>
                                                                </div></div><div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                                    <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-600 hover:underline">Upload more →</a>
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
                                                            @foreach($mediaItems as $m)
                                                                <div x-show="_mid === {{ $m->id }}">
                                                                    @if($m->isImage())
                                                                        <img src="{{ $m->url }}" class="h-20 w-auto object-cover rounded-lg">
                                                                    @else
                                                                        <p class="text-sm text-slate-500">{{ $m->name }}</p>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <button type="button" @click="_mshow = true"
                                                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-100 text-slate-600 rounded-lg text-sm font-medium transition-colors">
                                                            <span x-text="_mid ? 'Change media' : 'Select from library'"></span>
                                                        </button>
                                                        <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
                                                             @keydown.escape.window="_mshow = false">
                                                            <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                                                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                                                                    <h3 class="text-slate-800 font-semibold">Media Library</h3>
                                                                    <button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900">
                                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                    </button>
                                                                </div>
                                                                <div class="flex-1 min-h-0 flex overflow-hidden"><div class="w-48 border-r border-slate-200 flex-shrink-0 overflow-y-auto py-2"><button type="button" @click="$store._mlib.folder = null" :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm transition-colors">All media</button>@foreach($mediaFolders as $_mfdr)<button type="button" data-folder="{{ $_mfdr }}" @click="$store._mlib.folder = $el.dataset.folder" :class="$el.dataset.folder === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><span>{{ basename($_mfdr) }}</span></button>@endforeach</div><div class="flex-1 min-h-0 overflow-y-auto"><div class="p-4 grid grid-cols-3 gap-4">
                                                                    @foreach($mediaItems as $m)
                                                                        <button type="button" @click="_mid = {{ $m->id }}; _mshow = false; talos.markDirty()"
                                                                                data-folder="{{ $m->folder ?? '' }}" x-show="$store._mlib.folder===null||$el.dataset.folder===$store._mlib.folder" class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500"
                                                                                :class="_mid === {{ $m->id }} ? 'border-blue-500' : 'border-transparent'">
                                                                            @if($m->isImage())
                                                                                <img src="{{ $m->url }}" class="w-full h-36 object-cover">
                                                                            @else
                                                                                <div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs">{{ $m->ext }}</div>
                                                                            @endif
                                                                            <p class="text-xs text-slate-500 p-1 truncate">{{ $m->name }}</p>
                                                                        </button>
                                                                    @endforeach
                                                                </div>
                                                                </div></div><div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                                    <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-600 hover:underline">Upload more →</a>
                                                                    <button @click="_mid = null; _mshow = false; talos.markDirty()" class="text-sm text-slate-400 hover:text-slate-600">Clear</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endif
                                                @elseif($subField['type'] === 'component')
                                                    @php
                                                        $nestedUid    = $subField['components'][0] ?? null;
                                                        $nestedSchema = $nestedUid ? ($componentMap[$nestedUid] ?? null) : null;
                                                        $nestedRep    = !empty($subField['repeatable']);
                                                    @endphp
                                                    @if($nestedSchema)
                                                        @if($nestedRep)
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
                                                                                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="nestedOpen[ni] ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
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
                                                                                            @php $_nnOpts2 = $enumOpts($nnField); $_nnMulti2 = !empty($nnField['multiple']); @endphp
                                                                                            @if($_nnMulti2)
                                                                                                <div x-data="{ _opts: {{ json_encode($_nnOpts2) }} }">
                                                                                                    <div class="flex flex-wrap gap-1 mb-1" x-show="enumArr(nr['{{ $nnName }}']).length > 0">
                                                                                                        <template x-for="_ev in enumArr(nr['{{ $nnName }}'])" :key="'c'+_ev">
                                                                                                            <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                                                                                                                <span x-text="_ev"></span>
                                                                                                                <button type="button" @click.stop="nr['{{ $nnName }}'] = enumToggle(nr['{{ $nnName }}'], _ev)" class="w-3 h-3 flex items-center justify-center rounded-full hover:bg-purple-200"><svg class="w-1.5 h-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                                                                                            </span>
                                                                                                        </template>
                                                                                                    </div>
                                                                                                    <div class="border border-slate-200 rounded-lg overflow-hidden divide-y divide-slate-100">
                                                                                                        <template x-for="_eo in _opts" :key="_eo">
                                                                                                            <div class="flex items-center gap-2 px-3 py-2 cursor-pointer transition-colors select-none text-xs"
                                                                                                                 :class="enumArr(nr['{{ $nnName }}']).includes(_eo) ? 'bg-purple-50' : 'bg-white hover:bg-slate-50'"
                                                                                                                 @click="nr['{{ $nnName }}'] = enumToggle(nr['{{ $nnName }}'], _eo)">
                                                                                                                <div class="w-3.5 h-3.5 rounded flex items-center justify-center flex-shrink-0 border transition-all" :class="enumArr(nr['{{ $nnName }}']).includes(_eo) ? 'bg-purple-600 border-purple-600' : 'border-slate-300 bg-white'">
                                                                                                                    <svg x-show="enumArr(nr['{{ $nnName }}']).includes(_eo)" class="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                                                                </div>
                                                                                                                <span class="text-slate-700" x-text="_eo"></span>
                                                                                                            </div>
                                                                                                        </template>
                                                                                                    </div>
                                                                                                </div>
                                                                                            @else
                                                                                                <select x-model="nr['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                                                                    <option value="">— Select —</option>
                                                                                                    @foreach($_nnOpts2 as $nnOpt)
                                                                                                        <option value="{{ $nnOpt }}">{{ $nnOpt }}</option>
                                                                                                    @endforeach
                                                                                                </select>
                                                                                            @endif
                                                                                        @elseif($nnField['type'] === 'media')
                                                                                            @php $nnMultiple = !empty($nnField['multiple']); @endphp
                                                                                            @if($nnMultiple)
                                                                                            <div x-data="{ _mids: (() => { try { const v = nr['{{ $nnName }}']; return Array.isArray(v) ? v : (v ? JSON.parse(v) : []); } catch(e) { return []; } })(), _mshow: false }"
                                                                                                 x-init="$watch('_mids', v => nr['{{ $nnName }}'] = v)">
                                                                                                <div x-show="_mids.length > 0" class="flex flex-wrap gap-1.5 mb-2">
                                                                                                    @foreach($mediaItems as $m)
                                                                                                        <div x-show="_mids.includes({{ $m->id }})" class="relative group">
                                                                                                            @if($m->isImage())<img src="{{ $m->url }}" class="h-12 w-12 object-cover rounded">@else<div class="h-12 w-12 bg-slate-100 flex items-center justify-center text-slate-500 text-xs rounded">{{ $m->ext }}</div>@endif
                                                                                                            <button type="button" @click="_mids = _mids.filter(id => id !== {{ $m->id }}); talos.markDirty()" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-xs hidden group-hover:flex items-center justify-center">✕</button>
                                                                                                        </div>
                                                                                                    @endforeach
                                                                                                </div>
                                                                                                <button type="button" @click="_mshow = true" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-xs font-medium transition-colors">
                                                                                                    <span x-text="_mids.length ? 'Add / change' : 'Select from library'"></span>
                                                                                                </button>
                                                                                                <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6" @keydown.escape.window="_mshow = false">
                                                                                                    <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                                                                                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200"><h3 class="text-slate-800 font-semibold">Media Library</h3><button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
                                                                                                        <div class="flex-1 min-h-0 flex overflow-hidden"><div class="w-48 border-r border-slate-200 flex-shrink-0 overflow-y-auto py-2"><button type="button" @click="$store._mlib.folder = null" :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm transition-colors">All media</button>@foreach($mediaFolders as $_mfdr)<button type="button" data-folder="{{ $_mfdr }}" @click="$store._mlib.folder = $el.dataset.folder" :class="$el.dataset.folder === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><span>{{ basename($_mfdr) }}</span></button>@endforeach</div><div class="flex-1 min-h-0 overflow-y-auto"><div class="p-4 grid grid-cols-3 gap-4">
                                                                                                            @foreach($mediaItems as $m)
                                                                                                                <button type="button" @click="_mids.includes({{ $m->id }}) ? _mids = _mids.filter(id => id !== {{ $m->id }}) : _mids.push({{ $m->id }}); talos.markDirty()" data-folder="{{ $m->folder ?? '' }}" x-show="$store._mlib.folder===null||$el.dataset.folder===$store._mlib.folder" class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500" :class="_mids.includes({{ $m->id }}) ? 'border-blue-500' : 'border-transparent'">
                                                                                                                    @if($m->isImage())<img src="{{ $m->url }}" class="w-full h-36 object-cover">@else<div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs">{{ $m->ext }}</div>@endif
                                                                                                                    <p class="text-xs text-slate-500 p-1 truncate">{{ $m->name }}</p>
                                                                                                                </button>
                                                                                                            @endforeach
                                                                                                        </div>
                                                                                                        </div></div><div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                                                                            <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-600 hover:underline">Upload more →</a>
                                                                                                            <div class="flex items-center gap-3"><span class="text-sm text-slate-400" x-text="_mids.length + ' selected'"></span><button type="button" @click="_mids = []; talos.markDirty()" class="text-sm text-slate-400 hover:text-slate-600">Clear</button><button type="button" @click="_mshow = false" class="text-sm text-blue-600 font-medium">Done</button></div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                            @else
                                                                                            <div x-data="{ _mid: nr['{{ $nnName }}'] ? parseInt(nr['{{ $nnName }}']) : null, _mshow: false }"
                                                                                                 x-init="$watch('_mid', v => nr['{{ $nnName }}'] = v)">
                                                                                                <div x-show="_mid" class="mb-2">
                                                                                                    @foreach($mediaItems as $m)
                                                                                                        <div x-show="_mid === {{ $m->id }}">@if($m->isImage())<img src="{{ $m->url }}" class="h-14 w-auto object-cover rounded">@else<p class="text-xs text-slate-500">{{ $m->name }}</p>@endif</div>
                                                                                                    @endforeach
                                                                                                </div>
                                                                                                <button type="button" @click="_mshow = true" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-xs font-medium transition-colors">
                                                                                                    <span x-text="_mid ? 'Change' : 'Select from library'"></span>
                                                                                                </button>
                                                                                                <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6" @keydown.escape.window="_mshow = false">
                                                                                                    <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                                                                                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200"><h3 class="text-slate-800 font-semibold">Media Library</h3><button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
                                                                                                        <div class="flex-1 min-h-0 flex overflow-hidden"><div class="w-48 border-r border-slate-200 flex-shrink-0 overflow-y-auto py-2"><button type="button" @click="$store._mlib.folder = null" :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm transition-colors">All media</button>@foreach($mediaFolders as $_mfdr)<button type="button" data-folder="{{ $_mfdr }}" @click="$store._mlib.folder = $el.dataset.folder" :class="$el.dataset.folder === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><span>{{ basename($_mfdr) }}</span></button>@endforeach</div><div class="flex-1 min-h-0 overflow-y-auto"><div class="p-4 grid grid-cols-3 gap-4">
                                                                                                            @foreach($mediaItems as $m)
                                                                                                                <button type="button" @click="_mid = {{ $m->id }}; _mshow = false; talos.markDirty()" data-folder="{{ $m->folder ?? '' }}" x-show="$store._mlib.folder===null||$el.dataset.folder===$store._mlib.folder" class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500" :class="_mid === {{ $m->id }} ? 'border-blue-500' : 'border-transparent'">
                                                                                                                    @if($m->isImage())<img src="{{ $m->url }}" class="w-full h-36 object-cover">@else<div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs">{{ $m->ext }}</div>@endif
                                                                                                                    <p class="text-xs text-slate-500 p-1 truncate">{{ $m->name }}</p>
                                                                                                                </button>
                                                                                                            @endforeach
                                                                                                        </div>
                                                                                                        </div></div><div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                                                                            <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-600 hover:underline">Upload more →</a>
                                                                                                            <button type="button" @click="_mid = null; _mshow = false; talos.markDirty()" class="text-sm text-slate-400 hover:text-slate-600">Clear</button>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                            @endif
                                                                                        @else
                                                                                            <input type="text" x-model="nr['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                                                        @endif
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                                <button type="button" @click="nestedRows.push({}); nestedOpen[nestedRows.length-1] = true; talos.markDirty()"
                                                                        class="mt-2 w-full py-2 flex items-center justify-center gap-1 rounded-lg border border-dashed border-slate-300 hover:border-blue-500 text-slate-400 hover:text-blue-600 text-xs font-medium transition-all">
                                                                    + Add entry
                                                                </button>
                                                            </div>
                                                        @else
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
                                                                            @php $_nnOpts7 = $enumOpts($nnField); $_nnMulti7 = !empty($nnField['multiple']); @endphp
                                                                            @if($_nnMulti7)
                                                                                <div x-data="{ _opts: {{ json_encode($_nnOpts7) }} }">
                                                                                    <div class="flex flex-wrap gap-1 mb-1" x-show="enumArr((d.{{ $subName }} ?? {})['{{ $nnName }}']).length > 0">
                                                                                        <template x-for="_ev in enumArr((d.{{ $subName }} ?? {})['{{ $nnName }}'])" :key="'c7'+_ev">
                                                                                            <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                                                                                                <span x-text="_ev"></span>
                                                                                                <button type="button" @click.stop="const _o7=(d.{{ $subName }}??={}); _o7['{{ $nnName }}']=enumToggle(_o7['{{ $nnName }}'],_ev)" class="w-3 h-3 flex items-center justify-center rounded-full hover:bg-purple-200"><svg class="w-1.5 h-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                                                                            </span>
                                                                                        </template>
                                                                                    </div>
                                                                                    <div class="border border-slate-200 rounded-lg overflow-hidden divide-y divide-slate-100">
                                                                                        <template x-for="_eo in _opts" :key="_eo">
                                                                                            <div class="flex items-center gap-2 px-3 py-2 cursor-pointer transition-colors select-none text-xs"
                                                                                                 :class="enumArr((d.{{ $subName }} ?? {})['{{ $nnName }}']).includes(_eo) ? 'bg-purple-50' : 'bg-white hover:bg-slate-50'"
                                                                                                 @click="const _o7=(d.{{ $subName }}??={}); _o7['{{ $nnName }}']=enumToggle(_o7['{{ $nnName }}'],_eo)">
                                                                                                <div class="w-3.5 h-3.5 rounded flex items-center justify-center flex-shrink-0 border transition-all" :class="enumArr((d.{{ $subName }} ?? {})['{{ $nnName }}']).includes(_eo) ? 'bg-purple-600 border-purple-600' : 'border-slate-300 bg-white'">
                                                                                                    <svg x-show="enumArr((d.{{ $subName }} ?? {})['{{ $nnName }}']).includes(_eo)" class="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                                                </div>
                                                                                                <span class="text-slate-700" x-text="_eo"></span>
                                                                                            </div>
                                                                                        </template>
                                                                                    </div>
                                                                                </div>
                                                                            @else
                                                                                <select x-model="(d.{{ $subName }} ??= {})['{{ $nnName }}']" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                                                    <option value="">— Select —</option>
                                                                                    @foreach($_nnOpts7 as $nnOpt)
                                                                                        @if($nnOpt)<option value="{{ $nnOpt }}">{{ $nnOpt }}</option>@endif
                                                                                    @endforeach
                                                                                </select>
                                                                            @endif
                                                                        @elseif($nnField['type'] === 'media')
                                                                            @php $nnMultiple = !empty($nnField['multiple']); @endphp
                                                                            @if($nnMultiple)
                                                                            <div x-data="{ _mids: (() => { try { const v = (d.{{ $subName }} ?? {})['{{ $nnName }}']; return Array.isArray(v) ? v : (v ? JSON.parse(v) : []); } catch(e) { return []; } })(), _mshow: false }"
                                                                                 x-init="$watch('_mids', v => (d.{{ $subName }} ??= {})['{{ $nnName }}'] = v)">
                                                                                <div x-show="_mids.length > 0" class="flex flex-wrap gap-1.5 mb-2">
                                                                                    @foreach($mediaItems as $m)
                                                                                        <div x-show="_mids.includes({{ $m->id }})" class="relative group">
                                                                                            @if($m->isImage())<img src="{{ $m->url }}" class="h-12 w-12 object-cover rounded">@else<div class="h-12 w-12 bg-slate-100 flex items-center justify-center text-slate-500 text-xs rounded">{{ $m->ext }}</div>@endif
                                                                                            <button type="button" @click="_mids = _mids.filter(id => id !== {{ $m->id }}); talos.markDirty()" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-xs hidden group-hover:flex items-center justify-center">✕</button>
                                                                                        </div>
                                                                                    @endforeach
                                                                                </div>
                                                                                <button type="button" @click="_mshow = true" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-xs font-medium transition-colors">
                                                                                    <span x-text="_mids.length ? 'Add / change' : 'Select from library'"></span>
                                                                                </button>
                                                                                <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6" @keydown.escape.window="_mshow = false">
                                                                                    <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                                                                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200"><h3 class="text-slate-800 font-semibold">Media Library</h3><button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
                                                                                        <div class="flex-1 min-h-0 flex overflow-hidden"><div class="w-48 border-r border-slate-200 flex-shrink-0 overflow-y-auto py-2"><button type="button" @click="$store._mlib.folder = null" :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm transition-colors">All media</button>@foreach($mediaFolders as $_mfdr)<button type="button" data-folder="{{ $_mfdr }}" @click="$store._mlib.folder = $el.dataset.folder" :class="$el.dataset.folder === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><span>{{ basename($_mfdr) }}</span></button>@endforeach</div><div class="flex-1 min-h-0 overflow-y-auto"><div class="p-4 grid grid-cols-3 gap-4">
                                                                                            @foreach($mediaItems as $m)
                                                                                                <button type="button" @click="_mids.includes({{ $m->id }}) ? _mids = _mids.filter(id => id !== {{ $m->id }}) : _mids.push({{ $m->id }}); talos.markDirty()" data-folder="{{ $m->folder ?? '' }}" x-show="$store._mlib.folder===null||$el.dataset.folder===$store._mlib.folder" class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500" :class="_mids.includes({{ $m->id }}) ? 'border-blue-500' : 'border-transparent'">
                                                                                                    @if($m->isImage())<img src="{{ $m->url }}" class="w-full h-36 object-cover">@else<div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs">{{ $m->ext }}</div>@endif
                                                                                                    <p class="text-xs text-slate-500 p-1 truncate">{{ $m->name }}</p>
                                                                                                </button>
                                                                                            @endforeach
                                                                                        </div>
                                                                                        </div></div><div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                                                            <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-600 hover:underline">Upload more →</a>
                                                                                            <div class="flex items-center gap-3"><span class="text-sm text-slate-400" x-text="_mids.length + ' selected'"></span><button type="button" @click="_mids = []; talos.markDirty()" class="text-sm text-slate-400 hover:text-slate-600">Clear</button><button type="button" @click="_mshow = false" class="text-sm text-blue-600 font-medium">Done</button></div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            @else
                                                                            <div x-data="{ _mid: (d.{{ $subName }} ?? {})['{{ $nnName }}'] ? parseInt((d.{{ $subName }} ?? {})['{{ $nnName }}']) : null, _mshow: false }"
                                                                                 x-init="$watch('_mid', v => (d.{{ $subName }} ??= {})['{{ $nnName }}'] = v)">
                                                                                <div x-show="_mid" class="mb-2">
                                                                                    @foreach($mediaItems as $m)
                                                                                        <div x-show="_mid === {{ $m->id }}">@if($m->isImage())<img src="{{ $m->url }}" class="h-14 w-auto object-cover rounded">@else<p class="text-xs text-slate-500">{{ $m->name }}</p>@endif</div>
                                                                                    @endforeach
                                                                                </div>
                                                                                <button type="button" @click="_mshow = true" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-xs font-medium transition-colors">
                                                                                    <span x-text="_mid ? 'Change' : 'Select from library'"></span>
                                                                                </button>
                                                                                <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6" @keydown.escape.window="_mshow = false">
                                                                                    <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                                                                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200"><h3 class="text-slate-800 font-semibold">Media Library</h3><button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
                                                                                        <div class="flex-1 min-h-0 flex overflow-hidden"><div class="w-48 border-r border-slate-200 flex-shrink-0 overflow-y-auto py-2"><button type="button" @click="$store._mlib.folder = null" :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm transition-colors">All media</button>@foreach($mediaFolders as $_mfdr)<button type="button" data-folder="{{ $_mfdr }}" @click="$store._mlib.folder = $el.dataset.folder" :class="$el.dataset.folder === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><span>{{ basename($_mfdr) }}</span></button>@endforeach</div><div class="flex-1 min-h-0 overflow-y-auto"><div class="p-4 grid grid-cols-3 gap-4">
                                                                                            @foreach($mediaItems as $m)
                                                                                                <button type="button" @click="_mid = {{ $m->id }}; _mshow = false; talos.markDirty()" data-folder="{{ $m->folder ?? '' }}" x-show="$store._mlib.folder===null||$el.dataset.folder===$store._mlib.folder" class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500" :class="_mid === {{ $m->id }} ? 'border-blue-500' : 'border-transparent'">
                                                                                                    @if($m->isImage())<img src="{{ $m->url }}" class="w-full h-36 object-cover">@else<div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs">{{ $m->ext }}</div>@endif
                                                                                                    <p class="text-xs text-slate-500 p-1 truncate">{{ $m->name }}</p>
                                                                                                </button>
                                                                                            @endforeach
                                                                                        </div>
                                                                                        </div></div><div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                                                            <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-600 hover:underline">Upload more →</a>
                                                                                            <button type="button" @click="_mid = null; _mshow = false; talos.markDirty()" class="text-sm text-slate-400 hover:text-slate-600">Clear</button>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            @endif
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
                                                    <input type="text" x-model="d.{{ $subName }}" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                            @else
                                <div class="p-4 bg-slate-100 rounded-lg border border-dashed border-slate-300">
                                    <p class="text-sm text-slate-400">
                                        @if($firstUid) Component "{{ $firstUid }}" not found. @else No component assigned. @endif
                                    </p>
                                </div>
                            @endif
                            @break

                        {{-- ── Dynamic Zone (basic placeholder) ── --}}
                        @case('dynamiczone')
                            <input type="hidden" name="{{ $name }}"
                                   value="{{ is_array($value) ? json_encode($value) : $value }}">
                            <div class="p-4 bg-slate-100 rounded-lg border border-dashed border-slate-300 text-center">
                                <p class="text-sm text-slate-400">Dynamic Zone — populate via API.</p>
                            </div>
                            @break

                        {{-- ── Repeater (Strapi-style collapsible entries) ── --}}
                        @case('repeater')
                            @php
                                $subFields   = $field['subFields'] ?? [];
                                $repRaw      = is_array($value) ? $value : (is_string($value) && $value ? json_decode($value, true) : []);
                                $repRows     = json_encode($repRaw ?? []);
                                $repEmpty    = json_encode(collect($subFields)->mapWithKeys(fn($sf, $sn) => [$sn => $sf['default'] ?? ''])->all());
                            @endphp
                            <div x-data="repeaterField({{ $repRows }}, {{ $repEmpty }})">
                                <input type="hidden" name="{{ $name }}" :value="JSON.stringify(rows)">

                                {{-- Empty state --}}
                                <template x-if="rows.length === 0">
                                    <div class="rounded-lg border-2 border-dashed border-slate-300 py-10 flex flex-col items-center gap-2">
                                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="text-sm text-slate-400">No entry yet.</p>
                                        <p class="text-xs text-slate-400">Click on "Add an entry" to add your first entry.</p>
                                    </div>
                                </template>

                                {{-- Entry list --}}
                                <template x-if="rows.length > 0">
                                    <div class="rounded-lg border border-slate-300 overflow-hidden divide-y divide-slate-200">
                                        <template x-for="(row, idx) in rows" :key="idx">
                                            <div>
                                                {{-- Entry header --}}
                                                <div class="flex items-center gap-3 px-4 py-3 bg-slate-100 hover:bg-slate-100/80 cursor-pointer select-none"
                                                     @click="toggle(idx)">
                                                    {{-- Up/Down ordering buttons --}}
                                                    <div class="flex flex-col gap-0.5 flex-shrink-0">
                                                        <button type="button" @click.stop="moveUp(idx)" :disabled="idx === 0"
                                                                class="text-slate-400 hover:text-slate-600 disabled:opacity-20 disabled:cursor-not-allowed p-0.5 transition-colors">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                                        </button>
                                                        <button type="button" @click.stop="moveDown(idx)" :disabled="idx === rows.length - 1"
                                                                class="text-slate-400 hover:text-slate-600 disabled:opacity-20 disabled:cursor-not-allowed p-0.5 transition-colors">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                                        </button>
                                                    </div>
                                                    {{-- Number --}}
                                                    <span class="text-xs text-slate-400 font-mono flex-shrink-0 w-5" x-text="idx + 1"></span>
                                                    {{-- Preview --}}
                                                    <span class="flex-1 text-sm text-slate-500 truncate"
                                                          x-text="preview(row)"></span>
                                                    {{-- Delete --}}
                                                    <button type="button" @click.stop="removeRow(idx)"
                                                            class="flex-shrink-0 p-1.5 rounded text-slate-400 hover:text-red-600 hover:bg-red-900/20 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                    {{-- Chevron --}}
                                                    <span class="flex-shrink-0 text-slate-400 transition-transform duration-200"
                                                          :class="isOpen(idx) ? 'rotate-180' : ''">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                        </svg>
                                                    </span>
                                                </div>

                                                {{-- Entry body --}}
                                                <div x-show="isOpen(idx)"
                                                     x-transition:enter="transition ease-out duration-100"
                                                     x-transition:enter-start="opacity-0 -translate-y-1"
                                                     x-transition:enter-end="opacity-100 translate-y-0"
                                                     class="p-5 bg-slate-100 border-t border-slate-300 space-y-5">
                                                    @foreach($subFields as $subName => $subField)
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
                                                                <button type="button"
                                                                        @click="toggleBool(row, '{{ $subName }}')"
                                                                        class="flex items-center gap-3">
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
                                                                @php $_sfOpts8 = $enumOpts($subField); $_sfMulti8 = !empty($subField['multiple']); @endphp
                                                                @if($_sfMulti8)
                                                                    <div x-data="{ _opts: {{ json_encode($_sfOpts8) }} }">
                                                                        <div class="flex flex-wrap gap-1 mb-1" x-show="enumArr(row['{{ $subName }}']).length > 0">
                                                                            <template x-for="_ev in enumArr(row['{{ $subName }}'])" :key="'c8'+_ev">
                                                                                <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                                                                                    <span x-text="_ev"></span>
                                                                                    <button type="button" @click.stop="row['{{ $subName }}'] = enumToggle(row['{{ $subName }}'], _ev)" class="w-3 h-3 flex items-center justify-center rounded-full hover:bg-purple-200"><svg class="w-1.5 h-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                                                                </span>
                                                                            </template>
                                                                        </div>
                                                                        <div class="border border-slate-200 rounded-lg overflow-hidden divide-y divide-slate-100">
                                                                            <template x-for="_eo in _opts" :key="_eo">
                                                                                <div class="flex items-center gap-2 px-3 py-2 cursor-pointer transition-colors select-none text-sm"
                                                                                     :class="enumArr(row['{{ $subName }}']).includes(_eo) ? 'bg-purple-50' : 'bg-white hover:bg-slate-50'"
                                                                                     @click="row['{{ $subName }}'] = enumToggle(row['{{ $subName }}'], _eo)">
                                                                                    <div class="w-3.5 h-3.5 rounded flex items-center justify-center flex-shrink-0 border transition-all" :class="enumArr(row['{{ $subName }}']).includes(_eo) ? 'bg-purple-600 border-purple-600' : 'border-slate-300 bg-white'">
                                                                                        <svg x-show="enumArr(row['{{ $subName }}']).includes(_eo)" class="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                                    </div>
                                                                                    <span class="text-slate-700" x-text="_eo"></span>
                                                                                </div>
                                                                            </template>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <select x-model="row['{{ $subName }}']"
                                                                            class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                                                                        <option value="">— Select —</option>
                                                                        @foreach($_sfOpts8 as $eOpt)
                                                                            @if($eOpt)<option value="{{ $eOpt }}">{{ $eOpt }}</option>@endif
                                                                        @endforeach
                                                                    </select>
                                                                @endif

                                                            @elseif($subField['type'] === 'media')
                                                                <div x-data="{ _mid: row['{{ $subName }}'] ? parseInt(row['{{ $subName }}']) : null, _mshow: false }"
                                                                     x-init="$watch('_mid', v => row['{{ $subName }}'] = v)">
                                                                    <div x-show="_mid" class="mb-2">
                                                                        @foreach($mediaItems as $m)
                                                                            <div x-show="_mid === {{ $m->id }}">
                                                                                @if($m->isImage())
                                                                                    <img src="{{ $m->url }}" class="h-20 w-auto object-cover rounded-lg">
                                                                                @else
                                                                                    <p class="text-sm text-slate-500">{{ $m->name }}</p>
                                                                                @endif
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                    <button type="button" @click="_mshow = true"
                                                                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-100 text-slate-600 rounded-lg text-sm font-medium transition-colors">
                                                                        <span x-text="_mid ? 'Change media' : 'Select from library'"></span>
                                                                    </button>
                                                                    <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
                                                                         @keydown.escape.window="_mshow = false">
                                                                        <div class="bg-white border border-slate-200 rounded-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                                                                            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                                                                                <h3 class="text-slate-800 font-semibold">Media Library</h3>
                                                                                <button type="button" @click="_mshow = false" class="text-slate-400 hover:text-slate-900">
                                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                                </button>
                                                                            </div>
                                                                            <div class="flex-1 min-h-0 flex overflow-hidden"><div class="w-48 border-r border-slate-200 flex-shrink-0 overflow-y-auto py-2"><button type="button" @click="$store._mlib.folder = null" :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm transition-colors">All media</button>@foreach($mediaFolders as $_mfdr)<button type="button" data-folder="{{ $_mfdr }}" @click="$store._mlib.folder = $el.dataset.folder" :class="$el.dataset.folder === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg><span>{{ basename($_mfdr) }}</span></button>@endforeach</div><div class="flex-1 min-h-0 overflow-y-auto"><div class="p-4 grid grid-cols-3 gap-4">
                                                                                @foreach($mediaItems as $m)
                                                                                    <button type="button" @click="_mid = {{ $m->id }}; _mshow = false; talos.markDirty()"
                                                                                            data-folder="{{ $m->folder ?? '' }}" x-show="$store._mlib.folder===null||$el.dataset.folder===$store._mlib.folder" class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500"
                                                                                            :class="_mid === {{ $m->id }} ? 'border-blue-500' : 'border-transparent'">
                                                                                        @if($m->isImage())
                                                                                            <img src="{{ $m->url }}" class="w-full h-36 object-cover">
                                                                                        @else
                                                                                            <div class="w-full h-36 bg-slate-100 flex items-center justify-center text-slate-500 text-xs">{{ $m->ext }}</div>
                                                                                        @endif
                                                                                        <p class="text-xs text-slate-500 p-1 truncate">{{ $m->name }}</p>
                                                                                    </button>
                                                                                @endforeach
                                                                            </div>
                                                                            </div></div><div class="px-5 py-3 border-t border-slate-200 flex justify-between items-center">
                                                                                <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-600 hover:underline">Upload more →</a>
                                                                                <button @click="_mid = null; _mshow = false; talos.markDirty()" class="text-sm text-slate-400 hover:text-slate-600">Clear</button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

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

                                {{-- Add entry button --}}
                                <button type="button" @click="addRow()"
                                        class="mt-3 w-full py-3 flex items-center justify-center gap-2 rounded-lg border border-dashed border-slate-300 hover:border-blue-500 bg-white hover:bg-slate-100/50 text-slate-400 hover:text-blue-600 text-sm font-medium transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Add an entry
                                </button>
                            </div>
                            @break

                        {{-- ── Fallback ── --}}
                        @default
                            <input type="text" name="{{ $name }}" value="{{ $value }}"
                                   {{ $required ? 'required' : '' }}
                                   class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">

                    @endswitch
                </div>
            @endforeach
        </div>
    </form>
    {{-- ── End of main form ── --}}

    {{-- ── Sidebar (outside the main form to prevent nested-form issues) ── --}}
    <div class="w-64 space-y-4 sticky top-6">
        @php
            $navUser    = $talosUser ?? null;
            $canPublish = $navUser?->is_super_admin
                || in_array('publish', ($navUser?->role?->permissions['content-manager'][$uid] ?? []));
        @endphp
        <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-3">
            @if($draftable)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500">Status</span>
                    @if($isEdit && $entry->published_at)
                        <span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded">Published</span>
                    @else
                        <span class="text-xs bg-slate-100 text-slate-400 border border-slate-300 px-2 py-0.5 rounded">Draft</span>
                    @endif
                </div>
                <div class="space-y-2">
                    <button type="submit" form="content-form" name="publish" value="0"
                            class="w-full py-2 bg-slate-100 hover:bg-slate-100 text-slate-600 rounded-lg text-sm font-medium transition-colors">
                        {{ $canPublish ? 'Save as draft' : 'Save' }}
                    </button>
                    @if($canPublish)
                        <button type="submit" form="content-form" name="publish" value="1"
                                class="w-full py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors">
                            {{ $isEdit ? 'Save & Publish' : 'Create & Publish' }}
                        </button>
                    @endif
                </div>
            @else
                <button type="submit" form="content-form"
                        class="w-full py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors">
                    {{ $isEdit ? 'Save changes' : 'Create entry' }}
                </button>
            @endif

            <a href="{{ route('talos.content.index', ['uid' => $uid]) }}"
               class="block text-center text-sm text-slate-400 hover:text-slate-600 pt-1">← Back to list</a>
        </div>

        @if($i18n)
            {{-- Locale badge --}}
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Locale</p>
                <span class="inline-block px-3 py-1 bg-blue-50 text-blue-500 border border-blue-200 rounded-lg text-sm font-mono font-semibold">
                    {{ strtoupper($locale) }}
                </span>
            </div>

            {{-- Translations panel (edit only) --}}
            @if($isEdit)
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Translations</p>
                    <div class="space-y-1.5">
                        @foreach($locales as $loc)
                            @if($loc === $locale)
                                <div class="flex items-center justify-between px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg">
                                    <span class="text-xs font-mono font-semibold text-blue-500">{{ strtoupper($loc) }}</span>
                                    <span class="text-xs text-blue-600">Current</span>
                                </div>
                            @elseif(isset($siblings[$loc]))
                                <a href="{{ route('talos.content.edit', ['uid' => $uid, 'id' => $siblings[$loc]['id']]) }}"
                                   class="flex items-center justify-between px-3 py-2 bg-slate-100 hover:bg-slate-100 border border-slate-300 rounded-lg transition-colors">
                                    <span class="text-xs font-mono font-semibold text-slate-600">{{ strtoupper($loc) }}</span>
                                    <span class="text-xs text-emerald-700">Edit →</span>
                                </a>
                            @else
                                <form action="{{ route('talos.content.translate', ['uid' => $uid, 'id' => $entry->id]) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="locale" value="{{ $loc }}">
                                    <button type="submit"
                                            class="w-full flex items-center justify-between px-3 py-2 bg-slate-100 hover:bg-slate-100 border border-dashed border-slate-300 rounded-lg transition-colors">
                                        <span class="text-xs font-mono font-semibold text-slate-400">{{ strtoupper($loc) }}</span>
                                        <span class="text-xs text-slate-400">+ Add</span>
                                    </button>
                                </form>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        @endif

        @if($isEdit)
            <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-2 text-xs text-slate-400">
                <p>Created: {{ \Carbon\Carbon::parse($entry->created_at)->format('M d, Y H:i') }}</p>
                <p>Updated: {{ \Carbon\Carbon::parse($entry->updated_at)->format('M d, Y H:i') }}</p>
                @if($entry->published_at)
                    <p>Published: {{ \Carbon\Carbon::parse($entry->published_at)->format('M d, Y H:i') }}</p>
                @endif
            </div>

            {{-- Delete form is its own top-level form — no nesting risk --}}
            <form action="{{ route('talos.content.destroy', ['uid' => $uid, 'id' => $entry->id]) }}"
                  method="POST" data-confirm="Delete this entry permanently?">
                @csrf @method('DELETE')
                <button type="submit"
                        class="w-full py-2 bg-red-900/30 hover:bg-red-900/50 text-red-600 rounded-lg text-sm font-medium transition-colors border border-red-900">
                    Delete entry
                </button>
            </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
// ── Repeater Alpine component (Strapi-style) ──────────────────────────────
function repeaterField(initialRows, emptyRow) {
    return {
        rows:     Array.isArray(initialRows) ? initialRows : [],
        emptyRow: emptyRow || {},
        open:     [],   // indices of expanded entries

        init() {
            // Auto-expand first item when editing existing data
            if (this.rows.length > 0) this.open = [0];
        },

        addRow() {
            const idx = this.rows.length;
            this.rows.push(Object.assign({}, this.emptyRow));
            this.open.push(idx);    // new entry opens automatically
        },

        removeRow(idx) {
            this.rows.splice(idx, 1);
            // Shift open indices down past the removed one
            this.open = this.open
                .filter(i => i !== idx)
                .map(i => i > idx ? i - 1 : i);
        },

        toggle(idx) {
            const pos = this.open.indexOf(idx);
            if (pos === -1) { this.open.push(idx); }
            else            { this.open.splice(pos, 1); }
        },

        isOpen(idx) { return this.open.includes(idx); },

        // First non-empty string value → preview text in collapsed header
        preview(row) {
            const val = Object.values(row).find(v => v !== null && v !== '' && typeof v === 'string');
            return val ? String(val).substring(0, 60) : '—';
        },

        // Boolean helpers — avoids nested x-data inside x-for
        getBool(row, field)      { return !!row[field]; },
        toggleBool(row, field)   { row[field] = !row[field]; },

        moveUp(idx) {
            if (idx === 0) return;
            const temp = this.rows.splice(idx, 1)[0];
            this.rows.splice(idx - 1, 0, temp);
            this.open = this.open.map(i => i === idx ? idx - 1 : i === idx - 1 ? idx : i);
        },
        moveDown(idx) {
            if (idx === this.rows.length - 1) return;
            const temp = this.rows.splice(idx, 1)[0];
            this.rows.splice(idx + 1, 0, temp);
            this.open = this.open.map(i => i === idx ? idx + 1 : i === idx + 1 ? idx : i);
        },
    };
}

// ── Quill rich-text editors ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    var toolbarOptions = [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['blockquote', 'code-block'],
        ['link'],
        ['clean'],
    ];

    document.querySelectorAll('[data-quill-for]').forEach(function (container) {
        var fieldName  = container.dataset.quillFor;
        var hiddenInput = document.getElementById('quill-input-' + fieldName);

        var quill = new Quill(container, {
            theme: 'snow',
            modules: { toolbar: toolbarOptions },
            placeholder: 'Write something…',
        });

        // Seed existing content
        if (hiddenInput && hiddenInput.value) {
            quill.clipboard.dangerouslyPasteHTML(hiddenInput.value);
        }

        // Keep hidden input in sync
        quill.on('text-change', function () {
            if (hiddenInput) {
                var html = quill.root.innerHTML;
                hiddenInput.value = html === '<p><br></p>' ? '' : html;
            }
        });
    });

    // Final sync on submit (catches any edge cases)
    var form = document.getElementById('content-form');
    if (form) {
        form.addEventListener('submit', function () {
            document.querySelectorAll('[data-quill-for]').forEach(function (container) {
                var fieldName   = container.dataset.quillFor;
                var hiddenInput = document.getElementById('quill-input-' + fieldName);
                var quill       = Quill.find(container);
                if (quill && hiddenInput) {
                    var html = quill.root.innerHTML;
                    hiddenInput.value = html === '<p><br></p>' ? '' : html;
                }
            });
        });
    }
});
</script>

<script>
// ── Slug auto-generation ──────────────────────────────────────────────────────
(function () {
    const slugInput = document.getElementById('talos-slug-input');
    if (! slugInput) return;

    function slugify(str) {
        return str.toLowerCase().trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    // Find the first text/email/url input that is not the slug field itself
    const form = document.getElementById('content-form');
    const sourceField = form
        ? [...form.querySelectorAll('input[type=text], input[type=email], input[type=url], textarea')]
              .find(el => el !== slugInput && el.name && ! el.name.startsWith('_'))
        : null;

    let manuallyEdited = slugInput.value.length > 0;

    if (sourceField) {
        sourceField.addEventListener('input', function () {
            if (! manuallyEdited) {
                slugInput.value = slugify(this.value);
            }
        });
    }

    slugInput.addEventListener('input', function () {
        manuallyEdited = true;
    });

    slugInput.addEventListener('blur', function () {
        this.value = slugify(this.value);
    });
})();
</script>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('_mlib', { folder: null });
});

function enumPicker(opts, initialSelected) {
    return {
        opts:     opts || [],
        selected: initialSelected || [],
        toggle(v) {
            const i = this.selected.indexOf(v);
            i === -1 ? this.selected.push(v) : this.selected.splice(i, 1);
        },
    };
}

function enumArr(v)         { return Array.isArray(v) ? v : (v ? [v] : []); }
function enumToggle(cur, v) { const a = enumArr(cur).slice(); const i = a.indexOf(v); i === -1 ? a.push(v) : a.splice(i, 1); return a; }

function relPicker(initialEntries, initialSelected) {
    return {
        entries:  initialEntries  || [],
        selected: initialSelected || [],
        search:   '',
        filtered() {
            const q = this.search.trim().toLowerCase();
            return q ? this.entries.filter(e => e.label.toLowerCase().includes(q)) : this.entries;
        },
        toggle(id) {
            const i = this.selected.indexOf(id);
            i === -1 ? this.selected.push(id) : this.selected.splice(i, 1);
        },
        labelFor(id) {
            const e = this.entries.find(e => e.id === id);
            return e ? e.label : '#' + id;
        },
    };
}
</script>
@endpush
