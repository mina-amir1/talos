@extends('talos.layouts.app')

@section('title', 'Dashboard — Talos CMS')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-8">
    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $stats = [
                ['label' => 'Content Types', 'value' => count($contentTypes), 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z'],
                ['label' => 'Components',    'value' => count($components),   'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                ['label' => 'Media Files',   'value' => $mediaCount,          'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ['label' => 'Admin Users',   'value' => $userCount,           'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ];
        @endphp

        @foreach($stats as $stat)
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-800">{{ $stat['value'] }}</p>
                        <p class="text-xs text-slate-400">{{ $stat['label'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Collection entry counts --}}
    @if(count($contentTypes) > 0)
    @php
        $iconPalette = [
            ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
            ['bg' => '#d1fae5', 'text' => '#065f46'],
            ['bg' => '#ede9fe', 'text' => '#5b21b6'],
            ['bg' => '#fce7f3', 'text' => '#9d174d'],
            ['bg' => '#fef3c7', 'text' => '#92400e'],
            ['bg' => '#ccfbf1', 'text' => '#115e59'],
            ['bg' => '#e0e7ff', 'text' => '#3730a3'],
            ['bg' => '#fee2e2', 'text' => '#991b1b'],
            ['bg' => '#ffedd5', 'text' => '#9a3412'],
            ['bg' => '#cffafe', 'text' => '#155e75'],
        ];
    @endphp
    <div>
        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">Collections</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            @foreach($contentTypes as $type)
                @php
                    $uid     = $type['__uid'];
                    $palette = $iconPalette[abs(crc32($uid)) % count($iconPalette)];
                    $initial = strtoupper(mb_substr($type['info']['displayName'], 0, 1));
                    $count   = $collectionCounts[$uid] ?? 0;
                    $route   = $type['kind'] === 'singleType'
                        ? route('talos.content.index', ['uid' => $uid])
                        : route('talos.content.index', ['uid' => $uid]);
                @endphp
                <a href="{{ $route }}"
                   class="bg-white border border-slate-200 rounded-xl p-5 hover:border-slate-300 hover:shadow-sm transition-all group flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-sm font-bold flex-shrink-0"
                             style="background:{{ $palette['bg'] }}; color:{{ $palette['text'] }}">
                            {{ $initial }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ $type['info']['displayName'] }}</p>
                            <p class="text-[10px] font-medium mt-0.5" style="color:{{ $palette['text'] }}">
                                {{ $type['kind'] === 'singleType' ? 'Single type' : 'Collection' }}
                            </p>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-slate-800">{{ number_format($count) }}</p>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Content activity chart --}}
    @if(count($activityDatasets) > 0)
    @php
        $chartColors = [
            '#2563eb','#059669','#7c3aed','#db2777',
            '#d97706','#0891b2','#4f46e5','#dc2626',
            '#ea580c','#0d9488',
        ];
    @endphp
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-start justify-between mb-5">
            <div>
                <h2 class="text-slate-800 font-semibold">Content Activity</h2>
                <p class="text-xs text-slate-400 mt-0.5">Entries created per day — last 14 days</p>
            </div>
            <div class="flex flex-wrap justify-end gap-x-4 gap-y-1">
                @foreach($activityDatasets as $i => $ds)
                    <span class="flex items-center gap-1.5 text-xs text-slate-500">
                        <span class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0"
                              style="background:{{ $chartColors[$i % count($chartColors)] }}"></span>
                        {{ $ds['label'] }}
                    </span>
                @endforeach
            </div>
        </div>
        <div class="relative h-52">
            <canvas id="activityChart"></canvas>
        </div>
    </div>

    <script>
    (function () {
        const colors  = @json($chartColors);
        const labels  = @json($dateRange);
        const rawSets = @json($activityDatasets);

        const shortLabels = labels.map(d => {
            const [, m, day] = d.split('-');
            return `${parseInt(day)} ${['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][parseInt(m)-1]}`;
        });

        const datasets = rawSets.map((ds, i) => ({
            label:                ds.label,
            data:                 ds.data,
            borderColor:          colors[i % colors.length],
            backgroundColor:      colors[i % colors.length] + '18',
            borderWidth:          2,
            pointRadius:          3,
            pointHoverRadius:     5,
            pointBackgroundColor: colors[i % colors.length],
            fill:                 true,
            tension:              0.35,
        }));

        new Chart(document.getElementById('activityChart'), {
            type: 'line',
            data: { labels: shortLabels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { title: items => labels[items[0].dataIndex] } },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
                    y: { beginAtZero: true, ticks: { color: '#94a3b8', font: { size: 11 }, precision: 0 }, grid: { color: '#f1f5f9' } },
                },
            },
        });
    })();
    </script>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Content Types --}}
        <div class="bg-white border border-slate-200 rounded-xl">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                <h2 class="text-slate-800 font-semibold">Content Types</h2>
                <a href="{{ route('talos.content-type-builder.create') }}"
                   class="text-sm text-blue-600 hover:text-blue-500 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New
                </a>
            </div>
            <div class="divide-y divide-slate-200">
                @forelse($contentTypes as $type)
                    <a href="{{ route('talos.content.index', ['uid' => $type['__uid']]) }}"
                       class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-100 transition-colors group">
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $type['info']['displayName'] }}</p>
                            <p class="text-xs text-slate-400">{{ $type['kind'] === 'collectionType' ? 'Collection Type' : 'Single Type' }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs bg-slate-100 group-hover:bg-slate-100 text-slate-500 px-2 py-0.5 rounded">
                                {{ count($type['attributes'] ?? []) }} fields
                            </span>
                            <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center">
                        <p class="text-slate-400 text-sm">No content types yet.</p>
                        <a href="{{ route('talos.content-type-builder.create') }}"
                           class="mt-2 inline-block text-blue-600 text-sm hover:underline">Create your first</a>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Components --}}
        <div class="bg-white border border-slate-200 rounded-xl">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                <h2 class="text-slate-800 font-semibold">Components</h2>
                <a href="{{ route('talos.components.create') }}"
                   class="text-sm text-blue-600 hover:text-blue-500 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New
                </a>
            </div>
            <div class="divide-y divide-slate-200">
                @forelse($components as $component)
                    <a href="{{ route('talos.components.edit', ['uid' => $component['__uid']]) }}"
                       class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-100 transition-colors group">
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $component['info']['displayName'] }}</p>
                            <p class="text-xs text-slate-400">{{ $component['__category'] }}</p>
                        </div>
                        <span class="text-xs bg-slate-100 group-hover:bg-slate-100 text-slate-500 px-2 py-0.5 rounded">
                            {{ count($component['attributes'] ?? []) }} fields
                        </span>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center">
                        <p class="text-slate-400 text-sm">No components yet.</p>
                        <a href="{{ route('talos.components.create') }}"
                           class="mt-2 inline-block text-blue-600 text-sm hover:underline">Create your first</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Quick Start --}}
    @if(count($contentTypes) === 0)
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <h3 class="text-slate-800 font-semibold mb-1">Welcome to Talos CMS</h3>
            <p class="text-slate-500 text-sm mb-4">
                Get started by creating your first content type. It defines the structure of your data — just like in Strapi.
            </p>
            <div class="flex gap-3">
                <a href="{{ route('talos.content-type-builder.create') }}"
                   class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-medium transition-colors">
                    Create Content Type
                </a>
                <a href="{{ route('talos.components.create') }}"
                   class="px-4 py-2 bg-slate-100 hover:bg-slate-100 text-slate-700 rounded-lg text-sm font-medium transition-colors">
                    Create Component
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
