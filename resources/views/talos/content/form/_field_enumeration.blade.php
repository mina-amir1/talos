@php
    $eOpts  = $enumOpts($field);
    $eMulti = !empty($field['multiple']);
@endphp

@if($eMulti)
    @php $eSel = is_array($value) ? $value : ($value ? [$value] : []); @endphp
    <div x-data="enumPicker({{ json_encode($eOpts) }}, {{ json_encode($eSel) }})">
        <template x-for="v in selected" :key="v">
            <input type="hidden" name="{{ $name }}[]" :value="v">
        </template>

        <div class="flex flex-wrap gap-1.5 mb-2" x-show="selected.length > 0">
            <template x-for="v in selected" :key="'c'+v">
                <span class="inline-flex items-center gap-1 pl-2.5 pr-1.5 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                    <span x-text="v"></span>
                    <button type="button" @click.stop="toggle(v)"
                            class="w-4 h-4 flex items-center justify-center rounded-full hover:bg-purple-200 transition-colors">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </span>
            </template>
        </div>

        <div class="border border-slate-200 rounded-xl overflow-hidden divide-y divide-slate-100">
            <template x-for="opt in opts" :key="opt">
                <div class="flex items-center gap-3 px-4 py-2.5 cursor-pointer transition-colors select-none"
                     :class="selected.includes(opt) ? 'bg-purple-50 hover:bg-purple-50/80' : 'bg-white hover:bg-slate-50'"
                     @click="toggle(opt)">
                    <div class="w-4 h-4 rounded flex items-center justify-center flex-shrink-0 border transition-all"
                         :class="selected.includes(opt) ? 'bg-purple-600 border-purple-600' : 'border-slate-300 bg-white'">
                        <svg x-show="selected.includes(opt)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="text-sm text-slate-700" x-text="opt"></span>
                </div>
            </template>
        </div>

        <p class="text-xs text-slate-400 mt-2"
           x-text="selected.length ? selected.length + ' selected' : 'None selected'"></p>
    </div>
@else
    <select name="{{ $name }}" {{ $required ? 'required' : '' }}
            class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
        @if(!$required)<option value="">— Select —</option>@endif
        @foreach($eOpts as $opt)
            <option value="{{ $opt }}" {{ $value === $opt ? 'selected' : '' }}>{{ $opt }}</option>
        @endforeach
    </select>
@endif
