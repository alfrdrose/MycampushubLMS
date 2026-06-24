<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviewer_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('reviewers')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('student_models')->cascadeOnDelete();
            $table->string('status', 20)->default('viewed');
            $table->unsignedInteger('score')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['reviewer_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviewer_interactions');
    }
};
