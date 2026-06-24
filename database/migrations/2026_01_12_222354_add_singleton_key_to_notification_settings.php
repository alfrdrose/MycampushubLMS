<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1️⃣ Add column (nullable, NOT unique yet)
        if (!Schema::hasColumn('notification_settings', 'key')) {
            Schema::table('notification_settings', function (Blueprint $table) {
                $table->string('key')->nullable()->after('id');
            });
        }

        // 2️⃣ Keep ONE row only (singleton table)
        DB::statement("
            DELETE FROM notification_settings
            WHERE id NOT IN (
                SELECT id FROM (
                    SELECT MIN(id) AS id FROM notification_settings
                ) AS keep
            )
        ");

        // 3️⃣ Assign singleton key
        DB::table('notification_settings')->update([
            'key' => 'email_notifications'
        ]);

        // 4️⃣ Enforce uniqueness SAFELY
        if (!Schema::hasIndex('notification_settings', ['key'], 'unique')) {
            Schema::table('notification_settings', function (Blueprint $table) {
                $table->unique('key');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('notification_settings', ['key'], 'unique')) {
            Schema::table('notification_settings', function (Blueprint $table) {
                $table->dropUnique(['key']);
            });
        }

        if (Schema::hasColumn('notification_settings', 'key')) {
            Schema::table('notification_settings', function (Blueprint $table) {
                $table->dropColumn('key');
            });
        }
    }
};
