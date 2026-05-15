@extends('talos.layouts.app')

@section('title', 'Roles — Talos')
@section('header', 'Roles & Permissions')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Role list --}}
    <div class="lg:col-span-2 bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-800">
            <h2 class="text-white font-semibold">Roles</h2>
        </div>
        <div class="divide-y divide-gray-800">
            @foreach($roles as $role)
                <div class="flex items-center justify-between px-5 py-4">
                    <div>
                        <p class="text-sm font-medium text-white">{{ $role->name }}</p>
                        <p class="text-xs text-gray-500">{{ $role->description }} · {{ $role->users_count }} user(s)</p>
                    </div>
                    <form action="{{ route('talos.settings.roles.destroy', $role->id) }}"
                          method="POST" onsubmit="return confirm('Delete role?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-600 hover:text-red-400 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Create role --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <h2 class="text-white font-semibold mb-4">Create Role</h2>
        <form action="{{ route('talos.settings.roles.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1.5">Name</label>
                <input name="name" type="text" required placeholder="e.g. Editor"
                       class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1.5">Description</label>
                <input name="description" type="text" placeholder="Optional description"
                       class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
            </div>
            <button type="submit"
                    class="w-full py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors">
                Create role
            </button>
        </form>
    </div>
</div>
@endsection
