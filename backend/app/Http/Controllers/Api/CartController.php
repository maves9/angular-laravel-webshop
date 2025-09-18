<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Models\Product;

class CartController extends Controller
{
    // Get current cart from session
    public function index(Request $request): JsonResponse
    {
        try {
            $cart = session('cart');
            return response()->json($cart);
        } catch (Throwable $e) {
            Log::error('Cart index failed: '.$e->getMessage(), ['exception' => $e]);
            return response()->json(["message" => "Failed to fetch cart"], 500);
        }
    }

    // Add item to cart: { product_id, quantity = 1, options = [] }
    public function add(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'nullable|integer|min:1',
                'options' => 'nullable|array'
            ]);

            $product = Product::findOrFail($data['product_id']);

            $quantity = $data['quantity'] ?? 1;
            $options = $data['options'] ?? [];

            $cart = session('cart');

            // Simple cart item shape: product_id, quantity, options
            $cart[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $quantity,
                'options' => $options,
            ];

            $request->session()->put('cart', $cart);

            session(['cart' => $cart]);

            return response()->json($cart, 201);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            // Return validation errors as 422
            return response()->json(["message" => "Validation failed", "errors" => $ve->errors()], 422);
        } catch (Throwable $e) {
            Log::error('Cart add failed: '.$e->getMessage(), ['exception' => $e]);
            $payload = ["message" => "Failed to add item to cart"];
            if (config('app.debug')) {
                $payload['error'] = $e->getMessage();
            }
            return response()->json($payload, 500);
        }
    }

    // Remove item by index
    public function remove(Request $request, $index): JsonResponse
    {
        try {
            $cart = $request->session()->get('cart', []);
            if (!isset($cart[$index])) {
                return response()->json(['message' => 'Item not found'], 404);
            }
            array_splice($cart, $index, 1);
            $request->session()->put('cart', $cart);
            return response()->json($cart);
        } catch (Throwable $e) {
            Log::error('Cart remove failed: '.$e->getMessage(), ['exception' => $e]);
            return response()->json(["message" => "Failed to remove item"], 500);
        }
    }

    // Clear cart
    public function clear(Request $request): JsonResponse
    {
        try {
            $request->session()->forget('cart');
            return response()->json([], 204);
        } catch (Throwable $e) {
            Log::error('Cart clear failed: '.$e->getMessage(), ['exception' => $e]);
            return response()->json(["message" => "Failed to clear cart"], 500);
        }
    }
}