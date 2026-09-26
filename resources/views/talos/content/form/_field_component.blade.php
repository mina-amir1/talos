@php
    $compUids     = $field['components'] ?? [];
    $firstUid     = $compUids[0] ?? null;
    $compSchema   = $firstUid ? ($componentMap[$firstUid] ?? null) : null;
    $isRepeatable = $field['repeatable'] ?? false;
@endphp

{{-- ── Repeatable component ── --}}
@if($isRepeatable && $compSchema)
    @php
        $repRaw   = is_array($value) ? $value : (is_string($value) && $value ? json_decode($value, true) : []);
        $repRows  = json_encode($repRaw ?? []);
        $repEmpty = json_encode(collect($compSchema['attributes'] ?? [])->mapWithKeys(fn($sf, $sn) => [$sn => $sf['default'] ?? ''])->all());
    @endphp
    <p class="text-xs text-slate-400 mb-3 font-mono">{{ $firstUid }} · repeatable</p>

    <div x-data="repeaterField({{ $repRows }}, {{ $repEmpty }})">
        <input type="hidden" name="{{ $name }}" :value="JSON.stringify(rows)">
        @include('talos.content.form._repeater_rows_ui', ['attrs' => $compSchema['attributes'] ?? [], 'depth' => 0])
    </div>

{{-- ── Single component ── --}}
@elseif(!$isRepeatable && $compSchema)
    @php
        $compRaw      = is_array($value) ? $value : (is_string($value) && $value ? json_decode($value, true) : null);
        $compDefaults = collect($compSchema['attributes'] ?? [])
            ->mapWithKeys(fn($sf, $sn) => [$sn => $sf['default'] ?? null])
            ->filter(fn($v) => $v !== null)
            ->all();
        $compJson = json_encode(array_merge($compDefaults, $compRaw ?? []));
    @endphp
    <p class="text-xs text-slate-400 mb-3 font-mono">{{ $firstUid }}</p>

    <div x-data="{ d: {{ $compJson }} }">
        <input type="hidden" name="{{ $name }}" :value="JSON.stringify(d)">
        <div class="space-y-4">
            @foreach($compSchema['attributes'] ?? [] as $subName => $subField)
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-2">
                        {{ $subField['displayName'] ?? ucwords(str_replace('_', ' ', $subName)) }}
                        <span class="text-slate-400 text-xs font-normal ml-1">({{ $subField['type'] }})</span>
                    </label>
                    @include('talos.content.form._field_subfield', [
                        'subName'  => $subName,
                        'subField' => $subField,
                        'bind'     => "d['{$subName}']",
                        'depth'    => 0,
                        'openExpr' => 'true',
                    ])
                </div>
            @endforeach
        </div>
    </div>

{{-- ── Not found ── --}}
@else
    <div class="p-4 bg-slate-100 rounded-lg border border-dashed border-slate-300">
        <p class="text-sm text-slate-400">
            @if($firstUid) Component "{{ $firstUid }}" not found. @else No component assigned. @endif
        </p>
    </div>
@endif
