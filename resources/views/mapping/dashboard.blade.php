<x-layouts.app title="Dashboard">
    @php
        $labels = [
            'nodes' => 'Node',
            'links' => 'Link',
            'incidents' => 'Gangguan',
            'work_reports' => 'Rekam Kerja',
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
            <a class="btn" href="{{ route('reports.incidents.pdf') }}">Report Gangguan</a>
            <a class="btn" href="{{ route('reports.work-reports.pdf') }}">Report Rekam Kerja</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($totals as $label => $total)
            <div class="panel p-5">
                <div class="text-sm font-semibold text-slate-500">{{ $labels[$label] ?? str_replace('_', ' ', $label) }}</div>
                <div class="mt-3 text-3xl font-black text-slate-900">{{ $total }}</div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="panel p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Status Gangguan</h2>
                <span class="badge">{{ $totals['incidents'] ?? 0 }} total</span>
            </div>
            <div class="mt-4 space-y-2">
                @forelse ($incidentByStatus as $status => $total)
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 text-sm">
                        <span class="font-semibold text-slate-700">{{ $status }}</span>
                        <strong class="text-slate-900">{{ $total }}</strong>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada gangguan.</p>
                @endforelse
            </div>
        </section>

        <section class="panel p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Gangguan Terbaru</h2>
                <a class="text-sm font-bold text-sky-700 hover:text-sky-800" href="{{ route('incidents.index') }}">Lihat semua</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($latestIncidents as $incident)
                    <div class="flex items-center justify-between gap-4 py-3 text-sm">
                        <div class="min-w-0">
                            <div class="truncate font-bold text-slate-900">{{ $incident->title }}</div>
                            <div class="mt-1 text-slate-500">{{ $incident->node?->code ?? '-' }} - {{ $incident->category }}</div>
                        </div>
                        <span class="badge">{{ $incident->status }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada data.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
