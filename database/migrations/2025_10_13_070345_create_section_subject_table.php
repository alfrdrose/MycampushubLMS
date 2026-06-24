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
        Schema::create('section_subject', function (Blueprint $table) {
            $table->id();

            $table->foreignId('section_model_id')
            ->constrained('section_models')
            ->cascadeOnDelete();

            $table->foreignId('subject_model_id')
            ->constrained('subject_models')
            ->cascadeOnDelete();

            $table->unique(['section_model_id', 'subject_model_id']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_subject');
    }
};
