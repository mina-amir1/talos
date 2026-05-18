@extends('talos.layouts.app')

@section('title', $contentType['info']['displayName'] . ' — Content Manager')
@section('header', $contentType['info']['displayName'])

@section('header-actions')
    @if($i18n)
        <div class="flex items-center gap-1 bg-gray-800 rounded-lg p-1">
            @foreach($locales as $loc)
                <a href="{{ route('talos.content.index', ['uid' => $uid, 'locale' => $loc]) }}"
                   class="px-3 py-1 rounded text-xs font-medium transition-colors {{ $locale === $loc ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white' }}">
                    {{ strtoupper($loc) }}
                </a>
            @endforeach
        </div>
    @endif
    <a href="{{ route('talos.content-type-builder.edit', ['uid' => $uid]) }}"
       class="px-3 py-2 bg-gray-800 hover:bg-gray-700 text-gray-400 rounded-lg text-sm transition-colors flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        Edit schema
    </a>
    <a href="{{ route('talos.content.create', array_filter(['uid' => $uid, 'locale' => $i18n ? $locale : null])) }}"
       class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Create entry{{ $i18n ? ' (' . strtoupper($locale) . ')' : '' }}
    </a>
@endsection

@section('content')
@php
    $attributes = $contentType['attributes'] ?? [];
    $draftable  = $contentType['options']['draftAndPublish'] ?? false;

    // Pick the first 4 string-ish fields to show as columns
    $displayCols = collect($attributes)->filter(fn($f) => in_array($f['type'], ['string','text','email','uid','integer','boolean','enumeration']))->take(4)->keys()->toArray();
@endphp

<div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
    {{-- Table header --}}
    <div class="px-5 py-3.5 border-b border-gray-800 flex items-center justify-between gap-4">
        <p class="text-sm text-gray-400">
            {{ $entries->total() }} {{ $entries->total() === 1 ? 'entry' : 'entries' }}
            @if($draftable)
                <span class="text-gray-600 ml-2 text-xs">
                    (drafts visible — public API only exposes published)
                </span>
            @endif
        </p>
        <div class="text-xs text-gray-600 font-mono">
            {{ $contentType['info']['pluralName'] }} · {{ count($attributes) }} fields
        </div>
    </div>

    @if($entries->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-gray-600 gap-4">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-sm">No entries yet.</p>
            <a href="{{ route('talos.content.create', ['uid' => $uid]) }}"
               class="text-blue-400 hover:underline text-sm">Create the first entry</a>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-800">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">ID</th>
                        @foreach($displayCols as $col)
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                {{ str_replace('_', ' ', $col) }}
                            </th>
                        @endforeach
                        @if($i18n)
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Locale</th>
                        @endif
                        @if($draftable)
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        @endif
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @foreach($entries as $entry)
                        <tr class="hover:bg-gray-800 transition-colors">
                            <td class="px-4 py-3 text-gray-500 text-xs font-mono">{{ $entry->id }}</td>
                            @foreach($displayCols as $col)
                                <td class="px-4 py-3 text-gray-300">
                                    @php $val = $entry->$col; @endphp
                                    @if(is_bool($val))
                                        <span class="{{ $val ? 'text-green-400' : 'text-gray-600' }}">
                                            {{ $val ? 'Yes' : 'No' }}
                                        </span>
                                    @elseif(is_string($val) && strlen($val) > 60)
                                        {{ Str::limit($val, 60) }}
                                    @else
                                        {{ $val }}
                                    @endif
                                </td>
                            @endforeach
                            @if($i18n)
                                <td class="px-4 py-3">
                                    <span class="text-xs bg-gray-800 text-gray-400 border border-gray-700 px-2 py-0.5 rounded font-mono">
                                        {{ strtoupper($entry->locale ?? '?') }}
                                    </span>
                                </td>
                            @endif
                            @if($draftable)
                                <td class="px-4 py-3">
                                    @if($entry->published_at)
                                        <span class="text-xs bg-green-900/40 text-green-400 border border-green-800 px-2 py-0.5 rounded">Published</span>
                                    @else
                                        <span class="text-xs bg-gray-800 text-gray-500 border border-gray-700 px-2 py-0.5 rounded">Draft</span>
                                    @endif
                                </td>
                            @endif
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ \Carbon\Carbon::parse($entry->created_at)->diffForHumans() }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 justify-end">
                                    @if($draftable)
                                        @if($entry->published_at)
                                            <form action="{{ route('talos.content.unpublish', ['uid' => $uid, 'id' => $entry->id]) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-xs text-yellow-500 hover:text-yellow-400">Unpublish</button>
                                            </form>
                                        @else
                                            <form action="{{ route('talos.content.publish', ['uid' => $uid, 'id' => $entry->id]) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-xs text-green-500 hover:text-green-400">Publish</button>
                                            </form>
                                        @endif
                                    @endif
                                    <a href="{{ route('talos.content.edit', ['uid' => $uid, 'id' => $entry->id]) }}"
                                       class="text-gray-500 hover:text-blue-400 transition-colors p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('talos.content.destroy', ['uid' => $uid, 'id' => $entry->id]) }}"
                                          method="POST" onsubmit="return confirm('Delete this entry?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-gray-500 hover:text-red-400 transition-colors p-1">
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

        {{-- Pagination --}}
        @if($entries->hasPages())
            <div class="px-5 py-4 border-t border-gray-800">
                {{ $entries->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
