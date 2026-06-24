<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // Only applies if text is enabled
            $table->unsignedInteger('max_words')->nullable();
            $table->unsignedInteger('min_words')->nullable();

            // in MB or KB depending on how you’ll treat it
            $table->unsignedInteger('max_file_size')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['max_words', 'min_words', 'max_file_size']);
        });
    }
};
