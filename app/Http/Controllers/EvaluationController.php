<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesTagMetrics;
use App\Models\Evaluation;
use App\Models\SectionModel;
use App\Models\SectionSubject;
use App\Models\SubjectModel;
use App\Services\SectionStudentRoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\EvaluationScore;
use App\Models\Tag;

class EvaluationController extends Controller
{
    use HandlesTagMetrics;
    /**
     * Display a listing of the resource.
     */
    

public function storeEvaluation(Request $request)
{
    $request->validate([
        'section_subject_id' => 'required|exists:section_subject,id',
        'title' => 'required|string|max:255',
        'grading_period' => 'required|in:1st,2nd,3rd,4th',
        'classwork_type' => 'required|exists:subject_grading,id',
        'description' => 'nullable|string',
    ]);

    try {

        // 🔥 Get next position within same section_subject + grading period
        $maxPosition = Evaluation::where('section_subject_id', $request->section_subject_id)
            ->where('grading_period', $request->grading_period)
            ->max('position');

        $evaluation = Evaluation::create([
            'section_subject_id' => $request->section_subject_id,
            'title' => $request->title,
            'description' => $request->description ?? null,
            'time_posted' => now(),
            'visible' => 0,
            'grading_period' => $request->grading_period,
            'classwork_type' => $request->classwork_type,
            'total_score' => 0,

            // ✅ NEW
            'position' => ($maxPosition ?? 0) + 1,
        ]);

        return back()->with('success', 'Evaluation created successfully!');

    } catch (\Exception $e) {
        Log::error("Store Evaluation Error: " . $e->getMessage());

        return back()->with('error', $e->getMessage());
    }
}


public function updateEvaluation(Request $request, $section_id, $subject_id, $evaluation_id)
{
    $sectionSubject = \App\Models\SectionSubject::where('section_model_id', $section_id)
        ->where('subject_model_id', $subject_id)
        ->firstOrFail();

    $evaluation = \App\Models\Evaluation::where('id', $evaluation_id)
        ->where('section_subject_id', $sectionSubject->id)
        ->firstOrFail();

    $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'total_score' => 'required|integer|min:0',
        'visible' => 'required|boolean',
        'tag_ids' => 'nullable|array',
        'tag_ids.*' => 'integer|exists:tags,id',
        'tag_relevance' => 'nullable|array',
        'tag_relevance.*' => 'nullable|string',
        'tag_coverage' => 'nullable|array',
        'tag_coverage.*' => 'nullable',
    ]);

        $evaluation->update([
            'title' => $request->title,
            'description' => $request->description,
            'total_score' => $request->total_score,
            'visible' => $request->visible,
        ]);
        $tagIds = array_values(array_unique(array_map('intval', $request->input('tag_ids', []))));
        $tagSyncPayload = $this->buildTagSyncPayload(
            $tagIds,
            (array) $request->input('tag_relevance', []),
            (array) $request->input('tag_coverage', [])
        );
        $evaluation->tags()->sync($tagSyncPayload);

        return back()->with('success', 'Evaluation updated successfully!');
}


    /**
     * Show the form for creating a new resource.
     */
    public function viewEvaluation($section_id, $subject_id, $evaluation_id)
    {
        // Load section + subject
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);

        // Load the section_subject pivot entry
        $sectionSubject = \App\Models\SectionSubject::where('section_model_id', $section_id)
            ->where('subject_model_id', $subject_id)
            ->firstOrFail();

        // Load the specific evaluation
        $evaluation = \App\Models\Evaluation::where('id', $evaluation_id)
            ->where('section_subject_id', $sectionSubject->id)
            ->firstOrFail();
            
        //Load students + Scores
        $students = app(SectionStudentRoster::class)
            ->studentsQuery($section)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
        $scores = \App\Models\EvaluationScore::where('evaluation_id', $evaluation->id)
            ->get()
            ->keyBy('student_id');
        return view('pages.teacher.pages.viewEvaluation', [
            'title' => $subject->subject_name . ' - ' . $section->section_name . ' Evaluation',
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => false,
            'sidebar' => true,

            'section' => $section,
            'subject' => $subject,
            'sectionSubject' => $sectionSubject,
            'evaluation' => $evaluation,
            'students' => $students,
            'scores' => $scores,

        ]);
    }

    public function evaluationSettings($section_id, $subject_id, $evaluation_id)
    {
        // Load section + subject
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);

        // Load the section_subject pivot entry
        $sectionSubject = \App\Models\SectionSubject::where('section_model_id', $section_id)
            ->where('subject_model_id', $subject_id)
            ->firstOrFail();

        // Load the specific evaluation
        $evaluation = \App\Models\Evaluation::where('id', $evaluation_id)
            ->where('section_subject_id', $sectionSubject->id)
            ->firstOrFail();

        $tags = Tag::query()
            ->orderBy('name')
            ->get(['id', 'name', 'category']);
        $tagCategories = Tag::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->values();
        $assignedTags = $evaluation->tags()
            ->orderBy('name')
            ->get(['tags.id', 'tags.name', 'tags.category']);
        $assignedTagIds = $assignedTags->pluck('id')->all();

        return view('pages.teacher.pages.EvaluationSettings', [
            'title' => $subject->subject_name . ' - ' . $section->section_name . ' Evaluation Settings',
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => false,
            'sidebar' => true,

            'section' => $section,
            'subject' => $subject,
            'sectionSubject' => $sectionSubject,
            'evaluation' => $evaluation,
            'tags' => $tags,
            'tagCategories' => $tagCategories,
            'assignedTags' => $assignedTags,
            'assignedTagIds' => $assignedTagIds,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function deleteEvaluation($section_id, $subject_id, $evaluation_id)
    {
        $sectionSubject = SectionSubject::where('section_model_id', $section_id)
            ->where('subject_model_id', $subject_id)
            ->firstOrFail();

        $evaluation = Evaluation::where('id', $evaluation_id)
            ->where('section_subject_id', $sectionSubject->id)
            ->firstOrFail();

        $evaluation->tags()->detach();
        $evaluation->delete();

        return redirect()
            ->route('teacher.classwork.view', [$section_id, $subject_id])
            ->with('success', 'Evaluation deleted successfully!');
    }


    public function saveScores(Request $request, $section_id, $subject_id, $evaluation_id)
    {
        $sectionSubject = \App\Models\SectionSubject::where('section_model_id', $section_id)
            ->where('subject_model_id', $subject_id)
            ->firstOrFail();

        $evaluation = \App\Models\Evaluation::where('id', $evaluation_id)
            ->where('section_subject_id', $sectionSubject->id)
            ->firstOrFail();

        $scores = $request->input('scores', []);

        foreach ($scores as $studentId => $score) {
            EvaluationScore::updateOrCreate(
                [
                    'evaluation_id' => $evaluation->id,
                    'student_id' => $studentId,
                ],
                [
                    'score' => (int) $score,
                ]
            );
        }

        return back()->with('success', 'Scores saved successfully!');
    }

    public function updateEvaluationTags(Request $request, $section_id, $subject_id, $evaluation_id)
    {
        $sectionSubject = SectionSubject::where('section_model_id', $section_id)
            ->where('subject_model_id', $subject_id)
            ->firstOrFail();

        $evaluation = Evaluation::where('id', $evaluation_id)
            ->where('section_subject_id', $sectionSubject->id)
            ->firstOrFail();

        $validated = $request->validate([
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'tag_relevance' => 'nullable|array',
            'tag_relevance.*' => 'nullable|string',
            'tag_coverage' => 'nullable|array',
            'tag_coverage.*' => 'nullable',
        ]);

        $tagIds = array_values(array_unique(array_map('intval', $validated['tag_ids'] ?? [])));
        $tagSyncPayload = $this->buildTagSyncPayload(
            $tagIds,
            (array) $request->input('tag_relevance', []),
            (array) $request->input('tag_coverage', [])
        );
        $evaluation->tags()->sync($tagSyncPayload);

        return response()->json([
            'success' => true,
            'tag_ids' => $tagIds,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
