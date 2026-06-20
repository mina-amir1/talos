@extends('talos.layouts.app')

@section('title', 'Backup — Talos')
@section('header', 'Backup')

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
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Backup R2 Credentials</h2>

        <form method="POST" action="{{ route('talos.settings.backup.save') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Account ID</label>
                <input type="text" id="backup_account_id" name="r2_backup_account_id"
                       value="{{ old('r2_backup_account_id', $config['r2_backup_account_id']) }}"
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="abc123...">
                @error('r2_backup_account_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Access Key ID</label>
                <input type="text" id="backup_access_key" name="r2_backup_access_key"
                       value="{{ old('r2_backup_access_key', $config['r2_backup_access_key']) }}"
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Access Key ID">
                @error('r2_backup_access_key')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Secret Access Key</label>
                <input type="password" id="backup_secret_key" name="r2_backup_secret_key"
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="{{ $config['r2_backup_access_key'] ? '(stored — enter to override)' : 'Secret Access Key' }}">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Bucket Name</label>
                <input type="text" id="backup_bucket" name="r2_backup_bucket"
                       value="{{ old('r2_backup_bucket', $config['r2_backup_bucket']) }}"
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="my-backup-bucket">
                @error('r2_backup_bucket')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Schedule</label>
                    <div class="relative">
                        <select name="r2_backup_schedule"
                                class="w-full appearance-none border border-slate-200 rounded-lg px-3 py-2 text-sm pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="daily"  {{ old('r2_backup_schedule', $config['r2_backup_schedule']) === 'daily'  ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ old('r2_backup_schedule', $config['r2_backup_schedule']) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Retention (days)</label>
                    <input type="number" name="r2_backup_retention" min="1" max="365"
                           value="{{ old('r2_backup_retention', $config['r2_backup_retention']) }}"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('r2_backup_retention')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    Save Settings
                </button>
                <button type="button" id="btn-test-backup"
                        class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-200">
                    Test Connection
                </button>
            </div>
        </form>
    </div>

    {{-- Manual trigger --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Manual Backup</h2>
                <p class="text-xs text-slate-500 mt-0.5">Runs immediately. Backs up the SQLite database and all schema files.</p>
                @if($config['r2_backup_last_run'])
                    <p class="text-xs text-slate-400 mt-0.5">Last run: {{ \Illuminate\Support\Carbon::parse($config['r2_backup_last_run'])->diffForHumans() }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('talos.settings.backup.download') }}"
                   class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-200 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download
                </a>
                <button id="btn-trigger-backup"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    Run Now
                </button>
            </div>
        </div>
        <p id="backup-result" class="text-xs mt-3 hidden"></p>
    </div>

    {{-- Backup history --}}
    @if(count($history))
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200">
            <h2 class="text-sm font-semibold text-slate-800">Backup History</h2>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">File</th>
                    <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Size</th>
                    <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Date</th>
                    <th class="px-5 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($history as $b)
                <tr>
                    <td class="px-5 py-3 font-mono text-xs text-slate-700">{{ $b['name'] }}</td>
                    <td class="px-5 py-3 text-slate-500">{{ number_format($b['size'] / 1024, 1) }} KB</td>
                    <td class="px-5 py-3 text-slate-500">{{ $b['modified'] }}</td>
                    <td class="px-5 py-3 text-right">
                        <button data-key="{{ $b['key'] }}" data-name="{{ $b['name'] }}"
                                class="btn-delete-backup text-xs text-red-500 hover:text-red-700">
                            Delete
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>

<script>
(function () {
    // Test
    document.getElementById('btn-test-backup')?.addEventListener('click', async function () {
        this.textContent = 'Testing…';
        try {
            const res = await fetch('{{ route('talos.settings.backup.test') }}', {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    r2_backup_account_id: document.getElementById('backup_account_id').value.trim(),
                    r2_backup_access_key: document.getElementById('backup_access_key').value.trim(),
                    r2_backup_secret_key: document.getElementById('backup_secret_key').value,
                    r2_backup_bucket:     document.getElementById('backup_bucket').value.trim(),
                }),
            });
            const data = await res.json();
            if (data.ok) talos.toast('Connection successful!', 'success');
            else talos.toast('Connection failed: ' + (data.error ?? 'Check credentials.'), 'error');
        } catch { talos.toast('Request failed.', 'error'); }
        this.textContent = 'Test Connection';
    });

    // Trigger
    document.getElementById('btn-trigger-backup')?.addEventListener('click', async function () {
        if (!await talos.confirm('Run a backup now?', { confirmLabel: 'Run Backup', danger: false })) return;
        this.disabled = true;
        this.textContent = 'Running…';
        try {
            const res  = await fetch('{{ route('talos.settings.backup.trigger') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            });
            const data = await res.json();
            if (data.error) talos.toast('Backup failed: ' + data.error, 'error');
            else talos.toast('Backup complete!', 'success');
        } catch { talos.toast('Request failed.', 'error'); }
        this.disabled = false;
        this.textContent = 'Run Now';
    });

    // Delete backups
    document.querySelectorAll('.btn-delete-backup').forEach(btn => {
        btn.addEventListener('click', async function () {
            if (!await talos.confirm('Delete backup "' + this.dataset.name + '"?')) return;
            try {
                const res = await fetch('{{ route('talos.settings.backup.delete') }}', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ key: this.dataset.key }),
                });
                const data = await res.json();
                if (data.deleted) { this.closest('tr').remove(); talos.toast('Backup deleted.', 'success'); }
                else talos.toast('Delete failed.', 'error');
            } catch { talos.toast('Request failed.', 'error'); }
        });
    });
})();
</script>
@endsection
