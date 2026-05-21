@extends('talos.layouts.app')

@section('title', 'My Profile — Talos')
@section('header', 'My Profile')

@section('content')
<div class="max-w-md space-y-6">

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-xl px-4 py-3">
        {{ session('success') }}
    </div>
    @endif

    {{-- Info --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Account</h2>
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-blue-600 flex items-center justify-center text-white text-lg font-semibold flex-shrink-0">
                {{ strtoupper(substr($user->firstname, 0, 1)) }}
            </div>
            <div>
                <p class="text-sm font-medium text-slate-800">{{ $user->full_name }}</p>
                <p class="text-xs text-slate-500">{{ $user->email }}</p>
                @if($user->is_super_admin)
                    <span class="text-xs bg-amber-50 text-amber-600 border border-amber-200 px-1.5 py-0.5 rounded mt-1 inline-block">Super Admin</span>
                @elseif($user->role)
                    <span class="text-xs bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded mt-1 inline-block">{{ $user->role->name }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Change password --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Change Password</h2>

        <form method="POST" action="{{ route('talos.settings.profile.password') }}" class="space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Current password</label>
                <input type="password" name="current_password" required
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                              {{ $errors->has('current_password') ? 'border-red-400' : '' }}">
                @error('current_password')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">New password</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                              {{ $errors->has('password') ? 'border-red-400' : '' }}">
                @error('password')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Confirm new password</label>
                <input type="password" name="password_confirmation" required
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit"
                    class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                Update Password
            </button>
        </form>
    </div>

</div>
@endsection
