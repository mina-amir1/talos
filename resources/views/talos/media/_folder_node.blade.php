@php
    $children = $allFolders->where('parent_id', $folder->id);
    $isActive = $folderId === $folder->id;
@endphp

<div>
    <a href="{{ route('talos.media.index', ['folder' => $folder->id]) }}"
       class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition-colors
              {{ $isActive ? 'bg-blue-700 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}"
       style="padding-left: {{ 0.75 + $depth * 1 }}rem">
        <svg class="w-4 h-4 flex-shrink-0 {{ $isActive ? 'text-yellow-300' : 'text-yellow-500/70' }}" fill="currentColor" viewBox="0 0 24 24">
            <path d="M10 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2h-8l-2-2z"/>
        </svg>
        <span class="truncate">{{ $folder->name }}</span>
    </a>

    @if($children->isNotEmpty())
        <div class="ml-2">
            @foreach($children as $child)
                @include('talos.media._folder_node', [
                    'folder'     => $child,
                    'allFolders' => $allFolders,
                    'folderId'   => $folderId,
                    'depth'      => $depth + 1,
                ])
            @endforeach
        </div>
    @endif
</div>
