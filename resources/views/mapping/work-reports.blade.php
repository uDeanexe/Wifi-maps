<x-layouts.app title="Rekam Kerja">
    <div class="toolbar">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Rekam Kerja</h2>
            <p class="mt-1 text-sm text-slate-500">Catatan pekerjaan lapangan dan bukti teknisi.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <input class="form-control w-72" data-table-search="#work-reports-table" placeholder="Cari rekam kerja...">
            <a class="btn" href="{{ route('reports.work-reports.pdf') }}">Export PDF</a>
            <button type="button" class="btn-primary" data-modal-open="#work-report-create-modal">Tambah dari Gangguan</button>
        </div>
    </div>

    <div class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table id="work-reports-table" class="data-table">
                <thead><tr><th>Judul</th><th>Teknisi</th><th>Node</th><th>Gangguan</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            <td><div class="font-bold">{{ $report->report_title }}</div><div class="mt-1 max-w-md truncate text-xs text-slate-500">{{ $report->description }}</div></td>
                            <td>{{ $report->technician_name ?: '-' }}</td>
                            <td class="font-mono text-xs font-bold">{{ $report->node?->code ?? '-' }}</td>
                            <td>{{ $report->incident?->title ?? '-' }}</td>
                            <td><span class="badge">{{ $report->status }}</span></td>
                            <td class="text-right">
                                <form method="post" action="{{ route('work-reports.destroy', $report) }}">
                                    @csrf @method('delete')
                                    <button class="btn-danger" onclick="return confirm('Hapus rekam kerja ini?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row="1"><td colspan="6" class="text-center text-slate-500">Belum ada rekam kerja.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <dialog id="work-report-create-modal" class="modal-shell">
        <form method="post" action="{{ route('work-reports.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <div>
                    <h3 class="text-lg font-bold">Tambah Rekam Kerja dari Gangguan</h3>
                    <p class="mt-1 text-sm text-slate-500">Pilih gangguan, lalu isi hasil pekerjaan teknisi.</p>
                </div>
                <button type="button" class="btn" data-modal-close>Tutup</button>
            </div>
            <div class="modal-body grid gap-4 md:grid-cols-2">
                <label class="grid gap-2 md:col-span-2"><span class="form-label">Gangguan</span><select name="incident_id" class="form-control" required><option value="">Pilih gangguan</option>@foreach ($incidents as $incident)<option value="{{ $incident->id }}">{{ $incident->title }} - {{ $incident->status }}</option>@endforeach</select></label>
                <label class="grid gap-2"><span class="form-label">Node</span><select name="node_id" class="form-control"><option value="">Node opsional</option>@foreach ($nodes as $node)<option value="{{ $node->id }}">{{ $node->code }}</option>@endforeach</select></label>
                <label class="grid gap-2"><span class="form-label">Teknisi</span><input name="technician_name" class="form-control" placeholder="Nama teknisi"></label>
                <label class="grid gap-2"><span class="form-label">Foto Bukti</span><input name="photo" type="file" accept="image/*" class="form-control"></label>
                <label class="grid gap-2 md:col-span-2"><span class="form-label">Judul Laporan</span><input name="report_title" class="form-control" placeholder="Judul laporan" required></label>
                <label class="grid gap-2 md:col-span-2"><span class="form-label">Keterangan</span><textarea name="description" class="form-control min-h-32" placeholder="Keterangan pekerjaan" required></textarea></label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-modal-close>Batal</button>
                <button class="btn-primary">Simpan Rekam Kerja</button>
            </div>
        </form>
    </dialog>
</x-layouts.app>
