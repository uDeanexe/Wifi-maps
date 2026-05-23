<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Link;
use App\Models\Node;
use App\Models\WorkReport;
use App\Services\MappingService;
use App\Services\PdfReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly PdfReportService $pdf,
        private readonly MappingService $mapping,
    ) {}

    public function topologyPdf()
    {
        $nodes = $this->nodeRows();
        $links = $this->linkRows();
        $path = $this->pdf->generate([
            'type' => 'topology',
            'title' => 'Laporan Topologi',
            'generated_at' => now()->format('Y-m-d H:i'),
            'summary' => ['jumlah_node' => count($nodes), 'jumlah_link' => count($links)],
            'nodes' => $nodes,
            'links' => $links,
        ], 'topology-report-'.now()->format('Y-m-d-His').'.pdf');

        return response()->download($path)->deleteFileAfterSend();
    }

    public function linksPdf()
    {
        $links = $this->linkRows();
        $path = $this->pdf->generate([
            'type' => 'links',
            'title' => 'Laporan Link',
            'generated_at' => now()->format('Y-m-d H:i'),
            'summary' => ['jumlah_link' => count($links)],
            'links' => $links,
        ], 'links-report-'.now()->format('Y-m-d-His').'.pdf');

        return response()->download($path)->deleteFileAfterSend();
    }

    public function incidentsPdf()
    {
        $incidents = $this->incidentRows();
        $path = $this->pdf->generate([
            'type' => 'incidents',
            'title' => 'Laporan Gangguan',
            'generated_at' => now()->format('Y-m-d H:i'),
            'summary' => ['jumlah_gangguan' => count($incidents)],
            'incidents' => $incidents,
        ], 'incidents-report-'.now()->format('Y-m-d-His').'.pdf');

        return response()->download($path)->deleteFileAfterSend();
    }

    public function workReportsPdf()
    {
        $reports = WorkReport::with(['node', 'incident'])->latest()->get()->map(fn (WorkReport $report) => [
            'report_title' => $report->report_title,
            'status' => $report->status,
            'technician_name' => $report->technician_name,
            'description' => $report->description,
            'node_code' => $report->node?->code,
            'incident_title' => $report->incident?->title,
        ])->values()->all();

        $path = $this->pdf->generate([
            'type' => 'work_reports',
            'title' => 'Laporan Rekam Kerja',
            'generated_at' => now()->format('Y-m-d H:i'),
            'summary' => ['jumlah_rekam_kerja' => count($reports)],
            'work_reports' => $reports,
        ], 'work-reports-'.now()->format('Y-m-d-His').'.pdf');

        return response()->download($path)->deleteFileAfterSend();
    }

    public function nodeSuratJalanReview(Node $node, Request $request): View
    {
        $node->load('type');

        return view('reports.surat-jalan-review', [
            'title' => 'Review Surat Jalan Node',
            'backRoute' => route('nodes.index'),
            'downloadRoute' => route('nodes.surat-jalan', $node),
            'suratJalan' => $this->nodeSuratJalanPayload($node, $request),
            'source' => [
                'type' => 'Node',
                'title' => $node->code,
                'subtitle' => $node->name ?: $node->type?->label,
            ],
        ]);
    }

    public function nodeSuratJalan(Node $node, Request $request)
    {
        $node->load('type');

        return $this->suratJalan($request, $this->nodeSuratJalanPayload($node, $request), 'surat-jalan-node-'.$node->id.'-'.now()->format('YmdHis').'.pdf');
    }

    public function incidentSuratJalanReview(Incident $incident, Request $request): View
    {
        $incident->load('node.type');

        return view('reports.surat-jalan-review', [
            'title' => 'Review Surat Jalan Gangguan',
            'backRoute' => route('incidents.index'),
            'downloadRoute' => route('incidents.surat-jalan', $incident),
            'suratJalan' => $this->incidentSuratJalanPayload($incident, $request),
            'source' => [
                'type' => 'Gangguan',
                'title' => $incident->title,
                'subtitle' => trim(($incident->node?->code ?? '-').' - '.$incident->status),
            ],
        ]);
    }

    public function incidentSuratJalan(Incident $incident, Request $request)
    {
        $incident->load('node.type');

        return $this->suratJalan($request, $this->incidentSuratJalanPayload($incident, $request), 'surat-jalan-gangguan-'.$incident->id.'-'.now()->format('YmdHis').'.pdf');
    }

    private function nodeSuratJalanPayload(Node $node, Request $request): array
    {
        return [
            'document_no' => $request->query('document_no', 'SJ-'.str_pad((string) $node->id, 6, '0', STR_PAD_LEFT)),
            'tujuan' => $request->query('tujuan', $node->name),
            'keperluan' => $request->query('keperluan', 'Pekerjaan lapangan'),
            'kerusakan' => $request->query('kerusakan', $node->notes),
            'noc_admin' => $request->query('noc_admin', auth()->user()->name),
            'teknisi' => $request->query('teknisi'),
            'teknisi_contact' => $request->query('teknisi_contact'),
            'teknisi_email' => $request->query('teknisi_email'),
            'kendaraan' => $request->query('kendaraan'),
            'node' => $this->nodeRow($node),
        ];
    }

    private function incidentSuratJalanPayload(Incident $incident, Request $request): array
    {
        $node = $incident->node;

        return [
            'document_no' => $request->query('document_no', 'SJ-INC-'.str_pad((string) $incident->id, 6, '0', STR_PAD_LEFT)),
            'tujuan' => $request->query('tujuan', $incident->title),
            'keperluan' => $request->query('keperluan', $incident->work_order_notes ?: 'Penanganan gangguan lapangan'),
            'kerusakan' => $request->query('kerusakan', $incident->description),
            'noc_admin' => $request->query('noc_admin', $incident->noc_admin_name ?: auth()->user()->name),
            'teknisi' => $request->query('teknisi', $incident->technician_name),
            'teknisi_contact' => $request->query('teknisi_contact', $incident->technician_contact),
            'teknisi_email' => $request->query('teknisi_email', $incident->technician_email),
            'kendaraan' => $request->query('kendaraan'),
            'node' => $node ? $this->nodeRow($node) : ['code' => 'INC-'.$incident->id, 'name' => $incident->title],
        ];
    }

    public function incidentShareMessage(Incident $incident)
    {
        return response($this->mapping->incidentMessage($incident), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function incidentWhatsapp(Incident $incident, Request $request)
    {
        $message = $this->mapping->incidentMessage($incident);
        $phoneRaw = $request->query('phone', $incident->technician_contact);
        $phone = $this->normalizeWhatsappPhone($phoneRaw);

        if (! $phone) {
            return back()->with('error', 'Kontak teknisi belum diisi. Isi "Kontak Teknisi" dulu sebelum kirim WhatsApp.');
        }

        $url = 'https://wa.me/'.$phone.'?text='.rawurlencode($message);

        return redirect()->away($url);
    }

    private function normalizeWhatsappPhone(?string $value): ?string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        $clean = preg_replace('/[^0-9+]/', '', $raw) ?: '';
        $clean = ltrim($clean);
        if ($clean === '') {
            return null;
        }

        if (str_starts_with($clean, '+')) {
            $clean = substr($clean, 1);
        }

        if (str_starts_with($clean, '0')) {
            $clean = '62'.ltrim($clean, '0');
        }

        $digits = preg_replace('/\D/', '', $clean) ?: '';

        return $digits !== '' ? $digits : null;
    }

    public function linksCsv(): StreamedResponse
    {
        return $this->csv('links-'.now()->format('Y-m-d').'.csv', [
            'source_code', 'target_code', 'cable_type', 'core_count', 'core_number', 'pon_name', 'odc_name', 'notes',
        ], $this->linkRows());
    }

    public function nodesCsv(): StreamedResponse
    {
        return $this->csv('nodes-'.now()->format('Y-m-d').'.csv', [
            'code', 'type', 'type_label', 'name', 'latitude', 'longitude', 'address', 'notes',
        ], $this->nodeRows());
    }

    public function incidentsCsv(): StreamedResponse
    {
        return $this->csv('incidents-'.now()->format('Y-m-d').'.csv', [
            'title', 'category', 'status', 'node_code', 'reporter_name', 'technician_name', 'description',
        ], $this->incidentRows());
    }

    private function suratJalan(Request $request, array $suratJalan, string $filename)
    {
        $path = $this->pdf->generate([
            'type' => 'surat_jalan',
            'title' => 'Surat Jalan / Work Order Lapangan',
            'generated_at' => now()->format('Y-m-d H:i'),
            'surat_jalan' => $suratJalan,
        ], $filename);

        if ($request->boolean('print')) {
            return response()
                ->file($path, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="'.$filename.'"',
                ])
                ->deleteFileAfterSend();
        }

        return response()->download($path)->deleteFileAfterSend();
    }

    private function csv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($key) => $row[$key] ?? null, $headers));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function nodeRows(): array
    {
        return Node::with('type')->latest()->get()->map(fn (Node $node) => $this->nodeRow($node))->values()->all();
    }

    private function nodeRow(Node $node): array
    {
        return [
            'id' => $node->id,
            'code' => $node->code,
            'name' => $node->name,
            'type' => $node->type?->name,
            'type_label' => $node->type?->label,
            'latitude' => $node->latitude,
            'longitude' => $node->longitude,
            'address' => $node->address,
            'photo_path' => $node->photo_path,
            'notes' => $node->notes,
        ];
    }

    private function linkRows(): array
    {
        return Link::with(['source', 'target'])->latest()->get()->map(fn (Link $link) => [
            'source_code' => $link->source?->code,
            'target_code' => $link->target?->code,
            'cable_type' => $link->cable_type,
            'core_count' => $link->core_count,
            'core_number' => $link->core_number,
            'pon_name' => $link->pon_name,
            'odc_name' => $link->odc_name,
            'notes' => $link->notes,
        ])->values()->all();
    }

    private function incidentRows(): array
    {
        return Incident::with('node')->latest()->get()->map(fn (Incident $incident) => [
            'title' => $incident->title,
            'category' => $incident->category,
            'status' => $incident->status,
            'node_code' => $incident->node?->code,
            'reporter_name' => $incident->reporter_name,
            'technician_name' => $incident->technician_name,
            'description' => $incident->description,
        ])->values()->all();
    }
}
