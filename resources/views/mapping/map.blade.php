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
        <div class="map-routing-badge absolute right-3 top-3 z-[500] rounded-lg border border-slate-200 bg-white/90 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur">
            <span class="text-slate-500">Routing:</span> <span data-osrm-status>{{ config('services.osrm.enabled', true) ? 'Checking...' : 'OFF' }}</span>
        </div>
    </div>

    <style>
        .map-full-canvas {
            height: calc(100dvh - 6rem);
        }

        #network-map,
        #network-map .leaflet-container {
            width: 100%;
            height: 100%;
        }

        /* Keep the layer toggle from overlapping the routing badge. */
        #network-map .leaflet-top.leaflet-right {
            top: 52px;
        }

        #network-map .leaflet-top.leaflet-left {
            top: 52px;
        }

        #network-map .leaflet-draw-toolbar a {
            box-sizing: content-box;
        }

        .draw-popup-copy {
            margin-top: 0.5rem;
            width: 260px;
            min-height: 74px;
            resize: vertical;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            padding: 0.5rem;
            font: 11px/1.4 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            color: #334155;
        }

        details > summary::-webkit-details-marker {
            display: none;
        }

        @media (min-width: 1024px) {
            .map-full-canvas {
                height: calc(100dvh - 8rem);
            }
        }

        .filter-field {
            display: grid;
            gap: 0.5rem;
        }

        .map-filter-popover[open] > summary {
            border-color: #7dd3fc;
            background: #f0f9ff;
            color: #075985;
        }

        @media (max-width: 640px) {
            .map-status-badge {
                left: 0.75rem;
                right: 0.75rem;
                max-width: none;
                width: auto;
            }

            .map-routing-badge {
                left: 0.75rem;
                right: auto;
                top: 3.35rem;
            }
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
            const MIN_ZOOM = 10;
            const MAX_ZOOM = 18;
            const osrmEnabled = @json((bool) config('services.osrm.enabled', true));
            const osrmStatusEl = document.querySelector('[data-osrm-status]');
            const colors = {
                odc: '#7c3aed',
                pon: '#2563eb',
                box: '#059669',
                pole: '#d97706',
                customer: '#111827',
                server: '#0f766e',
                olc: '#be123c',
            };

            const normalizePoint = (latRaw, lngRaw) => {
                const lat = Number(latRaw);
                const lng = Number(lngRaw);
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
                let fixedLat = lat;
                let fixedLng = lng;
                // Handle common mistake: swapped lat/lng.
                if (Math.abs(fixedLat) > 90 && Math.abs(fixedLng) <= 90) {
                    [fixedLat, fixedLng] = [fixedLng, fixedLat];
                }
                if (Math.abs(fixedLat) > 90 || Math.abs(fixedLng) > 180) return null;
                // Avoid "Null Island" (0,0) which commonly appears from empty/invalid GPS values.
                if (Math.abs(fixedLat) < 1e-9 && Math.abs(fixedLng) < 1e-9) return null;
                return [fixedLat, fixedLng];
            };

            const hasCoords = (node) => !!normalizePoint(node.latitude, node.longitude);
            const mappedNodes = nodes.filter(hasCoords);
            const center = mappedNodes[0]
                ? normalizePoint(mappedNodes[0].latitude, mappedNodes[0].longitude)
                : [-6.2615, 107.1528];

            const map = L.map('network-map', {
                zoomControl: true,
                scrollWheelZoom: true,
                minZoom: MIN_ZOOM,
                maxZoom: MAX_ZOOM,
            }).setView(center, mappedNodes.length ? 15 : 12);

            const lightTiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: MAX_ZOOM,
                attribution: '&copy; OpenStreetMap contributors',
            });
            const darkTiles = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                maxZoom: MAX_ZOOM,
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
            });
            const satelliteTiles = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: MAX_ZOOM,
                attribution: 'Tiles &copy; Esri',
            });

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

            const baseLayers = {
                'Normal': lightTiles,
                'Dark': darkTiles,
                'Satellite': satelliteTiles,
            };
            L.control.layers(baseLayers, null, { position: 'topright' }).addTo(map);
            map.on('baselayerchange', (event) => {
                userSelectedBase = event?.name || 'custom';
                activeTiles = event?.layer || activeTiles;
            });

            const byId = new Map(nodes.map((node) => [String(node.id), node]));
            const markersById = new Map();
            const bounds = [];

            const markerIcon = (node) => {
                const color = colors[node.type] || '#111827';
                return L.divIcon({
                    className: '',
                    iconSize: [18, 18],
                    iconAnchor: [9, 9],
                    html: `<span style="display:block;width:18px;height:18px;border-radius:999px;background:${color};border:2px solid white;box-shadow:0 6px 16px rgba(0,0,0,.22)"></span>`,
                });
            };

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[char]));

            const drawnItems = new L.FeatureGroup().addTo(map);
            const bindDrawPopup = (layer, type) => {
                const geometry = layer.toGeoJSON()?.geometry ?? null;
                const geometryText = JSON.stringify(geometry, null, 2);
                layer.bindPopup(`
                    <div style="min-width:260px">
                        <div style="font-weight:800;color:#0f172a">Gambar manual: ${escapeHtml(type)}</div>
                        <div style="margin-top:4px;font-size:12px;color:#64748b">Belum disimpan ke database. Salin GeoJSON ini jika perlu arsip.</div>
                        <textarea class="draw-popup-copy" readonly>${escapeHtml(geometryText)}</textarea>
                    </div>
                `);
            };

            if (L.Control?.Draw) {
                const drawControl = new L.Control.Draw({
                    position: 'topleft',
                    edit: {
                        featureGroup: drawnItems,
                        remove: true,
                    },
                    draw: {
                        marker: true,
                        polyline: {
                            shapeOptions: {
                                color: '#0284c7',
                                weight: 3,
                                opacity: 0.9,
                            },
                        },
                        polygon: {
                            allowIntersection: false,
                            showArea: true,
                            shapeOptions: {
                                color: '#0f766e',
                                weight: 2,
                                opacity: 0.9,
                                fillOpacity: 0.18,
                            },
                        },
                        rectangle: {
                            shapeOptions: {
                                color: '#7c3aed',
                                weight: 2,
                                opacity: 0.9,
                                fillOpacity: 0.14,
                            },
                        },
                        circle: false,
                        circlemarker: false,
                    },
                });
                map.addControl(drawControl);

                map.on(L.Draw.Event.CREATED, (event) => {
                    const layer = event.layer;
                    drawnItems.addLayer(layer);
                    bindDrawPopup(layer, event.layerType || 'layer');
                    layer.openPopup?.();
                });

                map.on(L.Draw.Event.EDITED, (event) => {
                    event.layers.eachLayer((layer) => bindDrawPopup(layer, 'edited'));
                });
            }

            mappedNodes.forEach((node) => {
                const point = normalizePoint(node.latitude, node.longitude);
                if (!point) return;
                bounds.push(point);
                const mapsUrl = `https://www.google.com/maps?q=${encodeURIComponent(`${point[0]},${point[1]}`)}`;
                const photoSrc = node.photo_url || node.photo_path;
                const photo = photoSrc
                    ? `<img src="${escapeHtml(photoSrc)}" alt="${escapeHtml(node.code)}" style="margin-top:8px;max-width:220px;border-radius:8px;border:1px solid #e2e8f0">`
                    : '';

                const marker = L.marker(point, { icon: markerIcon(node) })
                    .addTo(map)
                    .bindPopup(`
                        <div style="min-width:220px">
                            <div style="font-weight:800;font-size:14px;color:#0f172a">${escapeHtml(node.code)}</div>
                            <div style="margin-top:4px;font-size:13px;color:#334155"><span style="color:#64748b">Nama:</span> ${escapeHtml(node.name || '-')}</div>
                            <div style="margin-top:4px;font-size:13px;color:#334155"><span style="color:#64748b">Jenis:</span> ${escapeHtml(node.type || '-')}</div>
                            <div style="margin-top:4px;font-size:13px;color:#334155"><span style="color:#64748b">Koordinat:</span> ${escapeHtml(point[0])}, ${escapeHtml(point[1])}</div>
                            <div style="margin-top:8px">
                                <a href="${mapsUrl}" target="_blank" rel="noreferrer" style="font-weight:700;color:#0369a1">Buka di Google Maps</a>
                            </div>
                            <div style="margin-top:8px;font-size:13px;color:#334155"><span style="color:#64748b">Alamat:</span> ${escapeHtml(node.address || '-')}</div>
                            <div style="margin-top:4px;font-size:13px;color:#334155"><span style="color:#64748b">Catatan:</span> ${escapeHtml(node.notes || '-')}</div>
                            ${photo}
                        </div>
                    `);
                markersById.set(String(node.id), marker);
            });

            links.forEach((link) => {
                const source = byId.get(String(link.source_node_id));
                const target = byId.get(String(link.target_node_id));
                if (!source || !target || !hasCoords(source) || !hasCoords(target)) return;
                const sourcePoint = normalizePoint(source.latitude, source.longitude);
                const targetPoint = normalizePoint(target.latitude, target.longitude);
                if (!sourcePoint || !targetPoint) return;

                const route = Array.isArray(link.route_geometry) && link.route_geometry.length >= 2
                    ? link.route_geometry.map((pair) => Array.isArray(pair) && pair.length >= 2 ? [Number(pair[0]), Number(pair[1])] : null).filter(Boolean)
                    : null;

                const line = route && route.length >= 2
                    ? route
                    : [sourcePoint, targetPoint];

                const label = [link.cable_type, link.core_count ? `core ${link.core_count}` : null, link.core_number]
                    .filter(Boolean)
                    .join(' - ');
                const color = colors[source.type] || '#0f172a';
                L.polyline(line, {
                    color,
                    weight: route ? 2.4 : 1.6,
                    opacity: route ? 0.85 : 0.65,
                    dashArray: route ? null : '6 8',
                    lineCap: 'round',
                    lineJoin: 'round',
                }).addTo(map).bindPopup(`
                    <div style="font-weight:800">${escapeHtml(source.code)} -> ${escapeHtml(target.code)}</div>
                    <div style="margin-top:4px;color:#64748b">${escapeHtml(label || 'Link')}</div>
                `);
            });

            const fitAll = () => {
                if (!bounds.length) return;
                map.fitBounds(bounds, { padding: [40, 40], maxZoom: MAX_ZOOM - 1 });
            };
            const focusNode = focus?.node_id ? byId.get(String(focus.node_id)) : null;
            if (focusNode && hasCoords(focusNode)) {
                const point = normalizePoint(focusNode.latitude, focusNode.longitude);
                if (point) map.setView(point, Math.min(MAX_ZOOM, 18));
                setTimeout(() => markersById.get(String(focusNode.id))?.openPopup(), 250);
            } else {
                const focusPoint = normalizePoint(focus?.latitude, focus?.longitude);
                if (focusPoint) {
                    map.setView(focusPoint, Math.min(MAX_ZOOM, 18));
                } else {
                    fitAll();
                }
            }
            document.querySelector('[data-map-fit]')?.addEventListener('click', fitAll);
            const invalidate = () => setTimeout(() => map.invalidateSize(), 120);
            window.addEventListener('layout:changed', invalidate);
            window.addEventListener('resize', invalidate);
            invalidate();

            if (!osrmEnabled) return;

            fetch(@json(route('osrm.status')), { headers: { 'Accept': 'application/json' } })
                .then((r) => r.json().then((data) => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    if (!osrmStatusEl) return;
                    if (data?.disabled) {
                        osrmStatusEl.textContent = 'OFF';
                        return;
                    }
                    if (ok && data?.ok) {
                        osrmStatusEl.textContent = 'OSRM OK';
                        return;
                    }
                    osrmStatusEl.textContent = 'OSRM not reachable';
                })
                .catch(() => {
                    if (osrmStatusEl) osrmStatusEl.textContent = 'OSRM not reachable';
                });
        })();
    </script>
</x-layouts.app>
