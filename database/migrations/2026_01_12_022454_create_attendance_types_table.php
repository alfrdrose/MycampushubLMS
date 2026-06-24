<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_types', function (Blueprint $table) {
            $table->id();

            // section_subject pivot
            $table->foreignId('section_subject_id')
                ->constrained('section_subject')
                ->cascadeOnDelete();

            // grading period
            $table->enum('grading_period', ['1st', '2nd', '3rd', '4th']);

            // classwork type from subject_grading
            $table->foreignId('subject_grading_id')
                ->constrained('subject_grading')
                ->cascadeOnDelete();

            $table->timestamps();

            // prevent duplicates
            $table->unique(
                ['section_subject_id', 'grading_period', 'subject_grading_id'],
                'attendance_type_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_types');
    }
};
