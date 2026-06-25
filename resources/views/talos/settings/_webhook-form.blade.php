<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Name</label>
        <input type="text" name="name" required
               value="{{ old('name', $w?->name) }}"
               placeholder="e.g. Next.js Revalidate"
               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">URL</label>
        <input type="url" name="url" required
               value="{{ old('url', $w?->url) }}"
               placeholder="https://example.com/api/revalidate"
               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        @error('url')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-4">
    <label class="block text-xs font-medium text-slate-600 mb-1">
        Secret <span class="text-slate-400 font-normal">(optional — used for HMAC signature verification)</span>
    </label>
    <input type="text" name="secret"
           value="{{ old('secret') }}"
           placeholder="{{ $w?->secret ? '(stored — enter to override)' : 'your-secret-key' }}"
           class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
</div>

<div class="mt-4">
    <label class="block text-xs font-medium text-slate-600 mb-2">Events</label>
    <div class="flex flex-wrap gap-3">
        @foreach($events as $value => $label)
            @php
                $checked = old('events')
                    ? in_array($value, (array) old('events'))
                    : in_array($value, $w?->events ?? array_keys($events));
            @endphp
            <label class="flex items-center gap-1.5 cursor-pointer select-none">
                <input type="checkbox" name="events[]" value="{{ $value }}"
                       {{ $checked ? 'checked' : '' }}
                       class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-slate-700">{{ $label }}</span>
            </label>
        @endforeach
    </div>
    @error('events')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
</div>

@if($contentTypes->isNotEmpty())
<div class="mt-4">
    <label class="block text-xs font-medium text-slate-600 mb-1">
        Content Types <span class="text-slate-400 font-normal">(leave all unchecked to fire for every type)</span>
    </label>
    <div class="flex flex-col gap-2">
        @foreach($contentTypes as $uid => $displayName)
            @php
                $selectedTypes = old('content_types', $w?->content_types ?? []);
                $checked = in_array($uid, (array) $selectedTypes);
            @endphp
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" name="content_types[]" value="{{ $uid }}"
                       {{ $checked ? 'checked' : '' }}
                       class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-slate-700">{{ $displayName }}</span>
                <span class="text-xs text-slate-400 font-mono">{{ $uid }}</span>
            </label>
        @endforeach
    </div>
</div>
@endif
