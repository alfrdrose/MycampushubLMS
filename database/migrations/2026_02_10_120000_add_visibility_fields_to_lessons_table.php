<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->boolean('visible')->default(true)->after('grading_period');
            $table->date('open_date')->nullable()->after('visible');
            $table->time('open_time')->nullable()->after('open_date');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['visible', 'open_date', 'open_time']);
        });
    }
};
