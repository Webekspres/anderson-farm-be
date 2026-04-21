<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEducationArticleRequest;
use App\Http\Requests\Api\V1\UpdateEducationArticleRequest;
use App\Http\Resources\EducationArticleResource;
use App\Models\EducationArticle;

class EducationArticleController extends Controller
{
    public function store(StoreEducationArticleRequest $request)
    {
        $data = $request->validated();
        if (!isset($data['id'])) {
            $data['id'] = (string) \Illuminate\Support\Str::uuid();
        }
        $article = EducationArticle::create($data);
        return response()->json([
            'success' => true,
            'message' => 'Education article created successfully.',
            'data' => new EducationArticleResource($article),
        ], 201);
    }

    public function update(UpdateEducationArticleRequest $request, $id)
    {
        $article = EducationArticle::find($id);
        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Education article not found.',
                'data' => null,
            ], 404);
        }
        $article->update($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Education article updated successfully.',
            'data' => new EducationArticleResource($article),
        ]);
    }

    public function destroy($id)
    {
        $article = EducationArticle::find($id);
        if (!$article || $article->deleted_at) {
            return response()->json([
                'success' => false,
                'message' => 'Education article not found or already deleted.',
                'data' => null,
            ], 404);
        }
        $article->delete();
        return response()->json([
            'success' => true,
            'message' => 'Education article deleted successfully.',
            'data' => null,
        ]);
    }
}
