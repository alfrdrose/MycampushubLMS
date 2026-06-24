<?php

use App\Models\Question;
use App\Models\QuestionBankItem;
use App\Models\Quiz;
use App\Models\SchoolYear;
use App\Models\SectionModel;
use App\Models\SubjectModel;
use App\Models\SubjectTeacher;
use App\Models\Tag;
use App\Models\TeacherModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::findOrCreate('teacher', 'web');
});

function createTeacherQuestionBankContext(): array
{
    $schoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $user = User::factory()->create();
    $user->assignRole('teacher');

    $teacher = TeacherModel::factory()->create([
        'user_id' => $user->id,
    ]);

    $section = SectionModel::factory()->create([
        'school_year_id' => $schoolYear->id,
        'start_year' => $schoolYear->start_year,
        'end_year' => $schoolYear->end_year,
    ]);

    $subject = SubjectModel::factory()->create();

    SubjectTeacher::create([
        'teacher_model_id' => $teacher->id,
        'section_model_id' => $section->id,
        'subject_model_id' => $subject->id,
    ]);

    $section->subjects()->attach($subject->id);

    return [$user, $teacher, $section, $subject, $schoolYear];
}

function createQuizForQuestionBuilder(SectionModel $section, SubjectModel $subject): Quiz
{
    return Quiz::create([
        'title' => 'Bank Import Quiz',
        'grading_period' => '1st',
        'description' => 'Quiz used for question bank import tests.',
        'questions' => [],
        'setting' => ['pagination' => 10],
        'section_model_id' => $section->id,
        'subject_model_id' => $subject->id,
        'status' => 'unpublished',
        'visible' => false,
        'is_open' => false,
        'accepting' => false,
        'max_attempt' => 1,
        'total_score' => 0,
    ]);
}

it('stores a subject-scoped question bank item and omits answer keys from list responses', function () {
    [$user, $teacher, $section, $subject] = createTeacherQuestionBankContext();

    $tag = Tag::create([
        'name' => 'Geography',
        'slug' => Str::slug('Geography'),
        'category' => 'Topic',
    ]);

    $storeResponse = $this
        ->actingAs($user)
        ->postJson(route('teacher.question-bank.store', [
            'section_id' => $section->id,
            'subject_id' => $subject->id,
        ]), [
            'scope' => 'subject',
            'type' => 'multiple_choice',
            'text' => 'What is the capital of France?',
            'option' => ['Paris', 'Rome', 'Madrid'],
            'answer_key' => ['0'],
            'default_points' => 3,
            'required' => true,
            'tag_ids' => [$tag->id],
            'tag_relevance' => [
                $tag->id => 'high',
            ],
        ]);

    $storeResponse
        ->assertCreated()
        ->assertJsonPath('data.scope', 'subject')
        ->assertJsonPath('data.default_points', 3)
        ->assertJsonPath('data.answer_key.0', 'Paris');

    $itemId = (int) $storeResponse->json('data.id');

    $this->assertDatabaseHas('question_bank_items', [
        'id' => $itemId,
        'created_by_teacher_id' => $teacher->id,
        'subject_model_id' => $subject->id,
        'section_model_id' => null,
        'type' => 'multiple_choice',
        'default_points' => 3,
        'required' => 1,
        'is_archived' => 0,
    ]);

    $this->assertDatabaseHas('taggables', [
        'tag_id' => $tag->id,
        'taggable_type' => QuestionBankItem::class,
        'taggable_id' => $itemId,
        'relevance_level' => 'high',
    ]);

    expect(DB::table('taggables')
        ->where('taggable_type', QuestionBankItem::class)
        ->where('taggable_id', $itemId)
        ->where('tag_id', $tag->id)
        ->value('coverage_ratio'))
        ->toBeNull();

    $indexResponse = $this
        ->actingAs($user)
        ->getJson(route('teacher.question-bank.index', [
            'section_id' => $section->id,
            'subject_id' => $subject->id,
        ]));

    $indexResponse
        ->assertOk()
        ->assertJsonPath('data.0.id', $itemId);

    $this->assertArrayNotHasKey('answer_key', $indexResponse->json('data.0'));
});

