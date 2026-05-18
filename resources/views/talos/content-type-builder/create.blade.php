@extends('talos.layouts.app')

@section('title', 'Create Content Type — Talos')
@section('header', 'Create Content Type')

@section('content')
<div class="max-w-2xl" x-data="{ kind: '{{ request('kind', 'collectionType') }}' }">
    <form action="{{ route('talos.content-type-builder.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Kind selector --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h2 class="text-white font-semibold mb-4">Select a type</h2>
            <div class="grid grid-cols-2 gap-4">
                <label class="cursor-pointer">
                    <input type="radio" name="kind" value="collectionType" x-model="kind" class="sr-only" />
                    <div :class="kind === 'collectionType' ? 'border-blue-500 bg-blue-900/20' : 'border-gray-700 hover:border-gray-600'"
                         class="border-2 rounded-xl p-4 transition-colors">
                        <div class="w-10 h-10 bg-blue-900/50 rounded-lg flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h3 class="text-white font-semibold">Collection Type</h3>
                        <p class="text-gray-500 text-xs mt-1">
                            Multiple entries — Articles, Products, Users, etc.
                        </p>
                    </div>
                </label>

                <label class="cursor-pointer">
                    <input type="radio" name="kind" value="singleType" x-model="kind" class="sr-only" />
                    <div :class="kind === 'singleType' ? 'border-purple-500 bg-purple-900/20' : 'border-gray-700 hover:border-gray-600'"
                         class="border-2 rounded-xl p-4 transition-colors">
                        <div class="w-10 h-10 bg-purple-900/50 rounded-lg flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-white font-semibold">Single Type</h3>
                        <p class="text-gray-500 text-xs mt-1">
                            One entry only — Homepage, Global settings, etc.
                        </p>
                    </div>
                </label>
            </div>
        </div>

        {{-- Basic info --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-4">
            <h2 class="text-white font-semibold">Basic settings</h2>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1.5">Display Name <span class="text-red-400">*</span></label>
                <input name="info[displayName]" type="text" value="{{ old('info.displayName') }}" required
                       placeholder="e.g. Article"
                       class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm
                              focus:outline-none focus:border-blue-500"
                       x-ref="displayName"
                       @input="
                           const v = $el.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
                           $refs.singular.value = v;
                           $refs.plural.value = v.endsWith('s') ? v : v + 's';
                       ">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">API ID (singular) <span class="text-red-400">*</span></label>
                    <input name="info[singularName]" type="text" value="{{ old('info.singularName') }}" required
                           placeholder="article" pattern="[a-z0-9_]+" x-ref="singular"
                           class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm
                                  focus:outline-none focus:border-blue-500 font-mono">
                    <p class="text-gray-600 text-xs mt-1">lowercase, no spaces</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">API ID (plural) <span class="text-red-400">*</span></label>
                    <input name="info[pluralName]" type="text" value="{{ old('info.pluralName') }}" required
                           placeholder="articles" pattern="[a-z0-9_]+" x-ref="plural"
                           class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm
                                  focus:outline-none focus:border-blue-500 font-mono">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1.5">Description</label>
                <textarea name="info[description]" rows="2"
                          class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm
                                 focus:outline-none focus:border-blue-500 resize-none">{{ old('info.description') }}</textarea>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-800 rounded-lg">
                <div>
                    <p class="text-sm font-medium text-white">Draft & Publish</p>
                    <p class="text-xs text-gray-500">Entries can be saved as draft before publishing</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="options[draftAndPublish]" value="0">
                    <input type="checkbox" name="options[draftAndPublish]" value="1"
                           {{ old('options.draftAndPublish') ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-700 peer-checked:bg-blue-600 rounded-full peer
                                peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px]
                                after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-800 rounded-lg">
                <div>
                    <p class="text-sm font-medium text-white">Internationalization (i18n)</p>
                    <p class="text-xs text-gray-500">Allow entries in multiple languages</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="options[i18n]" value="0">
                    <input type="checkbox" name="options[i18n]" value="1"
                           {{ old('options.i18n') ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-700 peer-checked:bg-blue-600 rounded-full peer
                                peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px]
                                after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('talos.content-type-builder.index') }}"
               class="px-4 py-2 text-gray-400 hover:text-white text-sm">Cancel</a>
            <button type="submit"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors">
                Continue →
            </button>
        </div>
    </form>
</div>
@endsection
