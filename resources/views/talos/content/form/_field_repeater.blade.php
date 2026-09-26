@php
    $subFields = $field['subFields'] ?? [];
    $repRaw    = is_array($value) ? $value : (is_string($value) && $value ? json_decode($value, true) : []);
    $repRows   = json_encode($repRaw ?? []);
    $repEmpty  = json_encode(collect($subFields)->mapWithKeys(fn($sf, $sn) => [$sn => $sf['default'] ?? ''])->all());
@endphp

<div x-data="repeaterField({{ $repRows }}, {{ $repEmpty }})">
    <input type="hidden" name="{{ $name }}" :value="JSON.stringify(rows)">
    @include('talos.content.form._repeater_rows_ui', ['attrs' => $subFields, 'depth' => 0])
</div>
