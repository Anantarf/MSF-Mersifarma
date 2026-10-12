<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterCustomer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorApiController extends Controller
{
    /**
     * Mengambil daftar dokter dari Master Customer List (MCL).
     * Secara default dibatasi ke distrik MR/DM, tetapi mendukung mode global seluruh MCL (?all=1 atau ?scope=all).
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $includeAll = $request->boolean('all') || $request->query('scope') === 'all';

        $doctors = MasterCustomer::query()
            ->select(['id', 'name', 'specialization', 'district', 'address', 'phone'])
            ->when(! $includeAll && $user?->district && in_array($user?->role, ['mr', 'dm'], true), function ($query) use ($user) {
                $query->where('district', $user->district);
            })
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar dokter MCL berhasil diambil',
            'data' => $doctors->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'specialization' => $doc->specialization,
                    'district' => $doc->district,
                    'address' => $doc->address,
                    'phone' => $doc->phone,
                ];
            }),
        ]);
    }
}
