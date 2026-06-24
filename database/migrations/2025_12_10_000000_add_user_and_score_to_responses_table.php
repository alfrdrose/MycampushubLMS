<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            if (!Schema::hasColumn('responses', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('quiz_id')
                    ->constrained()
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('responses', 'score')) {
                $table->integer('score')->default(0)->after('session_token');
            }

            $table->unique(['quiz_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {

            if (Schema::hasColumn('responses', 'score')) {
                $table->dropColumn('score');
            }

            // DROP FK FIRST
            if (Schema::hasColumn('responses', 'quiz_id')) {
                $table->dropForeign(['quiz_id']);
            }
            if (Schema::hasColumn('responses', 'user_id')) {
                $table->dropForeign(['user_id']);
            }

            // THEN drop the unique index
            $table->dropUnique(['quiz_id', 'user_id']);

            $table->dropColumn('user_id');

            $table->foreign('quiz_id')
                ->references('id')
                ->on('quizzes')
                ->cascadeOnDelete();
        });
    }
};
