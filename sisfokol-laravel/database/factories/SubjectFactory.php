<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = \App\Models\Subject::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('????'),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'is_exam' => false,
        ];
    }
}
