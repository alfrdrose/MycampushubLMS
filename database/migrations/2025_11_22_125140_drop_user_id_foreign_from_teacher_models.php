<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Safely drop the foreign key if it exists
        Schema::table('teacher_models', function (Blueprint $table) {
            // MySQL: Always drop FK by column name array
            $table->dropForeign(['user_id']);

            // Make column nullable AFTER dropping FK
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Re-add the foreign key
        Schema::table('teacher_models', function (Blueprint $table) {
            // Cannot make column non-nullable if there are existing nulls, so we keep it nullable
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
