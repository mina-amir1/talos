@extends('talos.layouts.app')

@php
    $isEdit = isset($entry);
    $title  = $isEdit ? 'Edit entry' : 'Create entry';
@endphp

@section('title', $title . ' — ' . $contentType['info']['displayName'])
@section('header', $contentType['info']['displayName'] . ' — ' . ($isEdit ? 'Edit' : 'Create'))

@section('header-actions')
    <a href="{{ route('talos.content-type-builder.api-settings', ['uid' => $uid]) }}"
       class="p-2 sm:px-3 sm:py-2 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-lg text-sm transition-colors flex items-center gap-1.5"
       title="API Fields">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span class="hidden sm:inline">API Fields</span>
    </a>
@endsection

@push('styles')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
    .ql-toolbar.ql-snow { background: #f8fafc; border: 1px solid #e2e8f0; border-bottom: none; border-radius: 0.5rem 0.5rem 0 0; }
    .ql-container.ql-snow { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0 0 0.5rem 0.5rem; }
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
    .ql-editor ol, .ql-editor ul { padding-left: 1.5em !important; margin: 0; }
    .ql-editor ol > li, .ql-editor ul > li { list-style-type: none; padding-left: 1.5em; }
    .ql-editor ul > li::before { content: '\2022'; }
    .ql-editor ol li:not(.ql-direction-rtl), .ql-editor ul li:not(.ql-direction-rtl) { padding-left: 1.5em; }
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

    $enumOpts = fn($f) => array_values(array_filter(array_map('trim', explode("\n", $f['enumValues'] ?? ''))));

    $mediaFolders = $mediaItems->pluck('folder')->filter()->unique()->sort()->values();

    $mediaItemsForJs = $mediaItems->map(fn($m) => [
        'id'      => $m->id,
        'name'    => $m->name,
        'url'     => $m->url,
        'ext'     => $m->ext,
        'folder'  => $m->folder,
        'isImage' => $m->isImage(),
    ])->values();

    $componentMap = [];
    foreach ($components as $comp) {
        $componentMap[$comp['__uid']] = $comp;
    }
@endphp

<div class="flex flex-col lg:flex-row gap-6 items-start">

    {{-- ── Main form ── --}}
    <form action="{{ $isEdit
            ? route('talos.content.update', ['uid' => $uid, 'id' => $entry->id])
            : route('talos.content.store', ['uid' => $uid]) }}"
          method="POST" enctype="multipart/form-data" id="content-form" class="flex-1 w-full min-w-0">
        @csrf
        @if($isEdit) @method('PUT') @endif
        @if($i18n)
            <input type="hidden" name="locale" value="{{ $locale }}">
            @if(!$isEdit)
                <input type="hidden" name="localizations_id" value="{{ request('localizations_id') }}">
            @endif
        @endif

        <div class="space-y-5">

            {{-- Slug (collection types only) --}}
            @include('talos.content.form._field_slug')

            {{-- Attributes --}}
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
                        @case('string')
                        @case('email')
                        @case('url')
                        @case('uid')
                            @include('talos.content.form._field_string')
                            @break

                        @case('text')
                            @include('talos.content.form._field_text')
                            @break

                        @case('richtext')
                            @include('talos.content.form._field_richtext')
                            @break

                        @case('integer')
                        @case('biginteger')
                        @case('decimal')
                        @case('float')
                            @include('talos.content.form._field_number')
                            @break

                        @case('boolean')
                            @include('talos.content.form._field_boolean')
                            @break

                        @case('datetime')
                        @case('date')
                        @case('time')
                            @include('talos.content.form._field_datetime')
                            @break

                        @case('enumeration')
                            @include('talos.content.form._field_enumeration')
                            @break

                        @case('json')
                            @include('talos.content.form._field_json')
                            @break

                        @case('relation')
                            @include('talos.content.form._field_relation')
                            @break

                        @case('media')
                            @include('talos.content.form._field_media')
                            @break

                        @case('component')
                            @include('talos.content.form._field_component')
                            @break

                        @case('dynamiczone')
                            @include('talos.content.form._field_dynamiczone')
                            @break

                        @case('repeater')
                            @include('talos.content.form._field_repeater')
                            @break

                        @default
                            <input type="text" name="{{ $name }}" value="{{ $value }}"
                                   {{ $required ? 'required' : '' }}
                                   class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                    @endswitch
                </div>
            @endforeach

        </div>
    </form>

    {{-- ── Sidebar (outside the form to avoid nested-form issues) ── --}}
    <div class="w-full lg:w-auto">
        @include('talos.content.form._sidebar')
    </div>

</div>
@endsection

@push('scripts')
    @include('talos.content.form._scripts')
@endpush
