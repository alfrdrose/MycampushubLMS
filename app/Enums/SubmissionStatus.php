<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case NotSubmitted = 'not_submitted';
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Late = 'late';
    case NeedsGrading = 'needs_grading';
    case Graded = 'graded';
    case Resubmitted = 'resubmitted';
    case Overdue = 'overdue';
    case Restricted = 'restricted';
    case Hidden = 'hidden';
}
