@extends('talos.layouts.app')

@section('title', 'Media Library — Talos')
@section('header', 'Media Library')

@section('content')
<div x-data="mediaLibrary()" class="flex gap-5" style="min-height: 70vh">

    {{-- ── Folder sidebar ──────────────────────────────────────────────── --}}
    <aside class="w-52 flex-shrink-0 space-y-0.5">

        <a href="{{ route('talos.media.index') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors
                  {{ $folder === '' ? 'bg-blue-700 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <svg class="w-4 h-4 flex-shrink-0 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M10 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2h-8l-2-2z"/>
            </svg>
            All assets
        </a>

        {{-- Directory tree --}}
        @foreach($allDirs as $dir)
            @php
                $depth = substr_count($dir, '/');
                $name  = basename($dir);
            @endphp
            <a href="{{ route('talos.media.index', ['path' => $dir]) }}"
               class="flex items-center gap-2 rounded-lg text-sm transition-colors
                      {{ $folder === $dir ? 'bg-blue-700 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}"
               style="padding: 0.375rem 0.75rem; padding-left: {{ 0.75 + $depth * 1.25 }}rem">
                <svg class="w-4 h-4 flex-shrink-0 {{ $folder === $dir ? 'text-yellow-300' : 'text-yellow-500/70' }}" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M10 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2h-8l-2-2z"/>
                </svg>
                <span class="truncate">{{ $name }}</span>
            </a>
        @endforeach

        {{-- New folder --}}
        <div class="pt-2 border-t border-gray-800 mt-2">
            <button @click="showNewFolder = !showNewFolder"
                    class="flex items-center gap-2 w-full px-3 py-2 rounded-lg text-xs text-gray-500 hover:text-white hover:bg-gray-800 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New folder
            </button>

            <form x-show="showNewFolder" x-cloak
                  action="{{ route('talos.media.folders.store') }}" method="POST"
                  class="mt-1 px-2 space-y-1.5">
                @csrf
                <input type="hidden" name="parent" value="{{ $folder }}">
                <input type="text" name="name" required placeholder="Folder name"
                       x-ref="folderInput" @focus="$refs.folderInput.select()"
                       class="w-full px-3 py-1.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500">
                <div class="flex gap-1">
                    <button type="submit"
                            class="flex-1 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-xs font-medium">Create</button>
                    <button type="button" @click="showNewFolder = false"
                            class="px-2 py-1 bg-gray-800 hover:bg-gray-700 text-gray-400 rounded text-xs">✕</button>
                </div>
            </form>
        </div>
    </aside>

    {{-- ── Main area ───────────────────────────────────────────────────── --}}
    <div class="flex-1 space-y-4 min-w-0">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-1 text-sm flex-wrap">
            <a href="{{ route('talos.media.index') }}" class="text-gray-500 hover:text-white transition-colors">Media</a>
            @if($folder)
                @php $parts = explode('/', $folder); $built = ''; @endphp
                @foreach($parts as $i => $part)
                    @php $built = $i === 0 ? $part : $built . '/' . $part; @endphp
                    <span class="text-gray-700">/</span>
                    <a href="{{ route('talos.media.index', ['path' => $built]) }}"
                       class="{{ $built === $folder ? 'text-white font-medium' : 'text-gray-500 hover:text-white transition-colors' }}">
                        {{ $part }}
                    </a>
                @endforeach

                <form action="{{ route('talos.media.folders.destroy') }}" method="POST"
                      class="ml-auto"
                      onsubmit="return confirm('Delete folder \'{{ addslashes(basename($folder)) }}\'? Files inside will be moved to the parent.')">
                    @csrf @method('DELETE')
                    <input type="hidden" name="path" value="{{ $folder }}">
                    <button type="submit" class="text-xs text-gray-600 hover:text-red-400 transition-colors">Delete folder</button>
                </form>
            @endif
        </div>

        {{-- Upload zone --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="relative border-2 border-dashed rounded-lg p-8 text-center transition-colors"
                 :class="dragging ? 'border-blue-500 bg-blue-900/20' : uploading ? 'border-blue-500 bg-blue-900/10' : 'border-gray-700 hover:border-gray-600'"
                 @dragenter.prevent="dragging = true"
                 @dragover.prevent="dragging = true"
                 @dragleave.prevent="dragging = false"
                 @drop.prevent="dragging = false; uploadFiles($event.dataTransfer.files)">

                {{-- Invisible full-size overlay during drag so children don't steal events --}}
                <div x-show="dragging" class="absolute inset-0 z-10 rounded-lg"></div>

                <svg class="w-8 h-8 mx-auto text-gray-600 mb-2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="text-gray-400 text-sm mb-1 pointer-events-none">
                    <span x-show="dragging" x-cloak class="text-blue-300 font-medium">Drop files here</span>
                    <span x-show="!dragging && !uploading">Drag & drop or
                        <label class="cursor-pointer text-blue-400 hover:text-blue-300 pointer-events-auto">
                            click to browse
                            <input type="file" multiple class="sr-only" @change="uploadFiles($event.target.files)">
                        </label>
                    </span>
                    <span x-show="uploading" x-cloak>Uploading… <span x-text="progress + '%'"></span></span>
                </p>
                <p class="text-xs text-gray-600 pointer-events-none">Images are automatically converted to WebP · uploading to <span class="font-mono text-gray-500">{{ $folder ?: 'root' }}</span></p>
                <div x-show="uploading" x-cloak class="mt-2 h-1.5 bg-gray-700 rounded-full overflow-hidden max-w-xs mx-auto">
                    <div class="h-full bg-blue-500 rounded-full transition-all" :style="`width:${progress}%`"></div>
                </div>
            </div>
        </div>

        {{-- Search / filter --}}
        <div class="flex items-center gap-3">
            <form method="GET" class="flex items-center gap-2 flex-1">
                @if($folder) <input type="hidden" name="path" value="{{ $folder }}"> @endif
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name…"
                       class="flex-1 px-4 py-2 bg-gray-900 border border-gray-800 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                <select name="type" class="px-4 py-2 bg-gray-900 border border-gray-800 rounded-lg text-gray-400 text-sm focus:outline-none focus:border-blue-500">
                    <option value="">All types</option>
                    <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>Images</option>
                    <option value="other"  {{ request('type') === 'other'  ? 'selected' : '' }}>Files</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-medium">Search</button>
            </form>
            <span class="text-gray-500 text-sm flex-shrink-0">{{ $media->total() }} file(s)</span>
        </div>

        {{-- Sub-folder tiles --}}
        @if(count($folders) > 0)
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3">
                @foreach($folders as $f)
                    <div class="group relative flex flex-col items-center gap-1.5 p-3 bg-gray-900 border border-gray-800 hover:border-gray-700 rounded-xl transition-colors">
                        <a href="{{ route('talos.media.index', ['path' => $f['path']]) }}" class="flex flex-col items-center gap-1.5 w-full">
                            <svg class="w-10 h-10 text-yellow-500/80 group-hover:text-yellow-400 transition-colors" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M10 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2h-8l-2-2z"/>
                            </svg>
                            <p class="text-xs text-gray-300 truncate w-full text-center">{{ $f['name'] }}</p>
                            <p class="text-xs text-gray-600">{{ $f['count'] }} file(s)</p>
                        </a>
                        <form action="{{ route('talos.media.folders.destroy') }}" method="POST"
                              onsubmit="return confirm('Delete folder \'{{ addslashes($f['name']) }}\' and all its contents? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <input type="hidden" name="path" value="{{ $f['path'] }}">
                            <button type="submit"
                                    class="absolute top-1.5 right-1.5 w-6 h-6 bg-red-500/0 hover:bg-red-500/80 rounded flex items-center justify-center text-gray-700 hover:text-white opacity-0 group-hover:opacity-100 transition-all"
                                    title="Delete folder">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
            @if($media->isNotEmpty())<div class="border-t border-gray-800"></div>@endif
        @endif

        {{-- Bulk action bar --}}
        <div x-show="selected.length > 0" x-cloak
             class="flex items-center gap-3 px-4 py-2.5 bg-blue-900/40 border border-blue-700/50 rounded-xl text-sm">
            <span class="text-blue-300 font-medium" x-text="selected.length + ' selected'"></span>
            <button type="button" @click="selectAll()"
                    class="text-xs text-gray-400 hover:text-white transition-colors">Select all</button>
            <button type="button" @click="selected = []"
                    class="text-xs text-gray-400 hover:text-white transition-colors">Clear</button>
            <div class="ml-auto flex items-center gap-2">
                {{-- Bulk move --}}
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-xs font-medium transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7h12m0 0l-4-4m4 4l-4 4m0 5H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        Move to
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false"
                         class="absolute right-0 mt-1 w-48 bg-gray-900 border border-gray-700 rounded-xl shadow-xl z-20 py-1">
                        <button type="button" @click="bulkMove(''); open = false"
                                class="w-full text-left px-3 py-1.5 text-xs text-gray-400 hover:text-white hover:bg-gray-800 transition-colors">
                            / Root
                        </button>
                        @foreach($allDirs as $dir)
                            <button type="button" @click="bulkMove('{{ addslashes($dir) }}'); open = false"
                                    class="w-full text-left px-3 py-1.5 text-xs text-gray-400 hover:text-white hover:bg-gray-800 transition-colors"
                                    style="padding-left: {{ 0.75 + substr_count($dir, '/') * 0.75 }}rem">
                                {{ str_repeat('· ', substr_count($dir, '/')) }}{{ basename($dir) }}
                            </button>
                        @endforeach
                    </div>
                </div>
                {{-- Bulk delete --}}
                <button type="button" @click="bulkDelete()"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-500 text-white rounded-lg text-xs font-medium transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete
                </button>
            </div>
        </div>

        {{-- File grid --}}
        @if($media->isEmpty() && count($folders) === 0)
            <div class="flex flex-col items-center justify-center py-16 text-gray-600 gap-3">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm">This folder is empty. Upload something or create a subfolder.</p>
            </div>
        @elseif($media->isNotEmpty())
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4" id="file-grid">
                @foreach($media as $file)
                    <div class="group relative bg-gray-900 border border-gray-800 rounded-xl overflow-hidden transition-colors cursor-pointer"
                         :class="selected.includes({{ $file->id }}) ? 'border-blue-500 ring-2 ring-blue-500/40' : 'hover:border-gray-700'"
                         x-data="{ moving: false }"
                         @click="toggleSelect({{ $file->id }})">

                        {{-- Checkbox --}}
                        <div class="absolute top-2 left-2 z-10"
                             :class="selected.length > 0 ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
                             @click.stop>
                            <input type="checkbox" :checked="selected.includes({{ $file->id }})"
                                   @change="toggleSelect({{ $file->id }})"
                                   class="w-4 h-4 rounded border-gray-600 bg-gray-800 text-blue-500 cursor-pointer">
                        </div>

                        @if($file->isImage())
                            <div class="aspect-square overflow-hidden">
                                <img src="{{ $file->url }}" alt="{{ $file->name }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                            </div>
                        @else
                            <div class="aspect-square bg-gray-800 flex flex-col items-center justify-center gap-2">
                                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="text-xs font-bold text-gray-500 uppercase">{{ $file->ext }}</span>
                            </div>
                        @endif

                        <div class="p-2">
                            <p class="text-xs text-gray-400 truncate" title="{{ $file->name }}">{{ $file->name }}</p>
                            <p class="text-xs text-gray-600">{{ $file->humanSize() }}</p>
                        </div>

                        {{-- Hover overlay (single-file actions, hidden in multi-select mode) --}}
                        <div x-show="selected.length === 0"
                             class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2"
                             @click.stop>
                            <a href="{{ $file->url }}" target="_blank"
                               class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-lg flex items-center justify-center text-white" title="Open">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                            <button type="button" @click="moving = !moving"
                                    class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-lg flex items-center justify-center text-white" title="Move">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 5H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                            </button>
                            <button type="button" @click="deleteFile({{ $file->id }}, $el.closest('[x-data]'))"
                                    class="w-8 h-8 bg-red-500/70 hover:bg-red-500 rounded-lg flex items-center justify-center text-white" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Single-file move popover --}}
                        <div x-show="moving" x-cloak @click.outside="moving = false"
                             class="absolute inset-x-0 bottom-0 bg-gray-900 border border-gray-700 rounded-b-xl p-2 z-10 shadow-xl"
                             @click.stop>
                            <p class="text-xs text-gray-500 mb-1.5 font-medium">Move to…</p>
                            <div class="space-y-0.5 max-h-36 overflow-y-auto">
                                <button type="button" @click="moveFile({{ $file->id }}, ''); moving = false"
                                        class="w-full text-left px-2 py-1 text-xs rounded transition-colors
                                               {{ !$file->folder ? 'text-blue-400' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                                    / Root
                                </button>
                                @foreach($allDirs as $dir)
                                    <button type="button" @click="moveFile({{ $file->id }}, '{{ addslashes($dir) }}'); moving = false"
                                            class="w-full text-left px-2 py-1 text-xs rounded transition-colors
                                                   {{ $file->folder === $dir ? 'text-blue-400' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}"
                                            style="padding-left: {{ 0.5 + substr_count($dir, '/') * 1 }}rem">
                                        {{ str_repeat('· ', substr_count($dir, '/')) }}{{ basename($dir) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($media->hasPages())
                <div class="mt-4">{{ $media->links() }}</div>
            @endif
        @endif
    </div>
</div>

@push('scripts')
<script>
function mediaLibrary() {
    return {
        uploading:     false,
        dragging:      false,
        progress:      0,
        showNewFolder: false,
        currentFolder: '{{ $folder }}',
        selected:      [],
        allIds:        @json($media->pluck('id')),

        toggleSelect(id) {
            const idx = this.selected.indexOf(id);
            idx === -1 ? this.selected.push(id) : this.selected.splice(idx, 1);
        },

        selectAll() {
            this.selected = [...this.allIds];
        },

        async uploadFiles(files) {
            if (!files || files.length === 0) return;
            this.uploading = true;
            this.progress  = 0;
            const csrf  = document.querySelector('meta[name="csrf-token"]').content;
            const total = files.length;
            let done    = 0;

            for (const file of files) {
                const form = new FormData();
                form.append('file', file);
                form.append('folder', this.currentFolder);

                await fetch('{{ route('talos.media.upload') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf },
                    body: form,
                });

                done++;
                this.progress = Math.round((done / total) * 100);
            }

            this.uploading = false;
            window.location.reload();
        },

        async deleteFile(id, card) {
            if (!confirm('Delete this file?')) return;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const r = await fetch(`{{ url(config('talos.admin_prefix', 'talos') . '/media') }}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            if (r.ok) card.remove();
        },

        async bulkDelete() {
            if (!this.selected.length) return;
            if (!confirm(`Delete ${this.selected.length} file(s)? This cannot be undone.`)) return;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            await Promise.all(this.selected.map(id =>
                fetch(`{{ url(config('talos.admin_prefix', 'talos') . '/media') }}/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                })
            ));
            window.location.reload();
        },

        async moveFile(id, folder) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            await fetch(`{{ url(config('talos.admin_prefix', 'talos') . '/media') }}/${id}/move`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ folder }),
            });
            window.location.reload();
        },

        async bulkMove(folder) {
            if (!this.selected.length) return;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            await Promise.all(this.selected.map(id =>
                fetch(`{{ url(config('talos.admin_prefix', 'talos') . '/media') }}/${id}/move`, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ folder }),
                })
            ));
            window.location.reload();
        },
    };
}
</script>
@endpush

@endsection
