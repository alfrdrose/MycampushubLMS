<?php

namespace Database\Seeders;

use App\Models\SectionModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SectionModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $count = fake()->numberBetween(1, 3);
            SectionModel::factory()
                ->count($count)
                ->sequence(...array_map(fn($rank) => ['section_rank' => $rank], range(1, $count)))
                ->create([
                    'grade_level' => $i + 1,
                    'start_year' => 2025,
                    'end_year' => 2026,
                ]);
        }
    }
}
