<x-layouts.app title="Map View">
    @php
        $typeLabel = collect($nodeTypes)->firstWhere('id', (int) ($filters['type'] ?? 0))?->label;
        $filterSummary = [
            'Cari' => $filters['q'] ?: 'Semua',
            'Tipe Node' => $typeLabel ?: 'Semua tipe',
            'Foto' => match ($filters['photo'] ?? '') {
                'with' => 'Ada foto',
                'without' => 'Belum ada foto',
                default => 'Semua',
            },
            'Koordinat' => match ($filters['coords'] ?? '') {
                'with' => 'Ada koordinat',
                'without' => 'Belum ada koordinat',
                default => 'Semua',
            },
            'Tanggal' => ($filters['date_from'] || $filters['date_to'])
                ? (($filters['date_from'] ?: 'awal').' sampai '.($filters['date_to'] ?: 'sekarang'))
                : 'Semua tanggal',
        ];
        $activeFilterSummary = collect($filterSummary)->reject(fn ($value) => in_array($value, ['Semua', 'Semua tipe', 'Semua tanggal'], true));
    @endphp

    <div class="map-full-canvas relative min-h-[420px] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div id="network-map" class="h-full w-full"></div>

        <details class="map-filter-popover absolute left-3 bottom-3 z-[600] w-[min(390px,calc(100%-1.5rem))]">
            <summary class="inline-flex cursor-pointer list-none items-center justify-center rounded-lg border border-slate-200 bg-white/95 px-4 py-2.5 text-sm font-black text-slate-800 shadow-sm backdrop-blur transition-colors hover:bg-slate-50">
                Filter{{ $activeFilterSummary->isNotEmpty() ? ' ('.$activeFilterSummary->count().')' : '' }}
            </summary>
            <form method="get" action="{{ route('map') }}" class="mt-2 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                    <div>
                        <div class="text-sm font-black text-slate-900">Filter Map</div>
                        <div class="mt-1 text-xs text-slate-500">Hasil filter ikut ke PDF dan CSV.</div>
                    </div>
                    <a class="text-xs font-bold text-slate-500 hover:text-slate-900" href="{{ route('map') }}">Reset</a>
                </div>
                <label class="filter-field">
                    <span class="form-label">Cari</span>
                    <input name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Kode, nama, alamat...">
                </label>
                <label class="filter-field">
                    <span class="form-label">Tipe Node</span>
                    <select name="type" class="form-control">
                        <option value="">Semua tipe</option>
                        @foreach ($nodeTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) ($filters['type'] ?? '') === (string) $type->id)>{{ $type->label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="filter-field">
                        <span class="form-label">Foto</span>
                        <select name="photo" class="form-control">
                            <option value="">Semua</option>
                            <option value="with" @selected(($filters['photo'] ?? '') === 'with')>Ada foto</option>
                            <option value="without" @selected(($filters['photo'] ?? '') === 'without')>Belum ada foto</option>
                        </select>
                    </label>
                    <label class="filter-field">
                        <span class="form-label">Koordinat</span>
                        <select name="coords" class="form-control">
                            <option value="">Semua</option>
                            <option value="with" @selected(($filters['coords'] ?? '') === 'with')>Ada koordinat</option>
                            <option value="without" @selected(($filters['coords'] ?? '') === 'without')>Belum ada koordinat</option>
                        </select>
                    </label>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="mb-2 text-xs font-bold uppercase text-slate-500">Tanggal</div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="filter-field">
                            <span class="form-label">Dari</span>
                            <input name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                        </label>
                        <label class="filter-field">
                            <span class="form-label">Sampai</span>
                            <input name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                        </label>
                    </div>
                </div>
                <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-3">
                    <button class="btn-primary">Terapkan Filter</button>
                </div>
            </form>
        </details>

        <div class="map-status-badge absolute left-3 top-3 z-[500] rounded-lg border border-slate-200 bg-white/90 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur">
            <span class="font-black text-slate-900">{{ count($mapNodes) }} node</span>
            <span class="mx-1 text-slate-300">/</span>
            <span class="font-black text-slate-900">{{ count($mapLinks) }} link</span>
            <span class="ml-2 text-slate-500">Update {{ $generatedAt }}</span>
        </div>

        <div class="absolute right-3 top-3 z-[650] w-[min(25rem,calc(100%-1.5rem))] rounded-xl border border-slate-200 bg-white/95 p-3 text-xs font-semibold leading-5 text-slate-700 shadow-lg backdrop-blur" data-map-panel>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-[11px] font-black uppercase tracking-wide text-slate-400">Mode map</div>
                    <div class="mt-0.5 text-sm font-black text-slate-900" data-draw-mode-label>Mode normal</div>
                </div>
                <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2">
                    <span class="text-[11px] font-black uppercase text-slate-500">Warna</span>
                    <input type="color" value="#0284c7" class="h-7 w-9 cursor-pointer rounded border border-slate-200 bg-white" data-line-color aria-label="Pilih warna garis">
                </label>
                <button type="button" class="hidden rounded-lg bg-rose-600 px-3 py-2 text-xs font-black text-white shadow-sm hover:bg-rose-700" data-route-cancel>Batal</button>
            </div>
            <div class="mt-2 text-xs text-slate-500" data-map-help>Klik kanan node, garis, atau area map untuk membuka menu.</div>
        </div>

        <div data-context-menu class="absolute z-[900] hidden min-w-60 overflow-hidden rounded-xl border border-slate-200 bg-white text-sm shadow-2xl">
            <div class="border-b border-slate-100 bg-slate-50 px-3 py-2 text-xs font-black uppercase tracking-wide text-slate-500" data-context-title>Menu</div>
            <button type="button" class="context-menu-item" data-context-action="info">Info</button>
            <button type="button" class="context-menu-item" data-context-action="start">Membuat garis dari node ini</button>
            <button type="button" class="context-menu-item" data-context-action="finish">Selesai di node ini</button>
            <button type="button" class="context-menu-item" data-context-action="bend">Tambah belokan di sini</button>
            <button type="button" class="context-menu-item" data-context-action="edit">Edit garis</button>
            <button type="button" class="context-menu-item" data-context-action="save-edit">Simpan edit</button>
            <button type="button" class="context-menu-item text-rose-700" data-context-action="delete">Delete garis</button>
            <button type="button" class="context-menu-item text-rose-700" data-context-action="cancel">Batalkan mode garis</button>
        </div>

        <div data-map-toast class="pointer-events-none absolute right-3 top-32 z-[700] hidden max-w-xs rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-xl"></div>
    </div>

    <style>
        .map-full-canvas { height: calc(100dvh - 6rem); }
        #network-map, #network-map .leaflet-container { width: 100%; height: 100%; }
        #network-map .leaflet-top.leaflet-right, #network-map .leaflet-top.leaflet-left { top: 52px; }
        #network-map .leaflet-draw-toolbar a { box-sizing: content-box; }
        .draw-popup-copy { margin-top: .5rem; width: 260px; min-height: 74px; resize: vertical; border: 1px solid #cbd5e1; border-radius: .5rem; padding: .5rem; font: 11px/1.4 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; color: #334155; }
        .context-menu-item { display: block; width: 100%; padding: .65rem .85rem; text-align: left; font-weight: 800; color: #334155; transition: background-color .15s ease, color .15s ease; }
        .context-menu-item:hover { background: #f0f9ff; color: #0369a1; }
        .context-menu-item[hidden] { display: none; }
        details > summary::-webkit-details-marker { display: none; }
        @media (min-width: 1024px) { .map-full-canvas { height: calc(100dvh - 8rem); } }
        .filter-field { display: grid; gap: .5rem; }
        .map-filter-popover[open] > summary { border-color: #7dd3fc; background: #f0f9ff; color: #075985; }
        @media (max-width: 640px) {
            .map-status-badge { left: .75rem; right: .75rem; max-width: none; width: auto; }
            [data-map-panel] { left: .75rem; right: .75rem; top: 3.7rem; width: auto; }
            [data-map-toast] { left: .75rem; right: .75rem; top: 9rem; max-width: none; }
        }
    </style>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
    <script>
        (() => {
            const nodes = @json($mapNodes);
            const links = @json($mapLinks);
            const focus = @json($mapFocus);
            const drawApi = {
                index: @json(route('map.drawings.index')),
                store: @json(route('map.drawings.store')),
                base: @json(url('/map/drawings')),
                csrf: @json(csrf_token()),
            };
            const MIN_ZOOM = 10;
            const MAX_ZOOM = 18;
            const colors = { odc: '#7c3aed', pon: '#2563eb', box: '#059669', pole: '#d97706', customer: '#111827', server: '#0f766e', olc: '#be123c' };
            const defaultLineColor = '#0284c7';

            const toastEl = document.querySelector('[data-map-toast]');
            const panelEl = document.querySelector('[data-map-panel]');
            const helpEl = document.querySelector('[data-map-help]');
            const modeLabelEl = document.querySelector('[data-draw-mode-label]');
            const lineColorInput = document.querySelector('[data-line-color]');
            const cancelButton = document.querySelector('[data-route-cancel]');
            const contextMenu = document.querySelector('[data-context-menu]');
            const contextTitle = document.querySelector('[data-context-title]');
            const contextButtons = Array.from(document.querySelectorAll('[data-context-action]'));
            let toastTimer = null;
            let contextTarget = null;
            let activeEditLayer = null;

            const currentColor = () => lineColorInput?.value || defaultLineColor;
            const polylineStyle = (color = currentColor(), active = false) => ({ color, weight: active ? 4 : 3, opacity: active ? .95 : .9, dashArray: active ? '8 8' : null, lineCap: 'round', lineJoin: 'round' });
            const storedStyle = (record) => {
                const color = record?.properties?.color || defaultLineColor;
                if (record?.type === 'polygon') return { color, weight: 2, opacity: .9, fillOpacity: .18 };
                if (record?.type === 'rectangle') return { color, weight: 2, opacity: .9, fillOpacity: .14 };
                return polylineStyle(color, false);
            };
            const showToast = (message, tone = 'success') => {
                if (!toastEl) return;
                clearTimeout(toastTimer);
                toastEl.textContent = message;
                toastEl.classList.remove('hidden', 'border-rose-200', 'text-rose-800', 'bg-rose-50', 'border-emerald-200', 'text-emerald-800', 'bg-emerald-50', 'border-sky-200', 'text-sky-800', 'bg-sky-50');
                if (tone === 'error') toastEl.classList.add('border-rose-200', 'text-rose-800', 'bg-rose-50');
                else if (tone === 'info') toastEl.classList.add('border-sky-200', 'text-sky-800', 'bg-sky-50');
                else toastEl.classList.add('border-emerald-200', 'text-emerald-800', 'bg-emerald-50');
                toastTimer = setTimeout(() => toastEl.classList.add('hidden'), 3000);
            };
            const setMode = (mode, message = null) => {
                const drawing = mode === 'drawing';
                const editing = mode === 'editing';
                if (modeLabelEl) modeLabelEl.textContent = editing ? 'Mode edit garis' : (drawing ? 'Mode membuat garis' : 'Mode normal');
                if (helpEl) helpEl.textContent = message || (editing ? 'Geser titik garis, lalu klik kanan garis dan pilih Simpan edit.' : (drawing ? 'Klik kanan map untuk belokan, klik kanan node tujuan untuk simpan.' : 'Klik kanan node, garis, atau area map untuk membuka menu.'));
                cancelButton?.classList.toggle('hidden', !(drawing || editing));
                panelEl?.classList.toggle('border-sky-300', drawing || editing);
                panelEl?.classList.toggle('bg-sky-50/95', drawing || editing);
            };
            const hideContextMenu = () => { contextMenu?.classList.add('hidden'); contextTarget = null; };

            const normalizePoint = (latRaw, lngRaw) => {
                const lat = Number(latRaw);
                const lng = Number(lngRaw);
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
                let fixedLat = lat;
                let fixedLng = lng;
                if (Math.abs(fixedLat) > 90 && Math.abs(fixedLng) <= 90) [fixedLat, fixedLng] = [fixedLng, fixedLat];
                if (Math.abs(fixedLat) > 90 || Math.abs(fixedLng) > 180) return null;
                if (Math.abs(fixedLat) < 1e-9 && Math.abs(fixedLng) < 1e-9) return null;
                return [fixedLat, fixedLng];
            };
            const pointOfNode = (node) => normalizePoint(node.latitude, node.longitude);
            const hasCoords = (node) => !!pointOfNode(node);
            const mappedNodes = nodes.filter(hasCoords);
            const center = mappedNodes[0] ? pointOfNode(mappedNodes[0]) : [-6.2615, 107.1528];
            const map = L.map('network-map', { zoomControl: true, scrollWheelZoom: true, minZoom: MIN_ZOOM, maxZoom: MAX_ZOOM }).setView(center, mappedNodes.length ? 15 : 12);

            const lightTiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: MAX_ZOOM, attribution: '&copy; OpenStreetMap contributors' });
            const darkTiles = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom: MAX_ZOOM, attribution: '&copy; OpenStreetMap contributors &copy; CARTO' });
            const satelliteTiles = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: MAX_ZOOM, attribution: 'Tiles &copy; Esri' });
            let activeTiles = null;
            let userSelectedBase = null;
            const syncTiles = () => {
                if (userSelectedBase) return;
                const wantsDark = document.documentElement.classList.contains('dark');
                const next = wantsDark ? darkTiles : lightTiles;
                if (activeTiles === next) return;
                if (activeTiles) map.removeLayer(activeTiles);
                activeTiles = next.addTo(map);
            };
            syncTiles();
            new MutationObserver(syncTiles).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            L.control.layers({ Normal: lightTiles, Dark: darkTiles, Satellite: satelliteTiles }, null, { position: 'topright' }).addTo(map);
            map.on('baselayerchange', (event) => { userSelectedBase = event?.name || 'custom'; activeTiles = event?.layer || activeTiles; });

            const byId = new Map(nodes.map((node) => [String(node.id), node]));
            const markersById = new Map();
            const networkLines = [];
            const bounds = [];
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
            const markerIcon = (node) => L.divIcon({ className: '', iconSize: [18, 18], iconAnchor: [9, 9], html: `<span style="display:block;width:18px;height:18px;border-radius:999px;background:${colors[node.type] || '#111827'};border:2px solid white;box-shadow:0 6px 16px rgba(0,0,0,.22)"></span>` });
            const headers = () => ({ 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': drawApi.csrf });
            const latLngFromNode = (nodeId) => {
                const node = byId.get(String(nodeId));
                const point = node ? pointOfNode(node) : null;
                return point ? L.latLng(point[0], point[1]) : null;
            };

            const drawnItems = new L.FeatureGroup().addTo(map);
            const drawUrl = (id) => `${drawApi.base}/${encodeURIComponent(id)}`;
            const layerTypeOf = (layer, fallback = 'polyline') => {
                if (layer._drawingType) return layer._drawingType;
                if (layer instanceof L.Marker) return 'marker';
                if (layer instanceof L.Rectangle) return 'rectangle';
                if (layer instanceof L.Polygon) return fallback === 'rectangle' ? 'rectangle' : 'polygon';
                if (layer instanceof L.Polyline) return 'polyline';
                return fallback;
            };
            const refreshSnappedDrawings = () => {
                drawnItems.eachLayer((layer) => {
                    if (layerTypeOf(layer) !== 'polyline' || !(layer instanceof L.Polyline)) return;
                    const props = layer._drawingProps || {};
                    const latlngs = layer.getLatLngs();
                    if (!Array.isArray(latlngs) || latlngs.length < 2 || Array.isArray(latlngs[0])) return;
                    const start = props.source_node_id ? latLngFromNode(props.source_node_id) : null;
                    const end = props.target_node_id ? latLngFromNode(props.target_node_id) : null;
                    if (start) latlngs[0] = start;
                    if (end) latlngs[latlngs.length - 1] = end;
                    if (start || end) {
                        layer.setLatLngs(latlngs);
                        updateLayer(layer).catch(() => {});
                    }
                });
            };
            const payloadFor = (layer, fallbackType) => ({
                type: layerTypeOf(layer, fallbackType),
                name: layer._drawingName || null,
                geometry: layer.toGeoJSON().geometry,
                properties: { source: 'context-menu-map', color: layer._drawingProps?.color || currentColor(), ...(layer._drawingProps || {}) },
            });
            const linkStatusLabel = (status) => ({ created: 'Data Link baru dibuat', existing: 'Terhubung ke Data Link yang sudah ada', updated: 'Data Link diperbarui' }[status] || 'Belum menjadi Data Link');
            const bindDrawPopup = (layer, type, record = null) => {
                const props = layer._drawingProps || record?.properties || {};
                const geometryText = JSON.stringify(layer.toGeoJSON()?.geometry ?? record?.geometry ?? null, null, 2);
                const savedText = layer._drawingId ? `Tersimpan #${layer._drawingId}` : 'Belum tersimpan';
                const connectText = props.source_node_code || props.target_node_code ? `<div style="margin-top:4px;font-size:12px;color:#0f766e">Nempel node: ${escapeHtml(props.source_node_code || '-')} → ${escapeHtml(props.target_node_code || '-')}</div>` : '';
                const linkText = props.link_id ? `<div style="margin-top:4px;font-size:12px;color:#0369a1;font-weight:700">${escapeHtml(linkStatusLabel(props.link_status))} #${escapeHtml(props.link_id)}</div>` : '';
                const colorText = props.color ? `<div style="margin-top:4px;font-size:12px;color:#475569">Warna: <span style="display:inline-block;width:14px;height:14px;border-radius:999px;background:${escapeHtml(props.color)};vertical-align:-2px;border:1px solid #cbd5e1"></span> ${escapeHtml(props.color)}</div>` : '';
                layer.bindPopup(`<div style="min-width:260px"><div style="font-weight:800;color:#0f172a">Garis manual</div><div style="margin-top:4px;font-size:12px;color:#64748b">${escapeHtml(savedText)}</div>${connectText}${linkText}${colorText}<textarea class="draw-popup-copy" readonly>${escapeHtml(geometryText)}</textarea></div>`);
            };
            const attachDrawingContext = (layer) => {
                layer.on('contextmenu', (event) => {
                    L.DomEvent.stop(event);
                    openContextMenu(event, { type: 'drawing', layer, latlng: event.latlng });
                });
            };
            const saveLayer = async (layer, fallbackType) => {
                const response = await fetch(drawApi.store, { method: 'POST', headers: headers(), body: JSON.stringify(payloadFor(layer, fallbackType)) });
                if (!response.ok) throw new Error('Gagal menyimpan gambar.');
                const data = await response.json();
                layer._drawingId = data.id;
                layer._drawingType = data.type;
                layer._drawingProps = data.properties || layer._drawingProps || {};
                if (layer.setStyle) layer.setStyle(polylineStyle(layer._drawingProps.color || currentColor(), false));
                bindDrawPopup(layer, data.type, data);
                attachDrawingContext(layer);
                return data;
            };
            const updateLayer = async (layer) => {
                if (!layer._drawingId) return saveLayer(layer, layer._drawingType);
                const response = await fetch(drawUrl(layer._drawingId), { method: 'PUT', headers: headers(), body: JSON.stringify(payloadFor(layer, layer._drawingType)) });
                if (!response.ok) throw new Error('Gagal memperbarui gambar.');
                const data = await response.json();
                layer._drawingProps = data.properties || layer._drawingProps || {};
                if (layer.setStyle) layer.setStyle(polylineStyle(layer._drawingProps.color || currentColor(), false));
                bindDrawPopup(layer, data.type, data);
                attachDrawingContext(layer);
                return data;
            };
            const deleteLayer = async (layer) => {
                if (!layer._drawingId) return;
                const response = await fetch(drawUrl(layer._drawingId), { method: 'DELETE', headers: headers() });
                if (!response.ok) throw new Error('Gagal menghapus gambar.');
            };
            const enableEditLayer = (layer) => {
                if (!layer?.editing?.enable) {
                    showToast('Garis ini belum mendukung edit titik.', 'error');
                    return;
                }
                if (activeEditLayer && activeEditLayer !== layer) activeEditLayer.editing?.disable?.();
                activeEditLayer = layer;
                layer.editing.enable();
                setMode('editing', 'Geser titik garis, lalu klik kanan garis dan pilih Simpan edit.');
                showToast('Mode edit garis aktif.', 'info');
            };
            const saveEditLayer = async (layer = activeEditLayer) => {
                if (!layer) return;
                layer.editing?.disable?.();
                await updateLayer(layer);
                activeEditLayer = null;
                setMode('normal');
                showToast('Edit garis berhasil disimpan.');
            };
            const removeDrawingLayer = async (layer) => {
                if (!layer) return;
                if (!confirm('Delete garis ini?')) return;
                await deleteLayer(layer);
                drawnItems.removeLayer(layer);
                if (activeEditLayer === layer) activeEditLayer = null;
                setMode('normal');
                showToast('Garis berhasil dihapus.');
            };
            const addStoredDrawing = (record) => {
                if (!record?.geometry) return;
                const feature = { type: 'Feature', geometry: record.geometry, properties: record.properties || {} };
                L.geoJSON(feature, {
                    pointToLayer: (_feature, latlng) => L.marker(latlng),
                    style: () => storedStyle(record),
                    onEachFeature: (_feature, layer) => {
                        layer._drawingId = record.id;
                        layer._drawingType = record.type;
                        layer._drawingName = record.name || null;
                        layer._drawingProps = record.properties || {};
                        drawnItems.addLayer(layer);
                        bindDrawPopup(layer, record.type, record);
                        attachDrawingContext(layer);
                    },
                });
            };

            fetch(drawApi.index, { headers: { 'Accept': 'application/json' } }).then((response) => response.ok ? response.json() : Promise.reject()).then((payload) => (payload.data || []).forEach(addStoredDrawing)).catch(() => console.warn('Gagal memuat gambar manual.'));

            if (L.Control?.Draw) {
                const drawControl = new L.Control.Draw({ position: 'topleft', edit: { featureGroup: drawnItems, remove: true }, draw: false });
                map.addControl(drawControl);
                map.on(L.Draw.Event.EDITED, (event) => event.layers.eachLayer((layer) => updateLayer(layer).then(() => showToast('Garis berhasil diperbarui.')).catch((error) => showToast(error.message, 'error'))));
                map.on(L.Draw.Event.DELETED, (event) => event.layers.eachLayer((layer) => deleteLayer(layer).then(() => showToast('Garis berhasil dihapus.')).catch((error) => showToast(error.message, 'error'))));
            }

            const activeRoute = { sourceNode: null, points: [], line: null, cursorLatLng: null };
            const resetRouteBuilder = () => {
                if (activeRoute.line) map.removeLayer(activeRoute.line);
                activeRoute.sourceNode = null;
                activeRoute.points = [];
                activeRoute.line = null;
                activeRoute.cursorLatLng = null;
                setMode('normal');
            };
            const routePreviewPoints = () => {
                const points = [...activeRoute.points];
                if (activeRoute.cursorLatLng) points.push(activeRoute.cursorLatLng);
                return points;
            };
            const redrawRoutePreview = () => {
                if (!activeRoute.sourceNode) return;
                const points = routePreviewPoints();
                if (!activeRoute.line) {
                    activeRoute.line = L.polyline(points, polylineStyle(currentColor(), true)).addTo(map);
                    return;
                }
                activeRoute.line.setLatLngs(points);
                activeRoute.line.setStyle(polylineStyle(currentColor(), true));
            };
            const startRouteFromNode = (node) => {
                const point = pointOfNode(node);
                if (!point) return;
                resetRouteBuilder();
                activeRoute.sourceNode = node;
                activeRoute.points = [L.latLng(point[0], point[1])];
                redrawRoutePreview();
                setMode('drawing', `Mulai dari ${node.code}. Klik kanan map untuk belokan, klik kanan node tujuan untuk simpan.`);
                showToast(`Mode garis aktif dari ${node.code}.`, 'info');
            };
            const addRouteBend = (latlng) => {
                if (!activeRoute.sourceNode) return;
                activeRoute.points.push(latlng);
                activeRoute.cursorLatLng = null;
                redrawRoutePreview();
                showToast('Belokan garis ditambahkan.', 'info');
            };
            const finishRouteAtNode = async (targetNode) => {
                if (!activeRoute.sourceNode) return;
                if (String(activeRoute.sourceNode.id) === String(targetNode.id)) { showToast('Node tujuan tidak boleh sama dengan node awal.', 'error'); return; }
                const targetPoint = pointOfNode(targetNode);
                if (!targetPoint) return;
                const color = currentColor();
                const finalPoints = [...activeRoute.points, L.latLng(targetPoint[0], targetPoint[1])];
                const layer = L.polyline(finalPoints, polylineStyle(color, false));
                layer._drawingType = 'polyline';
                layer._drawingProps = { source: 'context-menu-map', color, source_node_id: activeRoute.sourceNode.id, source_node_code: activeRoute.sourceNode.code, target_node_id: targetNode.id, target_node_code: targetNode.code };
                drawnItems.addLayer(layer);
                resetRouteBuilder();
                bindDrawPopup(layer, 'polyline');
                attachDrawingContext(layer);
                try {
                    await saveLayer(layer, 'polyline');
                    layer.openPopup?.();
                    showToast(`Garis ${layer._drawingProps.source_node_code} → ${layer._drawingProps.target_node_code} berhasil disimpan.`);
                } catch (error) {
                    drawnItems.removeLayer(layer);
                    showToast(error.message, 'error');
                }
            };

            const showInfo = (target) => {
                if (target.type === 'map') {
                    L.popup().setLatLng(target.latlng).setContent(`<div style="font-weight:800;color:#0f172a">Info Lokasi</div><div style="margin-top:4px;color:#475569">Lat: ${target.latlng.lat.toFixed(7)}<br>Lng: ${target.latlng.lng.toFixed(7)}</div>`).openOn(map);
                    return;
                }
                if (target.type === 'node') {
                    markersById.get(String(target.node.id))?.openPopup();
                    return;
                }
                if (target.type === 'drawing') {
                    target.layer.openPopup?.(target.latlng);
                }
            };
            const openContextMenu = (event, target) => {
                event.originalEvent?.preventDefault?.();
                hideContextMenu();
                contextTarget = target;
                const hasActive = !!activeRoute.sourceNode;
                const isNode = target.type === 'node';
                const isMap = target.type === 'map';
                const isDrawing = target.type === 'drawing';
                const isEditingTarget = isDrawing && activeEditLayer === target.layer;
                const title = isNode ? `Node ${target.node.code}` : (isDrawing ? 'Garis manual' : 'Area map');
                if (contextTitle) contextTitle.textContent = title;
                contextButtons.forEach((button) => {
                    const action = button.dataset.contextAction;
                    button.hidden = !(
                        action === 'info' ||
                        (action === 'start' && isNode && !hasActive && !activeEditLayer) ||
                        (action === 'finish' && isNode && hasActive) ||
                        (action === 'bend' && isMap && hasActive) ||
                        (action === 'edit' && isDrawing && !activeEditLayer && !hasActive) ||
                        (action === 'save-edit' && isEditingTarget) ||
                        (action === 'delete' && isDrawing) ||
                        (action === 'cancel' && (hasActive || activeEditLayer))
                    );
                });
                const containerPoint = event.containerPoint || (target.latlng ? map.latLngToContainerPoint(target.latlng) : L.point(16, 16));
                const mapSize = map.getSize();
                const width = 250;
                const height = 255;
                const x = Math.min(Math.max(containerPoint.x, 8), Math.max(8, mapSize.x - width - 8));
                const y = Math.min(Math.max(containerPoint.y, 8), Math.max(8, mapSize.y - height - 8));
                contextMenu.style.left = `${x}px`;
                contextMenu.style.top = `${y}px`;
                contextMenu.classList.remove('hidden');
            };
            contextButtons.forEach((button) => button.addEventListener('click', async () => {
                const action = button.dataset.contextAction;
                const target = contextTarget;
                hideContextMenu();
                if (!target) return;
                try {
                    if (action === 'info') showInfo(target);
                    if (action === 'start' && target.node) startRouteFromNode(target.node);
                    if (action === 'finish' && target.node) await finishRouteAtNode(target.node);
                    if (action === 'bend' && target.latlng) addRouteBend(target.latlng);
                    if (action === 'edit' && target.layer) enableEditLayer(target.layer);
                    if (action === 'save-edit') await saveEditLayer(target.layer || activeEditLayer);
                    if (action === 'delete' && target.layer) await removeDrawingLayer(target.layer);
                    if (action === 'cancel') {
                        if (activeEditLayer) activeEditLayer.editing?.disable?.();
                        activeEditLayer = null;
                        resetRouteBuilder();
                        showToast('Mode dibatalkan.', 'info');
                    }
                } catch (error) {
                    showToast(error.message || 'Aksi gagal.', 'error');
                }
            }));
            cancelButton?.addEventListener('click', () => {
                if (activeEditLayer) activeEditLayer.editing?.disable?.();
                activeEditLayer = null;
                resetRouteBuilder();
                showToast('Mode dibatalkan.', 'info');
            });
            lineColorInput?.addEventListener('input', () => redrawRoutePreview());
            map.on('click movestart zoomstart', hideContextMenu);
            map.on('contextmenu', (event) => openContextMenu(event, { type: 'map', latlng: event.latlng }));
            map.on('mousemove', (event) => { if (!activeRoute.sourceNode) return; activeRoute.cursorLatLng = event.latlng; redrawRoutePreview(); });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && (activeRoute.sourceNode || activeEditLayer)) {
                    if (activeEditLayer) activeEditLayer.editing?.disable?.();
                    activeEditLayer = null;
                    resetRouteBuilder();
                    showToast('Mode dibatalkan.', 'info');
                }
            });

            const refreshNetworkLinks = () => {
                networkLines.forEach(({ sourceId, targetId, line }) => {
                    const source = byId.get(String(sourceId));
                    const target = byId.get(String(targetId));
                    const sourcePoint = source ? pointOfNode(source) : null;
                    const targetPoint = target ? pointOfNode(target) : null;
                    if (sourcePoint && targetPoint) line.setLatLngs([sourcePoint, targetPoint]);
                });
            };
            const bindNodePopup = (marker, node) => {
                const point = pointOfNode(node) || marker.getLatLng();
                const mapsUrl = `https://www.google.com/maps?q=${encodeURIComponent(`${point[0] ?? point.lat},${point[1] ?? point.lng}`)}`;
                const photoSrc = node.photo_url || node.photo_path;
                const photo = photoSrc ? `<img src="${escapeHtml(photoSrc)}" alt="${escapeHtml(node.code)}" style="margin-top:8px;max-width:220px;border-radius:8px;border:1px solid #e2e8f0">` : '';
                marker.bindPopup(`<div style="min-width:230px"><div style="font-weight:800;font-size:14px;color:#0f172a">${escapeHtml(node.code)}</div><div style="margin-top:4px;font-size:13px;color:#334155"><span style="color:#64748b">Nama:</span> ${escapeHtml(node.name || '-')}</div><div style="margin-top:4px;font-size:13px;color:#334155"><span style="color:#64748b">Jenis:</span> ${escapeHtml(node.type || '-')}</div><div style="margin-top:4px;font-size:13px;color:#334155"><span style="color:#64748b">Koordinat:</span> ${escapeHtml(point[0] ?? point.lat)}, ${escapeHtml(point[1] ?? point.lng)}</div><div style="margin-top:6px;font-size:12px;color:#0f766e;font-weight:700">Node dikunci. Klik kanan node untuk membuka menu.</div><div style="margin-top:8px"><a href="${mapsUrl}" target="_blank" rel="noreferrer" style="font-weight:700;color:#0369a1">Buka di Google Maps</a></div><div style="margin-top:8px;font-size:13px;color:#334155"><span style="color:#64748b">Alamat:</span> ${escapeHtml(node.address || '-')}</div><div style="margin-top:4px;font-size:13px;color:#334155"><span style="color:#64748b">Catatan:</span> ${escapeHtml(node.notes || '-')}</div>${photo}</div>`);
            };

            mappedNodes.forEach((node) => {
                const point = pointOfNode(node);
                if (!point) return;
                bounds.push(point);
                const marker = L.marker(point, { icon: markerIcon(node), draggable: false }).addTo(map);
                bindNodePopup(marker, node);
                marker.on('contextmenu', (event) => { L.DomEvent.stop(event); openContextMenu(event, { type: 'node', node, latlng: event.latlng }); });
                markersById.set(String(node.id), marker);
            });

            links.forEach((link) => {
                const source = byId.get(String(link.source_node_id));
                const target = byId.get(String(link.target_node_id));
                if (!source || !target || !hasCoords(source) || !hasCoords(target)) return;
                const sourcePoint = pointOfNode(source);
                const targetPoint = pointOfNode(target);
                if (!sourcePoint || !targetPoint) return;
                const label = [link.cable_type, link.core_count ? `core ${link.core_count}` : null, link.core_number].filter(Boolean).join(' - ');
                const line = L.polyline([sourcePoint, targetPoint], { color: colors[source.type] || '#0f172a', weight: 1.8, opacity: .72, dashArray: '6 8', lineCap: 'round', lineJoin: 'round' }).addTo(map).bindPopup(`<div style="font-weight:800">${escapeHtml(source.code)} -> ${escapeHtml(target.code)}</div><div style="margin-top:4px;color:#64748b">${escapeHtml(label || 'Link')}</div>`);
                networkLines.push({ sourceId: link.source_node_id, targetId: link.target_node_id, line });
            });

            const fitAll = () => { if (bounds.length) map.fitBounds(bounds, { padding: [40, 40], maxZoom: MAX_ZOOM - 1 }); };
            const focusNode = focus?.node_id ? byId.get(String(focus.node_id)) : null;
            if (focusNode && hasCoords(focusNode)) {
                const point = pointOfNode(focusNode);
                if (point) map.setView(point, Math.min(MAX_ZOOM, 18));
                setTimeout(() => markersById.get(String(focusNode.id))?.openPopup(), 250);
            } else {
                const focusPoint = normalizePoint(focus?.latitude, focus?.longitude);
                focusPoint ? map.setView(focusPoint, Math.min(MAX_ZOOM, 18)) : fitAll();
            }
            document.querySelector('[data-map-fit]')?.addEventListener('click', fitAll);
            const invalidate = () => setTimeout(() => map.invalidateSize(), 120);
            window.addEventListener('layout:changed', invalidate);
            window.addEventListener('resize', invalidate);
            invalidate();
        })();
    </script>
</x-layouts.app>
