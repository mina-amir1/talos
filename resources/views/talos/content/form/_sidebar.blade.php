@php
    $navUser    = $talosUser ?? null;
    $canPublish = $navUser?->is_super_admin
        || in_array('publish', ($navUser?->role?->permissions['content-manager'][$uid] ?? []));
@endphp

<div class="w-64 space-y-4 sticky top-6">

    {{-- Save / Publish --}}
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

    {{-- Locale badge --}}
    @if($i18n)
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

    {{-- Timestamps --}}
    @if($isEdit)
        <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-2 text-xs text-slate-400">
            <p>Created: {{ \Carbon\Carbon::parse($entry->created_at)->format('M d, Y H:i') }}</p>
            <p>Updated: {{ \Carbon\Carbon::parse($entry->updated_at)->format('M d, Y H:i') }}</p>
            @if($entry->published_at)
                <p>Published: {{ \Carbon\Carbon::parse($entry->published_at)->format('M d, Y H:i') }}</p>
            @endif
        </div>

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
