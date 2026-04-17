<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOvkItemRequest;
use App\Http\Requests\Api\V1\UpdateOvkItemRequest;
use App\Http\Resources\Api\V1\OvkItemResource;
use App\Models\OvkItem;
use Illuminate\Http\Request;

class OvkItemController extends Controller
{
    public function index(Request $request)
    {
        $query = OvkItem::query();
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        if ($category = $request->query('category')) {
            $query->where('type', $category);
        }
        $query->orderBy('server_id', 'desc');
        $perPage = $request->query('per_page', 15);
        $useCursor = $request->boolean('cursor', false);
        $paginator = $useCursor
            ? $query->cursorPaginate($perPage)
            : $query->paginate($perPage);
        $meta = $useCursor
            ? [
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
                'has_next' => $paginator->hasMorePages(),
                'has_prev' => $paginator->previousCursor() !== null,
            ]
            : [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ];
        return response()->json([
            'success' => true,
            'message' => 'Daftar OVK berhasil dimuat',
            'data' => [
                'items' => OvkItemResource::collection($paginator),
                ...$meta
            ]
        ]);
    }

    public function store(StoreOvkItemRequest $request)
    {
        $data = $request->validated();
        $ovkItem = OvkItem::create($data);
        return response()->json([
            'success' => true,
            'message' => 'OVK berhasil dibuat',
            'data' => new OvkItemResource($ovkItem)
        ], 201);
    }

    public function show(OvkItem $ovkItem)
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail OVK berhasil dimuat',
            'data' => new OvkItemResource($ovkItem)
        ]);
    }

    public function update(UpdateOvkItemRequest $request, OvkItem $ovkItem)
    {
        $ovkItem->update($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'OVK berhasil diupdate',
            'data' => new OvkItemResource($ovkItem)
        ]);
    }

    public function destroy(OvkItem $ovkItem)
    {
        if ($ovkItem->deleted_at) {
            return response()->json([
                'success' => false,
                'message' => 'OVK sudah dihapus',
                'data' => null
            ], 404);
        }
        $ovkItem->delete();
        return response()->json([
            'success' => true,
            'message' => 'OVK berhasil dihapus',
            'data' => null
        ]);
    }
}
