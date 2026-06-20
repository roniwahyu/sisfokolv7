<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Classroom>
 */
class ClassroomFactory extends Factory
{
    protected $model = \App\Models\Classroom::class;

    public function definition(): array
    {
        return [
            'name' => 'X IPA '.fake()->randomDigitNotNull(),
            'level' => 'X',
            'major' => 'IPA',
            'capacity' => 30,
        ];
    }
}
