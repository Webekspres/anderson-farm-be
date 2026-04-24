<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FormConfig\StoreFormConfigRequest;
use App\Http\Requests\Api\V1\FormConfig\UpdateFormConfigRequest;
use App\Http\Resources\Api\V1\FormConfigResource;
use App\Models\FormConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FormConfigController extends Controller
{
    public function index(Request $request)
    {
        $query = FormConfig::query();
        $data = $query->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil daftar form config',
            'data' => FormConfigResource::collection($data),
        ]);
    }

    public function store(StoreFormConfigRequest $request)
    {
        $validated = $request->validated();
        
        if (!isset($validated['id'])) {
            $validated['id'] = (string) Str::uuid();
        }
        
        $formConfig = FormConfig::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Berhasil membuat form config',
            'data' => new FormConfigResource($formConfig),
        ], 201);
    }

    public function show(FormConfig $form_config)
    {
        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil detail form config',
            'data' => new FormConfigResource($form_config),
        ]);
    }

    public function update(UpdateFormConfigRequest $request, FormConfig $form_config)
    {
        $validated = $request->validated();
        $form_config->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Berhasil memperbarui form config',
            'data' => new FormConfigResource($form_config),
        ]);
    }

    public function destroy(FormConfig $form_config)
    {
        $form_config->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Berhasil menghapus form config',
            'data' => null,
        ]);
    }
}
