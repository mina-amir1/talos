@php $boolOn = $isEdit ? (bool)$value : (bool)($field['default'] ?? false); @endphp

<div x-data="{ on: {{ $boolOn ? 'true' : 'false' }} }">
    <input type="hidden" name="{{ $name }}" :value="on ? '1' : '0'">
    <button type="button" @click="on = !on" class="flex items-center gap-3">
        <div class="relative w-12 h-6 rounded-full transition-colors duration-200"
             :class="on ? 'bg-blue-600' : 'bg-slate-200'">
            <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                 :class="on ? 'translate-x-6' : 'translate-x-0'"></div>
        </div>
        <span class="text-sm font-semibold transition-colors"
              :class="on ? 'text-blue-600' : 'text-slate-400'"
              x-text="on ? 'True' : 'False'"></span>
    </button>
</div>
