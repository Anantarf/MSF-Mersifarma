<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCallActualRequest;
use App\Services\CallActualService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CallActualApiController extends Controller
{
    public function __construct(private readonly CallActualService $callActualService) {}

    public function store(StoreCallActualRequest $request): JsonResponse
    {
        $actual = $this->callActualService->recordDirect(Auth::user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Call Actual completed',
            'data' => [
                'id' => $actual->id,
                'doctor_id' => $actual->doctor_id,
                'doctor_name' => $actual->doctor?->name,
                'plan_id' => $actual->plan_id,
                'visit_date' => Carbon::parse($actual->visit_date)->format('Y-m-d'),
                'visit_type' => $actual->visit_type,
                'status' => 'completed',
                'products' => $actual->products->pluck('id')->values()->all(),
            ],
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $actuals = $this->callActualService->getActuals(
            Auth::user(),
            $request->query('date'),
            $request->filled('doctor_id') ? (int) $request->query('doctor_id') : null,
            $request->filled('mr_id') ? (int) $request->query('mr_id') : null,
            $request->query('month'),
            $request->query('visit_type'),
            $request->query('district')
        );

        return response()->json([
            'success' => true,
            'message' => 'Call Actuals retrieved',
            'data' => $actuals->map(fn ($actual) => [
                'id' => $actual->id,
                'mr_id' => $actual->mr_id,
                'mr_name' => $actual->mr?->name,
                'mr_district' => $actual->mr?->district,
                'doctor_id' => $actual->doctor_id,
                'doctor_name' => $actual->doctor?->name,
                'doctor_specialization' => $actual->doctor?->specialization,
                'doctor_address' => $actual->doctor?->address,
                'plan_id' => $actual->plan_id,
                'visit_date' => Carbon::parse($actual->visit_date)->format('Y-m-d'),
                'check_in' => $actual->check_in_at?->toIso8601String(),
                'check_out' => $actual->check_out_at?->toIso8601String(),
                'check_in_at' => $actual->check_in_at?->toIso8601String(),
                'check_out_at' => $actual->check_out_at?->toIso8601String(),
                'visit_type' => $actual->visit_type,
                'photo_url' => $actual->photo_url,
                'signature_url' => $actual->signature_url,
                'visit_reason' => $actual->visit_reason,
                'deviation_reason' => $actual->deviation_reason,
                'products' => $actual->products->map(fn ($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category,
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }
}
