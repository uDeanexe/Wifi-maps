<x-layouts.app title="Topology">
    @php
        $nodeColors = [
            'odc' => ['bg' => 'bg-violet-600', 'border' => 'border-violet-200', 'text' => 'text-violet-700', 'soft' => 'bg-violet-50'],
            'pon' => ['bg' => 'bg-blue-600', 'border' => 'border-blue-200', 'text' => 'text-blue-700', 'soft' => 'bg-blue-50'],
            'box' => ['bg' => 'bg-emerald-600', 'border' => 'border-emerald-200', 'text' => 'text-emerald-700', 'soft' => 'bg-emerald-50'],
            'pole' => ['bg' => 'bg-amber-600', 'border' => 'border-amber-200', 'text' => 'text-amber-700', 'soft' => 'bg-amber-50'],
            'customer' => ['bg' => 'bg-slate-800', 'border' => 'border-slate-200', 'text' => 'text-slate-700', 'soft' => 'bg-slate-50'],
            'server' => ['bg' => 'bg-teal-600', 'border' => 'border-teal-200', 'text' => 'text-teal-700', 'soft' => 'bg-teal-50'],
            'olc' => ['bg' => 'bg-rose-600', 'border' => 'border-rose-200', 'text' => 'text-rose-700', 'soft' => 'bg-rose-50'],
        ];
    @endphp

    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="grid h-12 w-12 place-items-center rounded-xl bg-slate-950 text-sm font-black text-white shadow-sm">WM</div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Topology Jaringan</h2>
                    <p class="mt-1 text-sm text-slate-500">Drag node, pakai connector untuk membuat link, lalu posisi tersimpan otomatis.</p>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a class="btn-primary" href="{{ route('reports.topology.pdf') }}">Export Topology PDF</a>
            <a class="btn" href="{{ route('nodes.index') }}">Data Node</a>
            <a class="btn" href="{{ route('links.index') }}">Data Link</a>
        </div>
    </div>

    <div class="mb-5 grid gap-3 md:grid-cols-3">
        <div class="panel px-4 py-3">
            <div class="text-xs font-bold uppercase text-slate-500">Total Node</div>
            <div class="mt-1 text-2xl font-black text-slate-900">{{ $nodes->count() }}</div>
        </div>
        <div class="panel px-4 py-3">
            <div class="text-xs font-bold uppercase text-slate-500">Total Link</div>
            <div class="mt-1 text-2xl font-black text-slate-900">{{ $links->count() }}</div>
        </div>
        <div class="panel px-4 py-3">
            <div class="text-xs font-bold uppercase text-slate-500">Status</div>
            <div data-connect-status class="mt-2 text-sm font-semibold text-slate-700">Pilih connector node untuk membuat link.</div>
        </div>
    </div>

    <div class="-mx-4 sm:-mx-6 lg:-mx-8">
        <div
            class="panel relative h-[82vh] min-h-[720px] overflow-hidden bg-slate-100 sm:rounded-2xl"
            data-topology-board
            data-link-url="{{ route('links.store') }}"
            data-csrf-token="{{ csrf_token() }}"
        >
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgb(148_163_184_/_0.18)_1px,transparent_1px),linear-gradient(to_bottom,rgb(148_163_184_/_0.18)_1px,transparent_1px)] bg-[size:28px_28px]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgb(14_165_233_/_0.14),transparent_28%),radial-gradient(circle_at_80%_70%,rgb(16_185_129_/_0.12),transparent_26%)]"></div>

        <div class="absolute left-5 top-5 z-10 flex items-center gap-3 rounded-xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm backdrop-blur">
            <div class="grid h-10 w-10 place-items-center rounded-lg bg-slate-950 text-xs font-black text-white">WM</div>
            <div>
                <div class="text-sm font-black text-slate-950">Wifi Maps Topology</div>
                <div class="text-xs text-slate-500">ODC - PON - Box - Tiang</div>
            </div>
        </div>

        <div class="absolute right-5 top-5 z-10 hidden rounded-xl border border-white/70 bg-white/90 p-3 shadow-sm backdrop-blur md:block">
            <div class="mb-2 text-xs font-bold uppercase text-slate-500">Legend</div>
            <div class="grid gap-2">
                @foreach ($nodes->pluck('type')->filter()->unique('name') as $type)
                    @php
                        $tone = $nodeColors[$type->name] ?? ['bg' => 'bg-slate-600'];
                    @endphp
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                        <span class="h-3 w-3 rounded-full {{ $tone['bg'] }}"></span>
                        {{ $type->label }}
                    </div>
                @endforeach
            </div>
        </div>

        <div data-topology-scene class="absolute inset-0 origin-top-left">
        <svg class="pointer-events-none absolute left-0 top-0 h-[1px] w-[1px] overflow-visible" data-link-layer>
            <defs>
                <marker id="topology-arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                    <path d="M 0 0 L 10 5 L 0 10 z" fill="#334155"></path>
                </marker>
                <filter id="link-shadow" x="-20%" y="-20%" width="140%" height="140%">
                    <feDropShadow dx="0" dy="1" stdDeviation="1.5" flood-color="#0f172a" flood-opacity=".18"/>
                </filter>
            </defs>
            @foreach ($links as $link)
                @if ($link->source && $link->target)
                    <path
                        data-link-id="{{ $link->id }}"
                        data-source="{{ $link->source_node_id }}"
                        data-target="{{ $link->target_node_id }}"
                        fill="none"
                        stroke="#334155"
                        stroke-width="2.25"
                        stroke-linecap="round"
                        stroke-dasharray="8 8"
                        marker-end="url(#topology-arrow)"
                        filter="url(#link-shadow)"
                    />
                @endif
            @endforeach
            <path data-preview-link class="hidden" fill="none" stroke="#0284c7" stroke-width="2.75" stroke-linecap="round" stroke-dasharray="6 6"/>
        </svg>

        @if ($nodes->isEmpty())
            <div class="relative z-10 grid h-full place-items-center">
                <div class="rounded-xl border border-slate-200 bg-white px-6 py-5 text-center shadow-sm">
                    <div class="mx-auto grid h-12 w-12 place-items-center rounded-xl bg-slate-950 text-sm font-black text-white">WM</div>
                    <div class="mt-3 text-sm font-bold text-slate-900">Belum ada node.</div>
                    <div class="mt-1 text-xs text-slate-500">Tambahkan node lewat data untuk melihat topology.</div>
                </div>
            </div>
        @endif

        @foreach ($nodes as $node)
            @php
                $tone = $nodeColors[$node->type?->name] ?? ['bg' => 'bg-slate-700', 'border' => 'border-slate-200', 'text' => 'text-slate-700', 'soft' => 'bg-slate-50'];
                $initial = strtoupper(substr($node->type?->label ?? 'N', 0, 2));
            @endphp
            <div
                data-node-id="{{ $node->id }}"
                data-position-url="{{ route('nodes.position', $node) }}"
                class="group absolute w-56 touch-none select-none rounded-xl border {{ $tone['border'] }} bg-white shadow-md ring-1 ring-white/70 transition hover:-translate-y-0.5 hover:shadow-xl"
                style="left: {{ $node->topology_x }}px; top: {{ $node->topology_y }}px"
            >
                <button type="button" data-port class="absolute -left-2.5 top-1/2 h-5 w-5 -translate-y-1/2 rounded-full border-[3px] border-white {{ $tone['bg'] }} shadow-md transition hover:scale-125" title="Connector kiri"></button>
                <button type="button" data-port class="absolute -right-2.5 top-1/2 h-5 w-5 -translate-y-1/2 rounded-full border-[3px] border-white {{ $tone['bg'] }} shadow-md transition hover:scale-125" title="Connector kanan"></button>
                <button type="button" data-port class="absolute left-1/2 -top-2.5 h-5 w-5 -translate-x-1/2 rounded-full border-[3px] border-white {{ $tone['bg'] }} shadow-md transition hover:scale-125" title="Connector atas"></button>
                <button type="button" data-port class="absolute bottom-[-10px] left-1/2 h-5 w-5 -translate-x-1/2 rounded-full border-[3px] border-white {{ $tone['bg'] }} shadow-md transition hover:scale-125" title="Connector bawah"></button>

                <div data-drag-handle class="cursor-move rounded-t-xl border-b border-slate-100 {{ $tone['soft'] }} px-3 py-3">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg {{ $tone['bg'] }} text-xs font-black text-white shadow-sm">{{ $initial }}</span>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-black text-slate-950">{{ $node->code }}</div>
                            <div class="truncate text-xs font-semibold {{ $tone['text'] }}">{{ $node->type?->label ?? 'Node' }}</div>
                        </div>
                    </div>
                </div>
                <div class="space-y-2 px-3 py-3 text-xs text-slate-500">
                    <div class="truncate font-semibold text-slate-700">{{ $node->name ?: 'Tanpa nama' }}</div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-lg bg-slate-50 px-2 py-1">
                            <div class="font-bold uppercase text-slate-400">Lat</div>
                            <div class="truncate font-mono text-slate-700">{{ $node->latitude ?: '-' }}</div>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-2 py-1">
                            <div class="font-bold uppercase text-slate-400">Lng</div>
                            <div class="truncate font-mono text-slate-700">{{ $node->longitude ?: '-' }}</div>
                        </div>
                    </div>
                    @if ($node->address)
                        <div class="truncate border-t border-slate-100 pt-2">{{ $node->address }}</div>
                    @endif
                </div>
            </div>
        @endforeach
        </div>
    </div>

    @verbatim
    <script>
        (() => {
            const board = document.querySelector('[data-topology-board]');
            const scene = document.querySelector('[data-topology-scene]');
            const layer = document.querySelector('[data-link-layer]');
            const preview = document.querySelector('[data-preview-link]');
            const status = document.querySelector('[data-connect-status]');
            if (!board || !scene || !layer) return;
            const token = board.dataset.csrfToken;

            let dragging = null;
            let connecting = null;
            let panning = null;
            let suppressClickUntil = 0;

            let zoom = 1;
            let panX = 0;
            let panY = 0;
            const minZoom = 0.25;
            const maxZoom = 2.5;

            const applyTransform = () => {
                scene.style.transform = `translate(${panX}px, ${panY}px) scale(${zoom})`;
            };

            const clampZoom = (value) => Math.min(maxZoom, Math.max(minZoom, value));

            const setStatus = (message) => {
                if (status) status.textContent = message;
            };

            const worldFromEvent = (event) => {
                const rect = board.getBoundingClientRect();
                const x = (event.clientX - rect.left - panX) / zoom;
                const y = (event.clientY - rect.top - panY) / zoom;
                return { x, y };
            };

            const centerOf = (el) => {
                const left = parseFloat(el.style.left || '0') || 0;
                const top = parseFloat(el.style.top || '0') || 0;
                return { x: left + el.offsetWidth / 2, y: top + el.offsetHeight / 2 };
            };

            const nodeCenter = (nodeId) => {
                const node = scene.querySelector(`[data-node-id="${nodeId}"]`);
                return node ? centerOf(node) : { x: 0, y: 0 };
            };

            const curve = (a, b) => {
                const dx = Math.max(Math.abs(b.x - a.x) * 0.45, 80);
                return `M ${a.x} ${a.y} C ${a.x + dx} ${a.y}, ${b.x - dx} ${b.y}, ${b.x} ${b.y}`;
            };

            const refreshLinks = () => {
                layer.querySelectorAll('[data-link-id]').forEach((path) => {
                    path.setAttribute('d', curve(nodeCenter(path.dataset.source), nodeCenter(path.dataset.target)));
                });
            };

            const fitToContent = () => {
                const nodes = Array.from(scene.querySelectorAll('[data-node-id]'));
                if (!nodes.length) {
                    zoom = 1;
                    panX = 0;
                    panY = 0;
                    applyTransform();
                    return;
                }

                const pad = 120;
                let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
                nodes.forEach((node) => {
                    const left = parseFloat(node.style.left || '0') || 0;
                    const top = parseFloat(node.style.top || '0') || 0;
                    minX = Math.min(minX, left);
                    minY = Math.min(minY, top);
                    maxX = Math.max(maxX, left + node.offsetWidth);
                    maxY = Math.max(maxY, top + node.offsetHeight);
                });

                const worldW = Math.max(1, (maxX - minX) + pad * 2);
                const worldH = Math.max(1, (maxY - minY) + pad * 2);
                const viewW = Math.max(1, board.clientWidth);
                const viewH = Math.max(1, board.clientHeight);

                zoom = clampZoom(Math.min(viewW / worldW, viewH / worldH, 1));
                panX = (viewW / 2) - ((minX + maxX) / 2) * zoom;
                panY = (viewH / 2) - ((minY + maxY) / 2) * zoom;
                applyTransform();
                refreshLinks();
            };

            const savePosition = async (node) => {
                const response = await fetch(node.dataset.positionUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        topology_x: parseInt(node.style.left, 10) || 0,
                        topology_y: parseInt(node.style.top, 10) || 0,
                    }),
                });
                if (!response.ok) {
                    const payload = await response.json().catch(() => ({}));
                    throw new Error(payload.message || 'Posisi gagal disimpan.');
                }
            };

            const addLinkPath = (link) => {
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.dataset.linkId = link.id;
                path.dataset.source = link.source_node_id;
                path.dataset.target = link.target_node_id;
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke', '#334155');
                path.setAttribute('stroke-width', '2.25');
                path.setAttribute('stroke-linecap', 'round');
                path.setAttribute('stroke-dasharray', '8 8');
                path.setAttribute('marker-end', 'url(#topology-arrow)');
                path.setAttribute('filter', 'url(#link-shadow)');
                layer.insertBefore(path, preview);
                refreshLinks();
            };

            board.addEventListener('pointerdown', (event) => {
                if (event.target.closest('[data-port]')) return;
                const node = event.target.closest('[data-node-id]');
                if (!node) {
                    if (event.button !== 0) return;
                    if (event.target.closest('a,button,input,select,textarea')) return;
                    event.preventDefault();
                    board.setPointerCapture?.(event.pointerId);
                    panning = {
                        pointerId: event.pointerId,
                        startClientX: event.clientX,
                        startClientY: event.clientY,
                        startPanX: panX,
                        startPanY: panY,
                        moved: false,
                    };
                    return;
                }

                event.preventDefault();
                board.setPointerCapture?.(event.pointerId);
                const world = worldFromEvent(event);
                const left = parseFloat(node.style.left || '0') || 0;
                const top = parseFloat(node.style.top || '0') || 0;
                dragging = {
                    node,
                    pointerId: event.pointerId,
                    offsetX: world.x - left,
                    offsetY: world.y - top,
                    moved: false,
                };
                node.classList.add('z-20');
            });

            board.addEventListener('pointermove', (event) => {
                if (dragging) {
                    event.preventDefault();
                    const world = worldFromEvent(event);
                    const x = Math.round(world.x - dragging.offsetX);
                    const y = Math.round(world.y - dragging.offsetY);
                    dragging.moved = dragging.moved
                        || Math.abs(x - (parseInt(dragging.node.style.left, 10) || 0)) > 1
                        || Math.abs(y - (parseInt(dragging.node.style.top, 10) || 0)) > 1;
                    dragging.node.style.left = `${x}px`;
                    dragging.node.style.top = `${y}px`;
                    refreshLinks();
                }

                if (panning) {
                    event.preventDefault();
                    const dx = event.clientX - panning.startClientX;
                    const dy = event.clientY - panning.startClientY;
                    panning.moved = panning.moved || Math.abs(dx) > 2 || Math.abs(dy) > 2;
                    panX = panning.startPanX + dx;
                    panY = panning.startPanY + dy;
                    applyTransform();
                }

                if (connecting) {
                    const world = worldFromEvent(event);
                    preview.classList.remove('hidden');
                    preview.setAttribute('d', curve(connecting.point, {
                        x: world.x,
                        y: world.y,
                    }));
                }
            });

            const stopDragging = async (event) => {
                if (!dragging) return;
                const node = dragging.node;
                const moved = dragging.moved;
                if (event?.pointerId) {
                    board.releasePointerCapture?.(event.pointerId);
                }
                dragging = null;
                node.classList.remove('z-20');
                if (moved) suppressClickUntil = Date.now() + 250;
                try {
                    await savePosition(node);
                    setStatus('Posisi node berhasil disimpan.');
                } catch (error) {
                    setStatus(error.message || 'Posisi node gagal disimpan. Muat ulang sebelum mencoba lagi.');
                }
            };

            const stopPanning = (event) => {
                if (!panning) return;
                if (event?.pointerId) {
                    board.releasePointerCapture?.(event.pointerId);
                }
                if (panning.moved) suppressClickUntil = Date.now() + 150;
                panning = null;
            };

            board.addEventListener('pointerup', stopDragging);
            board.addEventListener('pointercancel', stopDragging);
            board.addEventListener('pointerup', stopPanning);
            board.addEventListener('pointercancel', stopPanning);

            board.addEventListener('wheel', (event) => {
                if (!event.ctrlKey && !event.metaKey) return;
                event.preventDefault();
                const before = worldFromEvent(event);
                const delta = event.deltaY > 0 ? 0.92 : 1.08;
                const nextZoom = clampZoom(zoom * delta);
                if (nextZoom === zoom) return;
                zoom = nextZoom;
                // Keep cursor pointing at same world coordinate after zoom.
                const rect = board.getBoundingClientRect();
                panX = event.clientX - rect.left - before.x * zoom;
                panY = event.clientY - rect.top - before.y * zoom;
                applyTransform();
            }, { passive: false });

            board.addEventListener('click', async (event) => {
                if (Date.now() < suppressClickUntil) {
                    event.preventDefault();
                    return;
                }

                const port = event.target.closest('[data-port]');
                if (!port) return;
                const node = port.closest('[data-node-id]');
                if (!node) return;

                if (!connecting) {
                    connecting = { nodeId: node.dataset.nodeId, point: centerOf(node) };
                    node.classList.add('ring-4', 'ring-sky-200');
                    setStatus(`Connector aktif dari ${node.querySelector('.text-sm.font-black')?.textContent || 'node'}. Pilih node tujuan.`);
                    return;
                }

                const sourceNode = scene.querySelector(`[data-node-id="${connecting.nodeId}"]`);
                sourceNode?.classList.remove('ring-4', 'ring-sky-200');
                preview.classList.add('hidden');

                const sourceId = connecting.nodeId;
                const targetId = node.dataset.nodeId;
                connecting = null;

                if (sourceId === targetId) {
                    setStatus('Node sumber dan tujuan tidak boleh sama.');
                    return;
                }

                const exists = layer.querySelector(`[data-source="${sourceId}"][data-target="${targetId}"]`);
                if (exists) {
                    setStatus('Link antar node itu sudah ada.');
                    return;
                }

                const response = await fetch(board.dataset.linkUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ source_node_id: sourceId, target_node_id: targetId }),
                });

                if (!response.ok) {
                    const payload = await response.json().catch(() => ({}));
                    setStatus(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Gagal membuat link.');
                    return;
                }

                addLinkPath(await response.json());
                setStatus('Link berhasil dibuat.');
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape' || !connecting) return;
                scene.querySelector(`[data-node-id="${connecting.nodeId}"]`)?.classList.remove('ring-4', 'ring-sky-200');
                connecting = null;
                preview.classList.add('hidden');
                setStatus('Connector dibatalkan.');
            });

            board.addEventListener('dblclick', (event) => {
                if (event.target.closest('[data-node-id]')) return;
                event.preventDefault();
                fitToContent();
            });

            applyTransform();
            fitToContent();
            window.addEventListener('resize', fitToContent);
        })();
    </script>
    @endverbatim
        </div>
    </div>
</x-layouts.app>