it('filters question bank items by selected tags', function () {
    [$user, $teacher, $section, $subject] = createTeacherQuestionBankContext();

    $historyTag = Tag::create([
        'name' => 'History',
        'slug' => Str::slug('History'),
        'category' => 'Topic',
    ]);

    $scienceTag = Tag::create([
        'name' => 'Science',
        'slug' => Str::slug('Science'),
        'category' => 'Topic',
    ]);

    $historyItem = QuestionBankItem::factory()->create([
        'created_by_teacher_id' => $teacher->id,
        'subject_model_id' => $subject->id,
        'section_model_id' => null,
        'text' => 'Tagged history item',
    ]);
    $historyItem->tags()->sync([
        $historyTag->id => ['relevance_level' => 'high'],
    ]);

    $scienceItem = QuestionBankItem::factory()->create([
        'created_by_teacher_id' => $teacher->id,
        'subject_model_id' => $subject->id,
        'section_model_id' => null,
        'text' => 'Tagged science item',
    ]);
    $scienceItem->tags()->sync([
        $scienceTag->id => ['relevance_level' => 'medium'],
    ]);

    $response = $this
        ->actingAs($user)
        ->getJson(route('teacher.question-bank.index', [
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'tag_ids' => [$historyTag->id],
        ]));

    $texts = collect($response->json('data'))->pluck('text')->all();

    $response->assertOk();
    expect($texts)->toContain('Tagged history item');
    expect($texts)->not->toContain('Tagged science item');
});

it('returns the complete class-context question bank list for quiz import modals', function () {
    [$user, $teacher, $section, $subject] = createTeacherQuestionBankContext();

    foreach (range(1, 8) as $index) {
        QuestionBankItem::factory()->create([
            'created_by_teacher_id' => $teacher->id,
            'subject_model_id' => $subject->id,
            'section_model_id' => null,
            'text' => "Shared bank item {$index}",
            'is_archived' => false,
        ]);
    }

    foreach (range(1, 8) as $index) {
        QuestionBankItem::factory()->create([
            'created_by_teacher_id' => $teacher->id,
            'subject_model_id' => $subject->id,
            'section_model_id' => $section->id,
            'text' => "Section bank item {$index}",
            'is_archived' => false,
        ]);
    }

    $response = $this
        ->actingAs($user)
        ->getJson(route('teacher.question-bank.index', [
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'all' => 1,
        ]));

    $response
        ->assertOk()
        ->assertJsonCount(16, 'data')
        ->assertJsonPath('meta.last_page', 1)
        ->assertJsonPath('meta.total', 16);
});

it('supports section-scoped items plus archive and restore actions', function () {
    [$user, $teacher, $section, $subject] = createTeacherQuestionBankContext();

    $storeResponse = $this
        ->actingAs($user)
        ->postJson(route('teacher.question-bank.store', [
            'section_id' => $section->id,
            'subject_id' => $subject->id,
        ]), [
            'scope' => 'section',
            'type' => 'true_false',
            'text' => 'The Earth revolves around the Sun.',
            'answer_key' => 'true',
            'default_points' => 1,
        ]);

    $storeResponse
        ->assertCreated()
        ->assertJsonPath('data.scope', 'section');

    $itemId = (int) $storeResponse->json('data.id');

    $this->assertDatabaseHas('question_bank_items', [
        'id' => $itemId,
        'created_by_teacher_id' => $teacher->id,
        'subject_model_id' => $subject->id,
        'section_model_id' => $section->id,
        'is_archived' => 0,
    ]);

    $this->actingAs($user)
        ->deleteJson(route('teacher.question-bank.archive', [
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'item' => $itemId,
        ]))
        ->assertOk()
        ->assertJsonPath('data.is_archived', true);

    $this->assertDatabaseHas('question_bank_items', [
        'id' => $itemId,
        'is_archived' => 1,
    ]);

    $this->actingAs($user)
        ->postJson(route('teacher.question-bank.restore', [
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'item' => $itemId,
        ]))
        ->assertOk()
        ->assertJsonPath('data.is_archived', false);

    $this->assertDatabaseHas('question_bank_items', [
        'id' => $itemId,
        'is_archived' => 0,
    ]);
});

