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
    if (Schema::hasColumn('evaluations', 'classwork_type')) {
        return;
    }

    Schema::table('evaluations', function (Blueprint $table) {
        $table->foreignId('classwork_type')
              ->nullable()
              ->constrained('subject_grading')
              ->cascadeOnDelete()
              ->after('grading_period');
    });
}

public function down(): void
{
    Schema::table('evaluations', function (Blueprint $table) {
        $table->dropForeign(['classwork_type']);
        $table->dropColumn('classwork_type');
    });
}

};
