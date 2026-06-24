<?php

namespace App\Http\Middleware;

use App\Models\SchoolYear;
use App\Models\SectionModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminCurrentSectionAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $section = $this->resolveSection($request);
        if (! $section) {
            return $next($request);
        }

        $activeSchoolYearId = SchoolYear::activeId();

        if (! $activeSchoolYearId) {
            if ($this->allowsHistoricalReadAccess($request, $section)) {
                return $next($request);
            }

            abort(404, 'No school year is currently active.');
        }

        if ((int) $section->school_year_id !== $activeSchoolYearId) {
            if ($this->allowsHistoricalReadAccess($request, $section)) {
                return $next($request);
            }

            abort(404, 'This section is not available in the active school year.');
        }

        return $next($request);
    }

    private function allowsHistoricalReadAccess(Request $request, SectionModel $section): bool
    {
        return $this->isSafeReadRequest($request)
            && $this->selectedSchoolYearId($request) === (int) $section->school_year_id;
    }

    private function isSafeReadRequest(Request $request): bool
    {
        return in_array($request->method(), ['GET', 'HEAD'], true);
    }

    private function selectedSchoolYearId(Request $request): ?int
    {
        $selectedSchoolYear = $request->query('school_year');

        if (! is_numeric($selectedSchoolYear)) {
            return null;
        }

        return (int) $selectedSchoolYear;
    }

    private function resolveSection(Request $request): ?SectionModel
    {
        foreach (['section', 'id', 'section_id', 'sectionId', 'section_model_id'] as $parameter) {
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
