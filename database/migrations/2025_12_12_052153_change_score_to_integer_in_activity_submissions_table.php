<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_submissions', function (Blueprint $table) {
            // Change the column type to integer
            $table->integer('score')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('activity_submissions', function (Blueprint $table) {
            // Revert back to decimal if ever rolled back
            $table->decimal('score', 10, 2)->nullable()->change();
        });
    }
};

