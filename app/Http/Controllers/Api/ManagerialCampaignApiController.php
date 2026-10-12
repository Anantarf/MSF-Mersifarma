<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\ManagerialCampaign;
use App\Models\User;
use App\Services\ManagerialCampaignService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerialCampaignApiController extends Controller
{
    protected ManagerialCampaignService $campaignService;

    public function __construct(ManagerialCampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    /**
     * GET /api/managerial-campaigns
     * Mengambil daftar program kampanye rekomendasi.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $query = ManagerialCampaign::with(['creator', 'approver', 'products', 'doctors']);

        if ($user->role === 'mr') {
            // MR hanya melihat kampanye aktif di distriknya
            $query->active();
            if ($user->district) {
                $query->forDistrict($user->district);
            }
        } elseif ($user->role === 'dm') {
            // DM melihat kampanye di distriknya atau yang dia buat
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id);
                if ($user->district) {
                    $q->orWhere('district_name', $user->district);
                }
            });
        } elseif ($user->role === 'rsm') {
            // RSM melihat kampanye yang dibuat bawahannya (DM) atau yang diajukan kepadanya
            $subordinateDmIds = User::where('manager_id', $user->id)->pluck('id')->toArray();
            $query->whereIn('created_by', array_merge([$user->id], $subordinateDmIds));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $campaigns = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Managerial campaigns retrieved successfully.',
            'data' => $campaigns->map(function ($camp) {
                return [
                    'id' => $camp->id,
                    'title' => $camp->title,
                    'district_name' => $camp->district_name,
                    'specialization' => $camp->specialization,
                    'duration_months' => $camp->duration_months,
                    'start_date' => $camp->start_date ? Carbon::parse($camp->start_date)->toDateString() : null,
                    'end_date' => $camp->end_date ? Carbon::parse($camp->end_date)->toDateString() : null,
                    'status' => $camp->status,
                    'rejection_reason' => $camp->rejection_reason,
                    'created_by' => $camp->created_by,
                    'creator_name' => $camp->creator ? $camp->creator->name : null,
                    'approved_by' => $camp->approved_by,
                    'approver_name' => $camp->approver ? $camp->approver->name : null,
                    'product_count' => $camp->products->count(),
                    'products' => $camp->products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
                    'doctor_count' => $camp->doctors->count(),
                    'doctors' => $camp->doctors->map(fn ($d) => [
                        'id' => $d->id,
                        'name' => $d->name,
                        'specialization' => $d->specialization,
                        'district' => $d->district,
                    ]),
                    'created_at' => $camp->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * POST /api/managerial-campaigns
     * Membuat kartu program rekomendasi baru oleh DM/RSM.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'district_name' => 'nullable|string|max:100',
            'specialization' => 'nullable|string|max:100',
            'duration_months' => 'required|integer|between:1,3',
            'product_ids' => 'required|array|min:1|max:5',
            'product_ids.*' => 'integer|exists:products,id',
            'doctor_ids' => 'required|array|min:1',
            'doctor_ids.*' => 'integer|exists:master_customers,id',
            'notes' => 'nullable|string',
            'start_date' => 'nullable|date',
        ]);

        $campaign = $this->campaignService->createCampaign($user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Program rekomendasi distrik berhasil diajukan.',
            'data' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'district_name' => $campaign->district_name,
                'status' => $campaign->status,
                'start_date' => $campaign->start_date ? Carbon::parse($campaign->start_date)->toDateString() : null,
                'end_date' => $campaign->end_date ? Carbon::parse($campaign->end_date)->toDateString() : null,
                'doctor_count' => $campaign->doctors->count(),
                'product_count' => $campaign->products->count(),
            ],
        ], 201);
    }

    /**
     * GET /api/managerial-campaigns/{id}
     * Menampilkan detail program kampanye beserta live progress.
     */
    public function show(int $id): JsonResponse
    {
        $campaign = ManagerialCampaign::with(['creator', 'approver', 'products', 'doctors'])->find($id);

        if (! $campaign) {
            throw ApiException::notFound('CAMPAIGN_NOT_FOUND', 'Program rekomendasi tidak ditemukan.');
        }

        $progress = $this->campaignService->getCampaignProgress($campaign);

        return response()->json([
            'success' => true,
            'message' => 'Detail program rekomendasi berhasil dimuat.',
            'data' => array_merge($progress, [
                'creator_name' => $campaign->creator ? $campaign->creator->name : '-',
                'approver_name' => $campaign->approver ? $campaign->approver->name : '-',
                'rejection_reason' => $campaign->rejection_reason,
                'notes' => $campaign->notes,
                'products' => $campaign->products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
            ]),
        ]);
    }

    /**
     * PATCH /api/managerial-campaigns/{id}/review
     * Menyetujui atau menolak program rekomendasi.
     */
    public function review(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $campaign = ManagerialCampaign::with(['creator'])->find($id);
        if (! $campaign) {
            throw ApiException::notFound('CAMPAIGN_NOT_FOUND', 'Program rekomendasi tidak ditemukan.');
        }

        $validated = $request->validate([
            'decision' => 'required|string|in:approved,rejected',
            'reason' => 'nullable|string|max:500',
        ]);

        $reviewedCampaign = $this->campaignService->reviewCampaign(
            $campaign,
            $user,
            $validated['decision'],
            $validated['reason'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => $reviewedCampaign->status === 'approved'
                ? 'Program rekomendasi berhasil disetujui.'
                : 'Program rekomendasi berhasil ditolak dengan catatan perbaikan.',
            'data' => [
                'id' => $reviewedCampaign->id,
                'status' => $reviewedCampaign->status,
                'approved_by' => $reviewedCampaign->approved_by,
                'rejection_reason' => $reviewedCampaign->rejection_reason,
            ],
        ]);
    }

    /**
     * GET /api/managerial-campaigns/summary
     * Mengambil Executive Summary untuk RSM dan MM.
     */
    public function summary(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $summary = $this->campaignService->getExecutiveSummary($user);

        return response()->json([
            'success' => true,
            'message' => 'Executive summary berhasil dimuat.',
            'data' => $summary,
        ]);
    }

    /**
     * GET /api/managerial-campaigns/recommendations
     * Mengambil rekomendasi dokter aktif untuk picker MR.
     */
    public function doctorRecommendations(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $district = $request->query('district', $user->district ?? '');

        $recommendations = $this->campaignService->getActiveDoctorRecommendations($district);

        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi dokter berhasil diambil.',
            'data' => $recommendations->values(),
        ]);
    }
}
