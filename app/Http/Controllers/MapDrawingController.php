<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\MapDrawing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MapDrawingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => MapDrawing::latest()->get()->map(fn (MapDrawing $drawing) => $this->payload($drawing))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);
        $data = $this->syncLink($data);

        $drawing = MapDrawing::create([
            ...$data,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json($this->payload($drawing), 201);
    }

    public function update(Request $request, MapDrawing $drawing): JsonResponse
    {
        $data = $this->validatedData($request);
        $data = $this->syncLink($data, $drawing);

        $drawing->update($data);

        return response()->json($this->payload($drawing->refresh()));
    }

    public function destroy(MapDrawing $drawing): JsonResponse
    {
        $drawing->delete();

        return response()->json(['message' => 'Gambar berhasil dihapus.']);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'string', Rule::in(['marker', 'polyline', 'polygon', 'rectangle'])],
            'name' => ['nullable', 'string', 'max:255'],
            'geometry' => ['required', 'array'],
            'properties' => ['nullable', 'array'],
        ]);
    }

    private function syncLink(array $data, ?MapDrawing $drawing = null): array
    {
        if (($data['type'] ?? null) !== 'polyline') {
            return $data;
        }

        $properties = $data['properties'] ?? [];
        $sourceId = (int) ($properties['source_node_id'] ?? 0);
        $targetId = (int) ($properties['target_node_id'] ?? 0);

        if ($sourceId <= 0 || $targetId <= 0 || $sourceId === $targetId) {
            unset($properties['link_id'], $properties['link_status']);
            $data['properties'] = $properties;
            return $data;
        }

        $existingLinkId = (int) ($properties['link_id'] ?? $drawing?->properties['link_id'] ?? 0);
        $link = $existingLinkId > 0 ? Link::find($existingLinkId) : null;

        if ($link) {
            $link->update([
                'source_node_id' => $sourceId,
                'target_node_id' => $targetId,
            ]);
            $properties['link_status'] = 'updated';
        } else {
            $link = Link::firstOrCreate(
                [
                    'source_node_id' => $sourceId,
                    'target_node_id' => $targetId,
                ],
                [
                    'cable_type' => 'Manual Drawing',
                    'notes' => 'Dibuat otomatis dari garis manual di Map View.',
                ]
            );
            $properties['link_status'] = $link->wasRecentlyCreated ? 'created' : 'existing';
        }

        $properties['link_id'] = $link->id;
        $data['properties'] = $properties;

        return $data;
    }

    private function payload(MapDrawing $drawing): array
    {
        return [
            'id' => $drawing->id,
            'type' => $drawing->type,
            'name' => $drawing->name,
            'geometry' => $drawing->geometry,
            'properties' => $drawing->properties,
            'updated_at' => $drawing->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
