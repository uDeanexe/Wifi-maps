<?php

namespace App\Services;

use App\Models\Link;
use App\Models\Node;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MappingService
{
    public function storeNode(array $data, ?UploadedFile $photo = null): Node
    {
        if ($photo) {
            $data['photo_path'] = Storage::url($photo->store('nodes', 'public'));
        }

        return Node::create($this->nodePayload($data));
    }

    public function updateNode(Node $node, array $data, ?UploadedFile $photo = null): Node
    {
        if ($photo) {
            $this->deletePublicUpload($node->photo_path);
            $data['photo_path'] = Storage::url($photo->store('nodes', 'public'));
        }

        $node->update($this->nodePayload($data, $node));

        return $node->refresh();
    }

    public function deleteNode(Node $node): void
    {
        DB::transaction(function () use ($node): void {
            Link::where('source_node_id', $node->id)->orWhere('target_node_id', $node->id)->delete();
            $this->deletePublicUpload($node->photo_path);
            $node->delete();
        });
    }

    public function storeLink(array $data): Link
    {
        $this->validateLink($data);

        return Link::create($data);
    }

    public function updateLink(Link $link, array $data): Link
    {
        $this->validateLink($data, $link);

        $link->update($data);

        return $link->refresh();
    }

    private function nodePayload(array $data, ?Node $existing = null): array
    {
        [$latitude, $longitude] = $this->normalizeLatLng($data['latitude'] ?? null, $data['longitude'] ?? null);

        return [
            'node_type_id' => $data['node_type_id'],
            'code' => $data['code'],
            'name' => $data['name'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'address' => $data['address'] ?? null,
            'photo_path' => $data['photo_path'] ?? $existing?->photo_path,
            'notes' => $data['notes'] ?? null,
            'topology_x' => $data['topology_x'] ?? $existing?->topology_x ?? 100,
            'topology_y' => $data['topology_y'] ?? $existing?->topology_y ?? 100,
        ];
    }

    private function normalizeLatLng(mixed $latitude, mixed $longitude): array
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return [$latitude ?: null, $longitude ?: null];
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;

        // Common operator mistake: latitude and longitude swapped.
        // Latitude must be within [-90..90]; Longitude within [-180..180].
        if (abs($lat) > 90 && abs($lng) <= 90) {
            [$lat, $lng] = [$lng, $lat];
        }

        if (abs($lat) > 90 || abs($lng) > 180) {
            throw ValidationException::withMessages([
                'latitude' => 'Koordinat tidak valid. Pastikan latitude berada di rentang -90..90 dan longitude -180..180.',
                'longitude' => 'Koordinat tidak valid. Pastikan latitude berada di rentang -90..90 dan longitude -180..180.',
            ]);
        }

        return [$lat, $lng];
    }

    private function validateLink(array $data, ?Link $existing = null): void
    {
        if ((int) $data['source_node_id'] === (int) $data['target_node_id']) {
            throw ValidationException::withMessages(['target_node_id' => 'Node tujuan tidak boleh sama dengan node sumber.']);
        }

        $exists = Link::where('source_node_id', $data['source_node_id'])
            ->where('target_node_id', $data['target_node_id'])
            ->when($existing, fn ($query) => $query->whereKeyNot($existing->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['target_node_id' => 'Link antar node ini sudah ada.']);
        }
    }

    private function deletePublicUpload(?string $path): void
    {
        if (! $path || ! str_starts_with($path, '/storage/')) {
            return;
        }

        Storage::disk('public')->delete(str_replace('/storage/', '', $path));
    }
}

