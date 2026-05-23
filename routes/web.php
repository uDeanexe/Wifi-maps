<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MappingController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

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

    Route::get('/gangguan', [MappingController::class, 'incidents'])->name('incidents.index');
    Route::post('/gangguan', [MappingController::class, 'storeIncident'])->name('incidents.store');
    Route::put('/gangguan/{incident}', [MappingController::class, 'updateIncident'])->name('incidents.update');
    Route::patch('/gangguan/{incident}/confirm', [MappingController::class, 'confirmIncident'])->name('incidents.confirm');
    Route::patch('/gangguan/{incident}/complete', [MappingController::class, 'completeIncident'])->name('incidents.complete');
    Route::delete('/gangguan/{incident}', [MappingController::class, 'deleteIncident'])->name('incidents.destroy');

    Route::get('/rekam-kerja', [MappingController::class, 'workReports'])->name('work-reports.index');
    Route::post('/rekam-kerja', [MappingController::class, 'storeWorkReport'])->name('work-reports.store');
    Route::delete('/rekam-kerja/{workReport}', [MappingController::class, 'deleteWorkReport'])->name('work-reports.destroy');

    Route::get('/users', [MappingController::class, 'users'])->name('users.index');
    Route::post('/users', [MappingController::class, 'storeUser'])->name('users.store');

    Route::get('/reports/topology.pdf', [ReportController::class, 'topologyPdf'])->name('reports.topology.pdf');
    Route::get('/reports/links.pdf', [ReportController::class, 'linksPdf'])->name('reports.links.pdf');
    Route::get('/reports/incidents.pdf', [ReportController::class, 'incidentsPdf'])->name('reports.incidents.pdf');
    Route::get('/reports/work-reports.pdf', [ReportController::class, 'workReportsPdf'])->name('reports.work-reports.pdf');
    Route::get('/reports/nodes.csv', [ReportController::class, 'nodesCsv'])->name('reports.nodes.csv');
    Route::get('/reports/links.csv', [ReportController::class, 'linksCsv'])->name('reports.links.csv');
    Route::get('/reports/incidents.csv', [ReportController::class, 'incidentsCsv'])->name('reports.incidents.csv');
    Route::get('/nodes/{node}/surat-jalan/review', [ReportController::class, 'nodeSuratJalanReview'])->name('nodes.surat-jalan.review');
    Route::get('/nodes/{node}/surat-jalan.pdf', [ReportController::class, 'nodeSuratJalan'])->name('nodes.surat-jalan');
    Route::get('/gangguan/{incident}/surat-jalan/review', [ReportController::class, 'incidentSuratJalanReview'])->name('incidents.surat-jalan.review');
    Route::get('/gangguan/{incident}/surat-jalan.pdf', [ReportController::class, 'incidentSuratJalan'])->name('incidents.surat-jalan');
    Route::get('/gangguan/{incident}/share-message', [ReportController::class, 'incidentShareMessage'])->name('incidents.share-message');
    Route::get('/gangguan/{incident}/whatsapp', [ReportController::class, 'incidentWhatsapp'])->name('incidents.whatsapp');

    Route::post('/nodes/import-csv', [MappingController::class, 'importNodesCsv'])->name('nodes.import.csv');
    Route::post('/links/import-csv', [MappingController::class, 'importLinksCsv'])->name('links.import.csv');
    Route::post('/gangguan/import-csv', [MappingController::class, 'importIncidentsCsv'])->name('incidents.import.csv');
});
