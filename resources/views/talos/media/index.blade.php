@extends('talos.layouts.app')

@section('title', 'Media Library — Talos')
@section('header', 'Media Library')

@section('content')
<div x-data="mediaLibrary()" class="space-y-5">
    {{-- Upload zone --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <div class="border-2 border-dashed border-gray-700 rounded-lg p-10 text-center"
             @dragover.prevent @drop.prevent="uploadFiles($event.dataTransfer.files)"
             :class="uploading ? 'border-blue-500 bg-blue-900/10' : 'hover:border-gray-600'">
            <svg class="w-10 h-10 mx-auto text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <p class="text-gray-400 text-sm font-medium mb-1">
                <span x-show="!uploading">Drag & drop files here or</span>
                <span x-show="uploading">Uploading…</span>
            </p>
            <label x-show="!uploading" class="cursor-pointer">
                <span class="text-blue-400 hover:text-blue-300 text-sm">click to browse</span>
                <input type="file" multiple class="sr-only" @change="uploadFiles($event.target.files)">
            </label>
            <div x-show="uploading" class="mt-2">
                <div class="h-1.5 bg-gray-700 rounded-full overflow-hidden max-w-xs mx-auto">
                    <div class="h-full bg-blue-500 rounded-full transition-all" :style="`width:${progress}%`"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex items-center gap-3">
        <form method="GET" class="flex items-center gap-2 flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name…"
                   class="flex-1 px-4 py-2 bg-gray-900 border border-gray-800 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
            <select name="type" class="px-4 py-2 bg-gray-900 border border-gray-800 rounded-lg text-gray-400 text-sm focus:outline-none focus:border-blue-500">
                <option value="">All types</option>
                <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>Images</option>
                <option value="other" {{ request('type') === 'other' ? 'selected' : '' }}>Files</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-medium">Search</button>
        </form>
        <span class="text-gray-500 text-sm">{{ $media->total() }} file(s)</span>
    </div>

    {{-- Grid --}}
    @if($media->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-gray-600 gap-3">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-sm">No files uploaded yet.</p>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($media as $file)
                <div class="group relative bg-gray-900 border border-gray-800 rounded-xl overflow-hidden hover:border-gray-700 transition-colors">
                    {{-- Preview --}}
                    @if($file->isImage())
                        <div class="aspect-square overflow-hidden">
                            <img src="{{ $file->url }}" alt="{{ $file->alternative_text ?? $file->name }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                        </div>
                    @else
                        <div class="aspect-square bg-gray-800 flex flex-col items-center justify-center gap-2">
                            <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-xs font-bold text-gray-500 uppercase">{{ $file->ext }}</span>
                        </div>
                    @endif

                    {{-- Info --}}
                    <div class="p-2">
                        <p class="text-xs text-gray-400 truncate" title="{{ $file->name }}">{{ $file->name }}</p>
                        <p class="text-xs text-gray-600">{{ $file->humanSize() }}</p>
                    </div>

                    {{-- Hover actions --}}
                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        <a href="{{ $file->url }}" target="_blank"
                           class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-lg flex items-center justify-center text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                        <button @click="deleteFile({{ $file->id }}, $el)"
                                class="w-8 h-8 bg-red-500/70 hover:bg-red-500 rounded-lg flex items-center justify-center text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        @if($media->hasPages())
            <div class="mt-4">{{ $media->links() }}</div>
        @endif
    @endif
</div>

<script>
function mediaLibrary() {
    return {
        uploading: false,
        progress: 0,

        async uploadFiles(files) {
            if (!files || files.length === 0) return;
            this.uploading = true;
            this.progress = 0;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const total = files.length;
            let done = 0;

            for (const file of files) {
                const form = new FormData();
                form.append('file', file);

                await fetch('{{ route('talos.media.upload') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: form,
                });

                done++;
                this.progress = Math.round((done / total) * 100);
            }

            this.uploading = false;
            window.location.reload();
        },

        async deleteFile(id, btn) {
            if (!confirm('Delete this file?')) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            const r = await fetch(`{{ url(config('talos.admin_prefix', 'talos') . '/media') }}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });

            if (r.ok) {
                btn.closest('.group').remove();
            }
        },
    };
}
</script>
@endsection
