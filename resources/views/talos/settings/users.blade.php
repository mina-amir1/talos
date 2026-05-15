@extends('talos.layouts.app')

@section('title', 'Admin Users — Talos')
@section('header', 'Admin Users')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- User list --}}
    <div class="lg:col-span-2 bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-800">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @foreach($users as $user)
                    <tr class="hover:bg-gray-800 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 bg-blue-900 rounded-full flex items-center justify-center text-xs text-blue-300 font-bold">
                                    {{ strtoupper(substr($user->firstname, 0, 1)) }}
                                </div>
                                <span class="text-white">{{ $user->full_name }}</span>
                                @if($user->is_super_admin)
                                    <span class="text-xs bg-yellow-900/40 text-yellow-400 border border-yellow-800 px-1.5 py-0.5 rounded">Super</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-gray-400">{{ $user->email }}</td>
                        <td class="px-5 py-3.5 text-gray-500 text-xs">{{ $user->role?->name ?? '—' }}</td>
                        <td class="px-5 py-3.5">
                            @if(!$user->is_super_admin)
                                <form action="{{ route('talos.settings.users.destroy', $user->id) }}"
                                      method="POST" onsubmit="return confirm('Delete user?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-gray-600 hover:text-red-400 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Invite user --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <h2 class="text-white font-semibold mb-4">Add Admin User</h2>
        <form action="{{ route('talos.settings.users.store') }}" method="POST" class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">First name</label>
                    <input name="firstname" type="text" required
                           class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Last name</label>
                    <input name="lastname" type="text" required
                           class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Email</label>
                <input name="email" type="email" required
                       class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Password</label>
                <input name="password" type="password" required minlength="8"
                       class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Confirm password</label>
                <input name="password_confirmation" type="password" required
                       class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Role</label>
                <select name="role_id"
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-gray-300 text-sm focus:outline-none focus:border-blue-500">
                    <option value="">No role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="w-full py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors">
                Create user
            </button>
        </form>
    </div>
</div>
@endsection
