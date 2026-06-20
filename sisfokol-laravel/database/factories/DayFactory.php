<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Day>
 */
class DayFactory extends Factory
{
    protected $model = \App\Models\Day::class;

    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numberBetween(0, 6),
            'name' => fake()->randomElement(['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']),
        ];
    }
}
