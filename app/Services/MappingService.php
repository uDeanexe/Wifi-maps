<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Link;
use App\Models\Node;
use App\Models\WorkReport;
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

    public function storeIncident(array $data, ?UploadedFile $photo = null): Incident
    {
        if ($photo) {
            $data['photo_path'] = Storage::url($photo->store('incidents', 'public'));
        }

        $data = $this->incidentPayload($data);

        return Incident::create($data);
    }

    public function updateIncident(Incident $incident, array $data, ?UploadedFile $photo = null): Incident
    {
        if ($photo) {
            $this->deletePublicUpload($incident->photo_path);
            $data['photo_path'] = Storage::url($photo->store('incidents', 'public'));
        }

        $incident->update($this->incidentPayload($data, $incident));

        return $incident->refresh();
    }

    public function completeIncident(Incident $incident, array $data): Incident
    {
        $incident->update([
            'technician_report' => $data['technician_report'],
            'status' => $data['status'] ?? 'completed',
            'completed_at' => now(),
        ]);

        WorkReport::updateOrCreate(
            ['incident_id' => $incident->id],
            [
                'node_id' => $incident->node_id,
                'technician_name' => $incident->technician_name,
                'report_title' => 'Penyelesaian: '.$incident->title,
                'description' => $data['technician_report'],
                'status' => $data['status'] ?? 'completed',
            ],
        );

        return $incident->refresh();
    }

    public function confirmIncident(Incident $incident): Incident
    {
        $incident->update([
            'status' => 'in_progress',
            'assigned_at' => $incident->assigned_at ?? now(),
        ]);

        return $incident->refresh();
    }

    public function incidentMessage(Incident $incident): string
    {
        $incident->loadMissing('node.type');
        $node = $incident->node;
        $category = $incident->category === 'internet_mati' ? 'Internet Mati' : 'Kerusakan';
        [$lat, $lng] = $this->normalizeLatLngForDisplay($node?->latitude, $node?->longitude);
        $nocPhone = auth()->user()?->phone;
        $nocEmail = auth()->user()?->email;

        return collect([
            "Laporan {$category}",
            'Judul: '.($incident->title ?: '-'),
            'Status: '.($incident->status ?: '-'),
            'Node: '.($node?->code ?: '-'),
            'Lokasi: '.($node?->address ?: '-'),
            $lat !== null && $lng !== null
                ? 'Maps: https://www.google.com/maps?q='.rawurlencode($lat.','.$lng)
                : null,
            $incident->description ? 'Keluhan: '.$incident->description : null,
            $incident->work_order_notes ? 'Instruksi NOC: '.$incident->work_order_notes : null,
            'NOC/CS: '.($incident->noc_admin_name ?: '-'),
            'Kontak NOC/CS: '.($nocPhone ?: '-'),
            'Email NOC/CS: '.($nocEmail ?: '-'),
            'Teknisi: '.($incident->technician_name ?: '-'),
            'Kontak Teknisi: '.($incident->technician_contact ?: '-'),
            'Email Teknisi: '.($incident->technician_email ?: '-'),
            $incident->technician_report ? 'Laporan Teknisi: '.$incident->technician_report : null,
        ])->filter()->implode("\n");
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

    private function normalizeLatLngForDisplay(mixed $latitude, mixed $longitude): array
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return [null, null];
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;

        if (abs($lat) > 90 && abs($lng) <= 90) {
            [$lat, $lng] = [$lng, $lat];
        }

        if (abs($lat) > 90 || abs($lng) > 180) {
            return [null, null];
        }

        return [$lat, $lng];
    }

    private function incidentPayload(array $data, ?Incident $existing = null): array
    {
        $hasTechnician = filled($data['technician_name'] ?? null)
            || filled($data['technician_contact'] ?? null)
            || filled($data['technician_email'] ?? null);
        $status = $data['status'] ?? ($hasTechnician ? 'assigned' : 'reported');

        return [
            'node_id' => $data['node_id'] ?? null,
            'category' => $data['category'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'reporter_name' => $data['reporter_name'] ?? null,
            'reporter_contact' => $data['reporter_contact'] ?? null,
            'photo_path' => $data['photo_path'] ?? $existing?->photo_path,
            'noc_admin_name' => $data['noc_admin_name'] ?? null,
            'technician_name' => $data['technician_name'] ?? null,
            'technician_contact' => $data['technician_contact'] ?? null,
            'technician_email' => $data['technician_email'] ?? null,
            'work_order_notes' => $data['work_order_notes'] ?? null,
            'technician_report' => $data['technician_report'] ?? null,
            'status' => $status,
            'assigned_at' => $hasTechnician ? ($existing?->assigned_at ?? now()) : $existing?->assigned_at,
            'completed_at' => in_array($status, ['completed', 'closed'], true) ? now() : $existing?->completed_at,
        ];
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
