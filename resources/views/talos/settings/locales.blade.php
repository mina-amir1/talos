@extends('talos.layouts.app')

@section('title', 'Locales — Talos')
@section('header', 'Locales')

@section('content')
<div class="max-w-2xl space-y-6">

    {{-- Current locales --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-slate-800 font-semibold">Enabled locales</h2>
            <p class="text-xs text-slate-400 mt-0.5">Default locale cannot be removed. Add it first in <code class="bg-slate-100 px-1 rounded">.env</code> as <code class="bg-slate-100 px-1 rounded">TALOS_DEFAULT_LOCALE</code>.</p>
        </div>

        <div class="divide-y divide-slate-200">
            @foreach($locales as $locale)
                <div class="flex items-center justify-between px-5 py-3.5">
                    <div class="flex items-center gap-3">
                        <span class="w-12 text-center text-sm font-mono font-semibold text-slate-800 bg-slate-100 border border-slate-300 rounded px-2 py-0.5">
                            {{ strtoupper($locale) }}
                        </span>
                        @if($locale === $defaultLocale)
                            <span class="text-xs bg-blue-50 text-blue-600 border border-blue-200 px-2 py-0.5 rounded">Default</span>
                        @endif
                    </div>
                    @if($locale !== $defaultLocale)
                        <form action="{{ route('talos.settings.locales.destroy', $locale) }}" method="POST"
                              onsubmit="return confirm('Remove locale {{ strtoupper($locale) }}? Existing entries in this locale will remain in the database.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-slate-400 hover:text-red-600 transition-colors">Remove</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Add locale --}}
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <h2 class="text-slate-800 font-semibold mb-4">Add a locale</h2>
        <form action="{{ route('talos.settings.locales.store') }}" method="POST" class="flex gap-3">
            @csrf
            <input type="text" name="code" placeholder="e.g. ar, fr, de, zh"
                   pattern="[a-z]{2}(-[A-Z]{2})?"
                   class="flex-1 px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm font-mono
                          focus:outline-none focus:border-blue-500 placeholder-gray-600"
                   required>
            <button type="submit"
                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors">
                Add
            </button>
        </form>
        <p class="text-xs text-slate-400 mt-2">Use ISO 639-1 codes: <span class="font-mono">ar, fr, de, es, zh, ja, pt, ru, it</span></p>
    </div>

</div>
@endsection
