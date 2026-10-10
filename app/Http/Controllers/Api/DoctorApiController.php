<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterCustomer;
use Illuminate\Http\JsonResponse;

class DoctorApiController extends Controller
{
    /**
     * Mengambil seluruh dokter dari Master Customer List (MCL).
     */
    public function index(): JsonResponse
    {
        $doctors = MasterCustomer::orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar dokter MCL berhasil diambil',
            'data' => $doctors->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'specialization' => $doc->specialization,
                    'address' => $doc->address,
                    'phone' => $doc->phone,
                ];
            }),
        ]);
    }
}