it('limits question bank queries to the current teacher and current section context', function () {
    [$user, $teacher, $section, $subject, $schoolYear] = createTeacherQuestionBankContext();

    $visibleItem = QuestionBankItem::factory()->create([
        'created_by_teacher_id' => $teacher->id,
        'subject_model_id' => $subject->id,
        'section_model_id' => null,
        'text' => 'Visible shared item',
    ]);

    $otherSection = SectionModel::factory()->create([
        'school_year_id' => $schoolYear->id,
        'start_year' => $schoolYear->start_year,
        'end_year' => $schoolYear->end_year,
    ]);

    $hiddenSectionItem = QuestionBankItem::factory()->create([
        'created_by_teacher_id' => $teacher->id,
        'subject_model_id' => $subject->id,
        'section_model_id' => $otherSection->id,
        'text' => 'Other section only',
    ]);

    $otherUser = User::factory()->create();
    $otherUser->assignRole('teacher');
    $otherTeacher = TeacherModel::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    SubjectTeacher::create([
        'teacher_model_id' => $otherTeacher->id,
        'section_model_id' => $section->id,
        'subject_model_id' => $subject->id,
    ]);

    $foreignItem = QuestionBankItem::factory()->create([
        'created_by_teacher_id' => $otherTeacher->id,
        'subject_model_id' => $subject->id,
        'section_model_id' => null,
        'text' => 'Foreign teacher item',
    ]);

    $indexResponse = $this
        ->actingAs($user)
        ->getJson(route('teacher.question-bank.index', [
            'section_id' => $section->id,
            'subject_id' => $subject->id,
        ]));

    $texts = collect($indexResponse->json('data'))->pluck('text')->all();

    expect($texts)
        ->toContain($visibleItem->text)
        ->not->toContain($hiddenSectionItem->text)
        ->not->toContain($foreignItem->text);

    $this->actingAs($user)
        ->getJson(route('teacher.question-bank.show', [
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'item' => $foreignItem->id,
        ]))
        ->assertNotFound();
});

it('lists only quizzes from the current class context for question bank imports', function () {
    [$user, $teacher, $section, $subject, $schoolYear] = createTeacherQuestionBankContext();

    $visibleQuiz = createQuizForQuestionBuilder($section, $subject);
    $visibleQuiz->update(['title' => 'Visible Import Quiz']);

    $otherSection = SectionModel::factory()->create([
        'school_year_id' => $schoolYear->id,
        'start_year' => $schoolYear->start_year,
        'end_year' => $schoolYear->end_year,
    ]);

    $sameSubjectOtherSectionQuiz = Quiz::create([
        'title' => 'Other Section Quiz',
        'grading_period' => '1st',
        'description' => 'Should not be listed.',
        'questions' => [],
        'setting' => ['pagination' => 10],
        'section_model_id' => $otherSection->id,
        'subject_model_id' => $subject->id,
        'status' => 'draft',
        'visible' => false,
        'is_open' => false,
        'accepting' => false,
        'max_attempt' => 1,
        'total_score' => 0,
    ]);

    $otherSubject = SubjectModel::factory()->create();
    $sameSectionOtherSubjectQuiz = Quiz::create([
        'title' => 'Other Subject Quiz',
        'grading_period' => '1st',
        'description' => 'Should not be listed.',
        'questions' => [],
        'setting' => ['pagination' => 10],
        'section_model_id' => $section->id,
        'subject_model_id' => $otherSubject->id,
        'status' => 'draft',
        'visible' => false,
        'is_open' => false,
        'accepting' => false,
        'max_attempt' => 1,
        'total_score' => 0,
    ]);

    $response = $this
        ->actingAs($user)
        ->getJson(route('teacher.question-bank.quizzes', [
            'section_id' => $section->id,
            'subject_id' => $subject->id,
        ]));

    $titles = collect($response->json('data'))->pluck('title')->all();

    expect($titles)
        ->toContain($visibleQuiz->title)
        ->not->toContain($sameSubjectOtherSectionQuiz->title)
        ->not->toContain($sameSectionOtherSubjectQuiz->title);
});

