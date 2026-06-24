<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->date('week_start')->nullable()->after('guardian_email');
            $table->date('week_end')->nullable()->after('week_start');

            // optional but recommended
            $table->index(['guardian_email', 'week_start', 'week_end']);
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropIndex(['guardian_email', 'week_start', 'week_end']);
            $table->dropColumn(['week_start', 'week_end']);
        });
    }
};
