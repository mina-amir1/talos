@extends('talos.layouts.app')

@section('title', 'Webhooks — Talos')
@section('header', 'Webhooks')

@section('content')
<div class="max-w-3xl space-y-6" x-data="webhookPage()">

    {{-- Flash --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-xl px-4 py-3">
        {{ session('success') }}
    </div>
    @endif

    {{-- Existing webhooks --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Webhooks</h2>
                <p class="text-xs text-slate-400 mt-0.5">Talos will POST to these URLs when content events occur.</p>
            </div>
            <button @click="showAdd = !showAdd"
                    class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Webhook
            </button>
        </div>

        {{-- Add form --}}
        <div x-show="showAdd" x-cloak class="border-b border-slate-200 bg-slate-50 px-5 py-5">
            <h3 class="text-xs font-semibold text-slate-700 mb-4 uppercase tracking-wider">New Webhook</h3>
            <form action="{{ route('talos.settings.webhooks.store') }}" method="POST">
                @csrf
                @include('talos.settings._webhook-form', ['w' => null, 'events' => $events, 'contentTypes' => $contentTypes])
                <div class="flex gap-2 mt-4">
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                        Create
                    </button>
                    <button type="button" @click="showAdd = false"
                            class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-200">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        @forelse($webhooks as $w)
        <div class="border-b border-slate-100 last:border-0">
            {{-- Row --}}
            <div class="flex items-center gap-4 px-5 py-4">
                {{-- Toggle --}}
                <button type="button"
                        @click="toggle({{ $w->id }}, $el)"
                        data-active="{{ $w->is_active ? '1' : '0' }}"
                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors
                               {{ $w->is_active ? 'bg-blue-600' : 'bg-slate-300' }}"
                        title="{{ $w->is_active ? 'Disable' : 'Enable' }}">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform
                                 {{ $w->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                </button>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800">{{ $w->name }}</p>
                    <p class="text-xs text-slate-400 truncate font-mono">{{ $w->url }}</p>
                </div>

                {{-- Event badges --}}
                <div class="hidden sm:flex flex-wrap gap-1">
                    @foreach($w->events as $event)
                        @php
                            $color = match($event) {
                                'entry.create'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'entry.update'    => 'bg-blue-50 text-blue-700 border-blue-200',
                                'entry.delete'    => 'bg-red-50 text-red-700 border-red-200',
                                'entry.publish'   => 'bg-violet-50 text-violet-700 border-violet-200',
                                'entry.unpublish' => 'bg-slate-100 text-slate-600 border-slate-200',
                                default           => 'bg-slate-100 text-slate-600 border-slate-200',
                            };
                        @endphp
                        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded border {{ $color }}">{{ $event }}</span>
                    @endforeach
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 shrink-0">
                    <button type="button"
                            @click="test({{ $w->id }})"
                            class="text-xs text-slate-500 hover:text-slate-800 transition-colors">
                        Test
                    </button>
                    <button type="button"
                            @click="editing = editing === {{ $w->id }} ? null : {{ $w->id }}"
                            class="text-xs text-slate-500 hover:text-slate-800 transition-colors">
                        Edit
                    </button>
                    <button type="button"
                            @click="destroy({{ $w->id }}, '{{ addslashes($w->name) }}')"
                            class="text-xs text-red-400 hover:text-red-600 transition-colors">
                        Delete
                    </button>
                </div>
            </div>

            {{-- Edit form --}}
            <div x-show="editing === {{ $w->id }}" x-cloak class="bg-slate-50 px-5 py-5 border-t border-slate-100">
                <h3 class="text-xs font-semibold text-slate-700 mb-4 uppercase tracking-wider">Edit Webhook</h3>
                <form action="{{ route('talos.settings.webhooks.update', $w->id) }}" method="POST">
                    @csrf @method('PUT')
                    @include('talos.settings._webhook-form', ['w' => $w, 'events' => $events, 'contentTypes' => $contentTypes])
                    <div class="flex gap-2 mt-4">
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                            Save
                        </button>
                        <button type="button" @click="editing = null"
                                class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-200">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="px-5 py-10 text-center text-slate-400 text-sm">
            No webhooks yet. Add one to get started.
        </div>
        @endforelse
    </div>

    {{-- Payload reference --}}
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Payload format</h2>
        <pre class="text-xs bg-slate-50 border border-slate-200 rounded-lg p-4 overflow-x-auto text-slate-700 font-mono leading-relaxed">{
  "event":     "entry.publish",
  "uid":       "api.article",
  "createdAt": "2026-06-26T10:00:00+00:00",
  "data":      { ...entry fields... }
}</pre>
        <p class="text-xs text-slate-400 mt-3">
            Each request includes a <span class="font-mono bg-slate-100 px-1 rounded">X-Talos-Signature</span> header
            (<span class="font-mono">sha256=&lt;HMAC-SHA256 of body&gt;</span>) signed with your webhook secret.
            Verify it on the receiving end to confirm the request came from Talos.
        </p>
    </div>

</div>

<script>
function webhookPage() {
    return {
        showAdd: {{ $errors->any() ? 'true' : 'false' }},
        editing: null,

        async toggle(id, btn) {
            try {
                const res  = await fetch('{{ url('talos/settings/webhooks') }}/' + id + '/toggle', {
                    method:  'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                });
                const data = await res.json();
                const active = data.is_active;
                btn.dataset.active = active ? '1' : '0';
                btn.classList.toggle('bg-blue-600', active);
                btn.classList.toggle('bg-slate-300', !active);
                const knob = btn.querySelector('span');
                knob.classList.toggle('translate-x-4', active);
                knob.classList.toggle('translate-x-0', !active);
                talos.toast(active ? 'Webhook enabled.' : 'Webhook disabled.', 'success');
            } catch { talos.toast('Request failed.', 'error'); }
        },

        async test(id) {
            talos.toast('Sending test ping…', 'success');
            try {
                const res  = await fetch('{{ url('talos/settings/webhooks') }}/' + id + '/test', {
                    method:  'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                });
                const data = await res.json();
                if (data.ok) talos.toast('Test ping delivered (HTTP ' + data.status + ').', 'success');
                else         talos.toast('Test failed: ' + (data.error ?? 'HTTP ' + data.status), 'error');
            } catch { talos.toast('Request failed.', 'error'); }
        },

        async destroy(id, name) {
            if (!await talos.confirm('Delete webhook "' + name + '"?')) return;
            try {
                const res  = await fetch('{{ url('talos/settings/webhooks') }}/' + id, {
                    method:  'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                });
                const data = await res.json();
                if (data.deleted) { location.reload(); }
                else talos.toast('Delete failed.', 'error');
            } catch { talos.toast('Request failed.', 'error'); }
        },
    };
}
</script>
@endsection
