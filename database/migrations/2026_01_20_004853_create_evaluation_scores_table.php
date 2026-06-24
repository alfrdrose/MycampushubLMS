<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('student_models')
                ->cascadeOnDelete();

            $table->foreignId('evaluation_id')
                ->constrained('evaluations')
                ->cascadeOnDelete();

            $table->integer('score')->default(0);

            $table->timestamps();

            // Prevent duplicate student score per evaluation
            $table->unique(['student_id', 'evaluation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_scores');
    }
};
