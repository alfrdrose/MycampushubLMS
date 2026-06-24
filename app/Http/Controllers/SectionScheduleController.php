<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SectionModel;

class SectionScheduleController extends Controller
{
    public function index(SectionModel $section)
    {
        // Load subjects with teachers
        $section->load(['subjects.teachers']);

        /**
         * 🔥 AUTO-CLEANUP:
         * If a subject has a schedule but NO teacher anymore,
         * immediately remove its schedule.
         */
        foreach ($section->subjects as $subject) {
            $hasSchedule =
                $subject->pivot->start_time &&
                $subject->pivot->end_time;

            $hasNoTeacher = $subject->teachers->isEmpty();

            if ($hasSchedule && $hasNoTeacher) {
                $section->subjects()->updateExistingPivot($subject->id, [
                    'days'       => null,
                    'start_time' => null,
                    'end_time'   => null,
                ]);
            }
        }

        // Reload fresh data after cleanup
        $section->load(['subjects.teachers']);

        return view('pages.admin.section.schedule', [
            'title'     => 'Class Schedule – Grade ' . $section->grade_level .
                           ' Section ' . $section->section_rank,
            'section'   => $section,
            'subjects'  => $section->subjects,

            // layout flags
            'preloader' => false,
            'darkmode'  => true,
            'scrollup'  => true,
            'sidebar'   => true,
            'banner'    => false,
        ]);
    }
}
