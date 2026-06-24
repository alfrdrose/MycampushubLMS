<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'classwork_type')) {
                $table->foreignId('classwork_type')
                    ->nullable()
                    ->constrained('subject_grading')
                    ->onDelete('cascade')
                    ->after('grading_period');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('quizzes', 'classwork_type')) {
                $table->dropForeign(['classwork_type']);
                $table->dropColumn('classwork_type');
            }
        });
    }
};
