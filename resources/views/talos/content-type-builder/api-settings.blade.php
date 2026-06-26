@extends('talos.layouts.app')

@section('title', 'API Fields — ' . $contentType['info']['displayName'])
@section('header', 'API Fields')

@section('header-actions')
    <a href="{{ route('talos.content-type-builder.edit', $uid) }}"
       class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Back to Schema
    </a>
@endsection

@section('content')
@php
    /** @var array|null $apiFields */
    $attributes = $contentType['attributes'] ?? [];
    $typeLabels = [
        'string'      => 'Text',
        'text'        => 'Long Text',
        'richtext'    => 'Rich Text',
        'integer'     => 'Integer',
        'biginteger'  => 'Big Integer',
        'decimal'     => 'Decimal',
        'float'       => 'Float',
        'boolean'     => 'Boolean',
        'date'        => 'Date',
        'datetime'    => 'DateTime',
        'time'        => 'Time',
        'email'       => 'Email',
        'url'         => 'URL',
        'uid'         => 'UID',
        'json'        => 'JSON',
        'enumeration' => 'Enumeration',
        'media'       => 'Media',
        'relation'    => 'Relation',
        'component'   => 'Component',
        'dynamiczone' => 'Dynamic Zone',
        'repeater'    => 'Repeater',
        'password'    => 'Password',
    ];
    $typeColors = [
        'string'      => 'bg-blue-50 text-blue-700',
        'text'        => 'bg-blue-50 text-blue-700',
        'richtext'    => 'bg-blue-50 text-blue-700',
        'integer'     => 'bg-violet-50 text-violet-700',
        'biginteger'  => 'bg-violet-50 text-violet-700',
        'decimal'     => 'bg-violet-50 text-violet-700',
        'float'       => 'bg-violet-50 text-violet-700',
        'boolean'     => 'bg-amber-50 text-amber-700',
        'date'        => 'bg-teal-50 text-teal-700',
        'datetime'    => 'bg-teal-50 text-teal-700',
        'time'        => 'bg-teal-50 text-teal-700',
        'email'       => 'bg-emerald-50 text-emerald-700',
        'url'         => 'bg-emerald-50 text-emerald-700',
        'uid'         => 'bg-emerald-50 text-emerald-700',
        'json'        => 'bg-slate-100 text-slate-600',
        'enumeration' => 'bg-orange-50 text-orange-700',
        'media'       => 'bg-pink-50 text-pink-700',
        'relation'    => 'bg-indigo-50 text-indigo-700',
        'component'   => 'bg-amber-50 text-amber-700',
        'dynamiczone' => 'bg-amber-50 text-amber-700',
        'repeater'    => 'bg-amber-50 text-amber-700',
        'password'    => 'bg-red-50 text-red-700',
    ];
@endphp

