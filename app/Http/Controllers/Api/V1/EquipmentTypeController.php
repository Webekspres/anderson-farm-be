<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EquipmentType\StoreEquipmentTypeRequest;
use App\Http\Requests\Api\V1\EquipmentType\UpdateEquipmentTypeRequest;
use App\Http\Resources\Api\V1\EquipmentTypeResource;
use App\Models\EquipmentType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EquipmentTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    public function index(Request $request)
    {
        $query = EquipmentType::query();
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%$search%");
        }
        $query->orderBy('server_id', 'desc');
        $perPage = $request->get('per_page', 15);
        $useCursor = $request->boolean('cursor', false);
        if ($useCursor) {
            $result = $query->cursorPaginate($perPage);
            return EquipmentTypeResource::collection($result)->additional([
                'meta' => [
                    'per_page' => $result->perPage(),
                    'next_cursor' => $result->nextCursor()?->encode(),
                    'prev_cursor' => $result->previousCursor()?->encode(),
                ],
            ]);
        } else {
            $result = $query->paginate($perPage);
            return EquipmentTypeResource::collection($result)->additional([
                'meta' => [
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                ],
            ]);
        }
    }

    public function store(StoreEquipmentTypeRequest $request)
    {
        $equipmentType = EquipmentType::create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Berhasil membuat equipment type',
            'data' => new EquipmentTypeResource($equipmentType),
        ], Response::HTTP_CREATED);
    }

    public function show(EquipmentType $equipmentType)
    {
        return new EquipmentTypeResource($equipmentType);
    }

    public function update(UpdateEquipmentTypeRequest $request, $id)
    {
        $equipmentType = EquipmentType::findOrFail($id);
        $equipmentType->update($request->validated());
        return new EquipmentTypeResource($equipmentType);
    }

    public function destroy($id)
    {
        $equipmentType = EquipmentType::findOrFail($id);
        $equipmentType->delete();
        return response()->json(['message' => 'Deleted successfully', 'data' => null]);
    }
}
