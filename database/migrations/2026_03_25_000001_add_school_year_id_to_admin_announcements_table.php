<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_announcements')) {
            return;
        }

        Schema::table('admin_announcements', function (Blueprint $table): void {
            if (! Schema::hasColumn('admin_announcements', 'school_year_id')) {
                $table->foreignId('school_year_id')
                    ->nullable()
                    ->after('date_posted')
                    ->constrained('school_years')
                    ->nullOnDelete();
            }
        });

        if (! Schema::hasTable('school_years')) {
            return;
        }

        $activeSchoolYearId = DB::table('school_years')
            ->where('is_active', true)
            ->value('id');

        $fallbackSchoolYearId = $activeSchoolYearId
            ?: DB::table('school_years')
                ->orderByDesc('end_year')
                ->orderByDesc('start_year')
                ->value('id');

        if ($fallbackSchoolYearId !== null) {
            DB::table('admin_announcements')
                ->whereNull('school_year_id')
                ->update([
                    'school_year_id' => $fallbackSchoolYearId,
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_announcements') || ! Schema::hasColumn('admin_announcements', 'school_year_id')) {
            return;
        }

        Schema::table('admin_announcements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('school_year_id');
        });
    }
};
