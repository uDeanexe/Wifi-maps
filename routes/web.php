<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MappingController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [MappingController::class, 'dashboard'])->name('dashboard');
    Route::get('/map', [MappingController::class, 'map'])->name('map');
    Route::get('/topology', [MappingController::class, 'topology'])->name('topology');

    Route::get('/nodes', [MappingController::class, 'nodes'])->name('nodes.index');
    Route::post('/nodes', [MappingController::class, 'storeNode'])->name('nodes.store');
    Route::put('/nodes/{node}', [MappingController::class, 'updateNode'])->name('nodes.update');
    Route::patch('/nodes/{node}/position', [MappingController::class, 'updateNodePosition'])->name('nodes.position');
    Route::delete('/nodes/{node}', [MappingController::class, 'deleteNode'])->name('nodes.destroy');

    Route::get('/links', [MappingController::class, 'links'])->name('links.index');
    Route::post('/links', [MappingController::class, 'storeLink'])->name('links.store');
    Route::put('/links/{link}', [MappingController::class, 'updateLink'])->name('links.update');
    Route::delete('/links/{link}', [MappingController::class, 'deleteLink'])->name('links.destroy');

    Route::get('/users', [MappingController::class, 'users'])->name('users.index');
    Route::post('/users', [MappingController::class, 'storeUser'])->name('users.store');

    Route::get('/reports/topology.pdf', [ReportController::class, 'topologyPdf'])->name('reports.topology.pdf');
    Route::get('/reports/nodes.pdf', [ReportController::class, 'nodesPdf'])->name('reports.nodes.pdf');
    Route::get('/reports/nodes/visual-a4.pdf', [ReportController::class, 'nodesVisualA4Pdf'])->name('reports.nodes.visual-a4.pdf');
    Route::get('/reports/links.pdf', [ReportController::class, 'linksPdf'])->name('reports.links.pdf');
    Route::get('/reports/links/stickers.pdf', [ReportController::class, 'linksStickersPdf'])->name('reports.links.stickers.all.pdf');
    Route::get('/reports/links/{link}/stickers.pdf', [ReportController::class, 'linkStickersPdf'])->name('reports.links.stickers.pdf');
    Route::get('/reports/nodes.csv', [ReportController::class, 'nodesCsv'])->name('reports.nodes.csv');
    Route::get('/reports/links.csv', [ReportController::class, 'linksCsv'])->name('reports.links.csv');

    Route::post('/nodes/import-csv', [MappingController::class, 'importNodesCsv'])->name('nodes.import.csv');
    Route::post('/links/import-csv', [MappingController::class, 'importLinksCsv'])->name('links.import.csv');

    Route::get('/osrm/status', function () {
        if (! (bool) config('services.osrm.enabled', true)) {
            return response()->json([
                'ok' => false,
                'disabled' => true,
            ], 200);
        }
        $base = rtrim((string) config('services.osrm.base_url', env('OSRM_BASE_URL', 'http://127.0.0.1:5000')), '/');
        try {
            $response = Http::timeout(3)->acceptJson()->get($base.'/route/v1/driving/107.0,-6.0;107.0001,-6.0001', [
                'overview' => 'false',
                'geometries' => 'geojson',
            ]);
            return response()->json([
                'base_url' => $base,
                'ok' => $response->ok(),
                'status' => $response->status(),
            ], $response->ok() ? 200 : 502);
        } catch (\Throwable $e) {
            return response()->json([
                'base_url' => $base,
                'ok' => false,
                'error' => $e->getMessage(),
            ], 502);
        }
    })->name('osrm.status');
});
