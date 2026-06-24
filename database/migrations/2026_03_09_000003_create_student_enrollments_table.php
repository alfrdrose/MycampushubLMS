<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                ->constrained('student_models')
                ->cascadeOnDelete();
            $table->foreignId('school_year_id')
                ->constrained('school_years')
                ->cascadeOnDelete();
            $table->foreignId('section_id')
                ->nullable()
                ->constrained('section_models')
                ->nullOnDelete();
            $table->unsignedTinyInteger('grade_level')->nullable();
            $table->string('status', 32)->default('active');
            $table->boolean('is_current')->default(false);
            $table->foreignId('previous_enrollment_id')
                ->nullable()
                ->constrained('student_enrollments')
                ->nullOnDelete();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'school_year_id']);
            $table->index(['school_year_id', 'status']);
        });

        if (! Schema::hasTable('student_models') || ! Schema::hasTable('section_models')) {
            return;
        }

        $sections = DB::table('section_models')
            ->select('id', 'grade_level', 'school_year_id')
            ->whereNotNull('school_year_id')
            ->get()
            ->keyBy('id');

        if ($sections->isEmpty()) {
            return;
        }

        $now = now();

        DB::table('student_models')
            ->select('id', 'grade', 'section_id')
            ->whereNotNull('section_id')
            ->orderBy('id')
            ->get()
            ->each(function ($student) use ($sections, $now): void {
                $section = $sections->get($student->section_id);
                if (! $section || ! $section->school_year_id) {
                    return;
                }

                $gradeLevel = filter_var($student->grade, FILTER_VALIDATE_INT);
                if ($gradeLevel === false) {
                    $gradeLevel = (int) $section->grade_level;
                }

                DB::table('student_enrollments')->updateOrInsert(
                    [
                        'student_id' => $student->id,
                        'school_year_id' => $section->school_year_id,
                    ],
                    [
                        'section_id' => $student->section_id,
                        'grade_level' => $gradeLevel ?: null,
                        'status' => 'active',
                        'is_current' => true,
                        'previous_enrollment_id' => null,
                        'ended_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
