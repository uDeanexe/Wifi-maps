<x-layouts.app :title="$title">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">{{ $title }}</h2>
            <p class="mt-1 text-sm text-slate-500">Periksa data surat jalan sebelum dicetak atau di-download sebagai PDF.</p>
        </div>
        <a class="btn" href="{{ $backRoute }}">Kembali</a>
    </div>

    <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
        <form method="get" action="{{ $downloadRoute }}" target="_blank" class="panel overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Data Final PDF</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $source['type'] }}: {{ $source['title'] }}</p>
            </div>
            <div class="grid gap-4 px-5 py-5">
                <label class="grid gap-2">
                    <span class="form-label">No Dokumen</span>
                    <input name="document_no" value="{{ $suratJalan['document_no'] }}" class="form-control" data-review-input="document_no" required>
                </label>
                <label class="grid gap-2">
                    <span class="form-label">Tujuan</span>
                    <input name="tujuan" value="{{ $suratJalan['tujuan'] }}" class="form-control" data-review-input="tujuan" required>
                </label>
                <label class="grid gap-2">
                    <span class="form-label">Keperluan</span>
                    <textarea name="keperluan" class="form-control min-h-24" data-review-input="keperluan" required>{{ $suratJalan['keperluan'] }}</textarea>
                </label>
                <label class="grid gap-2">
                    <span class="form-label">Kerusakan / Catatan</span>
                    <textarea name="kerusakan" class="form-control min-h-24" data-review-input="kerusakan">{{ $suratJalan['kerusakan'] }}</textarea>
                </label>
                <label class="grid gap-2">
                    <span class="form-label">NOC / CS</span>
                    <input name="noc_admin" value="{{ $suratJalan['noc_admin'] ?? '' }}" class="form-control" data-review-input="noc_admin">
                </label>
                <label class="grid gap-2">
                    <span class="form-label">Teknisi</span>
                    <input name="teknisi" value="{{ $suratJalan['teknisi'] ?? '' }}" class="form-control" data-review-input="teknisi">
                </label>
                <label class="grid gap-2">
                    <span class="form-label">Kontak Teknisi</span>
                    <input name="teknisi_contact" value="{{ $suratJalan['teknisi_contact'] ?? '' }}" class="form-control" placeholder="08xxxx" data-review-input="teknisi_contact">
                </label>
                <label class="grid gap-2">
                    <span class="form-label">Email Teknisi</span>
                    <input name="teknisi_email" value="{{ $suratJalan['teknisi_email'] ?? '' }}" class="form-control" placeholder="email@domain.com" data-review-input="teknisi_email">
                </label>
                <label class="grid gap-2">
                    <span class="form-label">Kendaraan</span>
                    <input name="kendaraan" value="{{ $suratJalan['kendaraan'] ?? '' }}" class="form-control" placeholder="Motor / Mobil / Plat nomor">
                </label>
            </div>
            <div class="modal-footer">
                <a class="btn" href="{{ $backRoute }}">Batal</a>
                <button class="btn" type="submit" name="print" value="1">Print</button>
                <button class="btn-primary" type="submit">Download PDF</button>
            </div>
        </form>

        <section class="panel overflow-hidden bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-4">
                <div>
                    <h3 class="font-bold text-slate-900">Preview Dokumen</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $source['subtitle'] }}</p>
                </div>
                <span class="badge">Belum dicetak</span>
            </div>
            <div class="bg-slate-100 p-4 sm:p-6">
                <div class="mx-auto min-h-[720px] max-w-3xl rounded-sm bg-white p-8 shadow-xl ring-1 ring-slate-200">
                    <div class="border-b-4 border-sky-600 pb-5">
                        <div class="text-sm font-bold uppercase tracking-wide text-sky-700">PT. JASA ONLINE NUSANTARA</div>
                        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h1 class="text-2xl font-black text-slate-950">Surat Jalan / Work Order</h1>
                                <p class="mt-1 text-sm text-slate-500">Pekerjaan lapangan jaringan</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 px-4 py-3 text-right">
                                <div class="text-xs font-bold uppercase text-slate-500">No Dokumen</div>
                                <div class="mt-1 font-mono text-sm font-bold text-slate-900" data-review-output="document_no">{{ $suratJalan['document_no'] }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 p-4 md:col-span-2">
                            <div class="grid gap-4 md:grid-cols-[1fr_220px] md:items-start">
                                <div>
                                    <div class="text-xs font-bold uppercase text-slate-500">Tujuan</div>
                                    <div class="mt-2 font-semibold text-slate-900" data-review-output="tujuan">{{ $suratJalan['tujuan'] ?: '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs font-bold uppercase text-slate-500">Teknisi</div>
                                    <div class="mt-2 text-sm text-slate-900"><span data-review-output="teknisi">{{ $suratJalan['teknisi'] ?: '-' }}</span></div>
                                    <div class="mt-1 text-sm text-slate-900">Kontak: <span data-review-output="teknisi_contact">{{ $suratJalan['teknisi_contact'] ?: '-' }}</span></div>
                                    <div class="mt-1 text-sm text-slate-900">Email: <span data-review-output="teknisi_email">{{ $suratJalan['teknisi_email'] ?: '-' }}</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-4">
                            <div class="text-xs font-bold uppercase text-slate-500">Keperluan</div>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-800" data-review-output="keperluan">{{ $suratJalan['keperluan'] ?: '-' }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-4">
                            <div class="text-xs font-bold uppercase text-slate-500">QR Alamat</div>
                            <p class="mt-2 text-sm leading-6 text-slate-700">QR tersedia di PDF untuk scan lokasi.</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-4 md:col-span-2">
                            <div class="text-xs font-bold uppercase text-slate-500">Kerusakan / Catatan</div>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-800" data-review-output="kerusakan">{{ $suratJalan['kerusakan'] ?: '-' }}</p>
                        </div>
                    </div>

                    <div class="mt-8">
                        <h4 class="font-bold text-slate-900">Lokasi / Node</h4>
                        <div class="mt-3 overflow-hidden rounded-lg border border-slate-200">
                            <table class="w-full text-left text-sm">
                                <tbody class="divide-y divide-slate-100">
                                    <tr><th class="w-40 bg-slate-50 px-4 py-3">Kode</th><td class="px-4 py-3">{{ $suratJalan['node']['code'] ?? '-' }}</td></tr>
                                    <tr><th class="bg-slate-50 px-4 py-3">Nama</th><td class="px-4 py-3">{{ $suratJalan['node']['name'] ?? '-' }}</td></tr>
                                    <tr><th class="bg-slate-50 px-4 py-3">Jenis</th><td class="px-4 py-3">{{ $suratJalan['node']['type_label'] ?? $suratJalan['node']['type'] ?? '-' }}</td></tr>
                                    <tr><th class="bg-slate-50 px-4 py-3">Alamat</th><td class="px-4 py-3">{{ $suratJalan['node']['address'] ?? '-' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-10 grid grid-cols-3 gap-5 text-center text-sm">
                        @foreach (['Admin NOC', 'Teknisi', 'Supervisor'] as $label)
                            <div>
                                <div class="font-semibold text-slate-900">{{ $label }}</div>
                                <div class="mt-4 h-20 rounded-lg border border-slate-200"></div>
                                <div class="mt-2 text-xs text-slate-500">Nama / Tanggal</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
