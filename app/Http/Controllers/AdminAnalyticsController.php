<?php

namespace App\Http\Controllers;

use App\Support\AdminGradeAnalytics;
use Illuminate\Http\Request;

class AdminAnalyticsController extends Controller
{
    public function index(Request $request, AdminGradeAnalytics $analytics)
    {
        return view('pages.admin.dashboard.analytics', [
            'title' => 'My Campus LMS | Analytics',
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'sidebar' => true,
            'banner' => false,
            'analytics' => $analytics->build([
                'school_year' => $request->query('school_year'),
                'grading_period' => $request->query('grading_period'),
                'grade_level' => $request->query('grade_level'),
                'section_id' => $request->query('section_id'),
            ]),
        ]);
    }
}
