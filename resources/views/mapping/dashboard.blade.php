<x-layouts.app title="Dashboard">
    @php
        $labels = [
            'nodes' => 'Node',
            'links' => 'Link',
            'users' => 'User',
        ];
    @endphp

    <div class="toolbar">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Dashboard</h2>
            <p class="mt-1 text-sm text-slate-500">Ringkasan operasional mapping jaringan.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a class="btn" href="{{ route('map') }}">Buka Peta</a>
            <a class="btn-primary" href="{{ route('reports.index') }}">Pusat Report</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($totals as $label => $total)
            <div class="panel p-5">
                <div class="text-sm font-semibold text-slate-500">{{ $labels[$label] ?? str_replace('_', ' ', $label) }}</div>
                <div class="mt-3 text-3xl font-black text-slate-900">{{ $total }}</div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-[1fr_360px]">
        <div class="panel overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Mini Map</div>
                    <div class="mt-1 text-sm font-black text-slate-900">Lokasi node terbaru</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a class="btn" href="{{ route('map') }}">Buka Map View</a>
                    <a class="btn" href="{{ route('nodes.index') }}">Kelola Node</a>
                </div>
            </div>
            <div class="relative h-[360px] bg-slate-100">
                <div id="dashboard-mini-map" class="h-full w-full"></div>
            </div>
        </div>

        <div class="panel p-5">
            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Quick Actions</div>
            <div class="mt-2 text-sm font-black text-slate-900">Shortcut operasional</div>
            <div class="mt-4 grid gap-2">
                <a class="btn" href="{{ route('topology') }}">Topology (drag posisi)</a>
                <a class="btn" href="{{ route('nodes.index') }}">Data Node (tambah/edit)</a>
                <a class="btn" href="{{ route('links.index') }}">Data Link (tambah/edit)</a>
                <a class="btn" href="{{ route('map') }}">Periksa Lokasi Node</a>
            </div>
            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
                Tips: gunakan <strong>Map View</strong> untuk cek foto/koordinat, dan <strong>Topology</strong> untuk rapikan layout jaringan.
            </div>
        </div>
    </div>

    <style>
        #dashboard-mini-map,
        #dashboard-mini-map .leaflet-container {
            width: 100%;
            height: 100%;
        }
    </style>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (() => {
            const nodes = @json($mapNodes ?? []);
            const el = document.getElementById('dashboard-mini-map');
            if (!el || !window.L) return;
            const MIN_ZOOM = 10;
            const MAX_ZOOM = 17;

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
                if (Math.abs(fixedLat) > 90 && Math.abs(fixedLng) <= 90) {
                    [fixedLat, fixedLng] = [fixedLng, fixedLat];
                }
                if (Math.abs(fixedLat) > 90 || Math.abs(fixedLng) > 180) return null;
                if (Math.abs(fixedLat) < 1e-9 && Math.abs(fixedLng) < 1e-9) return null;
                return [fixedLat, fixedLng];
            };

            const mappedNodes = nodes
                .map((node) => ({ node, point: normalizePoint(node.latitude, node.longitude) }))
                .filter((row) => !!row.point);

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[char]));

            const center = mappedNodes[0]?.point || [-6.2615, 107.1528];
            const map = L.map(el, { zoomControl: true, scrollWheelZoom: true, minZoom: MIN_ZOOM, maxZoom: MAX_ZOOM }).setView(center, mappedNodes.length ? 15 : 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: MAX_ZOOM,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            const bounds = [];
            const markerIcon = (node) => {
                const color = colors[node.type] || '#111827';
                return L.divIcon({
                    className: '',
                    iconSize: [16, 16],
                    iconAnchor: [8, 8],
                    html: `<span style="display:block;width:16px;height:16px;border-radius:999px;background:${color};border:2px solid #ffffff;box-shadow:0 1px 3px rgba(15,23,42,.25)"></span>`,
                });
            };

            mappedNodes.slice(0, 120).forEach(({ node, point }) => {
                bounds.push(point);
                const popup = `
                    <div style="min-width:200px">
                        <div style="font-weight:800;color:#0f172a">${escapeHtml(node.code || '-')}</div>
                        <div style="margin-top:4px;font-size:12px;color:#334155">${escapeHtml(node.name || '-')}</div>
                        <div style="margin-top:4px;font-size:12px;color:#64748b">${escapeHtml(node.type_label || node.type || '-')}</div>
                        <div style="margin-top:6px;font-size:12px;color:#334155">${point[0]}, ${point[1]}</div>
                    </div>
                `;
                L.marker(point, { icon: markerIcon(node) }).addTo(map).bindPopup(popup);
            });

            if (bounds.length) {
                map.fitBounds(bounds, { padding: [18, 18], maxZoom: MAX_ZOOM - 1 });
            }

            const invalidate = () => setTimeout(() => map.invalidateSize(), 120);
            window.addEventListener('layout:changed', invalidate);
            window.addEventListener('resize', invalidate);
            invalidate();
        })();
    </script>
</x-layouts.app>
