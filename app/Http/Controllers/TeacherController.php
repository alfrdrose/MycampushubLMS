<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use Illuminate\Http\Request;
use App\Models\TeacherModel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $faculty = $request->input('faculty');
        $status = $request->input('status');

        $teachers = TeacherModel::query()
            ->with('user')
            ->when($search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orwhere('last_name', 'like', "%{$search}%")
                        ->orwhere('faculty', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($faculty, function ($query, $faculty) {
                $query->where('faculty', $faculty);
            })
            ->when(
                in_array($status, [UserStatus::Active->value, UserStatus::Inactive->value], true),
                function ($query) use ($status) {
                    $query->whereHas('user', function ($userQuery) use ($status) {
                        $userQuery->where('status', $status);
                    });
                }
            )
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        $columns = Schema::getColumnListing('teacher_models');
        $exclude = ['section','created_at', 'updated_at', 'section_id'];
        $columns = array_values(array_diff($columns, $exclude));
        $columns[] = 'status';

        return view('components.teachers.teachersContent', [
            'title' => 'My Campus LMS | Teachers',
            'preloader' => false,
            'darkmode'  => true,
            'scrollup'  => true,
            'sidebar'   => true,
            'banner'    => false,
            'teachers'     => $teachers,
            'columns'   => $columns,
            'faculty'    => $faculty,
            'status' => $status,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.admin.dashboard.addteacher', [
        'title' => 'My Campus LMS | Add Teacher',
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255'],
            'faculty' => [
                'required',
                Rule::in([
                    'english',
                    'mathematics',
                    'science',
                    'filipino',
                    'ap',              // Araling Panlipunan
                    'mapeh',
                    'esp',             // Values Education
                    'tle',             // TLE / ICT
                    'shs_core',        // Senior High Core
                    'shs_specialized', // Senior High Specialized
                ]),
            ],
            'user_id'    => ['nullable', 'numeric', 'exists:users,id'],
        ]
    
    );

        TeacherModel::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'faculty'     => $request->faculty,
            'user_id'    => $request->user_id,
        ]);

        return redirect()
        ->route('admin.teachers')
        ->with('success', 'Teacher added successfully!');
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
        $teacher = TeacherModel::findOrFail($id);

        return view('components.teachers.updateteacherContent', [
            'title' => 'My Campus LMS | Update Teacher',
            'preloader' => false,
            'darkmode'  => true,
            'scrollup'  => true,
            'sidebar'   => true,
            'banner'    => false,
            'student'   => $teacher,
            'teacher'   => $teacher, // Note: Added this line to pass the teacher data to the view
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $teacher = TeacherModel::with('user')->findOrFail($id);

        // Validate inputs
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'faculty' => [
                'required',
                Rule::in([
                    'english',
                    'mathematics',
                    'science',
                    'filipino',
                    'ap',              // Araling Panlipunan
                    'mapeh',
                    'esp',             // Values Education
                    'tle',             // TLE / ICT
                    'shs_core',        // Senior High Core
                    'shs_specialized', // Senior High Specialized
                ]),
            ],
            'status' => ['nullable', Rule::in([UserStatus::Active->value, UserStatus::Inactive->value])],
        ]);

        $teacher->first_name = $validated['first_name'];
        $teacher->last_name = $validated['last_name'];
        $teacher->faculty = $validated['faculty'];

        \DB::transaction(function () use ($teacher, $validated): void {
            $teacher->save();

            if ($teacher->user && array_key_exists('status', $validated) && $validated['status'] !== null) {
                $teacher->user->status = $validated['status'];
                $teacher->user->save();
            }
        });

        return redirect()
            ->route('admin.teachers')
            ->with('success', 'Teacher updated successfully!');
    }

    public function toggleStatus(string $id)
    {
        $teacher = TeacherModel::with('user')->findOrFail($id);

        abort_unless($teacher->user, 422, 'This teacher has no linked login account.');

        $teacher->user->status = $teacher->user->hasInactiveTeacherStatus()
            ? UserStatus::Active->value
            : UserStatus::Inactive->value;
        $teacher->user->save();

        return response()->json([
            'success' => true,
            'status' => $teacher->user->statusValue(),
            'label' => ucfirst($teacher->user->statusValue()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $teacher = TeacherModel::findOrFail($id);

        \DB::transaction(function () use ($teacher) {
            $user = $teacher->user_id ? \App\Models\User::find($teacher->user_id) : null;
            $teacher->delete();
            if ($user) {
                $user->delete();
            }
        });

            return response()->json([
                'success' => true,
                'message' => 'Teacher deleted successfully!'
            ]);
    }


}
