<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();

            // Grade value: 1–100
            $table->unsignedTinyInteger('grade');

            // Student FK
            $table->foreignId('student_id')
                ->constrained('student_models')
                ->cascadeOnDelete();

            // Section-Subject FK
            $table->foreignId('section_subject_id')
                ->constrained('section_subject')
                ->cascadeOnDelete();

            // Grading Period
            $table->enum('grading_period', ['1st', '2nd', '3rd', '4th']);

            $table->timestamps();

            // Prevent duplicates per grading period
            $table->unique([
                'student_id',
                'section_subject_id',
                'grading_period'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};

