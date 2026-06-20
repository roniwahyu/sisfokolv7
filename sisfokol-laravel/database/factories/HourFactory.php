<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hour>
 */
class HourFactory extends Factory
{
    protected $model = \App\Models\Hour::class;

    public function definition(): array
    {
        return [
            'name' => 'Jam ke-'.fake()->randomDigitNotNull(),
            'start_time' => '07:00:00',
            'end_time' => '07:45:00',
            'order' => fake()->randomDigitNotNull(),
        ];
    }
}
