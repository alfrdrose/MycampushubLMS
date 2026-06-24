<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->timestamp('time_posted')->nullable()->after('setting');
            $table->timestamp('due_date')->nullable()->after('time_posted');
            $table->boolean('accepting')->default(false)->after('due_date');
            $table->unsignedInteger('max_attempt')->default(1)->after('accepting');
            $table->unsignedInteger('total_score')->default(0)->after('max_attempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['time_posted', 'due_date', 'accepting', 'max_attempt', 'total_score']);
        });
    }
};