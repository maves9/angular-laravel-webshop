<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ProductVariant;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition()
    {
        return [
            'type' => $this->faker->randomElement(['size', 'color', 'fabric']),
            'value' => $this->faker->word(),
            // Provide translations for English and Danish; factories should create unique-ish texts
            'descriptions' => [
                'en' => $this->faker->unique()->sentence(6),
                'da' => $this->faker->unique()->sentence(6) . ' (DA)',
            ],
        ];
    }
}