it('imports selected saved questions into the chosen quiz as copied quiz questions', function () {
    [$user, $teacher, $section, $subject] = createTeacherQuestionBankContext();

    $quiz = createQuizForQuestionBuilder($section, $subject);

    $existingQuestion = $quiz->questions()->create([
        'order' => 1,
        'page_hint' => 1,
        'type' => 'multiple_choice',
        'text' => 'Existing quiz question?',
        'option' => ['A', 'B'],
        'answer_key' => ['A'],
        'required' => true,
        'points' => 2,
        'setting' => null,
    ]);

    $quiz->update([
        'questions' => [$existingQuestion->id],
        'total_score' => 2,
    ]);

    $tag = Tag::create([
        'name' => 'Imported Skill',
        'slug' => Str::slug('Imported Skill'),
        'category' => 'Skill',
    ]);

    $subjectScopedItem = QuestionBankItem::factory()->create([
        'created_by_teacher_id' => $teacher->id,
        'subject_model_id' => $subject->id,
        'section_model_id' => null,
        'type' => 'multiple_choice',
        'text' => 'Imported subject question?',
        'option' => ['Choice A', 'Choice B'],
        'answer_key' => ['Choice A'],
        'default_points' => 3,
    ]);
    $subjectScopedItem->tags()->sync([
        $tag->id => [
            'relevance_level' => 'high',
            'coverage_ratio' => null,
        ],
    ]);

    $sectionScopedItem = QuestionBankItem::factory()->create([
        'created_by_teacher_id' => $teacher->id,
        'subject_model_id' => $subject->id,
        'section_model_id' => $section->id,
        'type' => 'true_false',
        'text' => 'Imported section question?',
        'option' => null,
        'answer_key' => [true],
        'default_points' => 4,
    ]);

    $response = $this
        ->actingAs($user)
        ->postJson(route('teacher.question-bank.import-to-quiz', [
            'section_id' => $section->id,
            'subject_id' => $subject->id,
        ]), [
            'quiz_id' => $quiz->id,
            'item_ids' => [$subjectScopedItem->id, $sectionScopedItem->id],
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.imported_count', 2)
        ->assertJsonPath('data.quiz.id', $quiz->id);

    $quiz->refresh();
    $importedQuestions = $quiz->questions()->orderBy('order')->get();

    expect($importedQuestions)->toHaveCount(3);
    expect($importedQuestions->pluck('text')->all())
        ->toBe([
            'Existing quiz question?',
            'Imported subject question?',
            'Imported section question?',
        ]);
    expect($importedQuestions->pluck('order')->all())->toBe([1, 2, 3]);
    expect((int) $quiz->total_score)->toBe(9);
    expect($quiz->questions)->toHaveCount(3);

    $importedMultipleChoice = $importedQuestions->firstWhere('text', 'Imported subject question?');
    $this->assertDatabaseHas('taggables', [
        'tag_id' => $tag->id,
        'taggable_type' => Question::class,
        'taggable_id' => $importedMultipleChoice->id,
        'relevance_level' => 'high',
    ]);
});

it('stores and lists shared question bank items on the dedicated subject routes', function () {
    [$user, $teacher, $section, $subject] = createTeacherQuestionBankContext();

    $sectionScopedItem = QuestionBankItem::factory()->create([
        'created_by_teacher_id' => $teacher->id,
        'subject_model_id' => $subject->id,
        'section_model_id' => $section->id,
        'text' => 'Section-only question',
    ]);

    $storeResponse = $this
        ->actingAs($user)
        ->postJson(route('teacher.question-bank.subject.store', [
            'subject_id' => $subject->id,
        ]), [
            'type' => 'multiple_choice',
            'text' => 'Shared subject question',
            'option' => ['North', 'South'],
            'answer_key' => ['0'],
            'default_points' => 2,
            'required' => true,
        ]);

    $storeResponse
        ->assertCreated()
        ->assertJsonPath('data.scope', 'subject')
        ->assertJsonPath('data.text', 'Shared subject question');

    $itemId = (int) $storeResponse->json('data.id');

    $this->assertDatabaseHas('question_bank_items', [
        'id' => $itemId,
        'created_by_teacher_id' => $teacher->id,
        'subject_model_id' => $subject->id,
        'section_model_id' => null,
        'text' => 'Shared subject question',
    ]);

    $response = $this
        ->actingAs($user)
        ->getJson(route('teacher.question-bank.subject.index', [
            'subject_id' => $subject->id,
        ]));

    $texts = collect($response->json('data'))->pluck('text')->all();

    $response->assertOk();
    expect($texts)
        ->toContain('Shared subject question')
        ->not->toContain($sectionScopedItem->text);
});

it('lists quizzes across assigned sections for the dedicated subject question bank and imports into the chosen quiz', function () {
    [$user, $teacher, $section, $subject, $schoolYear] = createTeacherQuestionBankContext();

    $otherAssignedSection = SectionModel::factory()->create([
        'school_year_id' => $schoolYear->id,
        'start_year' => $schoolYear->start_year,
        'end_year' => $schoolYear->end_year,
    ]);
    $otherAssignedSection->subjects()->attach($subject->id);

    SubjectTeacher::create([
        'teacher_model_id' => $teacher->id,
        'section_model_id' => $otherAssignedSection->id,
        'subject_model_id' => $subject->id,
    ]);

    $currentSectionQuiz = createQuizForQuestionBuilder($section, $subject);
    $currentSectionQuiz->update(['title' => 'Current Section Quiz']);

    $otherSectionQuiz = createQuizForQuestionBuilder($otherAssignedSection, $subject);
    $otherSectionQuiz->update(['title' => 'Other Section Quiz']);

    $otherSubject = SubjectModel::factory()->create();
    $otherSubjectQuiz = Quiz::create([
        'title' => 'Other Subject Quiz',
        'grading_period' => '1st',
        'description' => 'Should not be listed.',
        'questions' => [],
        'setting' => ['pagination' => 10],
        'section_model_id' => $otherAssignedSection->id,
        'subject_model_id' => $otherSubject->id,
        'status' => 'draft',
        'visible' => false,
        'is_open' => false,
        'accepting' => false,
        'max_attempt' => 1,
        'total_score' => 0,
    ]);

    $quizzesResponse = $this
        ->actingAs($user)
        ->getJson(route('teacher.question-bank.subject.quizzes', [
            'subject_id' => $subject->id,
        ]));

    $quizData = collect($quizzesResponse->json('data'));
    $titles = $quizData->pluck('title')->all();
    $sectionNamesByTitle = $quizData->mapWithKeys(fn (array $quiz) => [
        $quiz['title'] => data_get($quiz, 'section.name'),
    ]);

    $quizzesResponse->assertOk();

    expect($titles)
        ->toContain($currentSectionQuiz->title)
        ->toContain($otherSectionQuiz->title)
        ->not->toContain($otherSubjectQuiz->title);
    expect($sectionNamesByTitle->get($otherSectionQuiz->title))->toBe($otherAssignedSection->section_name);

    $sharedItem = QuestionBankItem::factory()->create([
        'created_by_teacher_id' => $teacher->id,
        'subject_model_id' => $subject->id,
        'section_model_id' => null,
        'type' => 'short_answer',
        'text' => 'Shared import question',
        'answer_key' => ['Shared answer'],
        'default_points' => 5,
    ]);

    $importResponse = $this
        ->actingAs($user)
        ->postJson(route('teacher.question-bank.subject.import-to-quiz', [
            'subject_id' => $subject->id,
        ]), [
            'quiz_id' => $otherSectionQuiz->id,
            'item_ids' => [$sharedItem->id],
        ]);

    $importResponse
        ->assertOk()
        ->assertJsonPath('data.imported_count', 1)
        ->assertJsonPath('data.quiz.id', $otherSectionQuiz->id)
        ->assertJsonPath('data.quiz.section.name', $otherAssignedSection->section_name);

    expect($otherSectionQuiz->fresh()->questions()->pluck('text')->all())
        ->toContain('Shared import question');
});

it('returns complete saved question and quiz lists for the subject modal selectors', function () {
    [$user, $teacher, $section, $subject] = createTeacherQuestionBankContext();

    foreach (range(1, 16) as $index) {
        QuestionBankItem::factory()->create([
            'created_by_teacher_id' => $teacher->id,
            'subject_model_id' => $subject->id,
            'section_model_id' => null,
            'text' => "Shared subject question {$index}",
            'is_archived' => false,
        ]);
    }

    foreach (range(1, 13) as $index) {
        $quiz = createQuizForQuestionBuilder($section, $subject);
        $quiz->update([
            'title' => "Modal Quiz {$index}",
        ]);
    }

    $itemsResponse = $this
        ->actingAs($user)
        ->getJson(route('teacher.question-bank.subject.index', [
            'subject_id' => $subject->id,
            'all' => 1,
        ]));

    $itemsResponse
        ->assertOk()
        ->assertJsonCount(16, 'data')
        ->assertJsonPath('meta.last_page', 1)
        ->assertJsonPath('meta.total', 16);

    $quizzesResponse = $this
        ->actingAs($user)
        ->getJson(route('teacher.question-bank.subject.quizzes', [
            'subject_id' => $subject->id,
            'all' => 1,
        ]));

    $quizzesResponse
        ->assertOk()
        ->assertJsonCount(13, 'data')
        ->assertJsonPath('meta.last_page', 1)
        ->assertJsonPath('meta.total', 13);
});

it('renders the dedicated teacher question bank page for an assigned subject', function () {
    [$user, $teacher, $section, $subject] = createTeacherQuestionBankContext();
    $subject->update([
        'subject_name' => 'Science',
        'grade_level' => '7-A',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('teacher.questions', [
            'subject_id' => $subject->id,
        ]));

    $response
        ->assertOk()
        ->assertSee($subject->subject_name, false)
        ->assertDontSee('Science - 7-A', false)
        ->assertSee($section->section_name, false)
        ->assertDontSee('Reusable saved questions shared across every class you handle for the selected subject.', false)
        ->assertDontSee('Question Library', false)
        ->assertDontSee('Question CRUD', false)
        ->assertSee('Select Subject', false)
        ->assertDontSee('Saved questions created here are shared across all of your classes for this subject.', false)
        ->assertDontSee('Shared across 1 assigned section.', false)
        ->assertDontSee('Import Into Existing Quiz', false)
        ->assertDontSee('Select Saved Questions', false)
        ->assertDontSee('Select Quiz', false)
        ->assertSee('window.__questionBankConfig', false);
});

it('redirects the legacy class question bank route to the dedicated subject page', function () {
    [$user, $teacher, $section, $subject] = createTeacherQuestionBankContext();

    $this->actingAs($user)
        ->get(route('teacher.questions.legacy', [
            'section_id' => $section->id,
            'subject_id' => $subject->id,
        ]))
        ->assertRedirect(route('teacher.questions', [
            'subject_id' => $subject->id,
        ]));
});

it('exposes question bank import controls on quiz add and edit question pages', function () {
    [$user, $teacher, $section, $subject] = createTeacherQuestionBankContext();
    $quiz = createQuizForQuestionBuilder($section, $subject);

    $addResponse = $this
        ->actingAs($user)
        ->get(route('teacher.quiz.add', [
            'section' => $section->id,
            'subject' => $subject->id,
            'quiz' => $quiz->id,
        ]));

    $addResponse
        ->assertOk()
        ->assertSee('Import Questions', false)
        ->assertSee('questionBankRoutes', false)
        ->assertSee('/question-bank', false);

    $editResponse = $this
        ->actingAs($user)
        ->get(route('teacher.quiz.edit', [
            'section' => $section->id,
            'subject' => $subject->id,
            'quiz' => $quiz->id,
        ]));

    $editResponse
        ->assertOk()
        ->assertSee('Import Questions', false)
        ->assertSee('questionBankRoutes', false)
        ->assertSee('/question-bank', false);
});
