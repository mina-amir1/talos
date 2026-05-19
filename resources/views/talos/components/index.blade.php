@extends('talos.layouts.app')

@section('title', 'Components — Talos')
@section('header', 'Components')

@section('header-actions')
    <a href="{{ route('talos.components.create') }}"
       class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Create component
    </a>
@endsection

@section('content')
@if(count($grouped) === 0)
    <div class="flex flex-col items-center justify-center h-64 text-slate-400 gap-4">
        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
        <p class="text-lg font-medium text-slate-400">No components yet</p>
        <p class="text-sm text-slate-400 text-center max-w-sm">
            Components are reusable field groups you can embed in content types or use in dynamic zones.
        </p>
        <a href="{{ route('talos.components.create') }}"
           class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors">
            Create your first component
        </a>
    </div>
@else
    <div class="space-y-8">
        @foreach($grouped as $category => $components)
            <div>
                <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">{{ $category }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($components as $component)
                        <div class="bg-white border border-slate-200 rounded-xl p-5 hover:border-slate-300 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <form action="{{ route('talos.components.destroy', ['uid' => $component['__uid']]) }}"
                                      method="POST" onsubmit="return confirm('Delete this component?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-slate-400 hover:text-red-600 transition-colors p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                            <h3 class="text-slate-800 font-semibold">{{ $component['info']['displayName'] }}</h3>
                            <p class="text-slate-400 text-xs mt-0.5">{{ $component['__uid'] }}</p>
                            <p class="text-slate-400 text-xs mt-2">{{ count($component['attributes'] ?? []) }} field(s)</p>
                            <a href="{{ route('talos.components.edit', ['uid' => $component['__uid']]) }}"
                               class="mt-4 block text-center py-1.5 bg-slate-100 hover:bg-slate-100 text-slate-600 rounded text-xs font-medium transition-colors">
                                Edit Fields
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
