<?php

namespace Database\Seeders;

use App\Models\SubjectModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            'Mathematics',
            'English',
            'Science',
            'Social Studies',
            'Reading',
            'Writing',
            'Art',
            'Music',
            'Physical Education',
        ];

        for ($i = 1; $i <= 12; $i++) {
            SubjectModel::factory()
                ->count(count($subjects))
                ->sequence(...array_map(fn($name) => ['subject_name' => $name], $subjects))
                ->create([
                    'grade_level' => $i,
                ]);
        }
    }
}
