<x-layouts.app title="Data Link">
    <div class="toolbar">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Data Link</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola hubungan antar node dan informasi kabel/core.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <input class="form-control w-72" data-table-search="#links-table" placeholder="Cari link...">
            <a class="btn" href="{{ route('reports.links.csv') }}">Export CSV</a>
            <a class="btn" href="{{ route('reports.links.pdf') }}">Export PDF</a>
            <button type="button" class="btn" data-modal-open="#links-import-modal">Import CSV</button>
        </div>
    </div>

    <dialog id="links-import-modal" class="modal-shell">
        <form method="post" action="{{ route('links.import.csv') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <div>
                    <h3 class="text-lg font-bold">Import CSV Link</h3>
                    <p class="mt-1 text-sm text-slate-500">Kolom minimal: source_code, target_code.</p>
                </div>
                <button type="button" class="btn" data-modal-close>Tutup</button>
            </div>
            <div class="modal-body grid gap-4">
                <label class="grid gap-2">
                    <span class="form-label">File CSV</span>
                    <input name="csv" type="file" accept=".csv,text/csv" class="form-control" required>
                </label>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    <div class="font-semibold text-slate-900">Header CSV yang didukung</div>
                    <div class="mt-2 font-mono text-xs">source_code,target_code,cable_type,core_count,core_number,pon_name,odc_name,notes</div>
                    <div class="mt-2">Tips: node harus sudah ada (berdasarkan <code>code</code>).</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-modal-close>Batal</button>
                <button class="btn-primary">Import</button>
            </div>
        </form>
    </dialog>

    <div class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table id="links-table" class="data-table">
                <thead><tr><th>Sumber</th><th>Tujuan</th><th>Kabel</th><th>Core</th><th>Nomor Core</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($links as $link)
                        <tr>
                            <td class="font-bold">{{ $link->source?->code ?? '-' }}</td>
                            <td class="font-bold">{{ $link->target?->code ?? '-' }}</td>
                            <td>{{ $link->cable_type ?: '-' }}</td>
                            <td>{{ $link->core_count ?: '-' }}</td>
                            <td>{{ $link->core_number ?: '-' }}</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" class="btn" data-modal-open="#link-edit-{{ $link->id }}">Edit</button>
                                    <form method="post" action="{{ route('links.destroy', $link) }}">
                                        @csrf @method('delete')
                                        <button class="btn-danger" onclick="return confirm('Hapus link ini?')">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row="1"><td colspan="6" class="text-center text-slate-500">Belum ada link.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach ($links as $link)
        <dialog id="link-edit-{{ $link->id }}" class="modal-shell">
            <form method="post" action="{{ route('links.update', $link) }}">
                @csrf @method('put')
                <div class="modal-header">
                    <div>
                        <h3 class="text-lg font-bold">Edit Link</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $link->source?->code ?? '-' }} ke {{ $link->target?->code ?? '-' }}</p>
                    </div>
                    <button type="button" class="btn" data-modal-close>Tutup</button>
                </div>
                <div class="modal-body grid gap-4 md:grid-cols-2">
                    <label class="grid gap-2"><span class="form-label">Node Sumber</span><select name="source_node_id" class="form-control" required>@foreach ($nodes as $node)<option value="{{ $node->id }}" @selected($link->source_node_id === $node->id)>{{ $node->code }}</option>@endforeach</select></label>
                    <label class="grid gap-2"><span class="form-label">Node Tujuan</span><select name="target_node_id" class="form-control" required>@foreach ($nodes as $node)<option value="{{ $node->id }}" @selected($link->target_node_id === $node->id)>{{ $node->code }}</option>@endforeach</select></label>
                    <label class="grid gap-2"><span class="form-label">Tipe Kabel</span><input name="cable_type" value="{{ $link->cable_type }}" class="form-control" placeholder="FO"></label>
                    <label class="grid gap-2"><span class="form-label">Jumlah Core</span><input name="core_count" value="{{ $link->core_count }}" class="form-control" placeholder="12"></label>
                    <label class="grid gap-2"><span class="form-label">Nomor Core</span><input name="core_number" value="{{ $link->core_number }}" class="form-control" placeholder="1-12"></label>
                    <label class="grid gap-2"><span class="form-label">PON</span><input name="pon_name" value="{{ $link->pon_name }}" class="form-control" placeholder="PON name"></label>
                    <label class="grid gap-2"><span class="form-label">ODC</span><input name="odc_name" value="{{ $link->odc_name }}" class="form-control" placeholder="ODC name"></label>
                    <label class="grid gap-2"><span class="form-label">Catatan</span><input name="notes" value="{{ $link->notes }}" class="form-control" placeholder="Catatan"></label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-modal-close>Batal</button>
                    <button class="btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </dialog>
    @endforeach
</x-layouts.app>
