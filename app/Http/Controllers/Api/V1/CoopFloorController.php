<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CoopFloor\StoreCoopFloorRequest;
use App\Http\Requests\Api\V1\CoopFloor\UpdateCoopFloorRequest;
use App\Http\Resources\Api\V1\CoopFloorResource;
use App\Models\CoopFloor;
use App\Models\ProductionPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CoopFloorController extends Controller
{
    public function index(Request $request)
    {
        $query = CoopFloor::query()->with('coop');

        if ($request->filled('coop_id')) {
            $query->where('coop_id', $request->string('coop_id'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->string('search').'%');
        }

        $query->orderBy('server_id', 'desc');

        if ($request->filled('limit') || $request->filled('cursor')) {
            $limit = $request->integer('limit', 15);
            $floors = $query->cursorPaginate($limit, ['*'], 'cursor', $request->cursor);
            $meta = [
                'next_cursor' => $floors->nextCursor()?->encode(),
                'prev_cursor' => $floors->previousCursor()?->encode(),
                'has_next' => $floors->hasMorePages(),
                'has_prev' => $floors->previousCursor() !== null,
            ];
        } else {
            $perPage = $request->integer('per_page', 15);
            $floors = $query->paginate($perPage);
            $meta = [
                'total' => $floors->total(),
                'per_page' => $floors->perPage(),
                'current_page' => $floors->currentPage(),
                'last_page' => $floors->lastPage(),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar lantai kandang berhasil diambil.',
            'data' => array_merge([
                'items' => CoopFloorResource::collection($floors->items()),
            ], $meta),
        ]);
    }

    public function show(string $coopFloor)
    {
        $floor = CoopFloor::query()->with('coop')->findOrFail($coopFloor);

        return response()->json([
            'success' => true,
            'message' => 'Detail lantai kandang berhasil diambil.',
            'data' => new CoopFloorResource($floor),
        ]);
    }

    public function store(StoreCoopFloorRequest $request)
    {
        $floor = CoopFloor::create($this->prepareCreatePayload($request->validated()));

        return response()->json([
            'success' => true,
            'message' => 'Lantai kandang berhasil dibuat.',
            'data' => new CoopFloorResource($floor->load('coop')),
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateCoopFloorRequest $request, string $coopFloor)
    {
        $floor = CoopFloor::query()->findOrFail($coopFloor);
        $floor->fill($request->validated());
        $now = now();
        $floor->updated_at_client = $now;
        $floor->updated_at_server = $now;
        $floor->save();

        return response()->json([
            'success' => true,
            'message' => 'Lantai kandang berhasil diperbarui.',
            'data' => new CoopFloorResource($floor->load('coop')),
        ]);
    }

    public function destroy(string $coopFloor)
    {
        $floor = CoopFloor::query()->findOrFail($coopFloor);

        $hasActivePeriod = ProductionPeriod::query()
            ->where('floor_id', $floor->id)
            ->where('status', 'active')
            ->exists();

        if ($hasActivePeriod) {
            return response()->json([
                'success' => false,
                'message' => 'Lantai tidak dapat dihapus karena masih terikat dengan periode produksi aktif.',
                'data' => null,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $floor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lantai kandang berhasil dihapus.',
            'data' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function prepareCreatePayload(array $validated): array
    {
        $now = now();

        $validated['created_at_client'] = $now;
        $validated['updated_at_client'] = $now;
        $validated['created_at_server'] = $now;
        $validated['updated_at_server'] = $now;
        $validated['sync_status'] = 'SYNCED';

        if (empty($validated['server_id'])) {
            $maxServerId = CoopFloor::withTrashed()->max('server_id');
            $validated['server_id'] = ((int) $maxServerId) + 1;
        }

        return $validated;
    }
}
