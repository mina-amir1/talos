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
    /* Dark-theme overrides for Quill Snow */
    .ql-toolbar.ql-snow {
        background: #1f2937;
        border: 1px solid #374151;
        border-bottom: none;
        border-radius: 0.5rem 0.5rem 0 0;
    }
    .ql-container.ql-snow {
        background: #1f2937;
        border: 1px solid #374151;
        border-radius: 0 0 0.5rem 0.5rem;
    }
    .ql-toolbar .ql-stroke { stroke: #9ca3af; }
    .ql-toolbar .ql-fill   { fill:   #9ca3af; }
    .ql-toolbar .ql-picker-label,
    .ql-toolbar .ql-picker-item { color: #9ca3af; }
    .ql-toolbar button:hover .ql-stroke,
    .ql-toolbar button.ql-active .ql-stroke { stroke: #60a5fa; }
    .ql-toolbar button:hover .ql-fill,
    .ql-toolbar button.ql-active .ql-fill   { fill:   #60a5fa; }
    .ql-toolbar .ql-picker-options { background: #1f2937; border-color: #374151; }
    .ql-editor { color: #f3f4f6; min-height: 220px; font-size: 0.9rem; line-height: 1.6; }
    .ql-editor.ql-blank::before { color: #6b7280; font-style: normal; }
    .ql-editor h1, .ql-editor h2, .ql-editor h3 { color: #f9fafb; }
    .ql-editor blockquote { border-left-color: #4b5563; color: #9ca3af; }
    .ql-editor code, .ql-editor pre { background: #111827; color: #a5b4fc; }
    .ql-snow .ql-tooltip { background: #1f2937; border-color: #374151; color: #f3f4f6; box-shadow: none; }
    .ql-snow .ql-tooltip input[type=text] { background: #111827; border-color: #374151; color: #f3f4f6; }
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
            @foreach($attributes as $name => $field)
                @php
                    $value    = $isEdit ? ($entry->$name ?? null) : old($name);
                    $label    = ucwords(str_replace('_', ' ', $name));
                    $required = $field['required'] ?? false;
                @endphp

                @if($field['private'] ?? false)
                    @continue
                @endif

                <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
                    <label class="block text-sm font-medium text-gray-300 mb-3">
                        {{ $label }}
                        @if($required)<span class="text-red-400 ml-0.5">*</span>@endif
                        <span class="text-gray-600 text-xs font-normal ml-1">({{ $field['type'] }})</span>
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
                                   class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                            @break

                        {{-- ── Plain long text ── --}}
                        @case('text')
                            <textarea name="{{ $name }}" rows="4" {{ $required ? 'required' : '' }}
                                      class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500 resize-y">{{ $value }}</textarea>
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
                                    <select name="{{ $name }}[]" multiple size="6"
                                            class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                                        @foreach($relOpts['entries'] as $relEntry)
                                            <option value="{{ $relEntry->id }}"
                                                    {{ in_array($relEntry->id, $selected) ? 'selected' : '' }}>
                                                #{{ $relEntry->id }}{{ $entryLabel($relEntry) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Hold Ctrl / Cmd to select multiple.</p>
                                @else
                                    <select name="{{ $name }}"
                                            class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
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
                                <p class="text-sm text-gray-500 italic">
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
                                   class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
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
                                         :class="on ? 'bg-blue-600' : 'bg-gray-700'">
                                        <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                                             :class="on ? 'translate-x-6' : 'translate-x-0'"></div>
                                    </div>
                                    <span class="text-sm font-semibold transition-colors"
                                          :class="on ? 'text-blue-400' : 'text-gray-500'"
                                          x-text="on ? 'True' : 'False'"></span>
                                </button>
                            </div>
                            @break

                        {{-- ── Date / time ── --}}
                        @case('datetime')
                            <input type="datetime-local" name="{{ $name }}"
                                   value="{{ $value }}" {{ $required ? 'required' : '' }}
                                   class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                            @break

                        @case('date')
                        @case('time')
                            <input type="{{ $field['type'] }}" name="{{ $name }}"
                                   value="{{ $value }}" {{ $required ? 'required' : '' }}
                                   class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                            @break

                        {{-- ── Enumeration ── --}}
                        @case('enumeration')
                            <select name="{{ $name }}" {{ $required ? 'required' : '' }}
                                    class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                                @if(!$required)<option value="">— Select —</option>@endif
                                @foreach(explode("\n", trim($field['enumValues'] ?? '')) as $opt)
                                    @php $opt = trim($opt); @endphp
                                    @if($opt)
                                        <option value="{{ $opt }}" {{ $value === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @break

                        {{-- ── Raw JSON ── --}}
                        @case('json')
                            <textarea name="{{ $name }}" rows="5" {{ $required ? 'required' : '' }}
                                      class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm font-mono focus:outline-none focus:border-blue-500 resize-y">{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value }}</textarea>
                            <p class="text-xs text-gray-600 mt-1">Must be valid JSON</p>
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
                                                    <img src="{{ $m->url }}" class="h-20 w-20 object-cover rounded-lg border border-gray-700">
                                                @else
                                                    <div class="h-20 w-20 bg-gray-800 rounded-lg border border-gray-700 flex items-center justify-center">
                                                        <span class="text-xs text-gray-500 font-mono uppercase">{{ $m->ext }}</span>
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
                                            class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition-colors">
                                        <span x-text="ids.length ? ids.length + ' file(s) selected — edit' : 'Select files'"></span>
                                    </button>

                                    <div x-show="show" x-cloak
                                         class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
                                         @keydown.escape.window="show = false">
                                        <div class="bg-gray-900 border border-gray-800 rounded-xl w-full max-w-4xl max-h-[80vh] flex flex-col">
                                            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800">
                                                <h3 class="text-white font-semibold">
                                                    Media Library
                                                    <span class="text-gray-500 text-sm font-normal" x-text="ids.length ? '(' + ids.length + ' selected)' : ''"></span>
                                                </h3>
                                                <button @click="show = false" class="text-gray-500 hover:text-white">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="flex-1 overflow-y-auto p-4 grid grid-cols-4 gap-3">
                                                @foreach($mediaItems as $m)
                                                    <button type="button"
                                                            @click="ids.includes({{ $m->id }}) ? ids = ids.filter(i => i !== {{ $m->id }}) : ids.push({{ $m->id }})"
                                                            class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500 relative"
                                                            :class="ids.includes({{ $m->id }}) ? 'border-blue-500' : 'border-transparent'">
                                                        @if($m->isImage())
                                                            <img src="{{ $m->url }}" class="w-full h-24 object-cover">
                                                        @else
                                                            <div class="w-full h-24 bg-gray-800 flex items-center justify-center text-gray-400 text-xs">{{ $m->ext }}</div>
                                                        @endif
                                                        <p class="text-xs text-gray-400 p-1 truncate">{{ $m->name }}</p>
                                                        <div x-show="ids.includes({{ $m->id }})"
                                                             class="absolute top-1.5 right-1.5 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center pointer-events-none">
                                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                            <div class="px-5 py-3 border-t border-gray-800 flex justify-between items-center">
                                                <a href="{{ route('talos.media.index') }}" target="_blank"
                                                   class="text-sm text-blue-400 hover:underline">Upload more →</a>
                                                <div class="flex items-center gap-3">
                                                    <button type="button" @click="ids = []"
                                                            class="text-sm text-gray-500 hover:text-gray-300">Clear all</button>
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
                                                    <p class="text-sm text-gray-400">{{ $m->name }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>

                                    <button type="button" @click="show = true"
                                            class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition-colors">
                                        <span x-text="selectedId ? 'Change media' : 'Select from library'"></span>
                                    </button>

                                    <div x-show="show" x-cloak
                                         class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
                                         @keydown.escape.window="show = false">
                                        <div class="bg-gray-900 border border-gray-800 rounded-xl w-full max-w-4xl max-h-[80vh] flex flex-col">
                                            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800">
                                                <h3 class="text-white font-semibold">Media Library</h3>
                                                <button @click="show = false" class="text-gray-500 hover:text-white">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="flex-1 overflow-y-auto p-4 grid grid-cols-4 gap-3">
                                                @foreach($mediaItems as $m)
                                                    <button type="button"
                                                            @click="selectedId = {{ $m->id }}; show = false"
                                                            class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500"
                                                            :class="selectedId === {{ $m->id }} ? 'border-blue-500' : 'border-transparent'">
                                                        @if($m->isImage())
                                                            <img src="{{ $m->url }}" class="w-full h-24 object-cover">
                                                        @else
                                                            <div class="w-full h-24 bg-gray-800 flex items-center justify-center text-gray-400 text-xs">{{ $m->ext }}</div>
                                                        @endif
                                                        <p class="text-xs text-gray-400 p-1 truncate">{{ $m->name }}</p>
                                                    </button>
                                                @endforeach
                                            </div>
                                            <div class="px-5 py-3 border-t border-gray-800 flex justify-between items-center">
                                                <a href="{{ route('talos.media.index') }}" target="_blank"
                                                   class="text-sm text-blue-400 hover:underline">Upload more →</a>
                                                <button type="button" @click="selectedId = null; show = false"
                                                        class="text-sm text-gray-500 hover:text-gray-300">Clear</button>
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
                                    $repEmpty = json_encode(array_fill_keys(array_keys($compSchema['attributes'] ?? []), ''));
                                @endphp
                                <p class="text-xs text-gray-600 mb-3 font-mono">{{ $firstUid }} · repeatable</p>
                                <div x-data="repeaterField({{ $repRows }}, {{ $repEmpty }})">
                                    <input type="hidden" name="{{ $name }}" :value="JSON.stringify(rows)">

                                    <template x-if="rows.length === 0">
                                        <div class="rounded-lg border-2 border-dashed border-gray-700 py-10 flex flex-col items-center gap-2">
                                            <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <p class="text-sm text-gray-500">No entry yet.</p>
                                            <p class="text-xs text-gray-600">Click on "Add an entry" to add your first entry.</p>
                                        </div>
                                    </template>

                                    <template x-if="rows.length > 0">
                                        <div class="rounded-lg border border-gray-700 overflow-hidden divide-y divide-gray-700">
                                            <template x-for="(row, idx) in rows" :key="idx">
                                                <div>
                                                    <div class="flex items-center gap-3 px-4 py-3 bg-gray-800 hover:bg-gray-800/80 cursor-pointer select-none"
                                                         @click="toggle(idx)">
                                                        <div class="flex flex-col gap-0.5 flex-shrink-0">
                                                            <button type="button" @click.stop="moveUp(idx)" :disabled="idx === 0"
                                                                    class="text-gray-600 hover:text-gray-300 disabled:opacity-20 disabled:cursor-not-allowed p-0.5 transition-colors">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                                            </button>
                                                            <button type="button" @click.stop="moveDown(idx)" :disabled="idx === rows.length - 1"
                                                                    class="text-gray-600 hover:text-gray-300 disabled:opacity-20 disabled:cursor-not-allowed p-0.5 transition-colors">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                                            </button>
                                                        </div>
                                                        <span class="text-xs text-gray-500 font-mono flex-shrink-0 w-5" x-text="idx + 1"></span>
                                                        <span class="flex-1 text-sm text-gray-400 truncate" x-text="preview(row)"></span>
                                                        <button type="button" @click.stop="removeRow(idx)"
                                                                class="flex-shrink-0 p-1.5 rounded text-gray-600 hover:text-red-400 hover:bg-red-900/20 transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </button>
                                                        <span class="flex-shrink-0 text-gray-500 transition-transform duration-200" :class="isOpen(idx) ? 'rotate-180' : ''">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                        </span>
                                                    </div>
                                                    <div x-show="isOpen(idx)"
                                                         x-transition:enter="transition ease-out duration-100"
                                                         x-transition:enter-start="opacity-0 -translate-y-1"
                                                         x-transition:enter-end="opacity-100 translate-y-0"
                                                         class="p-5 bg-gray-950 border-t border-gray-700 space-y-5">
                                                        @foreach($compSchema['attributes'] ?? [] as $subName => $subField)
                                                            @php $subLabel = ucwords(str_replace('_', ' ', $subName)); @endphp
                                                            <div>
                                                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                                                    {{ $subLabel }}
                                                                    <span class="text-gray-600 text-xs font-normal ml-1">({{ $subField['type'] }})</span>
                                                                </label>
                                                                @if(in_array($subField['type'], ['string','email','url','uid']))
                                                                    <input type="{{ $subField['type'] === 'email' ? 'email' : ($subField['type'] === 'url' ? 'url' : 'text') }}" x-model="row['{{ $subName }}']" class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                                                                @elseif($subField['type'] === 'text')
                                                                    <textarea x-model="row['{{ $subName }}']" rows="4" class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500 resize-y"></textarea>
                                                                @elseif(in_array($subField['type'], ['integer','biginteger','decimal','float']))
                                                                    <input type="number" x-model.number="row['{{ $subName }}']" step="{{ in_array($subField['type'], ['decimal','float']) ? 'any' : '1' }}" class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                                                                @elseif($subField['type'] === 'boolean')
                                                                    <button type="button" @click="toggleBool(row, '{{ $subName }}')" class="flex items-center gap-3">
                                                                        <div class="relative w-12 h-6 rounded-full transition-colors duration-200" :class="getBool(row, '{{ $subName }}') ? 'bg-blue-600' : 'bg-gray-700'">
                                                                            <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200" :class="getBool(row, '{{ $subName }}') ? 'translate-x-6' : 'translate-x-0'"></div>
                                                                        </div>
                                                                        <span class="text-sm font-semibold" :class="getBool(row, '{{ $subName }}') ? 'text-blue-400' : 'text-gray-500'" x-text="getBool(row, '{{ $subName }}') ? 'True' : 'False'"></span>
                                                                    </button>
                                                                @elseif(in_array($subField['type'], ['date','datetime','time']))
                                                                    <input type="{{ $subField['type'] }}" x-model="row['{{ $subName }}']" class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                                                                @elseif($subField['type'] === 'enumeration')
                                                                    <select x-model="row['{{ $subName }}']" class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                                                                        <option value="">— Select —</option>
                                                                        @foreach(explode("\n", trim($subField['enumValues'] ?? '')) as $eOpt)
                                                                            @php $eOpt = trim($eOpt); @endphp
                                                                            @if($eOpt)<option value="{{ $eOpt }}">{{ $eOpt }}</option>@endif
                                                                        @endforeach
                                                                    </select>
                                                                @elseif($subField['type'] === 'media')
                                                                    <div x-data="{ _mid: row['{{ $subName }}'] ? parseInt(row['{{ $subName }}']) : null, _mshow: false }"
                                                                         x-init="$watch('_mid', v => row['{{ $subName }}'] = v)">
                                                                        <div x-show="_mid" class="mb-2">
                                                                            @foreach($mediaItems as $m)
                                                                                <div x-show="_mid === {{ $m->id }}">
                                                                                    @if($m->isImage())
                                                                                        <img src="{{ $m->url }}" class="h-20 w-auto object-cover rounded-lg">
                                                                                    @else
                                                                                        <p class="text-sm text-gray-400">{{ $m->name }}</p>
                                                                                    @endif
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                        <button type="button" @click="_mshow = true"
                                                                                class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition-colors">
                                                                            <span x-text="_mid ? 'Change media' : 'Select from library'"></span>
                                                                        </button>
                                                                        <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
                                                                             @keydown.escape.window="_mshow = false">
                                                                            <div class="bg-gray-900 border border-gray-800 rounded-xl w-full max-w-4xl max-h-[80vh] flex flex-col">
                                                                                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800">
                                                                                    <h3 class="text-white font-semibold">Media Library</h3>
                                                                                    <button @click="_mshow = false" class="text-gray-500 hover:text-white">
                                                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                                    </button>
                                                                                </div>
                                                                                <div class="flex-1 overflow-y-auto p-4 grid grid-cols-4 gap-3">
                                                                                    @foreach($mediaItems as $m)
                                                                                        <button type="button" @click="_mid = {{ $m->id }}; _mshow = false"
                                                                                                class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500"
                                                                                                :class="_mid === {{ $m->id }} ? 'border-blue-500' : 'border-transparent'">
                                                                                            @if($m->isImage())
                                                                                                <img src="{{ $m->url }}" class="w-full h-24 object-cover">
                                                                                            @else
                                                                                                <div class="w-full h-24 bg-gray-800 flex items-center justify-center text-gray-400 text-xs">{{ $m->ext }}</div>
                                                                                            @endif
                                                                                            <p class="text-xs text-gray-400 p-1 truncate">{{ $m->name }}</p>
                                                                                        </button>
                                                                                    @endforeach
                                                                                </div>
                                                                                <div class="px-5 py-3 border-t border-gray-800 flex justify-between items-center">
                                                                                    <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-400 hover:underline">Upload more →</a>
                                                                                    <button @click="_mid = null; _mshow = false" class="text-sm text-gray-500 hover:text-gray-300">Clear</button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <input type="text" x-model="row['{{ $subName }}']" class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <button type="button" @click="addRow()"
                                            class="mt-3 w-full py-3 flex items-center justify-center gap-2 rounded-lg border border-dashed border-gray-700 hover:border-blue-500 bg-gray-900 hover:bg-gray-800/50 text-gray-500 hover:text-blue-400 text-sm font-medium transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Add an entry
                                    </button>
                                </div>

                            @elseif(!$isRepeatable && $compSchema)
                                {{-- ── Single component — inline sub-fields ── --}}
                                @php
                                    $compRaw  = is_array($value) ? $value : (is_string($value) && $value ? json_decode($value, true) : null);
                                    $compJson = json_encode($compRaw ?? (object)[]);
                                @endphp
                                <p class="text-xs text-gray-600 mb-3 font-mono">{{ $firstUid }}</p>
                                <div x-data="{ d: {{ $compJson }} }">
                                    <input type="hidden" name="{{ $name }}" :value="JSON.stringify(d)">
                                    <div class="space-y-4">
                                        @foreach($compSchema['attributes'] ?? [] as $subName => $subField)
                                            @php $subLabel = ucwords(str_replace('_', ' ', $subName)); @endphp
                                            <div>
                                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                                    {{ $subLabel }}
                                                    <span class="text-gray-600 text-xs font-normal ml-1">({{ $subField['type'] }})</span>
                                                </label>
                                                @if(in_array($subField['type'], ['string','email','url','uid']))
                                                    <input type="{{ $subField['type'] === 'email' ? 'email' : ($subField['type'] === 'url' ? 'url' : 'text') }}" x-model="d.{{ $subName }}" class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                                                @elseif($subField['type'] === 'text')
                                                    <textarea x-model="d.{{ $subName }}" rows="4" class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500 resize-y"></textarea>
                                                @elseif(in_array($subField['type'], ['integer','biginteger','decimal','float']))
                                                    <input type="number" x-model.number="d.{{ $subName }}" step="{{ in_array($subField['type'], ['decimal','float']) ? 'any' : '1' }}" class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                                                @elseif($subField['type'] === 'boolean')
                                                    <div x-data="{ on: d.{{ $subName }} ?? {{ ($subField['default'] ?? false) ? 'true' : 'false' }} }" x-init="$watch('on', v => d.{{ $subName }} = v)">
                                                        <button type="button" @click="on = !on" class="flex items-center gap-3">
                                                            <div class="relative w-12 h-6 rounded-full transition-colors duration-200" :class="on ? 'bg-blue-600' : 'bg-gray-700'">
                                                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200" :class="on ? 'translate-x-6' : 'translate-x-0'"></div>
                                                            </div>
                                                            <span class="text-sm font-semibold" :class="on ? 'text-blue-400' : 'text-gray-500'" x-text="on ? 'True' : 'False'"></span>
                                                        </button>
                                                    </div>
                                                @elseif(in_array($subField['type'], ['date','datetime','time']))
                                                    <input type="{{ $subField['type'] }}" x-model="d.{{ $subName }}" class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                                                @elseif($subField['type'] === 'enumeration')
                                                    <select x-model="d.{{ $subName }}" class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                                                        <option value="">— Select —</option>
                                                        @foreach(explode("\n", trim($subField['enumValues'] ?? '')) as $eOpt)
                                                            @php $eOpt = trim($eOpt); @endphp
                                                            @if($eOpt)<option value="{{ $eOpt }}">{{ $eOpt }}</option>@endif
                                                        @endforeach
                                                    </select>
                                                @elseif($subField['type'] === 'media')
                                                    <div x-data="{ _mid: d.{{ $subName }} ? parseInt(d.{{ $subName }}) : null, _mshow: false }"
                                                         x-init="$watch('_mid', v => d.{{ $subName }} = v)">
                                                        <div x-show="_mid" class="mb-2">
                                                            @foreach($mediaItems as $m)
                                                                <div x-show="_mid === {{ $m->id }}">
                                                                    @if($m->isImage())
                                                                        <img src="{{ $m->url }}" class="h-20 w-auto object-cover rounded-lg">
                                                                    @else
                                                                        <p class="text-sm text-gray-400">{{ $m->name }}</p>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <button type="button" @click="_mshow = true"
                                                                class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition-colors">
                                                            <span x-text="_mid ? 'Change media' : 'Select from library'"></span>
                                                        </button>
                                                        <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
                                                             @keydown.escape.window="_mshow = false">
                                                            <div class="bg-gray-900 border border-gray-800 rounded-xl w-full max-w-4xl max-h-[80vh] flex flex-col">
                                                                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800">
                                                                    <h3 class="text-white font-semibold">Media Library</h3>
                                                                    <button @click="_mshow = false" class="text-gray-500 hover:text-white">
                                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                    </button>
                                                                </div>
                                                                <div class="flex-1 overflow-y-auto p-4 grid grid-cols-4 gap-3">
                                                                    @foreach($mediaItems as $m)
                                                                        <button type="button" @click="_mid = {{ $m->id }}; _mshow = false"
                                                                                class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500"
                                                                                :class="_mid === {{ $m->id }} ? 'border-blue-500' : 'border-transparent'">
                                                                            @if($m->isImage())
                                                                                <img src="{{ $m->url }}" class="w-full h-24 object-cover">
                                                                            @else
                                                                                <div class="w-full h-24 bg-gray-800 flex items-center justify-center text-gray-400 text-xs">{{ $m->ext }}</div>
                                                                            @endif
                                                                            <p class="text-xs text-gray-400 p-1 truncate">{{ $m->name }}</p>
                                                                        </button>
                                                                    @endforeach
                                                                </div>
                                                                <div class="px-5 py-3 border-t border-gray-800 flex justify-between items-center">
                                                                    <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-400 hover:underline">Upload more →</a>
                                                                    <button @click="_mid = null; _mshow = false" class="text-sm text-gray-500 hover:text-gray-300">Clear</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <input type="text" x-model="d.{{ $subName }}" class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                            @else
                                <div class="p-4 bg-gray-800 rounded-lg border border-dashed border-gray-700">
                                    <p class="text-sm text-gray-500">
                                        @if($firstUid) Component "{{ $firstUid }}" not found. @else No component assigned. @endif
                                    </p>
                                </div>
                            @endif
                            @break

                        {{-- ── Dynamic Zone (basic placeholder) ── --}}
                        @case('dynamiczone')
                            <input type="hidden" name="{{ $name }}"
                                   value="{{ is_array($value) ? json_encode($value) : $value }}">
                            <div class="p-4 bg-gray-800 rounded-lg border border-dashed border-gray-700 text-center">
                                <p class="text-sm text-gray-500">Dynamic Zone — populate via API.</p>
                            </div>
                            @break

                        {{-- ── Repeater (Strapi-style collapsible entries) ── --}}
                        @case('repeater')
                            @php
                                $subFields   = $field['subFields'] ?? [];
                                $repRaw      = is_array($value) ? $value : (is_string($value) && $value ? json_decode($value, true) : []);
                                $repRows     = json_encode($repRaw ?? []);
                                $repEmpty    = json_encode(array_fill_keys(array_keys($subFields), ''));
                            @endphp
                            <div x-data="repeaterField({{ $repRows }}, {{ $repEmpty }})">
                                <input type="hidden" name="{{ $name }}" :value="JSON.stringify(rows)">

                                {{-- Empty state --}}
                                <template x-if="rows.length === 0">
                                    <div class="rounded-lg border-2 border-dashed border-gray-700 py-10 flex flex-col items-center gap-2">
                                        <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="text-sm text-gray-500">No entry yet.</p>
                                        <p class="text-xs text-gray-600">Click on "Add an entry" to add your first entry.</p>
                                    </div>
                                </template>

                                {{-- Entry list --}}
                                <template x-if="rows.length > 0">
                                    <div class="rounded-lg border border-gray-700 overflow-hidden divide-y divide-gray-700">
                                        <template x-for="(row, idx) in rows" :key="idx">
                                            <div>
                                                {{-- Entry header --}}
                                                <div class="flex items-center gap-3 px-4 py-3 bg-gray-800 hover:bg-gray-800/80 cursor-pointer select-none"
                                                     @click="toggle(idx)">
                                                    {{-- Up/Down ordering buttons --}}
                                                    <div class="flex flex-col gap-0.5 flex-shrink-0">
                                                        <button type="button" @click.stop="moveUp(idx)" :disabled="idx === 0"
                                                                class="text-gray-600 hover:text-gray-300 disabled:opacity-20 disabled:cursor-not-allowed p-0.5 transition-colors">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                                        </button>
                                                        <button type="button" @click.stop="moveDown(idx)" :disabled="idx === rows.length - 1"
                                                                class="text-gray-600 hover:text-gray-300 disabled:opacity-20 disabled:cursor-not-allowed p-0.5 transition-colors">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                                        </button>
                                                    </div>
                                                    {{-- Number --}}
                                                    <span class="text-xs text-gray-500 font-mono flex-shrink-0 w-5" x-text="idx + 1"></span>
                                                    {{-- Preview --}}
                                                    <span class="flex-1 text-sm text-gray-400 truncate"
                                                          x-text="preview(row)"></span>
                                                    {{-- Delete --}}
                                                    <button type="button" @click.stop="removeRow(idx)"
                                                            class="flex-shrink-0 p-1.5 rounded text-gray-600 hover:text-red-400 hover:bg-red-900/20 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                    {{-- Chevron --}}
                                                    <span class="flex-shrink-0 text-gray-500 transition-transform duration-200"
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
                                                     class="p-5 bg-gray-950 border-t border-gray-700 space-y-5">
                                                    @foreach($subFields as $subName => $subField)
                                                        @php $subLabel = ucwords(str_replace('_', ' ', $subName)); @endphp
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                                                {{ $subLabel }}
                                                                <span class="text-gray-600 text-xs font-normal ml-1">({{ $subField['type'] }})</span>
                                                            </label>

                                                            @if(in_array($subField['type'], ['string','email','url','uid']))
                                                                <input type="{{ $subField['type'] === 'email' ? 'email' : ($subField['type'] === 'url' ? 'url' : 'text') }}"
                                                                       x-model="row['{{ $subName }}']"
                                                                       class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">

                                                            @elseif($subField['type'] === 'text')
                                                                <textarea x-model="row['{{ $subName }}']" rows="4"
                                                                          class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500 resize-y"></textarea>

                                                            @elseif(in_array($subField['type'], ['integer','biginteger','decimal','float']))
                                                                <input type="number" x-model.number="row['{{ $subName }}']"
                                                                       step="{{ in_array($subField['type'], ['decimal','float']) ? 'any' : '1' }}"
                                                                       class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">

                                                            @elseif($subField['type'] === 'boolean')
                                                                <button type="button"
                                                                        @click="toggleBool(row, '{{ $subName }}')"
                                                                        class="flex items-center gap-3">
                                                                    <div class="relative w-12 h-6 rounded-full transition-colors duration-200"
                                                                         :class="getBool(row, '{{ $subName }}') ? 'bg-blue-600' : 'bg-gray-700'">
                                                                        <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                                                                             :class="getBool(row, '{{ $subName }}') ? 'translate-x-6' : 'translate-x-0'"></div>
                                                                    </div>
                                                                    <span class="text-sm font-semibold transition-colors"
                                                                          :class="getBool(row, '{{ $subName }}') ? 'text-blue-400' : 'text-gray-500'"
                                                                          x-text="getBool(row, '{{ $subName }}') ? 'True' : 'False'"></span>
                                                                </button>

                                                            @elseif(in_array($subField['type'], ['date','datetime','time']))
                                                                <input type="{{ $subField['type'] }}" x-model="row['{{ $subName }}']"
                                                                       class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">

                                                            @elseif($subField['type'] === 'enumeration')
                                                                <select x-model="row['{{ $subName }}']"
                                                                        class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                                                                    <option value="">— Select —</option>
                                                                    @foreach(explode("\n", trim($subField['enumValues'] ?? '')) as $eOpt)
                                                                        @php $eOpt = trim($eOpt); @endphp
                                                                        @if($eOpt)
                                                                            <option value="{{ $eOpt }}">{{ $eOpt }}</option>
                                                                        @endif
                                                                    @endforeach
                                                                </select>

                                                            @elseif($subField['type'] === 'media')
                                                                <div x-data="{ _mid: row['{{ $subName }}'] ? parseInt(row['{{ $subName }}']) : null, _mshow: false }"
                                                                     x-init="$watch('_mid', v => row['{{ $subName }}'] = v)">
                                                                    <div x-show="_mid" class="mb-2">
                                                                        @foreach($mediaItems as $m)
                                                                            <div x-show="_mid === {{ $m->id }}">
                                                                                @if($m->isImage())
                                                                                    <img src="{{ $m->url }}" class="h-20 w-auto object-cover rounded-lg">
                                                                                @else
                                                                                    <p class="text-sm text-gray-400">{{ $m->name }}</p>
                                                                                @endif
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                    <button type="button" @click="_mshow = true"
                                                                            class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition-colors">
                                                                        <span x-text="_mid ? 'Change media' : 'Select from library'"></span>
                                                                    </button>
                                                                    <div x-show="_mshow" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-6"
                                                                         @keydown.escape.window="_mshow = false">
                                                                        <div class="bg-gray-900 border border-gray-800 rounded-xl w-full max-w-4xl max-h-[80vh] flex flex-col">
                                                                            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800">
                                                                                <h3 class="text-white font-semibold">Media Library</h3>
                                                                                <button @click="_mshow = false" class="text-gray-500 hover:text-white">
                                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                                </button>
                                                                            </div>
                                                                            <div class="flex-1 overflow-y-auto p-4 grid grid-cols-4 gap-3">
                                                                                @foreach($mediaItems as $m)
                                                                                    <button type="button" @click="_mid = {{ $m->id }}; _mshow = false"
                                                                                            class="rounded-lg overflow-hidden border-2 transition-colors hover:border-blue-500"
                                                                                            :class="_mid === {{ $m->id }} ? 'border-blue-500' : 'border-transparent'">
                                                                                        @if($m->isImage())
                                                                                            <img src="{{ $m->url }}" class="w-full h-24 object-cover">
                                                                                        @else
                                                                                            <div class="w-full h-24 bg-gray-800 flex items-center justify-center text-gray-400 text-xs">{{ $m->ext }}</div>
                                                                                        @endif
                                                                                        <p class="text-xs text-gray-400 p-1 truncate">{{ $m->name }}</p>
                                                                                    </button>
                                                                                @endforeach
                                                                            </div>
                                                                            <div class="px-5 py-3 border-t border-gray-800 flex justify-between items-center">
                                                                                <a href="{{ route('talos.media.index') }}" target="_blank" class="text-sm text-blue-400 hover:underline">Upload more →</a>
                                                                                <button @click="_mid = null; _mshow = false" class="text-sm text-gray-500 hover:text-gray-300">Clear</button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                            @else
                                                                <input type="text" x-model="row['{{ $subName }}']"
                                                                       class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
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
                                        class="mt-3 w-full py-3 flex items-center justify-center gap-2 rounded-lg border border-dashed border-gray-700 hover:border-blue-500 bg-gray-900 hover:bg-gray-800/50 text-gray-500 hover:text-blue-400 text-sm font-medium transition-all">
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
                                   class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">

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
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 space-y-3">
            @if($draftable)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-400">Status</span>
                    @if($isEdit && $entry->published_at)
                        <span class="text-xs bg-green-900/40 text-green-400 border border-green-800 px-2 py-0.5 rounded">Published</span>
                    @else
                        <span class="text-xs bg-gray-800 text-gray-500 border border-gray-700 px-2 py-0.5 rounded">Draft</span>
                    @endif
                </div>
                <div class="space-y-2">
                    <button type="submit" form="content-form" name="publish" value="0"
                            class="w-full py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition-colors">
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
               class="block text-center text-sm text-gray-500 hover:text-gray-300 pt-1">← Back to list</a>
        </div>

        @if($i18n)
            {{-- Locale badge --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Locale</p>
                <span class="inline-block px-3 py-1 bg-blue-900/40 text-blue-300 border border-blue-800 rounded-lg text-sm font-mono font-semibold">
                    {{ strtoupper($locale) }}
                </span>
            </div>

            {{-- Translations panel (edit only) --}}
            @if($isEdit)
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Translations</p>
                    <div class="space-y-1.5">
                        @foreach($locales as $loc)
                            @if($loc === $locale)
                                <div class="flex items-center justify-between px-3 py-2 bg-blue-900/20 border border-blue-800 rounded-lg">
                                    <span class="text-xs font-mono font-semibold text-blue-300">{{ strtoupper($loc) }}</span>
                                    <span class="text-xs text-blue-400">Current</span>
                                </div>
                            @elseif(isset($siblings[$loc]))
                                <a href="{{ route('talos.content.edit', ['uid' => $uid, 'id' => $siblings[$loc]['id']]) }}"
                                   class="flex items-center justify-between px-3 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-lg transition-colors">
                                    <span class="text-xs font-mono font-semibold text-gray-300">{{ strtoupper($loc) }}</span>
                                    <span class="text-xs text-green-400">Edit →</span>
                                </a>
                            @else
                                <form action="{{ route('talos.content.translate', ['uid' => $uid, 'id' => $entry->id]) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="locale" value="{{ $loc }}">
                                    <button type="submit"
                                            class="w-full flex items-center justify-between px-3 py-2 bg-gray-800 hover:bg-gray-700 border border-dashed border-gray-700 rounded-lg transition-colors">
                                        <span class="text-xs font-mono font-semibold text-gray-500">{{ strtoupper($loc) }}</span>
                                        <span class="text-xs text-gray-500">+ Add</span>
                                    </button>
                                </form>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        @endif

        @if($isEdit)
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 space-y-2 text-xs text-gray-600">
                <p>Created: {{ \Carbon\Carbon::parse($entry->created_at)->format('M d, Y H:i') }}</p>
                <p>Updated: {{ \Carbon\Carbon::parse($entry->updated_at)->format('M d, Y H:i') }}</p>
                @if($entry->published_at)
                    <p>Published: {{ \Carbon\Carbon::parse($entry->published_at)->format('M d, Y H:i') }}</p>
                @endif
            </div>

            {{-- Delete form is its own top-level form — no nesting risk --}}
            <form action="{{ route('talos.content.destroy', ['uid' => $uid, 'id' => $entry->id]) }}"
                  method="POST" onsubmit="return confirm('Delete this entry permanently?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="w-full py-2 bg-red-900/30 hover:bg-red-900/50 text-red-400 rounded-lg text-sm font-medium transition-colors border border-red-900">
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
@endpush
