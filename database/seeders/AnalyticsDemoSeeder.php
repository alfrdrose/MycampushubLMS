<?php

namespace Database\Seeders;

use App\Enums\QuizStatus;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Quiz;
use App\Models\Response;
use App\Models\SectionModel;
use App\Models\SectionSubject;
use App\Models\StudentModel;
use App\Models\SubjectGrading;
use App\Models\SubjectModel;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AnalyticsDemoSeeder extends Seeder
{
    private const SEED_CATEGORY = 'Analytics Seed';
    private const SEED_LABEL_PREFIX = '[Analytics Seed';
    private const DEFAULT_STUDENT_COUNT = 12;
    private const DEFAULT_ACTIVITY_COUNT = 6;
    private const DEFAULT_QUIZ_COUNT = 4;
    private const DEFAULT_MIN_TAGS = 1;
    private const DEFAULT_MAX_TAGS = 3;

    public function runForSectionSubject(SectionModel $section, SubjectModel $subject, array $options = []): array
    {
        $options = array_merge([
            'student_count' => self::DEFAULT_STUDENT_COUNT,
            'activity_count' => self::DEFAULT_ACTIVITY_COUNT,
            'quiz_count' => self::DEFAULT_QUIZ_COUNT,
            'min_tags' => self::DEFAULT_MIN_TAGS,
            'max_tags' => self::DEFAULT_MAX_TAGS,
            'reset' => true,
        ], $options);

        if (!empty($options['reset'])) {
            $this->clearForSectionSubject($section, $subject);
        }

        return DB::transaction(function () use ($section, $subject, $options) {
            $seedLabel = $this->seedLabel($section->id, $subject->id);
            $seedEmailPrefix = $this->seedEmailPrefix($section->id, $subject->id);
            $sectionSubject = SectionSubject::firstOrCreate([
                'section_model_id' => $section->id,
                'subject_model_id' => $subject->id,
            ]);

            $classworkTypes = SubjectGrading::where('subject_id', $subject->id)->get();

            if ($classworkTypes->isEmpty()) {
                $classworkTypes = collect([
                    SubjectGrading::create([
                        'subject_id' => $subject->id,
                        'classwork_type' => $seedLabel . ' Homework',
                        'percentage' => 30,
                    ]),
                    SubjectGrading::create([
                        'subject_id' => $subject->id,
                        'classwork_type' => $seedLabel . ' Quiz',
                        'percentage' => 30,
                    ]),
                    SubjectGrading::create([
                        'subject_id' => $subject->id,
                        'classwork_type' => $seedLabel . ' Project',
                        'percentage' => 40,
                    ]),
                ]);
            }

            $tagPool = Tag::query()->inRandomOrder()->get();
            if ($tagPool->isEmpty()) {
                $tagPool = $this->seedTags();
            }

            $existingSeedStudents = StudentModel::where('section_id', $section->id)
                ->where('email', 'like', $seedEmailPrefix . '%')
                ->get();

            $toCreate = max(0, (int) $options['student_count'] - $existingSeedStudents->count());
            $createdStudents = collect();

            for ($i = 1; $i <= $toCreate; $i++) {
                $index = $existingSeedStudents->count() + $i;
                $email = $seedEmailPrefix . $index . '@example.test';
                $name = 'Seed Student ' . $index;

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'password' => 'password',
                    ]
                );

                $this->assignStudentRole($user);

                $student = StudentModel::firstOrCreate(
                    ['email' => $email],
                    [
                        'first_name' => 'Seed',
                        'last_name' => 'Student ' . $index,
                        'grade' => (string) ($section->grade_level ?? ''),
                        'section_id' => $section->id,
                        'user_id' => $user->id,
                    ]
                );

                if ($student->user_id !== $user->id) {
                    $student->user_id = $user->id;
                    $student->save();
                }

                $createdStudents->push($student);
            }

            $students = StudentModel::where('section_id', $section->id)
                ->where('email', 'like', $seedEmailPrefix . '%')
                ->get();

            $periods = ['1st', '2nd', '3rd', '4th'];
            $activityCount = 0;
            $quizCount = 0;
            $submissionCount = 0;
            $responseCount = 0;

            for ($i = 1; $i <= (int) $options['activity_count']; $i++) {
                $totalScore = random_int(20, 50);
                $activity = Activity::create([
                    'title' => $seedLabel . ' Activity ' . $i,
                    'description' => 'Seeded analytics activity data.',
                    'time_posted' => now()->subDays(random_int(1, 30)),
                    'section_subject_id' => $sectionSubject->id,
                    'visible' => 1,
                    'accepting' => 1,
                    'accept_files' => 0,
                    'accept_text' => 1,
                    'max_number_files' => 1,
                    'max_attempt' => 1,
                    'total_score' => $totalScore,
                    'grading_period' => $periods[array_rand($periods)],
                    'classwork_type' => $classworkTypes->random()->id,
                ]);

                $this->syncRandomTags($activity, $tagPool, (int) $options['min_tags'], (int) $options['max_tags']);
                $activityCount++;

                foreach ($students as $student) {
                    ActivitySubmission::create([
                        'activity_id' => $activity->id,
                        'student_id' => $student->id,
                        'type' => 'essay',
                        'attempt_number' => 1,
                        'is_latest' => true,
                        'submitted_at' => now()->subDays(random_int(0, 10)),
                        'timing_status' => 'on_time',
                        'status' => 'graded',
                        'score' => random_int(0, $totalScore),
                    ]);
                    $submissionCount++;
                }
            }

            for ($i = 1; $i <= (int) $options['quiz_count']; $i++) {
                $totalScore = random_int(10, 40);
                $quiz = Quiz::create([
                    'title' => $seedLabel . ' Quiz ' . $i,
                    'grading_period' => $periods[array_rand($periods)],
                    'description' => 'Seeded analytics quiz data.',
                    'section_model_id' => $section->id,
                    'subject_model_id' => $subject->id,
                    'classwork_type' => $classworkTypes->random()->id,
                    'status' => QuizStatus::PUBLISHED->value,
                    'time_posted' => now()->subDays(random_int(1, 30)),
                    'accepting' => false,
                    'max_attempt' => 1,
                    'total_score' => $totalScore,
                ]);

                $this->syncRandomTags($quiz, $tagPool, (int) $options['min_tags'], (int) $options['max_tags']);
                $quizCount++;

                foreach ($students as $student) {
                    if (!$student->user_id) {
                        continue;
                    }

                    Response::updateOrCreate(
                        [
                            'quiz_id' => $quiz->id,
                            'user_id' => $student->user_id,
                        ],
                        [
                            'session_token' => (string) Str::uuid(),
                            'score' => random_int(0, $totalScore),
                        ]
                    );
                    $responseCount++;
                }
            }

            return [
                'students' => $createdStudents->count(),
                'activities' => $activityCount,
                'quizzes' => $quizCount,
                'submissions' => $submissionCount,
                'responses' => $responseCount,
            ];
        });
    }

    public function clearForSectionSubject(SectionModel $section, SubjectModel $subject): array
    {
        return DB::transaction(function () use ($section, $subject) {
            $seedLabel = $this->seedLabel($section->id, $subject->id);
            $seedEmailPrefix = $this->seedEmailPrefix($section->id, $subject->id);
            $sectionSubject = SectionSubject::where('section_model_id', $section->id)
                ->where('subject_model_id', $subject->id)
                ->first();

            $activityCount = 0;
            $quizCount = 0;
            $userCount = 0;

            if ($sectionSubject) {
                $activities = Activity::where('section_subject_id', $sectionSubject->id)
                    ->where('title', 'like', $seedLabel . '%')
                    ->get();

                foreach ($activities as $activity) {
                    $activity->tags()->detach();
                }

                $activityCount = $activities->count();

                if ($activityCount > 0) {
                    Activity::whereIn('id', $activities->pluck('id'))->delete();
                }
            }

            $quizzes = Quiz::where('section_model_id', $section->id)
                ->where('subject_model_id', $subject->id)
                ->where('title', 'like', $seedLabel . '%')
                ->get();

            foreach ($quizzes as $quiz) {
                $quiz->tags()->detach();
            }

            $quizCount = $quizzes->count();

            if ($quizCount > 0) {
                Quiz::whereIn('id', $quizzes->pluck('id'))->delete();
            }

            $users = User::where('email', 'like', $seedEmailPrefix . '%')->get();
            $userCount = $users->count();

            foreach ($users as $user) {
                $user->delete();
            }

            SubjectGrading::where('subject_id', $subject->id)
                ->where('classwork_type', 'like', $seedLabel . '%')
                ->delete();

            return [
                'activities' => $activityCount,
                'quizzes' => $quizCount,
                'users' => $userCount,
            ];
        });
    }

    private function seedLabel(int $sectionId, int $subjectId): string
    {
        return self::SEED_LABEL_PREFIX . " {$sectionId}-{$subjectId}]";
    }

    private function seedEmailPrefix(int $sectionId, int $subjectId): string
    {
        return 'analytics-seed+' . $sectionId . '-' . $subjectId . '-';
    }

    private function seedTags(): Collection
    {
        $tagNames = [
            'Foundations',
            'Problem Solving',
            'Critical Reading',
            'Data Skills',
            'Reasoning',
            'Communication',
            'Measurement',
            'Application',
        ];

        foreach ($tagNames as $tagName) {
            $slug = Str::slug(self::SEED_CATEGORY . ' ' . $tagName);
            Tag::firstOrCreate(
                ['slug' => $slug],
                ['name' => $tagName, 'category' => self::SEED_CATEGORY]
            );
        }

        return Tag::query()->inRandomOrder()->get();
    }

    private function syncRandomTags($model, Collection $tagPool, int $minTags, int $maxTags): void
    {
        $tagCount = max(1, min($tagPool->count(), random_int($minTags, $maxTags)));
        $tags = $tagPool->random($tagCount);

        if ($tags instanceof Tag) {
            $tags = collect([$tags]);
        }

        $model->tags()->sync($tags->pluck('id')->all());
    }

    private function assignStudentRole(User $user): void
    {
        if (!class_exists(Role::class)) {
            return;
        }

        if (!$user->exists || method_exists($user, 'hasRole') === false) {
            return;
        }

        if ($user->hasRole('student')) {
            return;
        }

        if (Role::where('name', 'student')->exists()) {
            $user->assignRole('student');
        }
    }
}
