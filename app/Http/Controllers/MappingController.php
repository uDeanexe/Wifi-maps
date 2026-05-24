<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Node;
use App\Models\NodeType;
use App\Models\User;
use App\Services\MappingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MappingController extends Controller
{
    public function __construct(private readonly MappingService $service) {}

    public function dashboard(): View
    {
        return view('mapping.dashboard', [
            'totals' => [
                'nodes' => Node::count(),
                'links' => Link::count(),
                'users' => User::count(),
            ],
        ]);
    }

    public function map(): View
    {
        $nodes = Node::with('type')->latest()->get();
        $links = Link::with(['source.type', 'target.type'])->latest()->get();

        return view('mapping.map', [
            'mapNodes' => $nodes->map(fn (Node $node) => [
                'id' => $node->id,
                'code' => $node->code,
                'name' => $node->name,
                'type' => $node->type?->name,
                'type_label' => $node->type?->label,
                'latitude' => $node->latitude,
                'longitude' => $node->longitude,
                'address' => $node->address,
                'notes' => $node->notes,
                'photo_path' => $node->photo_path,
            ])->values(),
            'mapLinks' => $links->map(fn (Link $link) => [
                'id' => $link->id,
                'source_node_id' => $link->source_node_id,
                'target_node_id' => $link->target_node_id,
                'cable_type' => $link->cable_type,
                'core_count' => $link->core_count,
                'core_number' => $link->core_number,
            ])->values(),
            'mapFocus' => [
                'node_id' => request('focus_node'),
                'latitude' => request('lat'),
                'longitude' => request('lng'),
            ],
        ]);
    }

    public function topology(): View
    {
        return view('mapping.topology', [
            'nodes' => Node::with('type')->orderBy('id')->get(),
            'links' => Link::with(['source', 'target'])->latest()->get(),
        ]);
    }

    public function nodes(): View
    {
        return view('mapping.nodes', [
            'nodeTypes' => NodeType::orderBy('id')->get(),
            'nodes' => Node::with('type')->latest()->get(),
        ]);
    }

    public function storeNode(Request $request): RedirectResponse
    {
        $node = $this->service->storeNode($request->validate($this->nodeRules()), $request->file('photo'));

        if (is_numeric($node->latitude) && is_numeric($node->longitude)) {
            return redirect()
                ->route('map', ['focus_node' => $node->id])
                ->with('status', 'Node berhasil dibuat dan ditampilkan di Map View.');
        }

        return back()->with('status', 'Node berhasil dibuat.');
    }

    public function updateNode(Request $request, Node $node): RedirectResponse
    {
        $this->service->updateNode($node, $request->validate($this->nodeRules($node)), $request->file('photo'));

        return back()->with('status', 'Node berhasil diupdate.');
    }

    public function deleteNode(Node $node): RedirectResponse
    {
        $this->service->deleteNode($node);

        return back()->with('status', 'Node berhasil dihapus.');
    }

    public function updateNodePosition(Request $request, Node $node)
    {
        $data = $request->validate([
            'topology_x' => ['required', 'integer'],
            'topology_y' => ['required', 'integer'],
        ]);

        $node->update($data);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Posisi topology berhasil disimpan.']);
        }

        return back()->with('status', 'Posisi topology berhasil disimpan.');
    }

    public function links(): View
    {
        return view('mapping.links', [
            'nodes' => Node::orderBy('code')->get(),
            'links' => Link::with(['source', 'target'])->latest()->get(),
        ]);
    }

    public function storeLink(Request $request)
    {
        $link = $this->service->storeLink($request->validate($this->linkRules()));

        if ($request->expectsJson()) {
            $link->load(['source', 'target']);

            return response()->json([
                'id' => $link->id,
                'source_node_id' => $link->source_node_id,
                'target_node_id' => $link->target_node_id,
                'source_code' => $link->source?->code,
                'target_code' => $link->target?->code,
                'message' => 'Link berhasil dibuat.',
            ]);
        }

        return back()->with('status', 'Link berhasil dibuat.');
    }

    public function updateLink(Request $request, Link $link): RedirectResponse
    {
        $this->service->updateLink($link, $request->validate($this->linkRules()));

        return back()->with('status', 'Link berhasil diupdate.');
    }

    public function deleteLink(Link $link): RedirectResponse
    {
        $link->delete();

        return back()->with('status', 'Link berhasil dihapus.');
    }

    public function users(): View
    {
        abort_unless(in_array(auth()->user()->role, ['superadmin', 'admin'], true), 403);

        return view('mapping.users', ['users' => User::latest()->get()]);
    }

    public function importNodesCsv(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        [$created, $updated, $skipped, $errors] = $this->importCsv($data['csv']->getRealPath(), function (array $row, int $line) {
            $code = trim((string) ($row['code'] ?? ''));
            if ($code === '') {
                return ['skip' => "Line {$line}: code wajib diisi."];
            }

            $typeText = trim((string) ($row['type'] ?? $row['type_label'] ?? ''));
            if ($typeText === '') {
                return ['skip' => "Line {$line}: type/type_label wajib diisi untuk node {$code}."];
            }

            $nodeType = NodeType::query()
                ->where('name', $typeText)
                ->orWhere('label', $typeText)
                ->first();

            if (! $nodeType) {
                return ['skip' => "Line {$line}: node type '{$typeText}' tidak ditemukan (node {$code})."];
            }

            $payload = [
                'node_type_id' => $nodeType->id,
                'code' => $code,
                'name' => $row['name'] ?? null,
                'latitude' => $row['latitude'] ?? null,
                'longitude' => $row['longitude'] ?? null,
                'address' => $row['address'] ?? null,
                'notes' => $row['notes'] ?? null,
            ];

            $existing = Node::where('code', $code)->first();
            if ($existing) {
                $this->service->updateNode($existing, $payload);
                return ['updated' => true];
            }

            $this->service->storeNode($payload);
            return ['created' => true];
        });

        return back()->with([
            'status' => "Import nodes selesai. created={$created}, updated={$updated}, skipped={$skipped}",
            'import_errors' => $errors,
        ]);
    }

    public function importLinksCsv(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        [$created, $updated, $skipped, $errors] = $this->importCsv($data['csv']->getRealPath(), function (array $row, int $line) {
            $sourceCode = trim((string) ($row['source_code'] ?? ''));
            $targetCode = trim((string) ($row['target_code'] ?? ''));
            if ($sourceCode === '' || $targetCode === '') {
                return ['skip' => "Line {$line}: source_code dan target_code wajib diisi."];
            }

            $source = Node::where('code', $sourceCode)->first();
            $target = Node::where('code', $targetCode)->first();
            if (! $source || ! $target) {
                return ['skip' => "Line {$line}: node tidak ditemukan (source={$sourceCode}, target={$targetCode})."];
            }

            $payload = [
                'source_node_id' => $source->id,
                'target_node_id' => $target->id,
                'cable_type' => $row['cable_type'] ?? null,
                'core_count' => $row['core_count'] ?? null,
                'core_number' => $row['core_number'] ?? null,
                'pon_name' => $row['pon_name'] ?? null,
                'odc_name' => $row['odc_name'] ?? null,
                'notes' => $row['notes'] ?? null,
            ];

            $existing = Link::where('source_node_id', $source->id)->where('target_node_id', $target->id)->first();
            if ($existing) {
                $this->service->updateLink($existing, $payload);
                return ['updated' => true];
            }

            $this->service->storeLink($payload);
            return ['created' => true];
        });

        return back()->with([
            'status' => "Import links selesai. created={$created}, updated={$updated}, skipped={$skipped}",
            'import_errors' => $errors,
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        abort_unless(in_array(auth()->user()->role, ['superadmin', 'admin'], true), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['superadmin', 'admin', 'supervisor_noc', 'teknisi'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (auth()->user()->role !== 'superadmin' && $data['role'] === 'superadmin') {
            abort(403);
        }

        User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'User berhasil dibuat.');
    }

    private function nodeRules(?Node $node = null): array
    {
        return [
            'node_type_id' => ['required', 'exists:node_types,id'],
            'code' => ['required', 'string', 'max:255', Rule::unique('nodes', 'code')->ignore($node)],
            'name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'topology_x' => ['nullable', 'integer'],
            'topology_y' => ['nullable', 'integer'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }

    private function linkRules(): array
    {
        return [
            'source_node_id' => ['required', 'exists:nodes,id'],
            'target_node_id' => ['required', 'exists:nodes,id'],
            'cable_type' => ['nullable', 'string', 'max:255'],
            'core_count' => ['nullable', 'integer'],
            'core_number' => ['nullable', 'string', 'max:255'],
            'pon_name' => ['nullable', 'string', 'max:255'],
            'odc_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function importCsv(string $path, callable $handler): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $fh = fopen($path, 'r');
        if (! $fh) {
            return [0, 0, 0, ['Gagal membaca file CSV.']];
        }

        $headers = fgetcsv($fh);
        if (! $headers) {
            fclose($fh);
            return [0, 0, 0, ['CSV kosong.']];
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);
        $line = 1;
        while (($row = fgetcsv($fh)) !== false) {
            $line++;
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }
            $assoc = [];
            foreach ($headers as $i => $key) {
                if ($key === '') {
                    continue;
                }
                $assoc[$key] = $row[$i] ?? null;
            }

            try {
                $result = $handler($assoc, $line);
                if (isset($result['created'])) $created++;
                elseif (isset($result['updated'])) $updated++;
                elseif (isset($result['skip'])) {
                    $skipped++;
                    if (count($errors) < 25) $errors[] = $result['skip'];
                }
            } catch (\Throwable $e) {
                $skipped++;
                if (count($errors) < 25) {
                    $errors[] = "Line {$line}: ".$e->getMessage();
                }
            }
        }

        fclose($fh);

        return [$created, $updated, $skipped, $errors];
    }
}
