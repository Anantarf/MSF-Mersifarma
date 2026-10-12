<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductApiController extends Controller
{
    public function index(): JsonResponse
    {
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
            ])->values()->all(),
        ]);
    }
}
