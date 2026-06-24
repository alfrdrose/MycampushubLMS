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
        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'section_model_id')) {
                $table->foreignId('section_model_id')->nullable()->constrained('section_models')->nullOnDelete();
            }
            if (!Schema::hasColumn('quizzes', 'subject_model_id')) {
                $table->foreignId('subject_model_id')->nullable()->constrained('subject_models')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeign(['section_model_id']);
            $table->dropColumn('section_model_id');

            $table->dropForeign(['subject_model_id']);
            $table->dropColumn('subject_model_id');
        });
    }
};
