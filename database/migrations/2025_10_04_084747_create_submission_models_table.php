<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('submission_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_model_id')->constrained()->cascadeOnDelete();

            $table->text('content')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status')->default('not_submitted');
            $table->integer('grade')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_models');
    }
};
