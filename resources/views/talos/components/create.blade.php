@extends('talos.layouts.app')

@section('title', 'Create Component — Talos')
@section('header', 'Create Component')

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('talos.components.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-4">
            <h2 class="text-white font-semibold">Component details</h2>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1.5">Display Name <span class="text-red-400">*</span></label>
                <input name="info[displayName]" type="text" value="{{ old('info.displayName') }}" required
                       placeholder="e.g. SEO Metadata"
                       class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm
                              focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1.5">Category <span class="text-red-400">*</span></label>
                <input name="category" type="text" value="{{ old('category', 'shared') }}" required
                       placeholder="shared" pattern="[a-z0-9_]+"
                       class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm font-mono
                              focus:outline-none focus:border-blue-500">
                <p class="text-gray-600 text-xs mt-1">Groups related components (e.g. shared, layout, blog)</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1.5">Description</label>
                <textarea name="info[description]" rows="2"
                          class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm
                                 focus:outline-none focus:border-blue-500 resize-none">{{ old('info.description') }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('talos.components.index') }}" class="text-gray-400 hover:text-white text-sm">Cancel</a>
            <button type="submit"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors">
                Continue →
            </button>
        </div>
    </form>
</div>
@endsection
