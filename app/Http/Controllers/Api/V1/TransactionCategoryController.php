<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTransactionCategoryRequest;
use App\Http\Requests\Api\V1\UpdateTransactionCategoryRequest;
use App\Http\Resources\Api\V1\TransactionCategoryResource;
use App\Models\TransactionCategory;
use Illuminate\Http\Request;


class TransactionCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = TransactionCategory::query();
        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($orderBy = $request->query('orderBy')) {
            $direction = $request->query('direction', 'asc');
            $query->orderBy($orderBy, $direction);
        }
        $perPage = $request->query('per_page', 15);
        $useCursor = $request->boolean('cursor', false);
        $paginator = $useCursor
            ? $query->cursorPaginate($perPage)
            : $query->paginate($perPage);
        $meta = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
        return TransactionCategoryResource::collection($paginator)->additional(['meta' => $meta]);
    }

    public function store(StoreTransactionCategoryRequest $request)
    {
        $data = $request->validated();
        $category = TransactionCategory::create($data);
        return new TransactionCategoryResource($category);
    }

    public function show(TransactionCategory $transactionCategory)
    {
        return new TransactionCategoryResource($transactionCategory);
    }

    public function update(UpdateTransactionCategoryRequest $request, TransactionCategory $transactionCategory)
    {
        $transactionCategory->update($request->validated());
        return new TransactionCategoryResource($transactionCategory);
    }

    public function destroy(TransactionCategory $transactionCategory)
    {
        $transactionCategory->delete();
        return response()->json(null, 204);
    }
}
