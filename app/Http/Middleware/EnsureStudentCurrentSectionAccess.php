<?php

namespace App\Http\Middleware;

use App\Models\SchoolYear;
use App\Models\SectionModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentCurrentSectionAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $section = $this->resolveSection($request);
        if (! $section) {
            return $next($request);
        }

        $student = $request->user()?->student;
        $activeSchoolYearId = SchoolYear::activeId();

        if (! $student || ! $activeSchoolYearId) {
            abort(403, 'No school year is currently active.');
        }

        if ((int) $student->section_id !== (int) $section->id) {
            abort(403, 'You are not enrolled in this section.');
        }

        if ((int) $section->school_year_id !== $activeSchoolYearId) {
            abort(403, 'This section is not accessible in the current school year.');
        }

        return $next($request);
    }

    private function resolveSection(Request $request): ?SectionModel
    {
        foreach (['section', 'section_id', 'sectionId', 'section_model_id'] as $parameter) {
            $value = $request->route($parameter);

            if ($value instanceof SectionModel) {
                return $value;
            }

            if (is_numeric($value)) {
                return SectionModel::find((int) $value);
            }
        }

        return null;
    }
}
