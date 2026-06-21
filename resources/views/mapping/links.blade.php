<x-layouts.app title="Data Link">
    <div class="space-y-5">
        <section class="data-page-hero">
            <div><div class="data-page-eyebrow">Koneksi jaringan</div><h2 class="data-page-title">Data Link</h2><p class="data-page-description">Kelola hubungan antar node, kabel, core, PON, dan ODC dengan lebih cepat.</p></div>
            <div class="flex flex-wrap gap-2"><a class="btn-hero" href="{{ route('topology') }}">Lihat Topology</a><button type="button" class="btn-primary gap-2" data-modal-open="#link-create-modal" data-primary-create><span class="text-lg leading-none">+</span> Tambah Link</button></div>
        </section>
        <section class="grid gap-3 sm:grid-cols-3">
            <div class="data-stat"><span class="data-stat-icon bg-sky-50 text-sky-700">↗</span><div><strong>{{ $links->count() }}</strong><span>Total link</span></div></div>
            <div class="data-stat"><span class="data-stat-icon bg-amber-50 text-amber-700">FO</span><div><strong>{{ $links->where('cable_type', 'FO')->count() }}</strong><span>Link fiber optic</span></div></div>
            <div class="data-stat"><span class="data-stat-icon bg-violet-50 text-violet-700">QR</span><div><strong>{{ $links->count() }}</strong><span>Sticker tersedia</span></div></div>
        </section>
        <section class="panel p-4 sm:p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex w-full gap-2 lg:max-w-md"><label class="relative min-w-0 flex-1"><span class="sr-only">Cari link</span><input class="form-control !pl-10" data-table-search="#links-table" data-search-summary="#links-search-summary" placeholder="Cari sumber, tujuan, kabel, core..."><span class="pointer-events-none absolute left-3 top-2.5 text-slate-400">⌕</span></label><button type="button" class="btn-compact hidden" data-clear-search="#links-table">Bersihkan</button></div>
                <div class="flex flex-wrap gap-2"><a class="btn-compact" href="{{ route('reports.index') }}">Pusat Report</a><a class="btn-compact" href="{{ route('reports.links.csv') }}" data-file-download="CSV link sedang disiapkan.">Unduh CSV</a><a class="btn-compact" href="{{ route('reports.links.stickers.all.pdf') }}" target="_blank" rel="noopener" data-file-download="Sticker QR sedang dibuat.">Semua Sticker QR</a><button type="button" class="btn-compact" data-modal-open="#links-import-modal">Import CSV</button></div>
            </div>
        </section>
    </div>

    <dialog id="link-create-modal" class="modal-shell">
        <form method="post" action="{{ route('links.store') }}" data-link-form>@csrf
            <div class="modal-header"><div><h3 class="text-lg font-black">Tambah Link Baru</h3><p class="mt-1 text-sm text-slate-500">Hubungkan dua node yang berbeda.</p></div><button type="button" class="btn" data-modal-close>Tutup</button></div>
            <div class="modal-body grid gap-4 md:grid-cols-2">
                <label class="grid gap-2"><span class="form-label">Node Sumber</span><select name="source_node_id" class="form-control" data-link-source required><option value="">Pilih sumber</option>@foreach($nodes as $node)<option value="{{ $node->id }}">{{ $node->code }}{{ $node->name ? ' — '.$node->name : '' }}</option>@endforeach</select></label>
                <label class="grid gap-2"><span class="form-label">Node Tujuan</span><select name="target_node_id" class="form-control" data-link-target required><option value="">Pilih tujuan</option>@foreach($nodes as $node)<option value="{{ $node->id }}">{{ $node->code }}{{ $node->name ? ' — '.$node->name : '' }}</option>@endforeach</select></label>
                <div class="md:col-span-2 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-sky-100 bg-sky-50 px-4 py-3"><p class="text-xs font-semibold text-sky-800" data-link-pair-status>Pilih node sumber dan tujuan yang berbeda.</p><button type="button" class="btn-compact" data-swap-link-nodes>Tukar Node</button></div>
                <label class="grid gap-2"><span class="form-label">Tipe Kabel</span><input name="cable_type" class="form-control" placeholder="Contoh: FO"></label><label class="grid gap-2"><span class="form-label">Jumlah Core</span><input name="core_count" class="form-control" inputmode="numeric" placeholder="12"></label>
                <label class="grid gap-2"><span class="form-label">Nomor Core</span><input name="core_number" class="form-control" placeholder="1-12"></label><label class="grid gap-2"><span class="form-label">PON</span><input name="pon_name" class="form-control" placeholder="Nama PON"></label>
                <label class="grid gap-2"><span class="form-label">ODC</span><input name="odc_name" class="form-control" placeholder="Nama ODC"></label><label class="grid gap-2"><span class="form-label">Catatan</span><input name="notes" class="form-control" placeholder="Catatan teknis"></label>
            </div><div class="modal-footer"><button type="button" class="btn" data-modal-close>Batal</button><button class="btn-primary">Simpan Link</button></div>
        </form>
    </dialog>

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

    <div class="panel mt-5 overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h3 class="font-black text-slate-900">Daftar Link</h3><p id="links-search-summary" class="text-xs text-slate-500">{{ $links->count() }} koneksi tersimpan</p></div></div>
        <div class="overflow-x-auto">
            <table id="links-table" class="data-table responsive-data-table">
                <thead><tr><th><button type="button" class="table-sort" data-sort-table="#links-table" data-sort-column="0">Sumber <span>↕</span></button></th><th><button type="button" class="table-sort" data-sort-table="#links-table" data-sort-column="1">Tujuan <span>↕</span></button></th><th><button type="button" class="table-sort" data-sort-table="#links-table" data-sort-column="2">Kabel <span>↕</span></button></th><th><button type="button" class="table-sort" data-sort-table="#links-table" data-sort-column="3" data-sort-type="number">Core <span>↕</span></button></th><th>Nomor Core</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($links as $link)
                        <tr>
                            <td data-label="Sumber" class="font-bold">{{ $link->source?->code ?? '-' }}</td>
                            <td data-label="Tujuan" class="font-bold">{{ $link->target?->code ?? '-' }}</td>
                            <td data-label="Kabel">{{ $link->cable_type ?: '-' }}</td>
                            <td data-label="Core">{{ $link->core_count ?: '-' }}</td>
                            <td data-label="Nomor Core">{{ $link->core_number ?: '-' }}</td>
                            <td data-label="Aksi" class="text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" class="btn-compact" data-copy-text="{{ $link->source?->code ?? '-' }} → {{ $link->target?->code ?? '-' }}" data-copy-label="Rute link">Salin</button>
                                    <a class="btn" href="{{ route('reports.links.stickers.pdf', $link) }}" target="_blank" rel="noopener">Print QR</a>
                                    <button type="button" class="btn" data-modal-open="#link-edit-{{ $link->id }}">Edit</button>
                                    <form method="post" action="{{ route('links.destroy', $link) }}" data-confirm-form="Hapus link {{ $link->source?->code ?? '-' }} ke {{ $link->target?->code ?? '-' }}?">
                                        @csrf @method('delete')
                                        <button class="btn-danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row="1"><td colspan="6"><div class="data-empty"><strong>Link belum tersedia</strong><span>Tambahkan koneksi pertama antar node.</span><button type="button" class="btn-primary mt-3" data-modal-open="#link-create-modal">Tambah Link</button></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-pagination" data-table-pagination="#links-table"></div>
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
                    <a class="btn" href="{{ route('reports.links.stickers.pdf', $link) }}" target="_blank" rel="noopener">Print Sticker (3 lembar)</a>
                    <button class="btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </dialog>
    @endforeach
</x-layouts.app>
