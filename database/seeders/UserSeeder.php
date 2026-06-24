<?php

namespace Database\Seeders;

use App\Models\StudentModel;
use App\Models\TeacherModel;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test user with admin role
        $admin = User::firstWhere('email', 'admin@email.com');
        if (!$admin) {
            $admin = User::factory()->create([
                'email' => 'admin@email.com',
                'name' => 'Admin User',
            ]);
        }
        $admin->assignRole('admin');

        // Create additional users with student role
        $student = StudentModel::firstWhere('email', 'student@gmail.com');
        if (!$student) {
            $student = StudentModel::factory()->create([
                'email' => 'student@gmail.com',
                'first_name' => 'Student',
                'last_name' => 'User',
            ]);
        }

        StudentModel::factory()->count(99)->create()
            ->add($student)
            ->each(function ($student) {
                $user = User::factory()->unverified($student->email !== 'student@gmail.com')->create([
                    'email' => $student->email,
                    'name' => $student->first_name . ' ' . $student->last_name,
                ])->assignRole('student');
                $student->update(['user_id' => $user->id]);
            });

        // Create additional users with teacher role
        $teacher = TeacherModel::firstWhere('email', 'teacher@gmail.com');
        if (!$teacher) {
            $teacher = TeacherModel::factory()->create([
                'email' => 'teacher@gmail.com',
                'first_name' => 'Teacher',
                'last_name' => 'User',
            ]);
        }

        TeacherModel::factory()->count(49)->create()
            ->add($teacher)
            ->each(function ($teacher) {
                $user = User::factory()->unverified($teacher->email !== 'teacher@gmail.com')->create([
                    'email' => $teacher->email,
                    'name' => $teacher->first_name . ' ' . $teacher->last_name,
                ])->assignRole('teacher');
                $teacher->update(['user_id' => $user->id]);
            });
    }
}
