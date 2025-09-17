<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::with(['variants', 'combinations'])->get()->map(function ($product) {
            return $this->formatProduct($product);
        });

        return response()->json($products);
    }

    private function formatProduct(Product $product): array
    {
        $sizes = $product->variants->where('type', 'size')->pluck('value')->unique()->values()->all();
        $colors = $product->variants->where('type', 'color')->pluck('value')->unique()->values()->all();
        $fabrics = $product->variants->where('type', 'fabric')->pluck('value')->unique()->values()->all();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'stock' => $product->stock,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
            'sizes' => $sizes,
            'colors' => $colors,
            'fabrics' => $fabrics,
            'combinations' => $product->combinations->map(function ($c) {
                return [
                    'id' => $c->id,
                    'sku' => $c->sku,
                    'price' => $c->price,
                    'stock' => $c->stock,
                    'options' => $c->options,
                ];
            })->values(),
        ];
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['variants', 'combinations']);
        return response()->json($this->formatProduct($product));
    }

    // List combinations for a product
    public function combinations(Product $product): JsonResponse
    {
        $product->load('combinations');
        return response()->json($product->combinations);
    }

    // Find a single combination by options: ?size=&color=&fabric=
    public function findCombination(Request $request, Product $product): JsonResponse
    {
        $size = $request->query('size');
        $color = $request->query('color');
        $fabric = $request->query('fabric');

        $query = $product->combinations()->newQuery();

        $query->whereJsonContains('options->size', $size);
        $query->whereJsonContains('options->color', $color);
        $query->whereJsonContains('options->fabric', $fabric);

        $combination = $query->first();

        if (!$combination) {
            return response()->json(null, 404);
        }

        return response()->json($combination);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'variants' => 'array',
        ]);

        $product = Product::create($data);

        if (!empty($data['variants'])) {
            foreach ($data['variants'] as $v) {
                $product->variants()->create($v);
            }
            // Optionally create combinations later; client can POST combinations separately
        }

        return response()->json($product->load('variants'), 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'stock' => 'sometimes|integer',
            'variants' => 'array',
        ]);

        $product->update($data);

        if (isset($data['variants'])) {
            // Simple approach: delete existing and recreate
            $product->variants()->delete();
            foreach ($data['variants'] as $v) {
                $product->variants()->create($v);
            }
            // Note: combinations are unchanged here - client can update combinations explicitly
        }

        return response()->json($product->load('variants'));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        return response()->json(null, 204);
    }
}

