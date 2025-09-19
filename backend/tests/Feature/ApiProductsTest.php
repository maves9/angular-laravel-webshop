<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;

class ApiProductsTest extends TestCase
{
    public function test_products_index_returns_ok()
    {
        // seed a few products so the API has data to return
        Product::factory()->count(3)->create();

        $response = $this->get('/api/products');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertCount(3, $data);
    }

    public function test_artisan_list_command_runs()
    {
        $exit = $this->artisan('list')->run();

        $this->assertTrue($exit === 0 || $exit === null);
    }
}
