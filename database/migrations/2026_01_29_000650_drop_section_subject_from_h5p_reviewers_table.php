<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('h5p_reviewers')) {
            return;
        }

        $hasSection = Schema::hasColumn('h5p_reviewers', 'section_model_id');
        $hasSubject = Schema::hasColumn('h5p_reviewers', 'subject_model_id');

        if (!$hasSection && !$hasSubject) {
            return;
        }

        Schema::table('h5p_reviewers', function (Blueprint $table) use ($hasSection, $hasSubject) {
            if ($hasSection) {
                $table->dropForeign(['section_model_id']);
            }
            if ($hasSubject) {
                $table->dropForeign(['subject_model_id']);
            }

            $columns = [];
            if ($hasSection) {
                $columns[] = 'section_model_id';
            }
            if ($hasSubject) {
                $columns[] = 'subject_model_id';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('h5p_reviewers')) {
            return;
        }

        $hasSection = Schema::hasColumn('h5p_reviewers', 'section_model_id');
        $hasSubject = Schema::hasColumn('h5p_reviewers', 'subject_model_id');

        if ($hasSection && $hasSubject) {
            return;
        }

        Schema::table('h5p_reviewers', function (Blueprint $table) use ($hasSection, $hasSubject) {
            if (!$hasSection) {
                $table->foreignId('section_model_id')
                    ->nullable()
                    ->constrained('section_models')
                    ->nullOnDelete();
            }
            if (!$hasSubject) {
                $table->foreignId('subject_model_id')
                    ->nullable()
                    ->constrained('subject_models')
                    ->nullOnDelete();
            }

            if (!$hasSection || !$hasSubject) {
                $table->index(['section_model_id', 'subject_model_id']);
            }
        });
    }
};
