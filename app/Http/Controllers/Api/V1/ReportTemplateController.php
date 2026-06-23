<?php
// app/Http/Controllers/Api/V1/ReportTemplateController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReportTemplate\StoreReportTemplateRequest;
use App\Http\Requests\Api\V1\ReportTemplate\UpdateReportTemplateRequest;
use App\Http\Resources\Api\V1\ReportTemplateResource;
use App\Models\ReportTemplate;
use Illuminate\Http\Request;

class ReportTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = ReportTemplate::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('report_type')) {
            $query->where('report_type', $request->report_type);
        }

        $query->orderBy('server_id', 'desc');

        if ($request->filled('limit') || $request->filled('cursor')) {
            $limit = $request->integer('limit', 15);
            $data = $query->cursorPaginate($limit, ['*'], 'cursor', $request->cursor);
            $meta = [
                'next_cursor' => $data->nextCursor()?->encode(),
                'prev_cursor' => $data->previousCursor()?->encode(),
                'has_next' => $data->hasMorePages(),
                'has_prev' => $data->previousCursor() !== null,
            ];
        } else {
            $perPage = $request->integer('per_page', 15);
            $data = $query->paginate($perPage);
            $meta = [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar template laporan berhasil diambil.',
            'data' => array_merge([
                'items' => ReportTemplateResource::collection($data->items()),
            ], $meta),
        ]);
    }

    public function store(StoreReportTemplateRequest $request)
    {
        $data = $request->validated();
        $template = ReportTemplate::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Template laporan berhasil dibuat.',
            'data' => new ReportTemplateResource($template),
        ], 201);
    }

    public function update(UpdateReportTemplateRequest $request, $id)
    {
        $template = ReportTemplate::findOrFail($id);
        $template->fill($request->validated());
        $template->save();

        return response()->json([
            'success' => true,
            'message' => 'Template laporan berhasil diupdate.',
            'data' => new ReportTemplateResource($template),
        ]);
    }

    public function destroy($id)
    {
        $template = ReportTemplate::findOrFail($id);
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template laporan berhasil dihapus.',
            'data' => null,
        ]);
    }
}
