<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('start_year');
            $table->unsignedSmallInteger('end_year');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['start_year', 'end_year']);
        });

        if (! Schema::hasTable('section_models')) {
            return;
        }

        $ranges = DB::table('section_models')
            ->select('start_year', 'end_year')
            ->whereNotNull('start_year')
            ->whereNotNull('end_year')
            ->distinct()
            ->orderBy('start_year')
            ->orderBy('end_year')
            ->get();

        if ($ranges->isEmpty()) {
            return;
        }

        $latestRange = $ranges->sortBy([
            ['end_year', 'asc'],
            ['start_year', 'asc'],
        ])->last();

        $now = now();

        DB::table('school_years')->insert(
            $ranges->map(function ($range) use ($latestRange, $now): array {
                return [
                    'start_year' => (int) $range->start_year,
                    'end_year' => (int) $range->end_year,
                    'is_active' => (int) $range->start_year === (int) $latestRange->start_year
                        && (int) $range->end_year === (int) $latestRange->end_year,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('school_years');
    }
};
