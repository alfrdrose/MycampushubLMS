<?php

namespace Database\Seeders;

use App\Models\TeacherModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeacherModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TeacherModel::factory()->count(10)->create();
    }
}
