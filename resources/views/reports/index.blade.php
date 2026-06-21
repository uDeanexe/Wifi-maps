<x-layouts.app title="Pusat Report">
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="overflow-hidden rounded-2xl bg-slate-950 px-6 py-7 text-white shadow-lg sm:px-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-sky-400/15 px-3 py-1 text-xs font-bold uppercase tracking-wider text-sky-300">Pusat Report</span>
                    <h2 class="mt-3 text-2xl font-black sm:text-3xl">Unduh data jaringan dari satu tempat</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">Pilih report sesuai kebutuhan. PDF cocok untuk dibaca atau dicetak, CSV cocok untuk pengolahan data.</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-white/10 bg-white/5 px-5 py-3"><div class="text-2xl font-black">{{ number_format($nodeCount) }}</div><div class="text-xs text-slate-400">Total node</div></div>
                    <div class="rounded-xl border border-white/10 bg-white/5 px-5 py-3"><div class="text-2xl font-black">{{ number_format($linkCount) }}</div><div class="text-xs text-slate-400">Total link</div></div>
                </div>
            </div>
        </section>

        <section class="panel p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><h3 class="text-lg font-black text-slate-900">Filter report node</h3><p class="mt-1 text-sm text-slate-500">Opsional. Filter ini diterapkan ke report node, visual A4, topology, dan CSV node.</p></div>
                <button type="button" class="btn hidden" data-report-reset>Reset filter</button>
            </div>
            <form class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-report-filter>
                <label><span class="form-label">Cari data</span><input class="form-control mt-1.5" name="q" placeholder="Kode, nama, alamat, catatan"></label>
                <label><span class="form-label">Jenis node</span><select class="form-control mt-1.5" name="type"><option value="">Semua jenis</option>@foreach($nodeTypes as $type)<option value="{{ $type->id }}">{{ $type->label }}</option>@endforeach</select></label>
                <label><span class="form-label">Kelengkapan foto</span><select class="form-control mt-1.5" name="photo"><option value="">Semua</option><option value="with">Ada foto</option><option value="without">Tanpa foto</option></select></label>
                <label><span class="form-label">Koordinat</span><select class="form-control mt-1.5" name="coords"><option value="">Semua</option><option value="with">Ada koordinat</option><option value="without">Tanpa koordinat</option></select></label>
                <label><span class="form-label">Dari tanggal</span><input class="form-control mt-1.5" type="date" name="date_from"></label>
                <label><span class="form-label">Sampai tanggal</span><input class="form-control mt-1.5" type="date" name="date_to"></label>
            </form>
            <p class="mt-4 text-xs font-semibold text-sky-700" data-report-filter-status>Semua data akan disertakan.</p>
        </section>

        @php
            $reports = [
                ['title'=>'Topology jaringan','desc'=>'Ringkasan lengkap node dan link dalam satu dokumen.','format'=>'PDF','url'=>route('reports.topology.pdf'),'filter'=>true,'count'=>$nodeCount.' node · '.$linkCount.' link'],
                ['title'=>'Daftar node','desc'=>'Tabel inventaris node yang ringkas dan siap dicetak.','format'=>'PDF','url'=>route('reports.nodes.pdf'),'filter'=>true,'count'=>$nodeCount.' node'],
                ['title'=>'Dokumentasi visual A4','desc'=>'Satu dokumentasi lokasi per halaman dengan foto dan detail.','format'=>'PDF','url'=>route('reports.nodes.visual-a4.pdf'),'filter'=>true,'count'=>$nodeCount.' node'],
                ['title'=>'Daftar link','desc'=>'Data koneksi, kabel, core, PON, ODC, dan catatan.','format'=>'PDF','url'=>route('reports.links.pdf'),'filter'=>true,'count'=>$linkCount.' link'],
                ['title'=>'Sticker QR link','desc'=>'Sticker QR siap cetak, tiga salinan untuk setiap link.','format'=>'PDF','url'=>route('reports.links.stickers.all.pdf'),'filter'=>false,'count'=>$linkCount.' link'],
                ['title'=>'Data node mentah','desc'=>'Format spreadsheet untuk audit atau pengolahan lanjutan.','format'=>'CSV','url'=>route('reports.nodes.csv'),'filter'=>true,'count'=>$nodeCount.' node'],
                ['title'=>'Data link mentah','desc'=>'Format spreadsheet untuk backup dan pengolahan data link.','format'=>'CSV','url'=>route('reports.links.csv'),'filter'=>true,'count'=>$linkCount.' link'],
            ];
        @endphp
        <section>
            <div class="mb-4"><h3 class="text-lg font-black text-slate-900">Pilih report</h3><p class="mt-1 text-sm text-slate-500">File akan dibuat saat tombol unduh ditekan.</p></div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($reports as $report)
                    <article class="report-card panel flex flex-col p-5">
                        <div class="flex items-start justify-between gap-3"><span class="badge {{ $report['format'] === 'PDF' ? '!bg-rose-50 !text-rose-700' : '!bg-emerald-50 !text-emerald-700' }}">{{ $report['format'] }}</span><span class="text-xs font-bold text-slate-400">{{ $report['count'] }}</span></div>
                        <h4 class="mt-4 text-base font-black text-slate-900">{{ $report['title'] }}</h4>
                        <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">{{ $report['desc'] }}</p>
                        <a class="btn-primary mt-5 w-full" href="{{ $report['url'] }}" data-report-download data-report-base-url="{{ $report['url'] }}" data-report-use-filter="{{ $report['filter'] ? '1' : '0' }}">Unduh {{ $report['format'] }}</a>
                    </article>
                @endforeach
            </div>
        </section>
    </div>

    <div class="report-progress" data-report-progress role="status" aria-live="polite" hidden>
        <span class="report-spinner"></span><span>Report sedang disiapkan...</span>
    </div>
</x-layouts.app>
