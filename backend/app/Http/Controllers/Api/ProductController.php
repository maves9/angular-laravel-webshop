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
        $variants = $product->variants->load('variantType');
        $sizes = $variants->filter(fn($v) => $v->variantType && $v->variantType->name === 'size')->pluck('value')->unique()->values()->all();
        $colors = $variants->filter(fn($v) => $v->variantType && $v->variantType->name === 'color')->pluck('value')->unique()->values()->all();
        $fabrics = $variants->filter(fn($v) => $v->variantType && $v->variantType->name === 'fabric')->pluck('value')->unique()->values()->all();

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

    public function combinations(Product $product): JsonResponse
    {
        $product->load('combinations');
        return response()->json($product->combinations);
    }

    public function findCombination(Request $request, Product $product): JsonResponse
    {
        $params = array_filter($request->query(), function($v) { return $v !== null && $v !== ''; });
        $query = $product->combinations()->newQuery();

        if (empty($params)) {
            return response()->json(['message' => 'No variant query parameters provided'], 400);
        }

        foreach ($params as $name => $value) {
            $query->whereJsonContains("options->{$name}", $value);
        }

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
            $product->variants()->delete();
            foreach ($data['variants'] as $v) {
                $product->variants()->create($v);
            }
        }

        return response()->json($product->load('variants'));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        return response()->json(null, 204);
    }
}

