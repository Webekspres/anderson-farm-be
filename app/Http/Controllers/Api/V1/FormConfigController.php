<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\FormConfigStoreRequest;
use App\Http\Requests\FormConfigUpdateRequest;
use App\Models\FormConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;

class FormConfigController extends Controller
{
    public function index(Request $request)
    {
        $query = FormConfig::query();
        // Optional: add filtering/sorting here
        $data = $query->get();
        return response()->json([
            'data' => $data,
            'meta' => [
                'count' => $data->count(),
            ],
        ]);
    }

    public function store(FormConfigStoreRequest $request)
    {
        $validated = $request->validated();
        $formConfig = FormConfig::create($validated);
        return response()->json([
            'data' => $formConfig,
            'meta' => [
                'message' => 'Created successfully',
            ],
        ], 201);
    }

    public function show(FormConfig $formConfig)
    {
        return response()->json([
            'data' => $formConfig,
            'meta' => null,
        ]);
    }

    public function update(FormConfigUpdateRequest $request, FormConfig $formConfig)
    {
        $validated = $request->validated();
        $formConfig->update($validated);
        return response()->json([
            'data' => $formConfig,
            'meta' => [
                'message' => 'Updated successfully',
            ],
        ]);
    }

    public function destroy(FormConfig $formConfig)
    {
        $formConfig->delete();
        return response()->json([
            'data' => null,
            'meta' => [
                'message' => 'Deleted successfully',
            ],
        ]);
    }
}
