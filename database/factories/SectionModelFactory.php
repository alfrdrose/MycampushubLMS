<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SectionModel>
 */
class SectionModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->numberBetween(2020, 2030);
        $endYear = $startYear + 1;

        return [
            'section_name' => strtoupper(fake()->randomLetter()) . fake()->randomNumber(2, true),
            'grade_level'  => fake()->numberBetween(1, 12),
            'start_year'   => $startYear,
            'end_year'     => $endYear,
        ];
    }
}
