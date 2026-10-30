<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection(Product::query()->orderBy('name')->get());
    }

    public function lowStock(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'threshold' => ['sometimes', 'integer', 'min:0', 'max:100000'],
        ]);
        $threshold = (int) ($validated['threshold'] ?? config('inventory.low_stock_threshold'));

        return ProductResource::collection(
            Product::query()
                ->where('stock_on_hand', '<', $threshold)
                ->orderBy('stock_on_hand')
                ->orderBy('name')
                ->get(),
        );
    }
}
