<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_models', function (Blueprint $table) {
            if (!Schema::hasColumn('student_models', 'guardian_email')) {
                $table->string('guardian_email')->nullable()->after('email');
            }
        });
    }


    public function down(): void
{
    Schema::table('student_models', function (Blueprint $table) {
        if (Schema::hasColumn('student_models', 'guardian_email')) {
            $table->dropColumn('guardian_email');
        }
    });
}

};
