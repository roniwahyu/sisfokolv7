<?php

namespace Database\Factories;

use App\Models\Hour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    protected $model = \App\Models\TimeSlot::class;

    public function definition(): array
    {
        return [
            'name' => 'Jam ke-'.fake()->randomDigitNotNull(),
            'hour_id' => Hour::factory(),
            'start_time' => '07:00:00',
            'end_time' => '07:45:00',
            'order' => fake()->randomDigitNotNull(),
        ];
    }
}
