<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePriceReferenceRequest;
use App\Http\Requests\Api\V1\UpdatePriceReferenceRequest;
use App\Http\Resources\Api\V1\PriceReferenceResource;
use App\Models\PriceReference;

class PriceReferenceController extends Controller
{
    public function store(StorePriceReferenceRequest $request)
    {
        $data = $request->validated();
        if (!isset($data['id'])) {
            $data['id'] = (string) \Illuminate\Support\Str::uuid();
        }
        $priceRef = PriceReference::create($data);
        return response()->json([
            'success' => true,
            'message' => 'Price reference created successfully.',
            'data' => new PriceReferenceResource($priceRef),
        ], 201);
    }

    public function update(UpdatePriceReferenceRequest $request, $id)
    {
        $priceRef = PriceReference::find($id);
        if (!$priceRef) {
            return response()->json([
                'success' => false,
                'message' => 'Price reference not found.',
                'data' => null,
            ], 404);
        }
        $priceRef->update($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Price reference updated successfully.',
            'data' => new PriceReferenceResource($priceRef),
        ]);
    }

    public function destroy($id)
    {
        $priceRef = PriceReference::find($id);
        if (!$priceRef || $priceRef->deleted_at) {
            return response()->json([
                'success' => false,
                'message' => 'Price reference not found or already deleted.',
                'data' => null,
            ], 404);
        }
        $priceRef->delete();
        return response()->json([
            'success' => true,
            'message' => 'Price reference deleted successfully.',
            'data' => null,
        ]);
    }
}
