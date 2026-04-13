<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckController extends Controller
{
    public function index(): JsonResponse
    {
        // Mengembalikan response JSON standar
        return response()->json([
            'success' => true,
            'message' => 'API Anderson Farm berjalan dengan baik!',
            'data'    => [
                'server_time' => now()->toDateTimeString(),
                'version'     => '1.0.0'
            ]
        ], 200);
    }
}