<div class="max-w-2xl space-y-5">

    {{-- Flash --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-xl px-4 py-3">
        {{ session('success') }}
    </div>
    @endif

    {{-- Context card --}}
    <div class="bg-white border border-slate-200 rounded-xl px-5 py-4">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center shrink-0">
                <svg class="w-4.5 h-4.5 text-blue-600 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-800">{{ $contentType['info']['displayName'] }}</p>
                <p class="text-xs text-slate-400 font-mono">{{ $uid }}</p>
            </div>
        </div>
        <p class="text-xs text-slate-500 mt-3">
            Choose which fields are returned in API responses. Unchecked fields are stripped before the response is sent.
            Leave all checked to expose everything (default).
        </p>
    </div>

    <form action="{{ route('talos.content-type-builder.api-settings.save', $uid) }}" method="POST">
        @csrf @method('PUT')

        {{-- System fields (always included) --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden mb-5">
            <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">System Fields</p>
                <p class="text-xs text-slate-400 mt-0.5">Always included — cannot be hidden</p>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach(['id' => 'integer', 'slug' => 'uid', 'created_at' => 'datetime', 'updated_at' => 'datetime'] as $field => $type)
                <div class="flex items-center gap-4 px-5 py-3 opacity-60">
                    <input type="checkbox" disabled checked
                           class="w-4 h-4 rounded border-slate-300 text-blue-600 cursor-not-allowed">
                    <div class="flex-1 flex items-center gap-3">
                        <span class="text-sm font-medium text-slate-700 font-mono">{{ $field }}</span>
                        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded {{ $typeColors[$type] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ $typeLabels[$type] ?? $type }}
                        </span>
                    </div>
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Custom fields --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Content Fields</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ count($attributes) }} field(s)</p>
                </div>
                <div class="flex gap-3 text-xs">
                    <button type="button" id="btn-check-all"
                            class="text-blue-600 hover:text-blue-500 transition-colors">Check all</button>
                    <span class="text-slate-300">·</span>
                    <button type="button" id="btn-uncheck-all"
                            class="text-slate-400 hover:text-slate-600 transition-colors">Uncheck all</button>
                </div>
            </div>

            @if(empty($attributes))
                <div class="px-5 py-8 text-center text-slate-400 text-sm">
                    No fields defined yet. Add fields in the Schema Builder first.
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($attributes as $fieldName => $def)
                    @php
                        $type    = $def['type'] ?? 'string';
                        $checked = $apiFields === null || in_array($fieldName, $apiFields);
                    @endphp
                    @if($def['private'] ?? false)
                    <div class="flex items-center gap-4 px-5 py-3 opacity-50">
                        <input type="checkbox" disabled
                               class="w-4 h-4 rounded border-slate-300 cursor-not-allowed">
                        <div class="flex-1 flex items-center gap-3 min-w-0">
                            <span class="text-sm font-medium text-slate-700 font-mono truncate">{{ $fieldName }}</span>
                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded shrink-0 {{ $typeColors[$type] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $typeLabels[$type] ?? $type }}
                            </span>
                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded shrink-0 bg-slate-100 text-slate-500">private — never exposed</span>
                        </div>
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    @else
                    <label class="flex items-center gap-4 px-5 py-3 cursor-pointer hover:bg-slate-50 transition-colors">
                        <input type="checkbox" name="fields[]" value="{{ $fieldName }}"
                               {{ $checked ? 'checked' : '' }}
                               class="field-checkbox w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                        <div class="flex-1 flex items-center gap-3 min-w-0">
                            <span class="text-sm font-medium text-slate-700 font-mono truncate">{{ $fieldName }}</span>
                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded shrink-0 {{ $typeColors[$type] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $typeLabels[$type] ?? $type }}
                            </span>
                            @if($def['required'] ?? false)
                                <span class="text-[10px] font-medium px-1.5 py-0.5 rounded shrink-0 bg-red-50 text-red-600">required</span>
                            @endif
                        </div>
                    </label>
                    @endif
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex items-center gap-3 mt-5">
            <button type="submit"
                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Save
            </button>
            <a href="{{ route('talos.content-type-builder.edit', $uid) }}"
               class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                Cancel
            </a>
            @if($apiFields !== null)
            <button type="button" id="btn-reset"
                    class="ml-auto text-xs text-slate-400 hover:text-slate-600 transition-colors">
                Reset to defaults (expose all)
            </button>
            @endif
        </div>
    </form>
</div>

<script>
    const checkboxes = document.querySelectorAll('.field-checkbox');

    document.getElementById('btn-check-all')?.addEventListener('click', () => {
        checkboxes.forEach(cb => cb.checked = true);
    });

    document.getElementById('btn-uncheck-all')?.addEventListener('click', () => {
        checkboxes.forEach(cb => cb.checked = false);
    });

    document.getElementById('btn-reset')?.addEventListener('click', async () => {
        if (!await talos.confirm('Reset API field settings? All fields will be exposed again.')) return;
        const form = document.querySelector('form');
        checkboxes.forEach(cb => cb.checked = false);
        form.submit();
    });
</script>
@endsection
