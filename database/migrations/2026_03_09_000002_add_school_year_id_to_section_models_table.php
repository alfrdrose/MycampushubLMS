<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('section_models')) {
            return;
        }

        Schema::table('section_models', function (Blueprint $table) {
            if (! Schema::hasColumn('section_models', 'school_year_id')) {
                $table->foreignId('school_year_id')
                    ->nullable()
                    ->after('end_year')
                    ->constrained('school_years')
                    ->nullOnDelete();
            }
        });

        if (! Schema::hasTable('school_years')) {
            return;
        }

        $schoolYearMap = DB::table('school_years')
            ->select('id', 'start_year', 'end_year')
            ->get()
            ->keyBy(fn ($schoolYear) => $schoolYear->start_year . '-' . $schoolYear->end_year);

        DB::table('section_models')
            ->select('id', 'start_year', 'end_year')
            ->orderBy('id')
            ->get()
            ->each(function ($section) use ($schoolYearMap): void {
                $schoolYear = $schoolYearMap->get($section->start_year . '-' . $section->end_year);
                if (! $schoolYear) {
                    return;
                }

                DB::table('section_models')
                    ->where('id', $section->id)
                    ->update([
                        'school_year_id' => $schoolYear->id,
                    ]);
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('section_models') || ! Schema::hasColumn('section_models', 'school_year_id')) {
            return;
        }

        Schema::table('section_models', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_year_id');
        });
    }
};
