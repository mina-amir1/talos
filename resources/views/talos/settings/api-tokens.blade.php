@extends('talos.layouts.app')

@section('title', 'API Tokens — Talos')
@section('header', 'API Tokens')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Token list --}}
    <div class="lg:col-span-2 bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-800">
            <h2 class="text-white font-semibold">Active Tokens</h2>
            <p class="text-xs text-gray-500 mt-0.5">Use these tokens as Bearer tokens for the Talos REST API</p>
        </div>
        @if($tokens->isEmpty())
            <div class="px-5 py-10 text-center text-gray-600 text-sm">No tokens yet.</div>
        @else
            <div class="divide-y divide-gray-800">
                @foreach($tokens as $token)
                    <div class="flex items-center justify-between px-5 py-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-white">{{ $token->name }}</p>
                                <span class="text-xs px-1.5 py-0.5 rounded border
                                    {{ $token->type === 'full-access' ? 'bg-red-900/40 text-red-400 border-red-800' : ($token->type === 'read-only' ? 'bg-blue-900/40 text-blue-400 border-blue-800' : 'bg-gray-800 text-gray-400 border-gray-700') }}">
                                    {{ $token->type }}
                                </span>
                                @if($token->isExpired())
                                    <span class="text-xs text-red-400">Expired</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Created by {{ $token->creator?->full_name ?? '—' }}
                                · Last used: {{ $token->last_used_at?->diffForHumans() ?? 'never' }}
                                @if($token->expires_at) · Expires: {{ $token->expires_at->format('M d, Y') }} @endif
                            </p>
                        </div>
                        <form action="{{ route('talos.settings.api-tokens.destroy', $token->id) }}"
                              method="POST" onsubmit="return confirm('Revoke this token?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-gray-600 hover:text-red-400 transition-colors px-3 py-1 text-xs">
                                Revoke
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Create token --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <h2 class="text-white font-semibold mb-4">Create Token</h2>
        <form action="{{ route('talos.settings.api-tokens.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Token name</label>
                <input name="name" type="text" required placeholder="e.g. Frontend App"
                       class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Type</label>
                <select name="type"
                        class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-gray-300 text-sm focus:outline-none focus:border-blue-500">
                    <option value="full-access">Full access</option>
                    <option value="read-only">Read only</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Expires at (optional)</label>
                <input name="expires_at" type="datetime-local"
                       class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-gray-300 text-sm focus:outline-none focus:border-blue-500">
            </div>
            <button type="submit"
                    class="w-full py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors">
                Generate token
            </button>
        </form>

        <div class="mt-5 pt-5 border-t border-gray-800">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">API Usage</p>
            <div class="bg-gray-950 rounded-lg p-3 font-mono text-xs text-gray-400 space-y-1">
                <p><span class="text-blue-400">GET</span> /api/articles</p>
                <p><span class="text-green-400">POST</span> /api/articles</p>
                <p><span class="text-yellow-400">PUT</span> /api/articles/{id}</p>
                <p><span class="text-red-400">DELETE</span> /api/articles/{id}</p>
            </div>
            <p class="text-xs text-gray-600 mt-2">Include as: <code class="text-gray-500">Authorization: Bearer {token}</code></p>
        </div>
    </div>
</div>
@endsection
