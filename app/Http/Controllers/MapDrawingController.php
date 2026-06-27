<?php

namespace App\Http\Controllers;

use App\Models\MapDrawing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MapDrawingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);

        $drawing = MapDrawing::create([
            ...$data,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json($this->payload($drawing), 201);
    }

    public function update(Request $request, MapDrawing $drawing): JsonResponse
    {
        $drawing->update($this->validatedData($request));

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
