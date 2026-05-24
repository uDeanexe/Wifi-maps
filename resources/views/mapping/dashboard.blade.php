<x-layouts.app title="Dashboard">
    @php
        $labels = [
            'nodes' => 'Node',
            'links' => 'Link',
            'users' => 'User',
        ];
    @endphp

    <div class="toolbar">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Dashboard</h2>
            <p class="mt-1 text-sm text-slate-500">Ringkasan operasional mapping jaringan.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a class="btn" href="{{ route('reports.topology.pdf') }}">Report Topology</a>
            <a class="btn" href="{{ route('reports.links.pdf') }}">Report Link</a>
            <a class="btn" href="{{ route('reports.nodes.csv') }}">Export Node CSV</a>
            <a class="btn" href="{{ route('reports.links.csv') }}">Export Link CSV</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($totals as $label => $total)
            <div class="panel p-5">
                <div class="text-sm font-semibold text-slate-500">{{ $labels[$label] ?? str_replace('_', ' ', $label) }}</div>
                <div class="mt-3 text-3xl font-black text-slate-900">{{ $total }}</div>
            </div>
        @endforeach
    </div>
</x-layouts.app>
