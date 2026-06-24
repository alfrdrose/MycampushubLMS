<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Activity;
use App\Models\Quiz;
use Illuminate\Support\Carbon;

class CloseExpiredActivities extends Command
{
    // php artisan activities:close-expired

    protected $signature = 'activities:close-expired';
    protected $description = 'Automatically close activities and quizzes past their due date and time';

    public function handle()
    {
        $manilaNow = Carbon::now('Asia/Manila');

        $this->output->title('Close expired command running at ' . $manilaNow);

        $activities = Activity::whereNotNull('due_date')
            ->where('is_open', 1)
            ->where('accepting', 0) // 🔒 do NOT close if still accepting
            ->where('due_date', '<', $manilaNow)
            ->get();

        $this->comment('Found ' . $activities->count() . ' activities eligible to close.');
        $quizzes = Quiz::whereNotNull('due_date')
            ->where('is_open', 1)
            ->where('accepting', 0)
            ->where('due_date', '<', $manilaNow)
            ->get();

        $this->comment('Found ' . $quizzes->count() . ' quizzes eligible to close.');

        if ($activities->isEmpty() && $quizzes->isEmpty()) {
            $this->info('No classwork closed.');
            return static::SUCCESS;
        }

        foreach ($activities as $activity) {
            $activity->update([
                'is_open' => 0,
            ]);

            $this->output->text(
                "Closed activity | ID: {$activity->id} | Title: {$activity->title} | ClosedAt: {$manilaNow}"
            );
        }

        foreach ($quizzes as $quiz) {
            $quiz->update([
                'is_open' => 0,
                'status' => ((int) ($quiz->visible ?? 0) === 1) ? 'closed' : 'unpublished',
            ]);

            $this->output->text(
                "Closed quiz | ID: {$quiz->id} | Title: {$quiz->title} | ClosedAt: {$manilaNow}"
            );
        }

        $this->info('Auto-close finished for activities and quizzes.');

        return static::SUCCESS;
    }
}
