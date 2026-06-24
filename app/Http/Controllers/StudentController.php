<?php

namespace App\Http\Controllers;

use App\Models\StudentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $grade = $request->input('grade');

        $students = StudentModel::query()
            ->with('currentEnrollment.schoolYear')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orwhere('last_name', 'like', "%{$search}%")
                        ->orwhere('grade', 'like', "%{$search}%")
                        ->orwhere('guardian_email', 'like', "%{$search}%");
                });
            })
            ->when($grade, function ($query, $grade) {
                $query->where('grade', $grade);
            })
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        $columns = Schema::getColumnListing('student_models');
        $exclude = ['section_id', 'created_at', 'updated_at'];
        $columns = array_values(array_diff($columns, $exclude));
        $columns[] = 'school_year';

        return view('components.students.studentsContent', [
            'title'     => 'My Campus LMS | students',
            'preloader' => false,
            'darkmode'  => true,
            'scrollup'  => true,
            'sidebar'   => true,
            'banner'    => false,
            'students'  => $students,
            'columns'   => $columns,
            'grade'     => $grade,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.admin.dashboard.addstudent', [
            'title'     => 'My Campus LMS | Add Student',
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
    $validated = $request->validate([
        'first_name'      => ['required', 'string', 'max:255'],
        'last_name'       => ['required', 'string', 'max:255'],
        'email'           => ['required', 'email', 'max:255'],
        'guardian_email'  => ['nullable', 'email', 'max:255'],
        'grade' => [
            'required',
            Rule::in(['1','2','3','4','5','6','7','8','9','10','11','12']),
        ],
        'user_id'         => ['nullable', 'numeric', 'exists:users,id'],
    ]);

    StudentModel::create([
        'first_name'     => $validated['first_name'],
        'last_name'      => $validated['last_name'],
        'email'          => $validated['email'],
        'guardian_email' => $validated['guardian_email'] ?? null,
        'grade'          => $validated['grade'],
        'user_id'        => $validated['user_id'] ?? null,
    ]);

    return redirect()
        ->route('admin.students')
        ->with('success', 'Student added successfully!');
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
        $student = StudentModel::findOrFail($id);

        return view('components.students.updatestudentContent', [
            'title'     => 'My Campus LMS | Update Student',
            'preloader' => false,
            'darkmode'  => true,
            'scrollup'  => true,
            'sidebar'   => true,
            'banner'    => false,
            'student'   => $student,
        ]);
    }

    public function enrollment(Request $request, string $id)
    {
        $student = StudentModel::query()
            ->with([
                'currentEnrollment.schoolYear',
                'currentEnrollment.section',
                'enrollments.schoolYear',
                'enrollments.section',
                'enrollments.previousEnrollment.section',
            ])
            ->findOrFail($id);

        $enrollments = $student->enrollments
            ->sortByDesc(function ($enrollment) {
                $schoolYear = $enrollment->schoolYear;

                return sprintf(
                    '%04d%04d%010d',
                    $schoolYear?->start_year ?? 0,
                    $schoolYear?->end_year ?? 0,
                    $enrollment->id
                );
            })
            ->values();

        return view('components.students.enrollmentContent', [
            'title' => 'My Campus LMS | Student Enrollment',
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'sidebar' => true,
            'banner' => false,
            'student' => $student,
            'enrollments' => $enrollments,
            'studentManagementQuery' => $request->only(['search', 'grade', 'page']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
{
    try {
        $student = StudentModel::findOrFail($id);

        $validated = $request->validate([
            'first_name'      => ['required', 'string', 'max:255'],
            'last_name'       => ['required', 'string', 'max:255'],
            'guardian_email'  => ['nullable', 'email', 'max:255'],
            'grade' => [
                'required',
                Rule::in(['1','2','3','4','5','6','7','8','9','10','11','12']),
            ],
        ]);

        $student->first_name     = $validated['first_name'];
        $student->last_name      = $validated['last_name'];
        $student->guardian_email = $validated['guardian_email'] ?? null;
        $student->grade          = $validated['grade'];

        $student->save();

        return redirect()
            ->route('admin.students')
            ->with('success', 'Student updated successfully!');

    } catch (\Throwable $e) {
        dd($e);
    }
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $student = StudentModel::findOrFail($id);
        \DB::transaction(function () use ($student) {
            $user = $student->user_id ? \App\Models\User::find($student->user_id) : null;
            $student->delete();
            if ($user) {
                $user->delete();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully!'
        ]);
    }

    public function attendance()
    {
        $user = auth()->user();
        $student = $user->student;

        if (!$user->isStudent()) {
            abort(403, 'Access denied.');
        }

        $attendanceRecords = $student->attendanceRecords()
            ->with('attendance')
            ->orderBy('attendance_date', 'desc')
            ->get();

        return view('pages.student.attendance', [
            'attendanceRecords' => $attendanceRecords,
            'title'     => 'My Attendance',
            'preloader' => false,
            'darkmode'  => true,
            'scrollup'  => true,
            'sidebar'   => true,
            'banner'    => false,
        ]);
    }
}
