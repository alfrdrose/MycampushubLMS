<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Activity;
use App\Models\Quiz;
use Illuminate\Support\Carbon;

class AutoPublishActivities extends Command
{
    // php artisan activities:auto-publish

    protected $signature = 'activities:auto-publish';
    protected $description = 'Automatically open activities and quizzes based on open_date and open_time';

    public function handle()
    {
        $manilaNow = Carbon::now('Asia/Manila');

        // Log::info('Auto-open command running at ' . $manilaNow);
        $this->output->title('Auto-open command running at ' . $manilaNow);

        $activities = Activity::whereNotNull('open_date')
            ->whereNotNull('open_time')
            ->where('is_open', 0)
            ->whereRaw("CONCAT(open_date, ' ', open_time) <= ?", [$manilaNow])
            ->where(function ($query) use ($manilaNow) {
                $query
                    // Not past due → safe to open
                    ->whereNull('due_date')
                    ->orWhere('due_date', '>', $manilaNow)
                    // Past due BUT still accepting → allow open
                    ->orWhere('accepting', 1);
            })
            ->get();

        $this->comment('Found ' . $activities->count() . ' activities eligible to open.');
        $quizzes = Quiz::whereNotNull('open_date')
            ->whereNotNull('open_time')
            ->where('is_open', 0)
            ->whereRaw("CONCAT(open_date, ' ', open_time) <= ?", [$manilaNow])
            ->where(function ($query) use ($manilaNow) {
                $query
                    ->whereNull('due_date')
                    ->orWhere('due_date', '>', $manilaNow)
                    ->orWhere('accepting', 1);
            })
            ->get();

        $this->comment('Found ' . $quizzes->count() . ' quizzes eligible to open.');

        if ($activities->isEmpty() && $quizzes->isEmpty()) {
            $this->info('No classwork opened.');
            return static::SUCCESS;
        }

        foreach ($activities as $activity) {
            $activity->update([
                'is_open' => 1,
            ]);

            $this->output->text(
                "Opened activity | ID: {$activity->id} | Title: {$activity->title} | OpenedAt: {$manilaNow}"
            );
        }

        foreach ($quizzes as $quiz) {
            $quiz->update([
                'is_open' => 1,
                'status' => ((int) ($quiz->visible ?? 0) === 1) ? 'published' : 'unpublished',
            ]);

            $this->output->text(
                "Opened quiz | ID: {$quiz->id} | Title: {$quiz->title} | OpenedAt: {$manilaNow}"
            );
        }

        $this->info('Auto-open finished for activities and quizzes.');

        return static::SUCCESS;
    }
}
