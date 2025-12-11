<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
{
    return [
        'name' => $this->faker->word(),
        'sku' => strtoupper(\Illuminate\Support\Str::random(8)),
        'price' => $this->faker->randomFloat(2, 10, 500),
        'stock' => $this->faker->numberBetween(1, 100),
        'description' => $this->faker->sentence(),
        'image' => 'https://picsum.photos/300/300?random=' . $this->faker->unique()->numberBetween(1, 100),

    ];
}

}
