<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_bank_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_teacher_id')
                ->constrained('teacher_models')
                ->cascadeOnDelete();
            $table->foreignId('subject_model_id')
                ->constrained('subject_models')
                ->cascadeOnDelete();
            $table->foreignId('section_model_id')
                ->nullable()
                ->constrained('section_models')
                ->nullOnDelete();
            $table->string('type');
            $table->longText('text');
            $table->json('option')->nullable();
            $table->json('setting')->nullable();
            $table->json('answer_key')->nullable();
            $table->boolean('required')->default(false);
            $table->unsignedInteger('default_points')->default(1);
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['created_by_teacher_id', 'subject_model_id', 'is_archived'], 'qbi_owner_subject_archived_idx');
            $table->index(['subject_model_id', 'section_model_id'], 'qbi_subject_section_idx');
            $table->index(['type', 'is_archived'], 'qbi_type_archived_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_items');
    }
};
