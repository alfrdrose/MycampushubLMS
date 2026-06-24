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
    Schema::table('activity_submissions', function (Blueprint $table) {
        $table->enum('type', ['file', 'essay', 'both'])
              ->default('file')
              ->change();
    });
}

public function down(): void
{
    Schema::table('activity_submissions', function (Blueprint $table) {
        $table->enum('type', ['file', 'essay'])
              ->change();
    });
}

};
