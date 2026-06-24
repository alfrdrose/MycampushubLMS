<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_submissions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('activity_id');
            $table->unsignedBigInteger('student_id');

            // Resubmission control
            $table->integer('attempt_number')->default(1);
            $table->boolean('is_latest')->default(true);

            // Submission type (file or essay)
            $table->enum('type', ['file', 'essay']);

            // Timing (late or on time)
            $table->enum('timing_status', ['on_time', 'late'])->nullable();

            // Lifecycle status
            $table->enum('status', ['submitted', 'graded', 'returned', 'missing'])
                  ->default('submitted');

            $table->dateTime('submitted_at')->nullable();

            // Grading info
            $table->decimal('score', 8, 2)->nullable();
            $table->text('feedback')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('activity_id')
                  ->references('id')
                  ->on('activities')
                  ->cascadeOnDelete();

            $table->foreign('student_id')
                    ->references('id')
                    ->on('student_models')
                    ->cascadeOnDelete();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_submissions');
    }
};
