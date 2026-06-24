<?php

namespace Database\Factories;

use App\Models\SubjectModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubjectModel>
 */
class SubjectModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_name' => $this->faker->randomElement([
                'Mathematics',
                'English',
                'Science',
                'Social Studies',
                'Reading',
                'Writing',
                'Art',
                'Music',
                'Physical Education',
            ]),
            'grade_level'  => $this->faker->numberBetween(1, 12),
        ];
    }
}
