@extends('talos.layouts.app')

@section('title', 'Media Storage — Talos')
@section('header', 'Media Storage')

@section('content')
<div class="max-w-2xl space-y-6">

    {{-- Flash --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-xl px-4 py-3">
        {{ session('success') }}
    </div>
    @endif

    {{-- R2 Credentials --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Cloudflare R2 Credentials</h2>

        <form method="POST" action="{{ route('talos.settings.storage.save') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Account ID</label>
                <input type="text" name="r2_media_account_id"
                       value="{{ old('r2_media_account_id', $config['r2_media_account_id']) }}"
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="abc123...">
                @error('r2_media_account_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Access Key ID</label>
                <input type="text" name="r2_media_access_key"
                       value="{{ old('r2_media_access_key', $config['r2_media_access_key']) }}"
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Access Key ID">
                @error('r2_media_access_key')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Secret Access Key</label>
                <input type="password" name="r2_media_secret_key"
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="{{ $config['r2_media_access_key'] ? '(stored — leave blank to keep)' : 'Secret Access Key' }}">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Bucket Name</label>
                <input type="text" name="r2_media_bucket"
                       value="{{ old('r2_media_bucket', $config['r2_media_bucket']) }}"
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="my-media-bucket">
                @error('r2_media_bucket')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Custom Domain <span class="text-slate-400">(optional)</span></label>
                <input type="url" name="r2_media_domain"
                       value="{{ old('r2_media_domain', $config['r2_media_domain']) }}"
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="https://media.example.com">
                @error('r2_media_domain')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    Save Credentials
                </button>
                <button type="button" id="btn-test-storage"
                        class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-200">
                    Test Connection
                </button>
            </div>
        </form>
    </div>

    {{-- Enable / Disable --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Use R2 for Media Storage</h2>
                <p class="text-xs text-slate-500 mt-0.5">When enabled, all uploads go directly to R2. Existing local files remain unless migrated.</p>
            </div>
            <button id="btn-toggle-storage"
                    data-enabled="{{ $config['r2_media_enabled'] }}"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none
                           {{ $config['r2_media_enabled'] === '1' ? 'bg-blue-600' : 'bg-slate-200' }}">
                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform
                             {{ $config['r2_media_enabled'] === '1' ? 'translate-x-6' : 'translate-x-1' }}"></span>
            </button>
        </div>
    </div>

    {{-- Migrate existing media --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h2 class="text-sm font-semibold text-slate-800 mb-1">Migrate Existing Media to R2</h2>
        <p class="text-xs text-slate-500 mb-4">Copy all locally stored media files to R2. Files already on R2 are skipped. This runs as a background job.</p>

        @php
            $migStatus = $config['r2_migration_status'];
            $migProgress = $config['r2_migration_progress'];
            $migFailed = $config['r2_migration_failed'];
        @endphp

        <div id="migration-status" class="mb-4 text-sm">
            @if($migStatus === 'running' || $migStatus === 'pending')
                <span class="inline-flex items-center gap-1.5 text-amber-700">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    Running — {{ $migProgress }} files processed
                </span>
            @elseif($migStatus === 'done')
                <span class="text-emerald-700">Done — {{ $migProgress }} files, {{ $migFailed }} failed</span>
            @elseif($migStatus === 'failed')
                <span class="text-red-600">Migration failed. Check your queue worker logs.</span>
            @else
                <span class="text-slate-400">Not started</span>
            @endif
        </div>

        <button id="btn-migrate"
                {{ in_array($migStatus, ['running', 'pending']) ? 'disabled' : '' }}
                class="px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed">
            Start Migration
        </button>
    </div>

</div>

<script>
(function () {
    // Toggle
    document.getElementById('btn-toggle-storage')?.addEventListener('click', async function () {
        const enabled = this.dataset.enabled !== '1';
        try {
            const res = await fetch('{{ route('talos.settings.storage.toggle') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ enabled }),
            });
            const data = await res.json();
            if (!res.ok) { talos.toast(data.error || 'Error', 'error'); return; }
            this.dataset.enabled = data.enabled ? '1' : '0';
            this.classList.toggle('bg-blue-600', data.enabled);
            this.classList.toggle('bg-slate-200', !data.enabled);
            this.querySelector('span').classList.toggle('translate-x-6', data.enabled);
            this.querySelector('span').classList.toggle('translate-x-1', !data.enabled);
            talos.toast(data.enabled ? 'R2 storage enabled.' : 'Switched to local storage.', 'success');
        } catch { talos.toast('Request failed.', 'error'); }
    });

    // Test
    document.getElementById('btn-test-storage')?.addEventListener('click', async function () {
        this.textContent = 'Testing…';
        const form = this.closest('form');
        const body = new FormData(form);
        try {
            const res = await fetch('{{ route('talos.settings.storage.test') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body,
            });
            const data = await res.json();
            if (data.ok) talos.toast('Connection successful!', 'success');
            else talos.toast('Connection failed: ' + (data.error ?? 'Check credentials.'), 'error');
        } catch { talos.toast('Request failed.', 'error'); }
        this.textContent = 'Test Connection';
    });

    // Migrate
    document.getElementById('btn-migrate')?.addEventListener('click', async function () {
        if (!await talos.confirm('Start migration? This will copy all local media to R2 in the background.', { confirmLabel: 'Start', danger: false })) return;
        this.disabled = true;
        try {
            const res = await fetch('{{ route('talos.settings.storage.migrate') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            });
            const data = await res.json();
            if (!res.ok) { talos.toast(data.error || 'Error', 'error'); this.disabled = false; return; }
            pollMigration();
        } catch { talos.toast('Request failed.', 'error'); this.disabled = false; }
    });

    function pollMigration() {
        const statusEl = document.getElementById('migration-status');
        const interval = setInterval(async () => {
            try {
                const res  = await fetch('{{ route('talos.settings.storage.migration-status') }}');
                const data = await res.json();
                if (data.status === 'running' || data.status === 'pending') {
                    statusEl.innerHTML = `<span class="text-amber-700">Running — ${data.progress} files processed</span>`;
                } else if (data.status === 'done') {
                    statusEl.innerHTML = `<span class="text-emerald-700">Done — ${data.progress} files, ${data.failed} failed</span>`;
                    clearInterval(interval);
                    document.getElementById('btn-migrate').disabled = false;
                } else if (data.status === 'failed') {
                    statusEl.innerHTML = `<span class="text-red-600">Migration failed. Check your queue worker logs.</span>`;
                    clearInterval(interval);
                    document.getElementById('btn-migrate').disabled = false;
                }
            } catch { clearInterval(interval); }
        }, 3000);
    }

    // Auto-poll if already running
    @if(in_array($config['r2_migration_status'], ['running', 'pending']))
    pollMigration();
    @endif
})();
</script>
@endsection
