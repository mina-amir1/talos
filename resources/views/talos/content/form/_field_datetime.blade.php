@if($field['type'] === 'datetime')
    <input type="datetime-local" name="{{ $name }}"
           value="{{ $value }}" {{ $required ? 'required' : '' }}
           class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
@else
    <input type="{{ $field['type'] }}" name="{{ $name }}"
           value="{{ $value }}" {{ $required ? 'required' : '' }}
           class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
@endif
