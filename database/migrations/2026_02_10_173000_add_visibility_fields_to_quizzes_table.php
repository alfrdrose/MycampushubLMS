<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $addVisible = !Schema::hasColumn('quizzes', 'visible');
        $addOpenDate = !Schema::hasColumn('quizzes', 'open_date');
        $addOpenTime = !Schema::hasColumn('quizzes', 'open_time');
        $addIsOpen = !Schema::hasColumn('quizzes', 'is_open');

        Schema::table('quizzes', function (Blueprint $table) use ($addVisible, $addOpenDate, $addOpenTime, $addIsOpen) {
            if ($addVisible) {
                $table->boolean('visible')->default(false)->after('due_date');
            }

            if ($addOpenDate) {
                $table->date('open_date')->nullable()->after('visible');
            }

            if ($addOpenTime) {
                $table->time('open_time')->nullable()->after('open_date');
            }

            if ($addIsOpen) {
                $table->boolean('is_open')->default(false)->after('open_time');
            }
        });

        if (
            !Schema::hasColumn('quizzes', 'visible') ||
            !Schema::hasColumn('quizzes', 'open_date') ||
            !Schema::hasColumn('quizzes', 'open_time') ||
            !Schema::hasColumn('quizzes', 'is_open')
        ) {
            return;
        }

        $manilaNow = Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');

        DB::table('quizzes')
            ->whereIn('status', ['unpublished', 'draft'])
            ->update([
                'visible' => 0,
                'is_open' => 0,
            ]);

        DB::table('quizzes')
            ->whereNotIn('status', ['unpublished', 'draft'])
            ->update([
                'visible' => 1,
            ]);

        DB::table('quizzes')
            ->whereNull('open_date')
            ->whereNotNull('time_posted')
            ->update([
                'open_date' => DB::raw('DATE(time_posted)'),
            ]);

        DB::table('quizzes')
            ->whereNull('open_time')
            ->whereNotNull('time_posted')
            ->update([
                'open_time' => DB::raw('TIME(time_posted)'),
            ]);

        DB::table('quizzes')->update([
            'is_open' => 0,
        ]);

        DB::table('quizzes')
            ->where('visible', 1)
            ->where(function ($query) use ($manilaNow) {
                $query->whereNull('open_date')
                    ->orWhereNull('open_time')
                    ->orWhereRaw("CONCAT(open_date, ' ', open_time) <= ?", [$manilaNow]);
            })
            ->where(function ($query) use ($manilaNow) {
                $query->whereNull('due_date')
                    ->orWhere('due_date', '>', $manilaNow)
                    ->orWhere('accepting', 1);
            })
            ->update([
                'is_open' => 1,
            ]);

        DB::table('quizzes')
            ->where('visible', 0)
            ->update([
                'is_open' => 0,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('quizzes', 'is_open')) {
                $dropColumns[] = 'is_open';
            }
            if (Schema::hasColumn('quizzes', 'open_time')) {
                $dropColumns[] = 'open_time';
            }
            if (Schema::hasColumn('quizzes', 'open_date')) {
                $dropColumns[] = 'open_date';
            }
            if (Schema::hasColumn('quizzes', 'visible')) {
                $dropColumns[] = 'visible';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
