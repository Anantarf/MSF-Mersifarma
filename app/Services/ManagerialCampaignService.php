<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\CallActual;
use App\Models\ManagerialCampaign;
use App\Models\MasterCustomer;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ManagerialCampaignService
{
    /**
     * Membuat kartu program rekomendasi manajerial baru.
     */
    public function createCampaign(User $creator, array $data): ManagerialCampaign
    {
        if (! in_array($creator->role, ['dm', 'rsm', 'mm'])) {
            throw ApiException::forbidden('FORBIDDEN', 'Hanya manajer yang dapat menyusun program rekomendasi.');
        }

        $durationMonths = (int) ($data['duration_months'] ?? 1);
        if ($durationMonths < 1 || $durationMonths > 3) {
            throw ApiException::validation('INVALID_DURATION', 'Durasi program rekomendasi maksimal 3 bulan (1, 2, atau 3 bulan).', [
                'duration_months' => ['Durasi harus antara 1 sampai 3 bulan.'],
            ]);
        }

        $startDate = isset($data['start_date'])
            ? Carbon::parse($data['start_date'])->startOfMonth()
            : Carbon::now()->startOfMonth();

        $endDate = $startDate->copy()->addMonths($durationMonths - 1)->endOfMonth();

        $productIds = $data['product_ids'] ?? [];
        if (empty($productIds) || count($productIds) > 5) {
            throw ApiException::validation('INVALID_PRODUCTS', 'Pilih minimal 1 dan maksimal 5 produk prioritas.', [
                'product_ids' => ['Pilih minimal 1 dan maksimal 5 produk prioritas.'],
            ]);
        }

        $doctorIds = $data['doctor_ids'] ?? [];
        if ($creator->role === 'dm' && empty($doctorIds)) {
            throw ApiException::validation('INVALID_DOCTORS', 'DM wajib memilih minimal 1 dokter target di distriknya.', [
                'doctor_ids' => ['Pilih minimal 1 dokter target di distrik Anda.'],
            ]);
        }

        $districtName = $creator->role === 'dm'
            ? trim($creator->district ?? '')
            : trim($data['district_name'] ?? ($creator->district ?? ''));
        if (empty($districtName)) {
            $districtName = $creator->role === 'rsm' ? 'Regional' : ($creator->district ?? 'Nasional');
        }

        // Validasi produk terdaftar
        $validProductsCount = Product::whereIn('id', $productIds)->count();
        if ($validProductsCount !== count($productIds)) {
            throw ApiException::validation('PRODUCT_NOT_FOUND', 'Terdapat produk yang tidak terdaftar dalam katalog master.', [
                'product_ids' => ['Beberapa produk tidak valid.'],
            ]);
        }

        // Validasi dokter terdaftar dan sesuai distrik jika pembuatnya DM
        if (! empty($doctorIds)) {
            $validDoctors = MasterCustomer::whereIn('id', $doctorIds)->get();
            if ($validDoctors->count() !== count($doctorIds)) {
                throw ApiException::validation('DOCTOR_NOT_FOUND', 'Terdapat dokter yang tidak terdaftar dalam Master Customer List.', [
                    'doctor_ids' => ['Beberapa dokter tidak valid.'],
                ]);
            }

            if ($creator->role === 'dm' && $creator->district) {
                $crossDistrict = $validDoctors->filter(fn ($d) => $d->district !== $creator->district);
                if ($crossDistrict->isNotEmpty()) {
                    throw ApiException::validation('CROSS_DISTRICT_DOCTOR', 'Dokter target harus berada dalam distrik '.$creator->district.'.', [
                        'doctor_ids' => ['Terdapat dokter dari luar wilayah distrik Anda.'],
                    ]);
                }
            }
        }

        $initialStatus = in_array($creator->role, ['rsm', 'mm'], true) ? 'approved' : 'pending_approval';

        return DB::transaction(function () use ($creator, $data, $districtName, $durationMonths, $startDate, $endDate, $productIds, $doctorIds, $initialStatus) {
            $campaign = ManagerialCampaign::create([
                'title' => trim($data['title']),
                'district_name' => $districtName,
                'specialization' => $data['specialization'] ?? null,
                'duration_months' => $durationMonths,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'status' => $initialStatus,
                'created_by' => $creator->id,
                'approved_by' => in_array($creator->role, ['rsm', 'mm'], true) ? $creator->id : null,
                'notes' => $data['notes'] ?? null,
            ]);

            $campaign->products()->sync($productIds);

            if (! empty($doctorIds)) {
                $doctorSyncData = [];
                foreach ($doctorIds as $doctorId) {
                    $doctorSyncData[$doctorId] = ['target_visits' => 1];
                }
                $campaign->doctors()->sync($doctorSyncData);
            }

            return $campaign->load(['creator', 'products', 'doctors']);
        });
    }

    /**
     * Meninjau pengajuan program rekomendasi (Approve / Reject).
     */
    public function reviewCampaign(ManagerialCampaign $campaign, User $reviewer, string $decision, ?string $reason = null): ManagerialCampaign
    {
        $decision = strtolower(trim($decision));
        if (! in_array($decision, ['approved', 'rejected'])) {
            throw ApiException::validation('INVALID_DECISION', 'Keputusan harus berupa approved atau rejected.');
        }

        // Wajib direct manager dari pembuat kampanye (DM diapprove oleh RSM)
        $isDirectManager = (int) $campaign->creator->manager_id === (int) $reviewer->id;

        if (! $isDirectManager) {
            throw ApiException::forbidden('APPROVAL_NOT_ALLOWED', 'Hanya atasan langsung (RSM) yang berhak meninjau program rekomendasi ini.');
        }

        if ($campaign->status !== 'pending_approval') {
            throw ApiException::conflict('CAMPAIGN_NOT_PENDING_APPROVAL', 'Program rekomendasi tidak dalam status menunggu persetujuan.');
        }

        if ($decision === 'rejected' && empty(trim((string) $reason))) {
            throw ApiException::validation('REASON_REQUIRED', 'Alasan penolakan wajib disertakan saat menolak pengajuan.', [
                'reason' => ['Alasan penolakan tidak boleh kosong.'],
            ]);
        }

        $campaign->status = $decision;
        $campaign->approved_by = $decision === 'approved' ? $reviewer->id : null;
        $campaign->rejection_reason = $decision === 'rejected' ? trim($reason) : null;
        $campaign->save();

        return $campaign->fresh(['creator', 'approver', 'products', 'doctors']);
    }

    /**
     * Mengambil kalkulasi progres real-time untuk sebuah kampanye (Monitoring DM).
     */
    public function getCampaignProgress(ManagerialCampaign $campaign): array
    {
        [$progress] = $this->calculateCampaignProgress($campaign);

        return $progress;
    }

    private function calculateCampaignProgress(ManagerialCampaign $campaign): array
    {
        $totalDoctors = $campaign->doctors()->count();
        $doctorIds = $campaign->doctors()->pluck('master_customers.id')->toArray();
        $productIds = $campaign->products()->pluck('products.id')->toArray();

        $startDate = Carbon::parse($campaign->start_date)->toDateString();
        $endDate = Carbon::parse($campaign->end_date)->toDateString();

        // Cari kunjungan valid pada dokter-dokter target dalam rentang tanggal kampanye
        $actuals = CallActual::with(['doctor', 'products'])
            ->whereIn('doctor_id', $doctorIds)
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->get();

        $visitedDoctorIds = $actuals->pluck('doctor_id')->unique()->toArray();
        $visitedCount = count($visitedDoctorIds);
        $reachPercentage = $totalDoctors > 0 ? round(($visitedCount / $totalDoctors) * 100, 1) : 0;
        $totalCalls = $actuals->count();

        // Count each visit once, even when it includes multiple campaign products.
        $priorityProductVisitCount = $actuals
            ->filter(fn ($actual) => $actual->products->contains(fn ($product) => in_array((int) $product->id, $productIds, true)))
            ->count();

        // Track distinct visit IDs for a non-duplicated summary across campaigns.
        $priorityProductVisitIds = DB::table('call_actual_products')
            ->join('call_actuals', 'call_actuals.id', '=', 'call_actual_products.call_actual_id')
            ->whereIn('call_actuals.doctor_id', $doctorIds)
            ->whereBetween('call_actuals.visit_date', [$startDate, $endDate])
            ->whereIn('call_actual_products.product_id', $productIds)
            ->distinct()
            ->pluck('call_actuals.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // Daftar dokter yang belum dikunjungi sama sekali
        $unvisitedDoctors = $campaign->doctors()
            ->whereNotIn('master_customers.id', $visitedDoctorIds)
            ->get(['master_customers.id', 'master_customers.name', 'master_customers.specialization', 'master_customers.district'])
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'specialization' => $doc->specialization,
                    'district' => $doc->district,
                ];
            })
            ->values();

        $progress = [
            'campaign_id' => $campaign->id,
            'title' => $campaign->title,
            'district_name' => $campaign->district_name,
            'specialization' => $campaign->specialization,
            'duration_months' => $campaign->duration_months,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $campaign->status,
            'total_doctors' => $totalDoctors,
            'visited_doctors_count' => $visitedCount,
            'unvisited_doctors_count' => $totalDoctors - $visitedCount,
            'reach_percentage' => $reachPercentage,
            'total_calls' => $totalCalls,
            'focus_detailing_count' => $priorityProductVisitCount,
            'unvisited_doctors' => $unvisitedDoctors,
        ];

        return [$progress, $priorityProductVisitIds];
    }

    /**
     * Mengambil ringkasan eksekutif berjenjang (Executive Summary) untuk RSM dan MM.
     */
    public function getExecutiveSummary(User $viewer): array
    {
        if (! in_array($viewer->role, ['rsm', 'mm'])) {
            throw ApiException::forbidden('FORBIDDEN', 'Hanya RSM atau MM yang dapat mengakses Executive Summary.');
        }

        // Ambil kampanye yang relevan
        $campaignsQuery = ManagerialCampaign::with(['products', 'doctors', 'creator'])
            ->where('status', 'approved');

        if ($viewer->role === 'rsm') {
            // Ambil kampanye yang dibuat oleh DM di bawah RSM ini
            $subordinateDmIds = User::where('manager_id', $viewer->id)->pluck('id')->toArray();
            $campaignsQuery->whereIn('created_by', array_merge([$viewer->id], $subordinateDmIds));
        }

        $campaigns = $campaignsQuery->get();

        $districtSummaries = [];
        $totalTargetNational = 0;
        $totalVisitedNational = 0;
        $priorityProductVisitIds = [];

        foreach ($campaigns as $campaign) {
            [$progress, $campaignPriorityVisitIds] = $this->calculateCampaignProgress($campaign);
            $totalTargetNational += $progress['total_doctors'];
            $totalVisitedNational += $progress['visited_doctors_count'];
            $priorityProductVisitIds = array_merge($priorityProductVisitIds, $campaignPriorityVisitIds);

            // Program arahan makro Regional dari RSM tidak dimasukkan ke dalam breakdown performa distrik
            // karena Regional adalah payung arahan strategis, bukan distrik operasional dengan target dokter langsung.
            if (strcasecmp((string) $campaign->district_name, 'Regional') === 0) {
                continue;
            }

            $districtSummaries[] = [
                'campaign_id' => $campaign->id,
                'title' => $campaign->title,
                'district_name' => $campaign->district_name,
                'dm_name' => $campaign->creator->name ?? '-',
                'creator_role' => $campaign->creator->role ?? 'dm',
                'duration_months' => $campaign->duration_months,
                'total_doctors' => $progress['total_doctors'],
                'visited_doctors_count' => $progress['visited_doctors_count'],
                'reach_percentage' => $progress['reach_percentage'],
                'total_calls' => $progress['total_calls'],
                'focus_detailing_count' => $progress['focus_detailing_count'],
            ];
        }

        $nationalReachPercentage = $totalTargetNational > 0
            ? round(($totalVisitedNational / $totalTargetNational) * 100, 1)
            : 0;

        return [
            'viewer_role' => $viewer->role,
            'summary_scope' => $viewer->role === 'mm' ? 'National Executive Summary' : 'Regional Executive Summary',
            'total_target_doctors' => $totalTargetNational,
            'total_visited_doctors' => $totalVisitedNational,
            'overall_reach_percentage' => $nationalReachPercentage,
            'total_focus_detailing' => count(array_unique($priorityProductVisitIds)),
            'district_breakdown' => $districtSummaries,
        ];
    }

    /**
     * Mengambil daftar ID dokter prioritas yang sedang aktif untuk seorang MR / distrik.
     */
    public function getActiveDoctorRecommendations(string $districtName): Collection
    {
        $activeCampaigns = ManagerialCampaign::active()
            ->forDistrict($districtName)
            ->with(['products', 'doctors'])
            ->get();

        $recommendedDoctors = collect();

        foreach ($activeCampaigns as $campaign) {
            $focusProducts = $campaign->products->pluck('name')->implode(', ');
            foreach ($campaign->doctors as $doc) {
                $recommendedDoctors->put($doc->id, [
                    'doctor_id' => $doc->id,
                    'campaign_id' => $campaign->id,
                    'campaign_title' => $campaign->title,
                    'focus_products' => $focusProducts,
                    'badge' => 'Rekomendasi Distrik',
                ]);
            }
        }

        return $recommendedDoctors;
    }
}
