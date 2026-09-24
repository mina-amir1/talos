@php
    $isMultiple = $field['multiple'] ?? false;

    // Normalize stored value to an array of IDs.
    if ($isMultiple) {
        $fileIds = is_array($value) ? array_values(array_filter($value, 'is_numeric')) : [];
    } else {
        $fileIds = is_numeric($value) ? [(int) $value] : [];
    }

    $files = $fileIds
        ? \App\Models\TalosFile::whereIn('id', $fileIds)->get()->keyBy('id')
        : collect();
@endphp

{{-- Hidden input carries current file ID(s) so the form can remove them --}}
@if($isMultiple)
    <div x-data="{ ids: {{ json_encode($fileIds) }} }">
        <input type="hidden" name="{{ $name }}_id" :value="JSON.stringify(ids)">

        @if($files->isNotEmpty())
            <ul class="space-y-2 mb-3">
                @foreach($fileIds as $fid)
                    @php $f = $files->get($fid); @endphp
                    @if($f)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded flex items-center justify-center">
                                    <span class="text-blue-600 text-xs font-bold uppercase">{{ $f->ext }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm text-slate-700 font-medium truncate">{{ $f->original_name }}</p>
                                    <p class="text-xs text-slate-400">{{ $f->humanSize() }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <a href="{{ route('talos.files.download', $f->id) }}"
                                   class="px-3 py-1.5 text-xs bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg transition-colors">
                                    Download
                                </a>
                                <button type="button" @click="ids = ids.filter(i => i !== {{ $f->id }})"
                                        class="p-1.5 text-slate-400 hover:text-red-500 transition-colors" title="Remove">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
        @else
            <p class="text-sm text-slate-400 italic">No files uploaded.</p>
        @endif
    </div>

@else
    @php $f = $files->first(); @endphp
    <div x-data="{ id: {{ $f ? $f->id : 'null' }} }">
        <input type="hidden" name="{{ $name }}_id" :value="id">

        @if($f)
            <div class="flex items-center justify-between gap-3 px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded flex items-center justify-center">
                        <span class="text-blue-600 text-xs font-bold uppercase">{{ $f->ext }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-slate-700 font-medium truncate">{{ $f->original_name }}</p>
                        <p class="text-xs text-slate-400">{{ $f->humanSize() }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="{{ route('talos.files.download', $f->id) }}"
                       class="px-3 py-1.5 text-xs bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg transition-colors">
                        Download
                    </a>
                    <button type="button" @click="id = null"
                            class="p-1.5 text-slate-400 hover:text-red-500 transition-colors" title="Remove">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @else
            <p class="text-sm text-slate-400 italic">No file uploaded.</p>
        @endif
    </div>
@endif
