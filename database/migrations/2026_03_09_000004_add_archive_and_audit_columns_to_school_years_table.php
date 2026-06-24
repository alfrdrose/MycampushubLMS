<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_years', function (Blueprint $table) {
            if (! Schema::hasColumn('school_years', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('is_active');
            }

            if (! Schema::hasColumn('school_years', 'teacher_audit_enabled')) {
                $table->boolean('teacher_audit_enabled')->default(true)->after('is_archived');
            }

            if (! Schema::hasColumn('school_years', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('teacher_audit_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('school_years', function (Blueprint $table) {
            if (Schema::hasColumn('school_years', 'archived_at')) {
                $table->dropColumn('archived_at');
            }

            if (Schema::hasColumn('school_years', 'teacher_audit_enabled')) {
                $table->dropColumn('teacher_audit_enabled');
            }

            if (Schema::hasColumn('school_years', 'is_archived')) {
                $table->dropColumn('is_archived');
            }
        });
    }
};
