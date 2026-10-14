<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductApiController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $priorityProductIds = [];

        if ($user && $user->role === 'mr') {
            $today = now()->toDateString();
            $hierarchyRsmId = null;

            if ($user->role === 'mr' && $user->manager_id) {
                $hierarchyRsmId = User::whereKey($user->manager_id)->value('manager_id');
            }

            $priorityProductIds = DB::table('campaign_products')
                ->join('managerial_campaigns', 'managerial_campaigns.id', '=', 'campaign_products.campaign_id')
                ->where('managerial_campaigns.status', 'approved')
                ->where('managerial_campaigns.start_date', '<=', $today)
                ->where('managerial_campaigns.end_date', '>=', $today)
                ->where(function ($q) use ($user, $hierarchyRsmId) {
                    if (! empty($user->district)) {
                        $q->where('managerial_campaigns.district_name', $user->district);
                    }

                    if ($user->role === 'mr' && $hierarchyRsmId) {
                        $q->orWhere(function ($regionalQuery) use ($hierarchyRsmId) {
                            $regionalQuery->where('managerial_campaigns.district_name', 'Regional')
                                ->where('managerial_campaigns.created_by', $hierarchyRsmId);
                        });
                    }
                })
                ->pluck('campaign_products.product_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->toArray();
        }

        $products = Product::query()
            ->select(['id', 'name', 'category'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved',
            'data' => $products->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'is_priority' => in_array((int) $product->id, $priorityProductIds, true),
            ])->values()->all(),
        ]);
    }
}
