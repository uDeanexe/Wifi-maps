<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class PdfReportService
{
    public function generate(array $payload, string $filename): string
    {
        $dir = storage_path('app/reports');
        File::ensureDirectoryExists($dir);

        $input = $dir.'/'.uniqid('report-', true).'.json';
        $output = $dir.'/'.$filename;

        file_put_contents($input, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $process = new Process([$this->nodeBinary(), base_path('scripts/pdf-report.mjs'), $input, $output], base_path());
        $process->setTimeout(60);
        $process->setEnv($this->processEnvironment());
        $process->run();

        @unlink($input);

        if (! $process->isSuccessful() || ! file_exists($output)) {
            Log::warning('Node PDF generator failed, using fallback PDF.', [
                'exit_code' => $process->getExitCode(),
                'error' => $process->getErrorOutput(),
                'output' => $process->getOutput(),
            ]);
            $this->generateFallbackPdf($payload, $output);
        }

        return $output;
    }

    private function nodeBinary(): string
    {
        foreach ([
            trim((string) shell_exec('where node 2>NUL')),
            trim((string) shell_exec('command -v node 2>/dev/null')),
            'C:\Program Files\nodejs\node.exe',
            'node',
        ] as $candidate) {
            $path = strtok($candidate, PHP_EOL);
            if ($path && (str_ends_with($path, 'node') || str_ends_with($path, 'node.exe')) && (file_exists($path) || $path === 'node')) {
                return $path;
            }
        }

        return 'node';
    }

    private function processEnvironment(): array
    {
        $systemRoot = getenv('SystemRoot') ?: getenv('SYSTEMROOT') ?: 'C:\Windows';
        $path = getenv('PATH') ?: getenv('Path') ?: 'C:\Windows\System32;C:\Windows;C:\Program Files\nodejs';

        return [
            'SystemRoot' => $systemRoot,
            'SYSTEMROOT' => $systemRoot,
            'WINDIR' => getenv('WINDIR') ?: $systemRoot,
            'PATH' => $path,
            'Path' => $path,
        ];
    }

    private function generateFallbackPdf(array $payload, string $output): void
    {
        $lines = $this->payloadLines($payload);
        $pages = array_chunk($lines, 44);
        $objects = [];
        $pagesObjectNumber = 2;
        $fontObjectNumber = 3;
        $pageObjectNumbers = [];
        $contentObjectNumbers = [];
        $nextObject = 4;

        foreach ($pages as $_) {
            $pageObjectNumbers[] = $nextObject++;
            $contentObjectNumbers[] = $nextObject++;
        }

        $objects[1] = '<< /Type /Catalog /Pages '.$pagesObjectNumber.' 0 R >>';
        $objects[$fontObjectNumber] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $kids = collect($pageObjectNumbers)->map(fn ($number) => $number.' 0 R')->implode(' ');
        $objects[$pagesObjectNumber] = '<< /Type /Pages /Kids ['.$kids.'] /Count '.count($pageObjectNumbers).' >>';

        foreach ($pages as $index => $pageLines) {
            $pageObject = $pageObjectNumbers[$index];
            $contentObject = $contentObjectNumbers[$index];
            $stream = $this->pageStream($pageLines, $index === 0 ? ($payload['title'] ?? 'Report') : null);
            $objects[$contentObject] = "<< /Length ".strlen($stream)." >>\nstream\n".$stream."\nendstream";
            $objects[$pageObject] = '<< /Type /Page /Parent '.$pagesObjectNumber.' 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 '.$fontObjectNumber.' 0 R >> >> /Contents '.$contentObject.' 0 R >>';
        }

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0 => 0];
        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(max(array_keys($objects)) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= max(array_keys($objects)); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size ".(max(array_keys($objects)) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";

        file_put_contents($output, $pdf);
    }

    private function pageStream(array $lines, ?string $title = null): string
    {
        $stream = "BT\n/F1 16 Tf\n50 790 Td\n";
        if ($title) {
            $stream .= '('.$this->pdfText($title).") Tj\n0 -28 Td\n/F1 9 Tf\n";
        } else {
            $stream .= "/F1 9 Tf\n";
        }

        foreach ($lines as $line) {
            $stream .= '('.$this->pdfText($line).") Tj\n0 -16 Td\n";
        }

        return $stream."ET";
    }

    private function pdfText(string $text): string
    {
        $text = preg_replace('/[^\x20-\x7E]/', '?', $text);

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function payloadLines(array $payload): array
    {
        $lines = [
            'Tanggal: '.($payload['generated_at'] ?? now()->format('Y-m-d H:i')),
            'Jenis: '.($payload['type'] ?? 'report'),
            '',
        ];

        if (isset($payload['summary'])) {
            $lines[] = 'Ringkasan';
            foreach ($payload['summary'] as $key => $value) {
                $lines[] = '- '.str_replace('_', ' ', (string) $key).': '.$value;
            }
            $lines[] = '';
        }

        foreach (['nodes' => 'Daftar Node', 'links' => 'Daftar Link', 'incidents' => 'Daftar Gangguan', 'work_reports' => 'Rekam Kerja'] as $key => $label) {
            if (! isset($payload[$key]) || ! is_array($payload[$key])) {
                continue;
            }

            $lines[] = $label;
            foreach ($payload[$key] as $index => $row) {
                $lines[] = ($index + 1).'. '.$this->rowSummary($row);
            }
            $lines[] = '';
        }

        if (isset($payload['surat_jalan'])) {
            $sj = $payload['surat_jalan'];
            $lines[] = 'Detail Surat Jalan';
            foreach (['document_no', 'tujuan', 'keperluan', 'kerusakan', 'noc_admin', 'teknisi', 'kendaraan'] as $key) {
                $lines[] = str_replace('_', ' ', $key).': '.($sj[$key] ?? '-');
            }
            $lines[] = 'Node: '.($sj['node']['code'] ?? '-').' - '.($sj['node']['name'] ?? '-');
        }

        return array_map(fn ($line) => substr((string) $line, 0, 110), $lines);
    }

    private function rowSummary(array $row): string
    {
        $parts = [];
        foreach ($row as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = $key.'='.$value;
        }

        return implode(' | ', array_slice($parts, 0, 5));
    }
}
