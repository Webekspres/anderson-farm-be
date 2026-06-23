<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Farm\StoreFarmRequest;
use App\Http\Requests\Api\V1\Farm\UpdateFarmRequest;
use App\Http\Resources\Api\V1\FarmResource;
use App\Models\Farm;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    public function index(Request $request)
    {
        $query = Farm::query();

        // Filter: search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        // Filter: is_active
        if (!is_null($request->is_active)) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->orderBy('server_id', 'desc');

        // Dynamic Pagination
        if ($request->filled('limit') || $request->filled('cursor')) {
            $limit = $request->integer('limit', 10);
            $farms = $query->cursorPaginate($limit, ['*'], 'cursor', $request->cursor);
            $meta = [
                'next_cursor' => $farms->nextCursor()?->encode(),
                'prev_cursor' => $farms->previousCursor()?->encode(),
                'has_next' => $farms->hasMorePages(),
                'has_prev' => $farms->previousCursor() !== null,
            ];
        } else {
            $perPage = $request->integer('per_page', 10);
            $farms = $query->paginate($perPage);
            $meta = [
                'total' => $farms->total(),
                'per_page' => $farms->perPage(),
                'current_page' => $farms->currentPage(),
                'last_page' => $farms->lastPage(),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar farm berhasil diambil.',
            'data' => array_merge([
                'items' => FarmResource::collection($farms->items()),
            ], $meta),
        ]);
    }

    public function store(StoreFarmRequest $request)
    {
        $farm = Farm::create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Farm berhasil dibuat.',
            'data' => new FarmResource($farm),
        ], 201);
    }

    public function update(UpdateFarmRequest $request, $id)
    {
        $farm = Farm::findOrFail($id);
        $farm->fill($request->validated());
        $farm->save();
        return response()->json([
            'success' => true,
            'message' => 'Farm berhasil diupdate.',
            'data' => new FarmResource($farm),
        ]);
    }

    public function destroy($id)
    {
        $farm = Farm::findOrFail($id);
        $farm->delete();
        return response()->json([
            'success' => true,
            'message' => 'Farm berhasil dihapus.',
            'data' => null,
        ]);
    }
}
