<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Node;
use App\Services\PdfReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly PdfReportService $pdf) {}

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

    public function nodesPdf()
    {
        $nodes = $this->nodeRows();
        $path = $this->pdf->generate([
            'type' => 'nodes',
            'title' => 'Laporan Node',
            'generated_at' => now()->format('Y-m-d H:i'),
            'summary' => ['jumlah_node' => count($nodes)],
            'nodes' => $nodes,
        ], 'nodes-report-'.now()->format('Y-m-d-His').'.pdf');

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
            'stickers' => [
                'enabled' => true,
                'copies' => 3,
                'position' => 'first',
            ],
        ], 'links-report-'.now()->format('Y-m-d-His').'.pdf');

        return response()->download($path)->deleteFileAfterSend();
    }

    public function linksStickersPdf()
    {
        $links = $this->linkRows();
        $path = $this->pdf->generate([
            'type' => 'link-stickers',
            'title' => 'Sticker Link (Barcode)',
            'generated_at' => now()->format('Y-m-d H:i'),
            'summary' => ['jumlah_link' => count($links)],
            'links' => $links,
            'stickers' => [
                'enabled' => true,
                'copies' => 3,
                'position' => 'first',
            ],
        ], 'links-stickers-'.now()->format('Y-m-d-His').'.pdf');

        return response()->download($path)->deleteFileAfterSend();
    }

    public function linkStickersPdf(Link $link)
    {
        $row = $this->linkRow($link->loadMissing(['source', 'target']));
        $path = $this->pdf->generate([
            'type' => 'link-stickers',
            'title' => 'Sticker Link (Barcode)',
            'generated_at' => now()->format('Y-m-d H:i'),
            'summary' => ['jumlah_link' => 1],
            'links' => [$row],
            'stickers' => [
                'enabled' => true,
                'copies' => 3,
                'position' => 'first',
            ],
        ], 'link-stickers-'.$link->id.'-'.now()->format('Y-m-d-His').'.pdf');

        return response()->download($path)->deleteFileAfterSend();
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
        return Node::with('type')
            ->latest()
            ->get()
            ->map(fn (Node $node) => $this->nodeRow($node))
            ->values()
            ->all();
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
        return Link::with(['source', 'target'])
            ->latest()
            ->get()
            ->map(fn (Link $link) => $this->linkRow($link))
            ->values()
            ->all();
    }

    private function linkRow(Link $link): array
    {
        return [
            'id' => $link->id,
            'source_code' => $link->source?->code,
            'target_code' => $link->target?->code,
            'cable_type' => $link->cable_type,
            'core_count' => $link->core_count,
            'core_number' => $link->core_number,
            'pon_name' => $link->pon_name,
            'odc_name' => $link->odc_name,
            'notes' => $link->notes,
        ];
    }
}
