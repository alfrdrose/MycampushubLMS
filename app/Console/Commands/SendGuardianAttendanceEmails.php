<?php

namespace App\Console\Commands;

use App\Mail\GuardianAttendanceMail;
use App\Models\NotificationSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendGuardianAttendanceEmails extends Command
{
    protected $signature = 'attendance:send-guardian-emails {--force}';
    protected $description = 'Send weekly attendance + classwork summary emails to guardians';

    // php artisan attendance:send-guardian-emails --force

    public function handle()
    {
        $now = Carbon::now();

        $this->output->title('Guardian attendance email job started at ' . $now);

        // 1. Notification settings
        $settings = NotificationSetting::where('key', 'email_notifications')
            ->where('enabled', 1)
            ->first();

        if (!$settings) {
            $this->info('Email notifications are disabled. Exiting job.');
            return static::SUCCESS;
        }

        $todayName = $now->format('l');
        $currentTime = $now->format('H:i');

        // 2. Only run on END DAY at SEND OUT TIME (unless forced)
        $force = $this->option('force');

        if (!$force) {
            if ($todayName !== $settings->end_day) {
                $this->info("Today is {$todayName}. Emails are scheduled for {$settings->end_day}. Exiting job.");
                return static::SUCCESS;
            }

            if ($currentTime !== $settings->send_out_time) {
                $this->info("Current time is {$currentTime}. Emails are scheduled for {$settings->send_out_time}. Exiting job.");
                return static::SUCCESS;
            }
        }

        // 3. Calculate week range
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $startIndex = array_search($settings->start_day, $days);
        $endIndex = array_search($settings->end_day, $days);

        if ($startIndex === false || $endIndex === false) {
            $this->error('Invalid start_day or end_day in notification settings. Exiting job.');
            return static::SUCCESS;
        }

        // Find most recent start_day
        $startDate = $now->copy()->startOfDay();
        while ($startDate->format('l') !== $settings->start_day) {
            $startDate->subDay();
        }

        $endDate = $now->copy()->endOfDay();

        // 4. Fetch weekly attendance
        $records = DB::table('attendance_records')
            ->join('attendances', 'attendances.id', '=', 'attendance_records.attendance_id')
            ->join('student_models', 'student_models.id', '=', 'attendance_records.student_id')
            ->join('section_subject', 'section_subject.id', '=', 'attendances.section_subject_id')
            ->join('subject_models', 'subject_models.id', '=', 'section_subject.subject_model_id')
            ->whereBetween('attendances.date', [
                $startDate->toDateString(),
                $endDate->toDateString()
            ])
            ->whereNotNull('student_models.guardian_email')
            ->select(
                'student_models.id as student_id',
                'student_models.user_id',
                'student_models.first_name',
                'student_models.last_name',
                'student_models.guardian_email',
                'subject_models.subject_name',
                'attendance_records.status',
                'attendances.date'
            )
            ->orderBy('attendances.date')
            ->get()
            ->groupBy('student_id');

        // 5. Send ONE email per student
        foreach ($records as $studentRecords) {

            $student = $studentRecords->first();

            // Prevent duplicate weekly sends
            $alreadySent = DB::table('email_logs')
                ->where('guardian_email', $student->guardian_email)
                ->where('week_start', $startDate->toDateString())
                ->where('week_end', $endDate->toDateString())
                ->exists();

            if ($alreadySent) {
                continue;
            }

            // -------------------------
            // WEEKLY ATTENDANCE
            // -------------------------
            $weeklyAttendance = [];

            foreach ($studentRecords as $record) {
                $dayName = Carbon::parse($record->date)->format('l');
                $weeklyAttendance[$record->subject_name][$dayName] = ucfirst($record->status);
            }

            $weekDays = array_slice($days, $startIndex, $endIndex - $startIndex + 1);

            // -------------------------
            // WEEKLY CLASSWORK (ACTIVITIES + QUIZZES)
            // -------------------------
            $weeklyClasswork = [];

            /* =======================
             | ACTIVITIES
             ======================= */
            $activities = DB::table('activities')
                ->join('section_subject', 'section_subject.id', '=', 'activities.section_subject_id')
                ->join('subject_models', 'subject_models.id', '=', 'section_subject.subject_model_id')
                ->leftJoin('activity_submissions as subs', function ($join) use ($student) {
                    $join->on('subs.activity_id', '=', 'activities.id')
                        ->where('subs.student_id', '=', $student->student_id)
                        ->where('subs.is_latest', '=', 1)
                        ->whereNull('subs.deleted_at');
                })
                ->whereBetween('activities.due_date', [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ])
                ->where(function ($q) {
                    $q->whereNotNull('subs.score')
                        ->orWhereNull('subs.id');
                })
                ->select(
                    'subject_models.subject_name',
                    'activities.title',
                    'activities.open_date',
                    'activities.due_date',
                    'activities.total_score',
                    'subs.score'
                )
                ->orderBy('activities.due_date')
                ->get();

            foreach ($activities as $activity) {
                $weeklyClasswork[] = [
                    'type' => 'Activity',
                    'subject' => $activity->subject_name,
                    'title' => $activity->title,
                    'open_date' => Carbon::parse($activity->open_date)->toFormattedDateString(),
                    'close_date' => Carbon::parse($activity->due_date)->toFormattedDateString(),
                    'score' => $activity->score !== null
                        ? "{$activity->score} / {$activity->total_score}"
                        : 'Missed',
                ];
            }

            /* =======================
             | QUIZZES
             ======================= */
            $quizzes = DB::table('quizzes')
                ->join('subject_models', 'subject_models.id', '=', 'quizzes.subject_model_id')

                ->leftJoin('responses as r', function ($join) use ($student) {
                    $join->on('r.quiz_id', '=', 'quizzes.id')
                        ->where('r.user_id', '=', $student->user_id);
                })

                ->where(function ($q) use ($startDate, $endDate) {

                    // ✅ CASE 1: GRADED quizzes (always include)
                    $q->whereNotNull('r.score')

                        // ✅ CASE 2: MISSED quizzes (must be due this week)
                        ->orWhere(function ($missed) use ($startDate, $endDate) {
                        $missed
                            ->whereNull('r.id')
                            ->whereNotNull('quizzes.due_date')
                            ->whereBetween('quizzes.due_date', [
                                $startDate->toDateString(),
                                $endDate->toDateString(),
                            ]);
                    });
                })

                ->select(
                    'subject_models.subject_name',
                    'quizzes.title',
                    'quizzes.time_posted',
                    'quizzes.due_date',
                    'quizzes.total_score',
                    'r.score'
                )
                ->orderByRaw('COALESCE(quizzes.due_date, quizzes.created_at)')
                ->get();


            foreach ($quizzes as $quiz) {
                $weeklyClasswork[] = [
                    'type' => 'Quiz',
                    'subject' => $quiz->subject_name,
                    'title' => $quiz->title,
                    'open_date' => Carbon::parse($quiz->time_posted)->toFormattedDateString(),
                    'close_date' => Carbon::parse($quiz->due_date)->toFormattedDateString(),
                    'score' => $quiz->score !== null
                        ? "{$quiz->score} / {$quiz->total_score}"
                        : 'Missed',
                ];
            }

            // -------------------------
            // SEND EMAIL
            // -------------------------
            Mail::to($student->guardian_email)->send(
                new GuardianAttendanceMail(
                    $student,
                    $weekDays,
                    $weeklyAttendance,
                    $startDate->toFormattedDateString(),
                    $endDate->toFormattedDateString(),
                    $weeklyClasswork
                )
            );

            // Log weekly send
            DB::table('email_logs')->insert([
                'guardian_email' => $student->guardian_email,
                'week_start' => $startDate->toDateString(),
                'week_end' => $endDate->toDateString(),
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->info('Guardian attendance email job completed.');

        return static::SUCCESS;
    }
}
