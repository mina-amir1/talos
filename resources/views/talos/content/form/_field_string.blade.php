<input type="{{ $field['type'] === 'email' ? 'email' : ($field['type'] === 'url' ? 'url' : 'text') }}"
       name="{{ $name }}" value="{{ $value }}"
       {{ $required ? 'required' : '' }}
       maxlength="{{ $field['maxLength'] ?? 255 }}"
       class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
