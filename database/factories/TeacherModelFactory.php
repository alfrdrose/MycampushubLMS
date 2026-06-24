<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeacherModel>
 */
class TeacherModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name'  => $this->faker->lastName(),
            'email'      => $this->faker->unique()->safeEmail(),
            'section'    => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'faculty' => $this->faker->randomElement(['Mathematics', 'Science', 'History', 'English']),
        ];
    }
}
