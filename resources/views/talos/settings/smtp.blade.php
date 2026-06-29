@extends('talos.layouts.app')

@section('title', 'SMTP Settings — Talos')
@section('header', 'SMTP Settings')

@section('content')
<div class="max-w-2xl space-y-6" x-data="smtpPage()">

    {{-- Flash --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-xl px-4 py-3">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl px-4 py-3">
        {{ session('error') }}
    </div>
    @endif

    {{-- Status banner --}}
    <div class="bg-white border border-slate-200 rounded-xl p-5 flex items-center justify-between">
        <div>
            <p class="text-sm font-semibold text-slate-800">Email sending</p>
            <p class="text-xs text-slate-400 mt-0.5">
                @if($smtp && $smtp->is_active && $smtp->host)
                    Active &mdash; sending via <span class="font-mono">{{ $smtp->host }}:{{ $smtp->port }}</span>
                @else
                    Disabled &mdash; configure and enable SMTP to send emails
                @endif
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full
                     {{ ($smtp && $smtp->is_active && $smtp->host) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
            <span class="w-1.5 h-1.5 rounded-full {{ ($smtp && $smtp->is_active && $smtp->host) ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
            {{ ($smtp && $smtp->is_active && $smtp->host) ? 'Active' : 'Inactive' }}
        </span>
    </div>

    {{-- SMTP form --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-sm font-semibold text-slate-800">SMTP Configuration</h2>
            <p class="text-xs text-slate-400 mt-0.5">Credentials are encrypted at rest.</p>
        </div>
        <form action="{{ route('talos.settings.smtp.save') }}" method="POST" class="p-5 space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Host</label>
                    <input type="text" name="host" value="{{ old('host', $smtp?->host) }}"
                           placeholder="smtp.example.com"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Port</label>
                    <input type="number" name="port" value="{{ old('port', $smtp?->port ?? 587) }}"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Encryption</label>
                <select name="encryption" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    @foreach(['tls' => 'TLS (recommended)', 'ssl' => 'SSL', 'none' => 'None'] as $val => $label)
                        <option value="{{ $val }}" {{ old('encryption', $smtp?->encryption ?? 'tls') === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Username</label>
                    <input type="text" name="username" value="{{ old('username', $smtp?->username) }}"
                           autocomplete="off"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Password</label>
                    <input type="password" name="password" placeholder="{{ $smtp?->host ? '(unchanged)' : '' }}"
                           autocomplete="new-password"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">From name</label>
                    <input type="text" name="from_name" value="{{ old('from_name', $smtp?->from_name ?? 'Talos CMS') }}"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">From email</label>
                    <input type="email" name="from_email" value="{{ old('from_email', $smtp?->from_email) }}"
                           placeholder="noreply@example.com"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $smtp?->is_active) ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-slate-700">Enable SMTP email sending</span>
            </label>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Save settings
                </button>
                <button type="button"
                        @click="testConnection()"
                        :disabled="testing"
                        class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-200 transition-colors flex items-center gap-2">
                    <svg x-show="testing" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="testing ? 'Testing…' : 'Test connection'"></span>
                </button>
            </div>
        </form>
    </div>

    {{-- Test email --}}
    @if($smtp && $smtp->host && $smtp->is_active)
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Send test email</h2>
        <form action="{{ route('talos.settings.smtp.test-email') }}" method="POST" class="flex gap-3">
            @csrf
            <input type="email" name="to" required placeholder="recipient@example.com"
                   class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit"
                    class="px-4 py-2 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors shrink-0">
                Send test
            </button>
        </form>
    </div>
    @endif

</div>

<script>
function smtpPage() {
    return {
        testing: false,

        async testConnection() {
            this.testing = true;
            try {
                const form = document.querySelector('form[action="{{ route('talos.settings.smtp.save') }}"]');
                const data = new FormData(form);

                const res  = await fetch('{{ route('talos.settings.smtp.test-connection') }}', {
                    method:  'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body:    data,
                });
                const json = await res.json();

                if (json.ok) talos.toast('Connection successful!', 'success');
                else         talos.toast('Connection failed: ' + (json.error ?? 'Unknown error'), 'error');
            } catch (e) {
                talos.toast('Request failed.', 'error');
            } finally {
                this.testing = false;
            }
        },
    };
}
</script>
@endsection
