<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('taggables')) {
            return;
        }

        Schema::table('taggables', function (Blueprint $table) {
            if (!Schema::hasColumn('taggables', 'relevance_level')) {
                $table->string('relevance_level', 20)
                    ->default('medium')
                    ->after('taggable_type');
            }

            if (!Schema::hasColumn('taggables', 'coverage_ratio')) {
                $table->decimal('coverage_ratio', 6, 4)
                    ->nullable()
                    ->after('relevance_level');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('taggables')) {
            return;
        }

        Schema::table('taggables', function (Blueprint $table) {
            if (Schema::hasColumn('taggables', 'coverage_ratio')) {
                $table->dropColumn('coverage_ratio');
            }

            if (Schema::hasColumn('taggables', 'relevance_level')) {
                $table->dropColumn('relevance_level');
            }
        });
    }
};
