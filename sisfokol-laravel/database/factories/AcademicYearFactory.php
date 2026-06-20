<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = \App\Models\AcademicYear::class;

    public function definition(): array
    {
        return [
            'name' => fake()->year().'/'.(fake()->year() + 1),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'is_active' => true,
        ];
    }
}
