<?php

namespace App\Http\Controllers;


use App\Enums\Role;
use App\Models\SubjectGrading;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\SubjectModel;
use App\Support\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;


class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
    $search = $request->input('search');
    $status = $request->input('status');

    $subjects = SubjectModel::query()
        ->when($search, function ($query, $search) {
            $query->where(function($q) use ($search) {
                $q->where('subject_name', 'like', "%{$search}%");
            });
        })
        ->when($status, function ($query, $status) {
            $query->where('status', $status);
        })
        ->orderBy('id', 'asc')
        ->paginate(10)
        ->withQueryString();

    $columns = Schema::getColumnListing('subject_models');
    $exclude = ['created_at', 'updated_at'];
    $columns = array_diff($columns, $exclude);
    $columns[] = 'actions';

    return view('components.subjects.subjectsContent', [
        'title' => 'My Campus LMS | Subjects',
        'preloader' => false,
        'darkmode'  => true,
        'scrollup'  => true,
        'sidebar'   => true,
        'banner'    => false,
        'subjects'     => $subjects,
        'columns'   => $columns,
        'status'    => $status,
    ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('components.subjects.addsubjectContent', [
        'title' => 'My Campus LMS | Add Subject',
        'preloader' => false,
        'darkmode'  => true,
        'scrollup'  => true,
        'sidebar'   => true,
        'banner'    => false,
    ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject_name' => ['required', 'string', 'max:255'],
            'grade_level' => ['required', 'integer', 'between:1,12'],
        ], [
            'name.required' => 'Please enter the subject name.',
            'grade_level.between' => 'Please select a valid level.'
        ]
    
    );

        SubjectModel::create([
            'subject_name' => $request->subject_name,
            'grade_level' => $request->grade_level,
        ]);

        return redirect()
        ->route('admin.subjects')
        ->with('success', 'Subject added successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $subject = SubjectModel::findOrFail($id);

        return view('components.subjects.updatesubjectContent', [
            'title' => 'My Campus LMS | Update Subject',
            'preloader' => false,
            'darkmode'  => true,
            'scrollup'  => true,
            'sidebar'   => true,
            'banner'    => false,
            'subject'   => $subject,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
    $subject = SubjectModel::findOrFail($id);

    // Validate inputs
    $validated = $request->validate([
        'name'        => 'required|string|max:255',
        'grade_level' => 'required|integer|min:1'
        
    ]);

    // Update fields
    //notes: $variable->columnname
    $subject->subject_name= $validated['name']; 
    $subject->grade_level = $validated['grade_level'];

    $subject->save();

    return redirect()
        ->route('admin.subjects')
        ->with('success', 'Subject updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $subject = SubjectModel::findOrFail($id);
            $subject->delete();

            return response()->json([
                'success' => true,
                'message' => 'Subject deleted successfully!'
            ]);
    }
    public function grading($id)
    {
        $subject = SubjectModel::findOrFail($id);
        $gradings = SubjectGrading::where('subject_id', $id)->get();

        return view('pages.admin.dashboard.subjectgrading', [
            'title'      => 'My Campus LMS | Subject Grading',
            'preloader'  => false,
            'darkmode'   => true,
            'scrollup'   => true,
            'sidebar'    => true,
            'banner'     => false,
            'subject'    => $subject,
            'gradings'   => $gradings
        ]);
    }
    public function storeGrading(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.classwork_type' => 'required|string|max:255',
            'items.*.percentage' => 'required|numeric|min:0|max:100'
        ]);

        $total = collect($request->items)->sum('percentage');

        if ($total != 100) {
            return response()->json([
                'success' => false,
                'message' => 'Total percentage must equal 100%'
            ], 422);
        }

        // Clear old grading first (optional but recommended)
        SubjectGrading::where('subject_id', $id)->delete();

        foreach ($request->items as $item) {
            SubjectGrading::create([
                'subject_id' => $id,
                'classwork_type' => $item['classwork_type'],
                'percentage' => $item['percentage']
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Grading saved successfully!'
        ]);
    }
    public function clearGrading($id)
    {
        $subject = SubjectModel::findOrFail($id);
        SubjectGrading::where('subject_id', $id)->delete();

        AdminActivityLog::log('deleted', $subject, [
            'module' => 'Grading Configuration',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grading has been cleared'
        ]);
    }


}
