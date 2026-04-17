<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Coop\StoreCoopRequest;
use App\Http\Requests\Api\V1\Coop\UpdateCoopRequest;
use App\Http\Resources\Api\V1\CoopResource;
use App\Models\Coop;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CoopController extends Controller
{
    public function index(Request $request)
    {
        $coops = Coop::query();
        if ($request->has('farm_id')) {
            $coops->where('farm_id', $request->farm_id);
        }
        $result = $coops->paginate($request->get('per_page', 15));
        return CoopResource::collection($result)->additional([
            'meta' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
            ],
        ]);
    }

    public function store(StoreCoopRequest $request)
    {
        $coop = Coop::create($request->validated());
        return (new CoopResource($coop))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Coop $coop)
    {
        return new CoopResource($coop);
    }

    public function update(UpdateCoopRequest $request, Coop $coop)
    {
        $coop->update($request->validated());
        return new CoopResource($coop);
    }

    public function destroy(Coop $coop)
    {
        $coop->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
