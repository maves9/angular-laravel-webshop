<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;

class CartTest extends TestCase
{
    public function test_add_item_to_cart_and_view()
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
            'options' => ['color' => 'red']
        ]);

        $response->assertStatus(201);
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals($product->id, $data[0]['product_id']);

        $view = $this->getJson('/api/cart');
        $view->assertStatus(200);
        $this->assertCount(1, $view->json());
    }

    public function test_remove_and_clear_cart()
    {
        $product = Product::factory()->create();
        $this->postJson('/api/cart/add', ['product_id' => $product->id]);
        $this->postJson('/api/cart/add', ['product_id' => $product->id]);

        $view = $this->getJson('/api/cart');
        $this->assertCount(2, $view->json());

        $rem = $this->deleteJson('/api/cart/0');
        $rem->assertStatus(200);
        $this->assertCount(1, $rem->json());

        $clear = $this->deleteJson('/api/cart/clear');
        $clear->assertStatus(204);

        $view2 = $this->getJson('/api/cart');
        $this->assertEquals([], $view2->json());
    }

    public function test_add_item_validation_error()
    {
        $resp = $this->postJson('/api/cart/add', ['quantity' => 2]);
        $resp->assertStatus(422);
        $this->assertArrayHasKey('errors', $resp->json());
    }
}
