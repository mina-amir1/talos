@extends('talos.layouts.app')

@section('title', 'Admin Users — Talos')
@section('header', 'Admin Users')

@section('content')
<div x-data="userManager()">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- User list --}}
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Name</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Email</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Role</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($users as $user)
                        <tr class="hover:bg-slate-100 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 bg-blue-900 rounded-full flex items-center justify-center text-xs text-blue-500 font-bold">
                                        {{ strtoupper(substr($user->firstname, 0, 1)) }}
                                    </div>
                                    <span class="text-slate-800">{{ $user->full_name }}</span>
                                    @if($user->is_super_admin)
                                        <span class="text-xs bg-amber-50 text-amber-600 border border-amber-200 px-1.5 py-0.5 rounded">Super</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-slate-500">{{ $user->email }}</td>
                            <td class="px-5 py-3.5 text-slate-400 text-xs">{{ $user->role?->name ?? '—' }}</td>
                            <td class="px-5 py-3.5">
                                @if(!$user->is_super_admin)
                                    <div class="flex items-center gap-1">
                                        @if($isSA)
                                        <button @click="openReset({{ $user->id }}, '{{ addslashes($user->full_name) }}')"
                                                class="text-slate-400 hover:text-blue-600 p-1" title="Reset password">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                            </svg>
                                        </button>
                                        @endif
                                        <form action="{{ route('talos.settings.users.destroy', $user->id) }}"
                                              method="POST" data-confirm="Delete user?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-slate-400 hover:text-red-600 p-1" title="Delete user">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Invite user --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <h2 class="text-slate-800 font-semibold mb-4">Add Admin User</h2>
            <form action="{{ route('talos.settings.users.store') }}" method="POST" class="space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">First name</label>
                        <input name="firstname" type="text" required
                               class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Last name</label>
                        <input name="lastname" type="text" required
                               class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Email</label>
                    <input name="email" type="email" required
                           class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Password</label>
                    <input name="password" type="password" required minlength="8"
                           class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Confirm password</label>
                    <input name="password_confirmation" type="password" required
                           class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Role</label>
                    <div class="relative">
                        <select name="role_id"
                                class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:border-blue-500 appearance-none pr-8">
                            <option value="">No role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <button type="submit"
                        class="w-full py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors">
                    Create user
                </button>
            </form>
        </div>
    </div>

    {{-- Reset password modal (inside x-data scope) --}}
    <div x-show="resetUserId !== null" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
         @keydown.escape.window="closeReset()">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4" @click.stop>
            <h2 class="text-sm font-semibold text-slate-800 mb-1">Reset Password</h2>
            <p class="text-xs text-slate-500 mb-4">Setting new password for <span class="font-medium text-slate-700" x-text="resetUserName"></span></p>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">New password</label>
                    <input type="password" x-model="newPassword" minlength="8"
                           placeholder="Min. 8 characters"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Confirm password</label>
                    <input type="password" x-model="confirmPassword"
                           placeholder="Repeat password"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-5">
                <button @click="closeReset()"
                        class="px-4 py-2 text-sm text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200">
                    Cancel
                </button>
                <button @click="submitReset()"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    Reset Password
                </button>
            </div>
        </div>
    </div>

</div>{{-- end x-data --}}

@push('scripts')
<script>
function userManager() {
    return {
        resetUserId:     null,
        resetUserName:   '',
        newPassword:     '',
        confirmPassword: '',

        openReset(id, name) {
            this.resetUserId     = id;
            this.resetUserName   = name;
            this.newPassword     = '';
            this.confirmPassword = '';
        },

        closeReset() {
            this.resetUserId = null;
        },

        async submitReset() {
            if (this.newPassword.length < 8) {
                talos.toast('Password must be at least 8 characters.', 'error');
                return;
            }
            if (this.newPassword !== this.confirmPassword) {
                talos.toast('Passwords do not match.', 'error');
                return;
            }

            try {
                const res = await fetch(`{{ url(config('talos.admin_prefix', 'talos') . '/settings/users') }}/${this.resetUserId}/password`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        password: this.newPassword,
                        password_confirmation: this.confirmPassword,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    talos.toast('Password reset successfully.', 'success');
                    this.closeReset();
                } else {
                    talos.toast(data.error || 'Failed to reset password.', 'error');
                }
            } catch {
                talos.toast('Request failed.', 'error');
            }
        },
    };
}
</script>
@endpush
@endsection
