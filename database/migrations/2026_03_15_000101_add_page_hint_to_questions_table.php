<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('questions') || Schema::hasColumn('questions', 'page_hint')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedInteger('page_hint')
                ->nullable()
                ->after('order');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('questions') || !Schema::hasColumn('questions', 'page_hint')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('page_hint');
        });
    }
};
