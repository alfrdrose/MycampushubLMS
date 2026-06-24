<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('school_years', 'teacher_audit_enabled')) {
            return;
        }

        DB::table('school_years')->update([
            'teacher_audit_enabled' => true,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('school_years', 'teacher_audit_enabled')) {
            return;
        }

        DB::table('school_years')->update([
            'teacher_audit_enabled' => false,
        ]);
    }
};
