<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Wifi Maps' }}</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/assets/img/jonusa.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <div class="flex min-h-screen bg-slate-50 text-slate-900">
        <div class="sidebar-backdrop" data-sidebar-backdrop></div>
        <aside id="app-sidebar" data-sidebar class="app-sidebar fixed inset-y-0 left-0 z-[1200] flex w-72 flex-col bg-slate-900 text-slate-100 shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-800 px-6 py-5">
                <div class="min-w-0">
                    <h1 class="truncate text-xl font-bold tracking-tight text-white">Mapping Jaringan</h1>
                    <p class="mt-1 text-xs font-medium text-slate-400">ODC - PON - Box - Tiang</p>
                </div>
                <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-700 text-slate-300 transition hover:bg-slate-800 hover:text-white lg:hidden" data-sidebar-close aria-label="Tutup menu">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            @php
                $items = [
                    ['route' => 'dashboard', 'label' => 'Dashboard'],
                    ['route' => 'map', 'label' => 'Map View'],
                    ['route' => 'nodes.index', 'label' => 'Data Node'],
                    ['route' => 'links.index', 'label' => 'Data Link'],
                    ['route' => 'reports.index', 'label' => 'Pusat Report'],
                ];
                if (in_array(auth()->user()->role, ['superadmin', 'admin'], true)) {
                    $items[] = ['route' => 'users.index', 'label' => 'Akun User'];
                }
            @endphp
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                @foreach ($items as $item)
                    <a href="{{ route($item['route']) }}"
                       class="group flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition-colors {{ request()->routeIs($item['route']) ? 'bg-sky-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="shrink-0 {{ request()->routeIs($item['route']) ? 'text-white' : 'text-slate-400 group-hover:text-white' }}">
                            @switch($item['route'])
                                @case('dashboard')
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h7M3 12h7M3 18h7M14 6h7M14 12h7M14 18h7"/></svg>
                                    @break
                                @case('map')
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6l7-2 7 2 7-2v14l-7 2-7-2-7 2V6z"/><path d="M10 4v14M18 6v14"/></svg>
                                    @break
                                @case('nodes.index')
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M8 4v16"/></svg>
                                    @break
                                @case('links.index')
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 14a3 3 0 0 1 0-4M14 10a3 3 0 0 1 0 4M12 12h2"/></svg>
                                    @break
                                @case('reports.index')
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M9 13h6M9 17h6"/></svg>
                                    @break
                                @default
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-3-3.87M4 21v-2a4 4 0 0 1 3-3.87"/><circle cx="12" cy="7" r="4"/></svg>
                            @endswitch
                        </span>
                        <span class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
            <div class="mt-auto h-3 border-t border-slate-800"></div>
        </aside>

        <main class="min-w-0 flex-1 lg:ml-72">
            <header class="top-navbar sticky top-0 z-[1100] flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 shadow-sm sm:px-6 lg:px-8">
                <div class="hidden flex-1 items-center gap-3 lg:flex">
                    <div class="text-xl font-semibold text-slate-800">Dashboard Operasional</div>
                </div>
                <div class="flex items-center gap-4 lg:hidden">
                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="false" aria-label="Buka menu">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="font-semibold text-slate-900">Dashboard Operasional</div>
                </div>
                <div class="ml-auto flex items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden min-w-0 text-right sm:block">
                            <div class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</div>
                            <div class="truncate text-xs text-slate-500">{{ auth()->user()->role }}</div>
                        </div>
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-sky-200 bg-sky-100 font-bold text-sky-700 shadow-sm">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    </div>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-slate-900 text-white shadow-sm transition-colors hover:bg-slate-800" title="Logout" aria-label="Logout">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <polyline points="16 17 21 12 16 7" />
                                <line x1="21" y1="12" x2="9" y2="12" />
                            </svg>
                        </button>
                    </form>
                </div>
            </header>
            <div class="p-4 sm:p-6 lg:p-8">
                @if (session('status'))
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
                @endif
                @if (session('import_errors'))
                    <details class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <summary class="cursor-pointer font-bold">{{ count(session('import_errors')) }} baris import dilewati — lihat detail</summary>
                        <ul class="mt-3 list-disc space-y-1 pl-5">
                            @foreach (session('import_errors') as $importError)
                                <li>{{ $importError }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
