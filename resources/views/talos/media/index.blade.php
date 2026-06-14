@extends('talos.layouts.app')

@section('title', 'Media Library — Talos')
@section('header', 'Media Library')

@section('content')
<div x-data="mediaLibrary()" class="flex gap-5" style="min-height: 70vh">

    {{-- ── Folder sidebar ──────────────────────────────────────────────── --}}
    <aside class="w-52 flex-shrink-0 space-y-0.5">

        <a href="{{ route('talos.media.index') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors
                  {{ $folder === '' ? 'bg-blue-700 text-white' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100' }}">
            <svg class="w-4 h-4 flex-shrink-0 text-amber-600" fill="currentColor" viewBox="0 0 24 24">
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
                      {{ $folder === $dir ? 'bg-blue-700 text-white' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100' }}"
               style="padding: 0.375rem 0.75rem; padding-left: {{ 0.75 + $depth * 1.25 }}rem">
                <svg class="w-4 h-4 flex-shrink-0 {{ $folder === $dir ? 'text-yellow-300' : 'text-amber-600/70' }}" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M10 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2h-8l-2-2z"/>
                </svg>
                <span class="truncate">{{ $name }}</span>
            </a>
        @endforeach

        {{-- New folder --}}
        <div class="pt-2 border-t border-slate-200 mt-2">
            <button @click="showNewFolder = !showNewFolder"
                    class="flex items-center gap-2 w-full px-3 py-2 rounded-lg text-xs text-slate-400 hover:text-slate-900 hover:bg-slate-100 transition-colors">
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
                       class="w-full px-3 py-1.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                <div class="flex gap-1">
                    <button type="submit"
                            class="flex-1 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-xs font-medium">Create</button>
                    <button type="button" @click="showNewFolder = false"
                            class="px-2 py-1 bg-slate-100 hover:bg-slate-100 text-slate-500 rounded text-xs">✕</button>
                </div>
            </form>
        </div>
    </aside>

    {{-- ── Main area ───────────────────────────────────────────────────── --}}
    <div class="flex-1 space-y-4 min-w-0">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-1 text-sm flex-wrap">
            <a href="{{ route('talos.media.index') }}" class="text-slate-400 hover:text-slate-900 transition-colors">Media</a>
            @if($folder)
                @php $parts = explode('/', $folder); $built = ''; @endphp
                @foreach($parts as $i => $part)
                    @php $built = $i === 0 ? $part : $built . '/' . $part; @endphp
                    <span class="text-gray-700">/</span>
                    <a href="{{ route('talos.media.index', ['path' => $built]) }}"
                       class="{{ $built === $folder ? 'text-slate-800 font-medium' : 'text-slate-400 hover:text-slate-900 transition-colors' }}">
                        {{ $part }}
                    </a>
                @endforeach

                <form action="{{ route('talos.media.folders.destroy') }}" method="POST"
                      class="ml-auto"
                      data-confirm="Delete folder '{{ addslashes(basename($folder)) }}'? Files inside will be moved to the parent.">
                    @csrf @method('DELETE')
                    <input type="hidden" name="path" value="{{ $folder }}">
                    <button type="submit" class="text-xs text-slate-400 hover:text-red-600 transition-colors">Delete folder</button>
                </form>
            @endif
        </div>

        {{-- Upload zone --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="relative border-2 border-dashed rounded-lg p-8 text-center transition-colors"
                 :class="dragging ? 'border-blue-500 bg-blue-50' : uploading ? 'border-blue-500 bg-blue-900/10' : 'border-slate-300 hover:border-slate-300'"
                 @dragenter.prevent="dragging = true"
                 @dragover.prevent="dragging = true"
                 @dragleave.prevent="dragging = false"
                 @drop.prevent="dragging = false; uploadFiles($event.dataTransfer.files)">

                {{-- Invisible full-size overlay during drag so children don't steal events --}}
                <div x-show="dragging" class="absolute inset-0 z-10 rounded-lg"></div>

                <svg class="w-8 h-8 mx-auto text-slate-400 mb-2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="text-slate-500 text-sm mb-1 pointer-events-none">
                    <span x-show="dragging" x-cloak class="text-blue-500 font-medium">Drop files here</span>
                    <span x-show="!dragging && !uploading">Drag & drop or
                        <label class="cursor-pointer text-blue-600 hover:text-blue-500 pointer-events-auto">
                            click to browse
                            <input type="file" multiple class="sr-only" data-no-dirty @change="uploadFiles($event.target.files)">
                        </label>
                    </span>
                    <span x-show="uploading" x-cloak>Uploading… <span x-text="progress + '%'"></span></span>
                </p>
                <p class="text-xs text-slate-400 pointer-events-none">Images are automatically converted to WebP (SVGs kept as-is) · uploading to <span class="font-mono text-slate-400">{{ $folder ?: 'root' }}</span></p>
                <div x-show="uploading" x-cloak class="mt-2 h-1.5 bg-slate-200 rounded-full overflow-hidden max-w-xs mx-auto">
                    <div class="h-full bg-blue-500 rounded-full transition-all" :style="`width:${progress}%`"></div>
                </div>
            </div>
        </div>

        {{-- Search / filter --}}
        <div class="flex items-center gap-3">
            <form method="GET" class="flex items-center gap-2 flex-1">
                @if($folder) <input type="hidden" name="path" value="{{ $folder }}"> @endif
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name…"
                       class="flex-1 px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                <select name="type" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-500 text-sm focus:outline-none focus:border-blue-500">
                    <option value="">All types</option>
                    <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>Images</option>
                    <option value="other"  {{ request('type') === 'other'  ? 'selected' : '' }}>Files</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-medium">Search</button>
            </form>
            <span class="text-slate-400 text-sm flex-shrink-0">{{ $media->total() }} file(s)</span>
        </div>

        {{-- Sub-folder tiles --}}
        @if(count($folders) > 0)
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3">
                @foreach($folders as $f)
                    <div class="group relative flex flex-col items-center gap-1.5 p-3 bg-white border border-slate-200 hover:border-slate-300 rounded-xl transition-colors">
                        <a href="{{ route('talos.media.index', ['path' => $f['path']]) }}" class="flex flex-col items-center gap-1.5 w-full">
                            <svg class="w-10 h-10 text-amber-600/80 group-hover:text-amber-600 transition-colors" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M10 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2h-8l-2-2z"/>
                            </svg>
                            <p class="text-xs text-slate-600 truncate w-full text-center">{{ $f['name'] }}</p>
                            <p class="text-xs text-slate-400">{{ $f['count'] }} file(s)</p>
                        </a>
                        <form action="{{ route('talos.media.folders.destroy') }}" method="POST"
                              data-confirm="Delete folder '{{ addslashes($f['name']) }}' and all its contents? This cannot be undone.">
                            @csrf @method('DELETE')
                            <input type="hidden" name="path" value="{{ $f['path'] }}">
                            <button type="submit"
                                    class="absolute top-1.5 right-1.5 w-6 h-6 bg-red-500/0 hover:bg-red-500/80 rounded flex items-center justify-center text-gray-700 hover:text-slate-900 opacity-0 group-hover:opacity-100 transition-all"
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
            @if($media->isNotEmpty())<div class="border-t border-slate-200"></div>@endif
        @endif

        {{-- Bulk action bar --}}
        <div x-show="selected.length > 0" x-cloak
             class="flex items-center gap-3 px-4 py-2.5 bg-blue-50 border border-blue-200 rounded-xl text-sm">
            <span class="text-blue-500 font-medium" x-text="selected.length + ' selected'"></span>
            <button type="button" @click="selectAll()"
                    class="text-xs text-slate-500 hover:text-slate-900 transition-colors">Select all</button>
            <button type="button" @click="selected = []"
                    class="text-xs text-slate-500 hover:text-slate-900 transition-colors">Clear</button>
            <div class="ml-auto flex items-center gap-2">
                {{-- Bulk move --}}
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-medium transition-colors">
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
                         class="absolute right-0 mt-1 w-48 bg-white border border-slate-300 rounded-xl shadow-xl z-20 py-1">
                        <button type="button" @click="bulkMove(''); open = false"
                                class="w-full text-left px-3 py-1.5 text-xs text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors">
                            / Root
                        </button>
                        @foreach($allDirs as $dir)
                            <button type="button" @click="bulkMove('{{ addslashes($dir) }}'); open = false"
                                    class="w-full text-left px-3 py-1.5 text-xs text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors"
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
            <div x-show="!newFiles.length"
                 class="flex flex-col items-center justify-center py-16 text-slate-400 gap-3">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm">This folder is empty. Upload something or create a subfolder.</p>
            </div>
        @endif
        @if($media->isNotEmpty() || true)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4" id="file-grid"
                 x-show="{{ $media->isNotEmpty() ? 'true' : 'false' }} || newFiles.length > 0">
                @foreach($media as $file)
                    <div class="group relative bg-white border border-slate-200 rounded-xl overflow-hidden transition-colors cursor-pointer"
                         :class="selected.includes({{ $file->id }}) ? 'border-blue-500 ring-2 ring-blue-500/40' : 'hover:border-slate-300'"
                         x-data="{ moving: false }"
                         @click="toggleSelect({{ $file->id }})"
                         @if($file->status === 'converting') data-converting-id="{{ $file->id }}" @endif>

                        {{-- Checkbox --}}
                        <div class="absolute top-2 left-2 z-10"
                             :class="selected.length > 0 ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
                             @click.stop>
                            <input type="checkbox" :checked="selected.includes({{ $file->id }})"
                                   @change="toggleSelect({{ $file->id }})"
                                   data-no-dirty
                                   class="w-4 h-4 rounded border-slate-300 bg-white text-blue-600 cursor-pointer">
                        </div>

                        @if($file->status === 'converting')
                            <div data-badge="converting"
                                 class="absolute top-2 right-2 z-10 flex items-center gap-1 bg-amber-500 text-white text-[10px] font-semibold px-1.5 py-0.5 rounded-full">
                                <svg class="w-2.5 h-2.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                Converting
                            </div>
                        @endif

                        @if($file->isImage())
                            <div data-img-wrapper
                                 class="aspect-square overflow-hidden {{ $file->status === 'converting' ? 'opacity-60' : '' }}">
                                <img src="{{ $file->url }}" alt="{{ $file->name }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                            </div>
                        @else
                            <div class="aspect-square bg-slate-100 flex flex-col items-center justify-center gap-2">
                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="text-xs font-bold text-slate-400 uppercase">{{ $file->ext }}</span>
                            </div>
                        @endif

                        <div class="p-2">
                            <p class="text-xs text-slate-500 truncate" title="{{ $file->name }}">{{ $file->name }}</p>
                            <p class="text-xs text-slate-400">{{ $file->humanSize() }}</p>
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
                             class="absolute inset-x-0 bottom-0 bg-white border border-slate-300 rounded-b-xl p-2 z-10 shadow-xl"
                             @click.stop>
                            <p class="text-xs text-slate-400 mb-1.5 font-medium">Move to…</p>
                            <div class="space-y-0.5 max-h-36 overflow-y-auto">
                                <button type="button" @click="moveFile({{ $file->id }}, ''); moving = false"
                                        class="w-full text-left px-2 py-1 text-xs rounded transition-colors
                                               {{ !$file->folder ? 'text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100' }}">
                                    / Root
                                </button>
                                @foreach($allDirs as $dir)
                                    <button type="button" @click="moveFile({{ $file->id }}, '{{ addslashes($dir) }}'); moving = false"
                                            class="w-full text-left px-2 py-1 text-xs rounded transition-colors
                                                   {{ $file->folder === $dir ? 'text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100' }}"
                                            style="padding-left: {{ 0.5 + substr_count($dir, '/') * 1 }}rem">
                                        {{ str_repeat('· ', substr_count($dir, '/')) }}{{ basename($dir) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Newly uploaded files (no page reload) --}}
                <template x-for="f in newFiles" :key="f.id">
                    <div class="group relative bg-white border border-slate-200 rounded-xl overflow-hidden transition-colors cursor-pointer"
                         :class="selected.includes(f.id) ? 'border-blue-500 ring-2 ring-blue-500/40' : 'hover:border-slate-300'"
                         @click="toggleSelect(f.id)">

                        <div class="absolute top-2 left-2 z-10"
                             :class="selected.length > 0 ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
                             @click.stop>
                            <input type="checkbox" :checked="selected.includes(f.id)"
                                   @change="toggleSelect(f.id)"
                                   class="w-4 h-4 rounded border-slate-300 bg-white text-blue-600 cursor-pointer">
                        </div>

                        <template x-if="f.status === 'converting'">
                            <div class="absolute top-2 right-2 z-10 flex items-center gap-1 bg-amber-500 text-white text-[10px] font-semibold px-1.5 py-0.5 rounded-full">
                                <svg class="w-2.5 h-2.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                Converting
                            </div>
                        </template>

                        <template x-if="f.isImage">
                            <div class="aspect-square overflow-hidden" :class="f.status === 'converting' ? 'opacity-60' : ''">
                                <img :src="f.url" :alt="f.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                            </div>
                        </template>
                        <template x-if="!f.isImage">
                            <div class="aspect-square bg-slate-100 flex flex-col items-center justify-center gap-2">
                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="text-xs font-bold text-slate-400 uppercase" x-text="f.ext"></span>
                            </div>
                        </template>

                        <div class="p-2">
                            <p class="text-xs text-slate-500 truncate" x-text="f.name"></p>
                            <p class="text-xs text-slate-400" x-text="f.size_human"></p>
                        </div>

                        <div x-show="selected.length === 0"
                             class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2"
                             @click.stop>
                            <a :href="f.url" target="_blank"
                               class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-lg flex items-center justify-center text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                            <button type="button" @click="deleteFile(f.id, $el)"
                                    class="w-8 h-8 bg-red-500/70 hover:bg-red-500 rounded-lg flex items-center justify-center text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
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
        newFiles:      [],

        humanSize(b) {
            if (b >= 1048576) return (b / 1048576).toFixed(2) + ' MB';
            if (b >= 1024)    return (b / 1024).toFixed(2)    + ' KB';
            return b + ' B';
        },

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
                const fd = new FormData();
                fd.append('file', file);
                fd.append('folder', this.currentFolder);

                const r = await fetch('{{ route('talos.media.upload') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf },
                    body: fd,
                });
                const j = await r.json();

                if (j.data) {
                    const m = j.data;
                    if (!this.allIds.includes(m.id)) {
                        this.newFiles.unshift({
                            id:        m.id,
                            name:      m.name,
                            url:       m.url,
                            ext:       m.ext || '',
                            isImage:   (m.mime_type || '').startsWith('image/'),
                            status:    m.status || 'ready',
                            size_human: this.humanSize(m.size || 0),
                        });
                        this.allIds.unshift(m.id);
                        if ((m.status || 'ready') === 'converting') this.pollConverting();
                    }
                }

                done++;
                this.progress = Math.round((done / total) * 100);
            }

            this.uploading = false;
        },

        async deleteFile(id, card) {
            if (!await talos.confirm('Delete this file?')) return;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const r = await fetch(`{{ url(config('talos.admin_prefix', 'talos') . '/media') }}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            if (r.ok) {
                const newIdx = this.newFiles.findIndex(f => f.id === id);
                if (newIdx !== -1) this.newFiles.splice(newIdx, 1);
                else card.remove();
                this.selected = this.selected.filter(s => s !== id);
                this.allIds   = this.allIds.filter(s => s !== id);
            }
        },

        async bulkDelete() {
            if (!this.selected.length) return;
            if (!await talos.confirm(`Delete ${this.selected.length} file(s)? This cannot be undone.`)) return;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            await Promise.all(this.selected.map(id =>
                fetch(`{{ url(config('talos.admin_prefix', 'talos') . '/media') }}/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                })
            ));
            talos.markClean(); window.location.reload();
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
            talos.markClean(); window.location.reload();
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
            talos.markClean(); window.location.reload();
        },

        init() {
            this.pollConverting();
        },

        pollConverting() {
            if (this._polling) return;
            this._polling = true;
            const base    = '{{ url(config('talos.admin_prefix', 'talos') . '/media') }}';
            const started = Date.now();
            const maxWait = 5 * 60 * 1000;

            const tick = async () => {
                const cards         = [...document.querySelectorAll('[data-converting-id]')];
                const newConverting = this.newFiles.filter(f => f.status === 'converting');

                if ((!cards.length && !newConverting.length) || Date.now() - started > maxWait) {
                    this._polling = false;
                    return;
                }

                await Promise.all(cards.map(async card => {
                    const id = card.dataset.convertingId;
                    try {
                        const res  = await fetch(`${base}/${id}`, { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data.data?.status === 'ready') {
                            card.querySelector('[data-badge="converting"]')?.remove();
                            card.querySelector('[data-img-wrapper]')?.classList.remove('opacity-60');
                            const img = card.querySelector('img');
                            if (img && data.data.url) img.src = data.data.url + '?v=' + Date.now();
                            delete card.dataset.convertingId;
                        }
                    } catch {}
                }));

                await Promise.all(newConverting.map(async f => {
                    try {
                        const res  = await fetch(`${base}/${f.id}`, { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data.data?.status === 'ready') {
                            const idx = this.newFiles.findIndex(n => n.id === f.id);
                            if (idx !== -1) {
                                this.newFiles[idx] = {
                                    ...this.newFiles[idx],
                                    status: 'ready',
                                    url: data.data.url || this.newFiles[idx].url,
                                };
                            }
                        }
                    } catch {}
                }));

                setTimeout(tick, 3000);
            };

            setTimeout(tick, 3000);
        },
    };
}
</script>
@endpush

@endsection
