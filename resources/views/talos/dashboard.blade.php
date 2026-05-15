@extends('talos.layouts.app')

@section('title', 'Dashboard — Talos CMS')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-8">
    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $stats = [
                ['label' => 'Content Types', 'value' => count($contentTypes), 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z'],
                ['label' => 'Components',    'value' => count($components),   'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                ['label' => 'Media Files',   'value' => $mediaCount,          'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ['label' => 'Admin Users',   'value' => $userCount,           'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ];
        @endphp

        @foreach($stats as $stat)
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">{{ $stat['value'] }}</p>
                        <p class="text-xs text-gray-500">{{ $stat['label'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Content Types --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800">
                <h2 class="text-white font-semibold">Content Types</h2>
                <a href="{{ route('talos.content-type-builder.create') }}"
                   class="text-sm text-blue-400 hover:text-blue-300 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New
                </a>
            </div>
            <div class="divide-y divide-gray-800">
                @forelse($contentTypes as $type)
                    <a href="{{ route('talos.content.index', ['uid' => $type['__uid']]) }}"
                       class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-800 transition-colors group">
                        <div>
                            <p class="text-sm font-medium text-white">{{ $type['info']['displayName'] }}</p>
                            <p class="text-xs text-gray-500">{{ $type['kind'] === 'collectionType' ? 'Collection Type' : 'Single Type' }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs bg-gray-800 group-hover:bg-gray-700 text-gray-400 px-2 py-0.5 rounded">
                                {{ count($type['attributes'] ?? []) }} fields
                            </span>
                            <svg class="w-4 h-4 text-gray-600 group-hover:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center">
                        <p class="text-gray-500 text-sm">No content types yet.</p>
                        <a href="{{ route('talos.content-type-builder.create') }}"
                           class="mt-2 inline-block text-blue-400 text-sm hover:underline">Create your first</a>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Components --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800">
                <h2 class="text-white font-semibold">Components</h2>
                <a href="{{ route('talos.components.create') }}"
                   class="text-sm text-blue-400 hover:text-blue-300 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New
                </a>
            </div>
            <div class="divide-y divide-gray-800">
                @forelse($components as $component)
                    <a href="{{ route('talos.components.edit', ['uid' => $component['__uid']]) }}"
                       class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-800 transition-colors group">
                        <div>
                            <p class="text-sm font-medium text-white">{{ $component['info']['displayName'] }}</p>
                            <p class="text-xs text-gray-500">{{ $component['__category'] }}</p>
                        </div>
                        <span class="text-xs bg-gray-800 group-hover:bg-gray-700 text-gray-400 px-2 py-0.5 rounded">
                            {{ count($component['attributes'] ?? []) }} fields
                        </span>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center">
                        <p class="text-gray-500 text-sm">No components yet.</p>
                        <a href="{{ route('talos.components.create') }}"
                           class="mt-2 inline-block text-blue-400 text-sm hover:underline">Create your first</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Quick Start --}}
    @if(count($contentTypes) === 0)
        <div class="bg-blue-900/20 border border-blue-700/50 rounded-xl p-6">
            <h3 class="text-white font-semibold mb-1">Welcome to Talos CMS</h3>
            <p class="text-gray-400 text-sm mb-4">
                Get started by creating your first content type. It defines the structure of your data — just like in Strapi.
            </p>
            <div class="flex gap-3">
                <a href="{{ route('talos.content-type-builder.create') }}"
                   class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-medium transition-colors">
                    Create Content Type
                </a>
                <a href="{{ route('talos.components.create') }}"
                   class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg text-sm font-medium transition-colors">
                    Create Component
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
