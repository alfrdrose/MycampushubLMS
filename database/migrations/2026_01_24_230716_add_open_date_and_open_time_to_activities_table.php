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
        Schema::table('activities', function (Blueprint $table) {
            if (!Schema::hasColumn('activities', 'open_date')) {
                $table->date('open_date')->nullable()->after('due_date');
            }

            if (!Schema::hasColumn('activities', 'open_time')) {
                $table->time('open_time')->nullable()->after('open_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'open_time')) {
                $table->dropColumn('open_time');
            }

            if (Schema::hasColumn('activities', 'open_date')) {
                $table->dropColumn('open_date');
            }
        });
    }

};
