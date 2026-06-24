<?php

namespace App\Http\Controllers;

use App\Models\AnnouncementModel;
use App\Models\AnnouncementReply;
use App\Models\SectionSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->back();
    }

    public function create()
    {
        $sectionSubjects = SectionSubject::all();
        return view('announcements.create', compact('sectionSubjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'section_subject_id' => 'required|integer',
        ]);

        AnnouncementModel::create([
            'title' => $request->title,
            'body' => $request->body,
            'teacher_id' => Auth::user()->teacher->id,
            'section_subject_id' => $request->section_subject_id,
        ]);

        return redirect()->back()->with('success', 'Announcement posted successfully!');
    }

    public function destroy($id)
    {
        $announcement = AnnouncementModel::findOrFail($id);
        $announcement->delete();

        return back()->with('success', 'Announcement deleted successfully!');
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'reply' => 'required|string'
        ]);

        $announcement = AnnouncementModel::with('sectionSubject')->findOrFail($id);
        $user = auth()->user();

        if ($user->hasRole('student')) {
            $student = $user->student;
            $sectionSubject = $announcement->sectionSubject;

            if (
                !$student
                || !$sectionSubject
                || (int) $student->section_id !== (int) $sectionSubject->section_model_id
            ) {
                abort(403, 'You are not allowed to reply to this announcement.');
            }
        }

        AnnouncementReply::create([
            'announcement_id' => $announcement->id,
            'user_id' => auth()->id(),
            'reply' => $request->reply,
        ]);

        return back()->with('success', 'Reply added.');
    }

}
