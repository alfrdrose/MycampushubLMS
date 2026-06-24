<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TeacherProfileController extends Controller
{
    /**
     * Show edit profile page
     */
    public function edit()
    {
        $teacher = Auth::user()->teacher;

        abort_if(!$teacher, 403);

        return view('pages.teacher.pages.TeacherProfile', [
            'title' => 'Teacher Profile',
            'teacher' => $teacher,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => false,
            'sidebar' => true,
        ]);
    }

    /**
     * Update teacher profile
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $teacher = $user->teacher;

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['nullable', 'min:8', 'confirmed'],
            'profile_image' => ['nullable', 'image', 'max:2048'],
        ]);

        // Update email
        if ($user->email !== $validated['email']) {
            $user->email = $validated['email'];
        }

        // Update password
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Profile image
        if ($request->boolean('remove_profile_image')) {
            $teacher->clearMediaCollection('profile');
        }

        if ($request->hasFile('profile_image')) {
            $teacher->clearMediaCollection('profile_picture');

            $teacher
                ->addMediaFromRequest('profile_image')
                ->toMediaCollection('profile_picture');
        }


        return back()->with('status', 'Profile updated successfully.');
    }
}
