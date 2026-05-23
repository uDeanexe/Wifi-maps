<x-layouts.app title="Gangguan">
    <div class="toolbar">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Gangguan</h2>
            <p class="mt-1 text-sm text-slate-500">Alur laporan user, surat jalan NOC, dan laporan balik teknisi.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <input class="form-control w-72" data-table-search="#incidents-table" placeholder="Cari gangguan...">
            <a class="btn" href="{{ route('reports.incidents.csv') }}">Export CSV</a>
            <a class="btn" href="{{ route('reports.incidents.pdf') }}">Export PDF</a>
            <button type="button" class="btn" data-modal-open="#incidents-import-modal">Import CSV</button>
            <button type="button" class="btn-primary" data-modal-open="#incident-create-modal">Tambah Gangguan</button>
        </div>
    </div>

    <dialog id="incidents-import-modal" class="modal-shell">
        <form method="post" action="{{ route('incidents.import.csv') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <div>
                    <h3 class="text-lg font-bold">Import CSV Gangguan</h3>
                    <p class="mt-1 text-sm text-slate-500">Kolom minimal: title, category. node_code opsional.</p>
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
                    <div class="mt-2 font-mono text-xs">id,title,category,status,node_code,description,reporter_name,reporter_contact,noc_admin_name,technician_name,technician_contact,technician_email,work_order_notes</div>
                    <div class="mt-2">Tips: isi <code>id</code> jika ingin update incident yang sudah ada.</div>
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
            <table id="incidents-table" class="data-table">
                <thead><tr><th>Judul</th><th>Node</th><th>Kategori</th><th>Status</th><th>Pelapor</th><th>Teknisi</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($incidents as $incident)
                        <tr>
                            <td>
                                <div class="font-bold text-slate-900">{{ $incident->title }}</div>
                                <div class="mt-1 max-w-md truncate text-xs text-slate-500">{{ $incident->description ?: '-' }}</div>
                                <div class="mt-1 text-xs font-semibold text-sky-700">
                                    {{ $incident->noc_admin_name ? 'NOC/CS: '.$incident->noc_admin_name : 'NOC/CS belum diisi' }}
                                </div>
                            </td>
                            <td class="font-mono text-xs font-bold">{{ $incident->node?->code ?? '-' }}</td>
                            <td>{{ $incident->category === 'internet_mati' ? 'Internet Mati' : 'Kerusakan' }}</td>
                            <td><span class="badge">{{ $incident->status }}</span></td>
                            <td>{{ $incident->reporter_name ?: '-' }}</td>
                            <td>{{ $incident->technician_name ?: '-' }}</td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <a class="btn" href="{{ route('incidents.surat-jalan.review', $incident) }}">Surat Jalan</a>
                                    <button type="button" class="btn" data-modal-open="#incident-wa-{{ $incident->id }}">Kirim WA</button>
                                    <a class="btn" href="{{ route('incidents.share-message', $incident) }}" target="_blank">Copy Text</a>
                                    @if (! in_array($incident->status, ['completed', 'closed'], true))
                                        <form method="post" action="{{ route('incidents.confirm', $incident) }}">
                                            @csrf @method('patch')
                                            <button class="btn">Konfirmasi Teknisi</button>
                                        </form>
                                        <button type="button" class="btn-primary" data-modal-open="#incident-complete-{{ $incident->id }}">Selesai</button>
                                    @else
                                        <a class="btn" href="{{ route('work-reports.index') }}">Rekam Kerja</a>
                                    @endif
                                    <form method="post" action="{{ route('incidents.destroy', $incident) }}">
                                        @csrf @method('delete')
                                        <button class="btn-danger" onclick="return confirm('Hapus gangguan {{ $incident->title }}?')">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row="1"><td colspan="7" class="text-center text-slate-500">Belum ada gangguan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <dialog id="incident-create-modal" class="modal-shell">
        <form method="post" action="{{ route('incidents.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <div>
                    <h3 class="text-lg font-bold">Tambah Gangguan</h3>
                    <p class="mt-1 text-sm text-slate-500">Laporan dari NOC/CS, dibuatkan surat jalan, lalu dikirim ke teknisi.</p>
                </div>
                <button type="button" class="btn" data-modal-close>Tutup</button>
            </div>
            <div class="modal-body grid gap-4 md:grid-cols-2">
                <label class="grid gap-2"><span class="form-label">Node</span><select name="node_id" class="form-control"><option value="">Node opsional</option>@foreach ($nodes as $node)<option value="{{ $node->id }}">{{ $node->code }}</option>@endforeach</select></label>
                <label class="grid gap-2"><span class="form-label">Kategori</span><select name="category" class="form-control" required><option value="kerusakan">Kerusakan</option><option value="internet_mati">Internet Mati</option></select></label>
                <label class="grid gap-2 md:col-span-2"><span class="form-label">Judul</span><input name="title" class="form-control" placeholder="Judul gangguan" required></label>
                <label class="grid gap-2"><span class="form-label">Pelapor</span><input name="reporter_name" class="form-control" placeholder="Nama pelapor"></label>
                <label class="grid gap-2"><span class="form-label">Kontak Pelapor</span><input name="reporter_contact" class="form-control" placeholder="Nomor kontak"></label>
                <label class="grid gap-2"><span class="form-label">NOC / CS</span><input name="noc_admin_name" class="form-control" placeholder="Nama NOC atau CS"></label>
                <label class="grid gap-2">
                    <span class="form-label">Pilih Teknisi</span>
                    <select class="form-control" data-tech-picker>
                        <option value="">Teknisi opsional</option>
                        @foreach (($technicians ?? collect()) as $tech)
                            <option value="{{ $tech->id }}" data-name="{{ $tech->name }}" data-phone="{{ $tech->phone }}" data-email="{{ $tech->email }}">{{ $tech->name }}{{ $tech->phone ? ' ('.$tech->phone.')' : '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-2"><span class="form-label">Teknisi</span><input name="technician_name" class="form-control" placeholder="Nama teknisi" data-tech-name></label>
                <label class="grid gap-2"><span class="form-label">Kontak Teknisi</span><input name="technician_contact" class="form-control" placeholder="Nomor teknisi" data-tech-phone></label>
                <label class="grid gap-2"><span class="form-label">Email Teknisi</span><input name="technician_email" type="email" class="form-control" placeholder="email@domain.com" data-tech-email></label>
                <label class="grid gap-2"><span class="form-label">Foto</span><input name="photo" type="file" accept="image/*" class="form-control"></label>
                <label class="grid gap-2 md:col-span-2"><span class="form-label">Keluhan</span><textarea name="description" class="form-control min-h-24" placeholder="Keterangan gangguan"></textarea></label>
                <label class="grid gap-2 md:col-span-2"><span class="form-label">Instruksi NOC</span><textarea name="work_order_notes" class="form-control min-h-24" placeholder="Instruksi pekerjaan"></textarea></label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-modal-close>Batal</button>
                <button class="btn-primary">Simpan Gangguan</button>
            </div>
        </form>
    </dialog>

    <script>
        (() => {
            const picker = document.querySelector('#incident-create-modal [data-tech-picker]');
            const nameInput = document.querySelector('#incident-create-modal [data-tech-name]');
            const phoneInput = document.querySelector('#incident-create-modal [data-tech-phone]');
            const emailInput = document.querySelector('#incident-create-modal [data-tech-email]');
            if (!picker || !nameInput || !phoneInput || !emailInput) return;

            picker.addEventListener('change', () => {
                const opt = picker.selectedOptions?.[0];
                if (!opt || !opt.value) return;
                if (!nameInput.value.trim()) nameInput.value = opt.dataset.name || '';
                if (!phoneInput.value.trim()) phoneInput.value = opt.dataset.phone || '';
                if (!emailInput.value.trim()) emailInput.value = opt.dataset.email || '';
            });
        })();
    </script>

    @foreach ($incidents as $incident)
        <dialog id="incident-wa-{{ $incident->id }}" class="modal-shell">
            <form method="get" action="{{ route('incidents.whatsapp', $incident) }}" target="_blank">
                <div class="modal-header">
                    <div>
                        <h3 class="text-lg font-bold">Kirim WhatsApp</h3>
                        <p class="mt-1 text-sm text-slate-500">Pesan otomatis dari sistem, dikirim ke 1 teknisi yang dipilih.</p>
                    </div>
                    <button type="button" class="btn" data-modal-close>Tutup</button>
                </div>
                <div class="modal-body grid gap-4">
                    <label class="grid gap-2">
                        <span class="form-label">Teknisi</span>
                        <input value="{{ $incident->technician_name ?: '-' }}" class="form-control" disabled>
                    </label>
                    <label class="grid gap-2">
                        <span class="form-label">Kontak Teknisi (WA)</span>
                        <input name="phone" value="{{ $incident->technician_contact ?: '' }}" class="form-control" placeholder="Contoh: 0812xxxx / +62812xxxx" required>
                    </label>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                        <div class="font-semibold text-slate-900">Preview Pesan</div>
                        <p class="mt-2 whitespace-pre-line">{{ app(\App\Services\MappingService::class)->incidentMessage($incident) }}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-modal-close>Batal</button>
                    <button class="btn-primary">Buka WhatsApp</button>
                </div>
            </form>
        </dialog>

        <dialog id="incident-complete-{{ $incident->id }}" class="modal-shell">
            <form method="post" action="{{ route('incidents.complete', $incident) }}">
                @csrf @method('patch')
                <div class="modal-header">
                    <div>
                        <h3 class="text-lg font-bold">Konfirmasi Selesai Teknisi</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $incident->title }} - hasil ini otomatis masuk ke Rekam Kerja.</p>
                    </div>
                    <button type="button" class="btn" data-modal-close>Tutup</button>
                </div>
                <div class="modal-body grid gap-4">
                    <label class="grid gap-2"><span class="form-label">Laporan Teknisi</span><textarea name="technician_report" class="form-control min-h-32" placeholder="Hasil pekerjaan teknisi" required></textarea></label>
                    <label class="grid gap-2"><span class="form-label">Status</span><select name="status" class="form-control"><option value="completed">Completed</option><option value="closed">Closed</option></select></label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-modal-close>Batal</button>
                    <button class="btn-primary">Simpan Laporan</button>
                </div>
            </form>
        </dialog>
    @endforeach
</x-layouts.app>
