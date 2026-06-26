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
