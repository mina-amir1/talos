<textarea name="{{ $name }}" rows="5" {{ $required ? 'required' : '' }}
          class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm font-mono focus:outline-none focus:border-blue-500 resize-y">{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value }}</textarea>
<p class="text-xs text-slate-400 mt-1">Must be valid JSON</p>
