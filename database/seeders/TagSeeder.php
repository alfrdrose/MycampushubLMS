<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            ['name' => 'Counting', 'category' => 'Mathematics'],
            ['name' => 'Addition', 'category' => 'Mathematics'],
            ['name' => 'Fractions', 'category' => 'Mathematics'],
            ['name' => 'Algebra', 'category' => 'Mathematics'],
            ['name' => 'Geometry', 'category' => 'Mathematics'],
            ['name' => 'Statistics', 'category' => 'Mathematics'],
            ['name' => 'Phonics', 'category' => 'English'],
            ['name' => 'Reading Comprehension', 'category' => 'English'],
            ['name' => 'Creative Writing', 'category' => 'English'],
            ['name' => 'Literary Analysis', 'category' => 'English'],
            ['name' => 'Plants', 'category' => 'Science'],
            ['name' => 'Weather', 'category' => 'Science'],
            ['name' => 'Cell Structure', 'category' => 'Biology'],
            ['name' => 'Motion', 'category' => 'Physics'],
            ['name' => 'Periodic Table', 'category' => 'Chemistry'],
            ['name' => 'Community', 'category' => 'Social Studies'],
            ['name' => 'Maps', 'category' => 'Geography'],
            ['name' => 'World War II', 'category' => 'History'],
            ['name' => 'Climate', 'category' => 'Geography'],
            ['name' => 'Constitution', 'category' => 'Civics'],
            ['name' => 'Programming Basics', 'category' => 'Computer Science'],
            ['name' => 'Keyboarding', 'category' => 'ICT'],
            ['name' => 'Supply and Demand', 'category' => 'Economics'],
            ['name' => 'Colors', 'category' => 'Art'],
            ['name' => 'Perspective', 'category' => 'Art'],
            ['name' => 'Rhythm', 'category' => 'Music'],
            ['name' => 'Harmony', 'category' => 'Music'],
            ['name' => 'Movement', 'category' => 'PE'],
            ['name' => 'Hygiene', 'category' => 'Health'],
            ['name' => 'Nutrition', 'category' => 'Health'],
        ];

        foreach ($tags as $tag) {
            $slug = Str::slug($tag['category'] . ' ' . $tag['name']);
            Tag::updateOrCreate(
                ['slug' => $slug],
                ['name' => $tag['name'], 'category' => $tag['category']]
            );
        }
    }
}
