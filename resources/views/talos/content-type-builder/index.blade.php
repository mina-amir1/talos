@extends('talos.layouts.app')

@section('title', 'Content-Type Builder — Talos')
@section('header', 'Content-Type Builder')

@section('header-actions')
    <a href="{{ route('talos.content-type-builder.create') }}"
       class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Create new type
    </a>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Collection Types --}}
    <div>
        <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Collection Types</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($contentTypes as $type)
                @if($type['kind'] === 'collectionType')
                    <div class="bg-white border border-slate-200 rounded-xl p-5 group hover:border-slate-300 transition-colors">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div class="flex items-center gap-1">
                                @if($type['options']['draftAndPublish'] ?? false)
                                    <span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded">D&P</span>
                                @endif
                                <form action="{{ route('talos.content-type-builder.destroy', ['uid' => $type['__uid']]) }}"
                                      method="POST" onsubmit="return confirm('Delete this content type and ALL its data?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-slate-400 hover:text-red-600 transition-colors p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <h3 class="text-slate-800 font-semibold">{{ $type['info']['displayName'] }}</h3>
                        <p class="text-slate-400 text-xs mt-0.5">{{ $type['info']['pluralName'] }}</p>
                        <p class="text-slate-400 text-xs mt-2">{{ count($type['attributes'] ?? []) }} field(s)</p>
                        <div class="flex gap-2 mt-4">
                            <a href="{{ route('talos.content-type-builder.edit', ['uid' => $type['__uid']]) }}"
                               class="flex-1 text-center py-1.5 bg-slate-100 hover:bg-slate-100 text-slate-600 rounded text-xs font-medium transition-colors">
                                Edit Schema
                            </a>
                            <a href="{{ route('talos.content.index', ['uid' => $type['__uid']]) }}"
                               class="flex-1 text-center py-1.5 bg-blue-50 hover:bg-blue-900/60 text-blue-600 rounded text-xs font-medium transition-colors">
                                Manage Content
                            </a>
                        </div>
                    </div>
                @endif
            @endforeach

            <a href="{{ route('talos.content-type-builder.create') }}"
               class="border-2 border-dashed border-slate-200 hover:border-slate-300 rounded-xl p-5 flex flex-col items-center justify-center gap-3 text-slate-400 hover:text-slate-500 transition-colors min-h-[160px]">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" />
                </svg>
                <span class="text-sm">Add Collection Type</span>
            </a>
        </div>
    </div>

    {{-- Single Types --}}
    <div>
        <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Single Types</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($contentTypes as $type)
                @if($type['kind'] === 'singleType')
                    <div class="bg-white border border-slate-200 rounded-xl p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 bg-violet-50 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <form action="{{ route('talos.content-type-builder.destroy', ['uid' => $type['__uid']]) }}"
                                  method="POST" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-slate-400 hover:text-red-600 transition-colors p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                        <h3 class="text-slate-800 font-semibold">{{ $type['info']['displayName'] }}</h3>
                        <p class="text-slate-400 text-xs mt-0.5">{{ $type['info']['singularName'] }}</p>
                        <div class="flex gap-2 mt-4">
                            <a href="{{ route('talos.content-type-builder.edit', ['uid' => $type['__uid']]) }}"
                               class="flex-1 text-center py-1.5 bg-slate-100 hover:bg-slate-100 text-slate-600 rounded text-xs font-medium">
                                Edit Schema
                            </a>
                        </div>
                    </div>
                @endif
            @endforeach

            <a href="{{ route('talos.content-type-builder.create') }}?kind=singleType"
               class="border-2 border-dashed border-slate-200 hover:border-slate-300 rounded-xl p-5 flex flex-col items-center justify-center gap-3 text-slate-400 hover:text-slate-500 transition-colors min-h-[160px]">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" />
                </svg>
                <span class="text-sm">Add Single Type</span>
            </a>
        </div>
    </div>

    {{-- Components overview --}}
    @if(count($components) > 0)
        <div>
            <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Components</h2>
            <div class="bg-white border border-slate-200 rounded-xl divide-y divide-slate-200">
                @foreach($components as $component)
                    <a href="{{ route('talos.components.edit', ['uid' => $component['__uid']]) }}"
                       class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-100 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ $component['info']['displayName'] }}</p>
                                <p class="text-xs text-slate-400">{{ $component['__category'] }} · {{ count($component['attributes'] ?? []) }} fields</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
