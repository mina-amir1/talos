<input type="number" name="{{ $name }}" value="{{ $value }}"
       {{ $required ? 'required' : '' }}
       step="{{ in_array($field['type'], ['decimal','float']) ? 'any' : '1' }}"
       min="{{ $field['min'] ?? '' }}" max="{{ $field['max'] ?? '' }}"
       class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
