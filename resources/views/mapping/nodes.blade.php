<x-layouts.app title="Data Node">
    <div class="toolbar">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Data Node</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola ODC, PON, ODP, tiang, server, dan customer.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a class="btn" href="{{ route('reports.nodes.csv', request()->query()) }}">Export CSV</a>
            <a class="btn" href="{{ route('reports.nodes.pdf', request()->query()) }}">PDF Node</a>
            <a class="btn" href="{{ route('reports.nodes.visual-a4.pdf', request()->query()) }}">PDF Visual A4</a>
            <a class="btn" href="{{ route('reports.topology.pdf', request()->query()) }}">PDF Topology</a>
            <button type="button" class="btn" data-modal-open="#nodes-import-modal">Import CSV</button>
            <button type="button" class="btn-primary" data-modal-open="#node-create-modal">Tambah Node</button>
        </div>
    </div>

    <form method="get" action="{{ route('nodes.index') }}" class="panel mb-5 grid gap-4 p-4 lg:grid-cols-[1.4fr_1fr_1fr_1fr_1fr_1fr_auto] lg:items-end">
        <label class="grid gap-2">
            <span class="form-label">Cari</span>
            <input name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Kode, nama, alamat, catatan...">
        </label>
        <label class="grid gap-2">
            <span class="form-label">Tipe Node</span>
            <select name="type" class="form-control">
                <option value="">Semua tipe</option>
                @foreach ($nodeTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) ($filters['type'] ?? '') === (string) $type->id)>{{ $type->label }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-2">
            <span class="form-label">Foto</span>
            <select name="photo" class="form-control">
                <option value="">Semua</option>
                <option value="with" @selected(($filters['photo'] ?? '') === 'with')>Ada foto</option>
                <option value="without" @selected(($filters['photo'] ?? '') === 'without')>Belum ada foto</option>
            </select>
        </label>
        <label class="grid gap-2">
            <span class="form-label">Koordinat</span>
            <select name="coords" class="form-control">
                <option value="">Semua</option>
                <option value="with" @selected(($filters['coords'] ?? '') === 'with')>Ada koordinat</option>
                <option value="without" @selected(($filters['coords'] ?? '') === 'without')>Belum ada koordinat</option>
            </select>
        </label>
        <label class="grid gap-2">
            <span class="form-label">Dari Tanggal</span>
            <input name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
        </label>
        <label class="grid gap-2">
            <span class="form-label">Sampai Tanggal</span>
            <input name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
        </label>
        <div class="flex flex-wrap gap-2">
            <button class="btn-primary">Terapkan</button>
            <a class="btn" href="{{ route('nodes.index') }}">Reset</a>
        </div>
    </form>

    <dialog id="nodes-import-modal" class="modal-shell">
        <form method="post" action="{{ route('nodes.import.csv') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <div>
                    <h3 class="text-lg font-bold">Import CSV Node</h3>
                    <p class="mt-1 text-sm text-slate-500">Kolom minimal: code, type (atau type_label).</p>
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
                    <div class="mt-2 font-mono text-xs">code,type,type_label,name,latitude,longitude,address,notes</div>
                    <div class="mt-2">Tips: export dulu via tombol <strong>Export CSV</strong> untuk contoh format.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-modal-close>Batal</button>
                <button class="btn-primary">Import</button>
            </div>
        </form>
    </dialog>

    <dialog id="node-create-modal" class="modal-shell node-create-modal">
        <div class="grid overflow-hidden lg:grid-cols-[420px_1fr]">
            <form method="post" action="{{ route('nodes.store') }}" enctype="multipart/form-data" class="bg-white">
                @csrf
                <div class="modal-header">
                    <div class="flex items-center gap-3">
                        <div class="grid h-11 w-11 place-items-center rounded-xl bg-sky-600 font-black text-white shadow-sm">+</div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900">Tambah Node</h3>
                            <p class="mt-1 text-sm text-slate-500">Isi koordinat, klik peta, atau ambil posisi by GPS.</p>
                        </div>
                    </div>
                    <button type="button" class="btn" data-modal-close>Tutup</button>
                </div>
                <div class="modal-body grid gap-4">
                    <label class="grid gap-2">
                        <span class="form-label">Tipe</span>
                        <select name="node_type_id" class="form-control" required>
                            <option value="">Pilih tipe node</option>
                            @foreach ($nodeTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-2"><span class="form-label">Kode</span><input name="code" class="form-control" placeholder="ODC-001" required></label>
                        <label class="grid gap-2"><span class="form-label">Nama</span><input name="name" class="form-control" placeholder="Nama node"></label>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-2"><span class="form-label">Latitude</span><input name="latitude" data-node-lat class="form-control" placeholder="-6.2615"></label>
                        <label class="grid gap-2"><span class="form-label">Longitude</span><input name="longitude" data-node-lng class="form-control" placeholder="107.1528"></label>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="btn" data-node-gps>By GPS</button>
                        <button type="button" class="btn" data-node-center-map>Preview Koordinat</button>
                        <span data-node-gps-status class="px-2 py-2 text-xs font-semibold text-slate-500">Klik peta untuk memilih titik.</span>
                    </div>
                    <label class="grid gap-2"><span class="form-label">Alamat</span><input name="address" class="form-control" placeholder="Alamat lokasi"></label>
                    <div class="grid gap-2">
                        <span class="form-label">Foto</span>
                        <div class="dropzone" data-dropzone>
                            <input name="photo" type="file" accept="image/*" class="sr-only" data-dropzone-input>
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-900">Drag &amp; drop foto</div>
                                    <div class="mt-1 text-xs text-slate-500">Atau klik tombol pilih file. Format: JPG/PNG/WEBP.</div>
                                    <div class="mt-2 text-xs text-slate-600" data-dropzone-meta>Belum ada file dipilih.</div>
                                </div>
                                <div class="flex shrink-0 flex-col gap-2">
                                    <button type="button" class="dropzone-button" data-dropzone-pick>Pilih File</button>
                                    <button type="button" class="dropzone-clear hidden" data-dropzone-clear>Clear</button>
                                </div>
                            </div>
                            <img data-dropzone-preview class="mt-4 hidden w-full rounded-lg border border-slate-200 bg-white object-contain" alt="Preview foto">
                        </div>
                    </div>
                    <label class="grid gap-2"><span class="form-label">Catatan</span><textarea name="notes" class="form-control min-h-24" placeholder="Catatan teknis"></textarea></label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-modal-close>Batal</button>
                    <button class="btn-primary">Simpan & Buka Map</button>
                </div>
            </form>

            <div class="relative min-h-[520px] bg-slate-100">
                <div class="absolute left-4 top-4 z-10 rounded-xl border border-white/70 bg-white/95 px-4 py-3 shadow-sm backdrop-blur">
                    <div class="text-xs font-bold uppercase text-slate-500">Preview Lokasi</div>
                    <div class="mt-1 text-sm font-black text-slate-900">Klik peta atau pakai GPS</div>
                </div>
                <div id="node-create-map" class="h-full min-h-[520px] w-full"></div>
            </div>
        </div>
    </dialog>

    <div class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table id="nodes-table" class="data-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Tipe</th>
                        <th>Nama</th>
                        <th>Koordinat</th>
                        <th>Alamat</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($nodes as $node)
                        <tr>
                            <td class="font-bold text-slate-900">{{ $node->code }}</td>
                            <td><span class="badge">{{ $node->type?->label ?? '-' }}</span></td>
                            <td>{{ $node->name ?: '-' }}</td>
                            <td class="font-mono text-xs">{{ $node->latitude ?: '-' }}, {{ $node->longitude ?: '-' }}</td>
                            <td class="max-w-xs truncate">{{ $node->address ?: '-' }}</td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    @if (is_numeric($node->latitude) && is_numeric($node->longitude))
                                        <a class="btn" href="{{ route('map', ['focus_node' => $node->id]) }}">Map</a>
                                    @endif
                                    <button type="button" class="btn" data-modal-open="#node-edit-{{ $node->id }}">Edit</button>
                                    <form method="post" action="{{ route('nodes.destroy', $node) }}">
                                        @csrf @method('delete')
                                        <button class="btn-danger" onclick="return confirm('Hapus node {{ $node->code }}?')">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row="1"><td colspan="6" class="text-center text-slate-500">Belum ada node.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach ($nodes as $node)
        <dialog id="node-edit-{{ $node->id }}" class="modal-shell">
            <form method="post" action="{{ route('nodes.update', $node) }}" enctype="multipart/form-data">
                @csrf @method('put')
                <div class="modal-header">
                    <div>
                        <h3 class="text-lg font-bold">Edit Node {{ $node->code }}</h3>
                        <p class="mt-1 text-sm text-slate-500">Data master node hanya diedit atau dihapus dari halaman ini.</p>
                    </div>
                    <button type="button" class="btn" data-modal-close>Tutup</button>
                </div>
                <div class="modal-body grid gap-4 md:grid-cols-2">
                    <label class="grid gap-2"><span class="form-label">Tipe</span><select name="node_type_id" class="form-control" required>@foreach ($nodeTypes as $type)<option value="{{ $type->id }}" @selected($node->node_type_id === $type->id)>{{ $type->label }}</option>@endforeach</select></label>
                    <label class="grid gap-2"><span class="form-label">Kode</span><input name="code" value="{{ $node->code }}" class="form-control" required></label>
                    <label class="grid gap-2"><span class="form-label">Nama</span><input name="name" value="{{ $node->name }}" class="form-control" placeholder="Nama node"></label>
                    <div class="grid gap-2 md:col-span-2">
                        <span class="form-label">Ganti Foto</span>
                        <div
                            class="dropzone"
                            data-dropzone
                            @if ($node->photo_path) data-dropzone-initial-src="{{ url($node->photo_path) }}" data-dropzone-initial-label="Foto saat ini." @endif
                        >
                            <input name="photo" type="file" accept="image/*" class="sr-only" data-dropzone-input>
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-900">Drag &amp; drop foto baru</div>
                                    <div class="mt-1 text-xs text-slate-500">Kalau tidak pilih file, foto lama tetap dipakai.</div>
                                    <div class="mt-2 text-xs text-slate-600" data-dropzone-meta>{{ $node->photo_path ? 'Foto saat ini.' : 'Belum ada file dipilih.' }}</div>
                                </div>
                                <div class="flex shrink-0 flex-col gap-2">
                                    <button type="button" class="dropzone-button" data-dropzone-pick>Pilih File</button>
                                    <button type="button" class="dropzone-clear hidden" data-dropzone-clear>Clear</button>
                                </div>
                            </div>
                            <img
                                data-dropzone-preview
                                class="mt-4 w-full rounded-lg border border-slate-200 bg-white object-contain {{ $node->photo_path ? '' : 'hidden' }}"
                                alt="Preview foto"
                                @if ($node->photo_path) src="{{ url($node->photo_path) }}" @endif
                            >
                        </div>
                    </div>
                    <label class="grid gap-2"><span class="form-label">Latitude</span><input name="latitude" value="{{ $node->latitude }}" class="form-control" placeholder="-6.2615"></label>
                    <label class="grid gap-2"><span class="form-label">Longitude</span><input name="longitude" value="{{ $node->longitude }}" class="form-control" placeholder="107.1528"></label>
                    <label class="grid gap-2 md:col-span-2"><span class="form-label">Alamat</span><input name="address" value="{{ $node->address }}" class="form-control" placeholder="Alamat lokasi"></label>
                    <label class="grid gap-2 md:col-span-2"><span class="form-label">Catatan</span><textarea name="notes" class="form-control min-h-24" placeholder="Catatan teknis">{{ $node->notes }}</textarea></label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-modal-close>Batal</button>
                    <button class="btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </dialog>
    @endforeach

    <style>
        #node-create-modal {
            width: min(1120px, calc(100vw - 2rem));
        }

        #node-create-modal .modal-body {
            max-height: calc(88vh - 150px);
        }

        #node-create-map,
        #node-create-map .leaflet-container {
            width: 100%;
            height: 100%;
        }

        @media (max-width: 1023px) {
            #node-create-modal .modal-body {
                max-height: 45vh;
            }
        }
    </style>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (() => {
            const existingNodes = @json($nodes->map(fn ($node) => [
                'latitude' => $node->latitude,
                'longitude' => $node->longitude,
            ])->values());
            const latInput = document.querySelector('[data-node-lat]');
            const lngInput = document.querySelector('[data-node-lng]');
            const gpsButton = document.querySelector('[data-node-gps]');
            const centerButton = document.querySelector('[data-node-center-map]');
            const status = document.querySelector('[data-node-gps-status]');
            const el = document.getElementById('node-create-map');
            if (!el || !window.L) return;

            const normalizePoint = (latRaw, lngRaw) => {
                const lat = Number(latRaw);
                const lng = Number(lngRaw);
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
                let fixedLat = lat;
                let fixedLng = lng;
                if (Math.abs(fixedLat) > 90 && Math.abs(fixedLng) <= 90) {
                    [fixedLat, fixedLng] = [fixedLng, fixedLat];
                }
                if (Math.abs(fixedLat) > 90 || Math.abs(fixedLng) > 180) return null;
                if (Math.abs(fixedLat) < 1e-9 && Math.abs(fixedLng) < 1e-9) return null;
                return [fixedLat, fixedLng];
            };
            const validPoint = (lat, lng) => !!normalizePoint(lat, lng);
            const firstNode = existingNodes.find((node) => validPoint(node.latitude, node.longitude));
            const defaultPoint = firstNode
                ? normalizePoint(firstNode.latitude, firstNode.longitude)
                : [-6.2615, 107.1528];

            const map = L.map(el, { zoomControl: true, scrollWheelZoom: true }).setView(defaultPoint, firstNode ? 15 : 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 20,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            let marker = L.marker(defaultPoint, { draggable: true }).addTo(map);

            const setStatus = (message) => {
                if (status) status.textContent = message;
            };

            const writePoint = (lat, lng, move = true) => {
                latInput.value = Number(lat).toFixed(7);
                lngInput.value = Number(lng).toFixed(7);
                const point = [Number(lat), Number(lng)];
                marker.setLatLng(point);
                if (move) map.setView(point, Math.max(map.getZoom(), 17));
            };

            marker.on('dragend', () => {
                const point = marker.getLatLng();
                writePoint(point.lat, point.lng, false);
                setStatus('Koordinat diambil dari marker.');
            });

            map.on('click', (event) => {
                writePoint(event.latlng.lat, event.latlng.lng);
                setStatus('Koordinat diambil dari klik peta.');
            });

            const previewFromInput = () => {
                const point = normalizePoint(latInput.value, lngInput.value);
                if (!point) {
                    setStatus('Isi latitude dan longitude yang valid.');
                    return;
                }
                writePoint(point[0], point[1]);
                setStatus('Preview mengikuti koordinat input.');
            };

            latInput.addEventListener('change', previewFromInput);
            lngInput.addEventListener('change', previewFromInput);
            centerButton?.addEventListener('click', previewFromInput);
            document.addEventListener('click', (event) => {
                if (event.target.closest('[data-modal-open="#node-create-modal"]')) {
                    setTimeout(() => map.invalidateSize(), 180);
                }
            });
            window.addEventListener('layout:changed', () => setTimeout(() => map.invalidateSize(), 160));

            gpsButton?.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    setStatus('GPS browser tidak tersedia.');
                    return;
                }

                setStatus('Mengambil lokasi GPS...');
                navigator.geolocation.getCurrentPosition((position) => {
                    writePoint(position.coords.latitude, position.coords.longitude);
                    setStatus(`GPS berhasil, akurasi ${Math.round(position.coords.accuracy)} meter.`);
                }, () => {
                    setStatus('Gagal mengambil GPS. Pastikan izin lokasi browser aktif.');
                }, {
                    enableHighAccuracy: true,
                    timeout: 12000,
                    maximumAge: 0,
                });
            });

            setTimeout(() => map.invalidateSize(), 160);
        })();
    </script>
</x-layouts.app>
