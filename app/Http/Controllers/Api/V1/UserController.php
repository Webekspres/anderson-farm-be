<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Fitur Paginate dan Search sesuai OpenAPI contract
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('username', 'LIKE', "%{$search}%")
                  ->orWhere('full_name', 'LIKE', "%{$search}%");
        }

        $perPage = $request->get('per_page', 10);
        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar user berhasil diambil.',
            'data'    => [
                'total'        => $users->total(),
                'per_page'     => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'users'        => $users->items(),
            ]
        ], 200);
    }
}
