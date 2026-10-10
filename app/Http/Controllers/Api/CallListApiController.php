<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCallListRequest;
use App\Http\Requests\UpdateCallListRequest;
use App\Http\Requests\ReviewCallListRequest;
use App\Models\CallList;
use App\Services\CallListService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CallListApiController extends Controller
{
    protected CallListService $callListService;

    public function __construct(CallListService $callListService)
    {
        $this->callListService = $callListService;
    }

    /**
     * GET /api/call-lists
     * Mengambil daftar Call List milik sendiri atau antrean approval untuk manager.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $mode = $request->query('mode', 'owner'); // 'owner' atau 'review'

        $query = CallList::with(['owner', 'assignedApprover', 'doctors']);

        if ($mode === 'review') {
            // Manager melihat antrean pengajuan yang ditugaskan kepadanya
            $query->where('assigned_approver_id', $user->id);
            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            } else {
                $query->where('status', 'pending_approval');
            }
        } else {
            // Default: user melihat Call List miliknya sendiri
            $query->where('owner_id', $user->id);
            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }
        }

        if ($request->filled('period_month')) {
            $normalizedDate = Carbon::createFromFormat('Y-m', $request->query('period_month'))->startOfMonth()->format('Y-m-d');
            $query->where('period_month', $normalizedDate);
        }

        $callLists = $query->orderBy('period_month', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Call Lists retrieved',
            'data' => $callLists->map(function ($cl) {
                return [
                    'id' => $cl->id,
                    'owner_id' => $cl->owner_id,
                    'owner_name' => $cl->owner ? $cl->owner->name : null,
                    'period_month' => Carbon::parse($cl->period_month)->format('Y-m'),
                    'status' => $cl->status,
                    'doctor_count' => $cl->doctors->count(),
                    'assigned_approver_id' => $cl->assigned_approver_id,
                    'assigned_approver_name' => $cl->assignedApprover ? $cl->assignedApprover->name : null,
                    'submitted_at' => $cl->submitted_at ? $cl->submitted_at->toIso8601String() : null,
                    'approved_at' => $cl->approved_at ? $cl->approved_at->toIso8601String() : null,
                    'created_at' => $cl->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * GET /api/call-lists/{id}
     * Mengambil detail Call List beserta daftar dokter dan log histori approval.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        $callList = CallList::with(['owner', 'assignedApprover', 'doctors', 'approvalLogs.actor'])
            ->findOrFail($id);

        // Hanya pemilik atau manager yang ditugaskan yang boleh melihat detail
        if ($callList->owner_id !== $user->id && $callList->assigned_approver_id !== $user->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki izin untuk melihat detail Call List ini.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Call List retrieved',
            'data' => [
                'id' => $callList->id,
                'owner_id' => $callList->owner_id,
                'owner_name' => $callList->owner ? $callList->owner->name : null,
                'period_month' => Carbon::parse($callList->period_month)->format('Y-m'),
                'status' => $callList->status,
                'assigned_approver_id' => $callList->assigned_approver_id,
                'assigned_approver_name' => $callList->assignedApprover ? $callList->assignedApprover->name : null,
                'submitted_at' => $callList->submitted_at ? $callList->submitted_at->toIso8601String() : null,
                'approved_at' => $callList->approved_at ? $callList->approved_at->toIso8601String() : null,
                'created_at' => $callList->created_at->toIso8601String(),
                'doctors' => $callList->doctors->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'name' => $doc->name,
                        'specialization' => $doc->specialization,
                        'address' => $doc->address,
                        'phone' => $doc->phone,
                    ];
                }),
                'approval_history' => $callList->approvalLogs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'actor_id' => $log->actor_id,
                        'actor_name' => $log->actor ? $log->actor->name : null,
                        'event' => $log->event,
                        'event_origin' => $log->event_origin,
                        'from_status' => $log->from_status,
                        'to_status' => $log->to_status,
                        'reason' => $log->reason,
                        'created_at' => $log->created_at ? $log->created_at->toIso8601String() : null,
                    ];
                }),
            ],
        ]);
    }

    /**
     * POST /api/call-lists
     * Membuat draft Call List bulanan baru.
     */
    public function store(StoreCallListRequest $request): JsonResponse
    {
        $callList = $this->callListService->createDraft(
            Auth::user(),
            $request->input('period_month'),
            $request->input('doctor_ids')
        );

        return response()->json([
            'success' => true,
            'message' => 'Call List draft created',
            'data' => [
                'id' => $callList->id,
                'period_month' => Carbon::parse($callList->period_month)->format('Y-m'),
                'status' => $callList->status,
                'doctor_ids' => $callList->doctors->pluck('id')->toArray(),
            ],
        ], 201);
    }

    /**
     * PUT /api/call-lists/{id}
     * Memperbarui daftar dokter sasaran pada draft Call List.
     */
    public function update(UpdateCallListRequest $request, int $id): JsonResponse
    {
        $callList = CallList::findOrFail($id);

        $updatedList = $this->callListService->updateDraft(
            $callList,
            Auth::user(),
            $request->input('doctor_ids')
        );

        return response()->json([
            'success' => true,
            'message' => 'Call List draft updated',
            'data' => [
                'id' => $updatedList->id,
                'period_month' => Carbon::parse($updatedList->period_month)->format('Y-m'),
                'status' => $updatedList->status,
                'doctor_ids' => $updatedList->doctors->pluck('id')->toArray(),
            ],
        ]);
    }

    /**
     * POST /api/call-lists/{id}/submit
     * Mengajukan Call List draft ke atasan langsung untuk persetujuan.
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $callList = CallList::findOrFail($id);

        $submittedList = $this->callListService->submit($callList, Auth::user());

        return response()->json([
            'success' => true,
            'message' => 'Call List submitted for approval',
            'data' => [
                'id' => $submittedList->id,
                'status' => $submittedList->status,
                'assigned_approver_id' => $submittedList->assigned_approver_id,
                'submitted_at' => $submittedList->submitted_at ? $submittedList->submitted_at->toIso8601String() : null,
            ],
        ]);
    }

    /**
     * PATCH /api/call-lists/{id}/approve
     * Memproses persetujuan (Approve / Reject) oleh direct manager.
     */
    public function approve(ReviewCallListRequest $request, int $id): JsonResponse
    {
        $callList = CallList::with('owner')->findOrFail($id);

        $decision = $request->input('status'); // 'approved' atau 'rejected'
        $reason = $request->input('reason');

        $reviewedList = $this->callListService->review(
            $callList,
            Auth::user(),
            $decision,
            $reason
        );

        if ($decision === 'approved') {
            return response()->json([
                'success' => true,
                'message' => 'Call List approved',
                'data' => [
                    'id' => $reviewedList->id,
                    'status' => 'approved',
                    'decision' => 'approved',
                    'approved_at' => $reviewedList->approved_at ? $reviewedList->approved_at->toIso8601String() : null,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Call List rejected and returned to draft',
            'data' => [
                'id' => $reviewedList->id,
                'status' => 'draft',
                'decision' => 'rejected',
                'reason' => $reason,
            ],
        ]);
    }
}
