<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CoopEquipment\StoreCoopEquipmentRequest;
use App\Http\Resources\Api\V1\CoopEquipmentResource;
use App\Models\Coop;
use App\Models\CoopEquipment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CoopEquipmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Coop $coop, Request $request)
    {
        // Get all floors of this coop, then get all equipment on those floors
        $floorIds = $coop->coopFloors()->pluck('id')->toArray();
        $items = CoopEquipment::whereIn('floor_id', $floorIds)
            ->with('equipmentType')
            ->orderBy('server_id', 'desc')
            ->get();

        return CoopEquipmentResource::collection($items)->additional([
            'success' => true,
            'message' => 'Coop equipments retrieved',
        ])->response()->setStatusCode(Response::HTTP_OK);
    }

    public function store(StoreCoopEquipmentRequest $request, Coop $coop)
    {
        $payload = $request->validated();

        // Validate that floor_id belongs to this coop
        $floor = $coop->coopFloors()->find($payload['floor_id']);
        if (!$floor) {
            return response()->json([
                'success' => false,
                'message' => 'Floor not found in this coop',
                'data' => null,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $equipment = CoopEquipment::create($payload);

        return (new CoopEquipmentResource($equipment->load('equipmentType')))->additional([
            'success' => true,
            'message' => 'Created successfully',
        ])->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Coop $coop, CoopEquipment $equipment)
    {
        // Check if equipment belongs to any floor of this coop
        $floorIds = $coop->coopFloors()->pluck('id')->toArray();
        if (!in_array($equipment->floor_id, $floorIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Not Found',
                'data' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        $equipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deleted successfully',
            'data' => null,
        ], Response::HTTP_OK);
    }
}
