<?php

// app/Http/Controllers/Api/V1/AreaController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Area\ShowAreaRequest;
use App\Http\Requests\Api\V1\Area\StoreAreaRequest;
use App\Http\Requests\Api\V1\Area\UpdateAreaRequest;
use App\Http\Resources\Api\V1\AreaResource;
use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index(Request $request)
    {
        $query = Area::query();

        // Filter by name (search)
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }
        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $query->orderBy('server_id', 'desc');

        // Dynamic Pagination
        if ($request->filled('limit') || $request->filled('cursor')) {
            $limit = $request->integer('limit', 15);
            $areas = $query->cursorPaginate($limit, ['*'], 'cursor', $request->cursor);
            $meta = [
                'next_cursor' => $areas->nextCursor()?->encode(),
                'prev_cursor' => $areas->previousCursor()?->encode(),
                'has_next' => $areas->hasMorePages(),
                'has_prev' => $areas->previousCursor() !== null,
            ];
        } else {
            $perPage = $request->integer('per_page', 15);
            $areas = $query->paginate($perPage);
            $meta = [
                'total' => $areas->total(),
                'per_page' => $areas->perPage(),
                'current_page' => $areas->currentPage(),
                'last_page' => $areas->lastPage(),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar area berhasil diambil.',
            'data' => array_merge([
                'items' => AreaResource::collection($areas->items()),
            ], $meta),
        ]);
    }

    public function show(ShowAreaRequest $request, string $area)
    {
        $areaModel = Area::query()->findOrFail($area);

        return response()->json([
            'success' => true,
            'message' => 'Detail area berhasil diambil.',
            'data' => new AreaResource($areaModel),
        ]);
    }

    public function store(StoreAreaRequest $request)
    {
        $data = $request->validated();
        $data['manager_id'] ??= $request->user()->id;

        // `type` / `size_m2` accepted by the form request for API compatibility,
        // but are not persisted columns on `areas` yet.
        unset($data['type'], $data['size_m2']);

        $area = Area::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Area berhasil dibuat.',
            'data' => new AreaResource($area),
        ], 201);
    }

    public function update(UpdateAreaRequest $request, $id)
    {
        $area = Area::findOrFail($id);
        $area->fill($request->validated());
        $area->save();

        return response()->json([
            'success' => true,
            'message' => 'Area berhasil diupdate.',
            'data' => new AreaResource($area),
        ]);
    }

    public function destroy($id)
    {
        $area = Area::findOrFail($id);
        $area->delete();

        return response()->json([
            'success' => true,
            'message' => 'Area berhasil dihapus.',
            'data' => null,
        ]);
    }
}
