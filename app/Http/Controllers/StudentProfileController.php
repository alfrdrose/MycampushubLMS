<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StudentProfileController extends Controller
{
    /**
     * Show edit profile page
     */
    public function edit()
    {
        $student = Auth::user()->student;

        abort_if(!$student, 403);

        return view('pages.student.pages.StudentProfile', [
            'title'     => 'Student Profile',
            'student'   => $student,
            'preloader' => false,
            'darkmode'  => true,
            'scrollup'  => true,
            'banner'    => false,
            'sidebar'   => true,
        ]);
    }

    /**
     * Update student profile
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $student = $user->student;

        abort_if(!$student, 403);

        /* =========================================
         | Validation
         ========================================= */
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['nullable', 'min:8', 'confirmed'],
            'profile_image' => ['nullable', 'image', 'max:2048'],
            'remove_profile_image' => ['nullable', 'boolean'],
        ]);

        /* =========================================
         | Update email
         ========================================= */
        if ($user->email !== $validated['email']) {
            $user->email = $validated['email'];
        }

        /* =========================================
         | Update password
         ========================================= */
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        /* =========================================
         | Profile image handling (Media Library)
         ========================================= */

        // Remove image
        if ($request->boolean('remove_profile_image')) {
            $student->clearMediaCollection('profile_image');
        }

        // Upload / replace image
        if ($request->hasFile('profile_image')) {
            $student->clearMediaCollection('profile_image');

            $student
                ->addMediaFromRequest('profile_image')
                ->toMediaCollection('profile_image');
        }

        return back()->with('status', 'Profile updated successfully.');
    }
}
