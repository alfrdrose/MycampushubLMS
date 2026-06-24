<?php

namespace App\Http\Controllers\Concerns;

trait HandlesTagMetrics
{
    protected function allowedTagRelevanceLevels(): array
    {
        $levels = config('reviewers.tag_relevance_levels', []);
        if (!is_array($levels) || empty($levels)) {
            return ['very_high', 'high', 'medium', 'low', 'very_low'];
        }

        return array_values(array_unique(array_map(static function ($level) {
            return strtolower(trim((string) $level));
        }, $levels)));
    }

    protected function defaultTagRelevanceLevel(): string
    {
        $default = strtolower(trim((string) config('reviewers.default_tag_relevance', 'medium')));
        if (!in_array($default, $this->allowedTagRelevanceLevels(), true)) {
            return 'medium';
        }

        return $default;
    }

    protected function normalizeTagRelevanceLevel(?string $level): string
    {
        $normalized = strtolower(trim((string) $level));
        if (!in_array($normalized, $this->allowedTagRelevanceLevels(), true)) {
            return $this->defaultTagRelevanceLevel();
        }

        return $normalized;
    }

    protected function fallbackCoverageRatio(int $tagCount = 1): float
    {
        $mode = strtolower(trim((string) config('reviewers.default_tag_coverage_mode', 'equal')));
        if ($mode === 'full') {
            return 1.0;
        }

        return 1.0 / max(1, $tagCount);
    }

    protected function normalizeCoverageRatio($coverage, int $tagCount = 1): float
    {
        if ($coverage === null || $coverage === '') {
            return $this->fallbackCoverageRatio($tagCount);
        }

        $numeric = (float) $coverage;
        if (!is_finite($numeric)) {
            return $this->fallbackCoverageRatio($tagCount);
        }

        // Accept both ratios (0..1) and percentages (0..100) defensively.
        if ($numeric > 1) {
            $numeric /= 100;
        }

        return max(0.0, min(1.0, $numeric));
    }

    protected function normalizeCoveragePercent($coverage): ?float
    {
        if ($coverage === null || $coverage === '') {
            return null;
        }

        $numeric = (float) $coverage;
        if (!is_finite($numeric)) {
            return null;
        }

        return max(0.0, min(100.0, $numeric));
    }

    protected function buildTagSyncPayload(array $tagIds, array $tagRelevance = [], array $tagCoverage = []): array
    {
        $tagIds = array_values(array_unique(array_map('intval', $tagIds)));
        $tagCount = count($tagIds);

        $payload = [];
        foreach ($tagIds as $tagId) {
            $tagIdKey = (string) $tagId;
            $relevanceRaw = $tagRelevance[$tagId] ?? $tagRelevance[$tagIdKey] ?? null;
            $coverageRaw = $tagCoverage[$tagId] ?? $tagCoverage[$tagIdKey] ?? null;

            $payload[$tagId] = [
                'relevance_level' => $this->normalizeTagRelevanceLevel(
                    $relevanceRaw === null ? null : (string) $relevanceRaw
                ),
                'coverage_ratio' => $this->normalizeCoverageRatio($coverageRaw, $tagCount),
            ];
        }

        return $payload;
    }

    protected function resolveTagRelevanceWeight(?string $level): float
    {
        $weights = config('reviewers.tag_relevance_weights', []);
        $defaultLevel = $this->defaultTagRelevanceLevel();
        $normalizedLevel = $this->normalizeTagRelevanceLevel($level);

        $weight = $weights[$normalizedLevel] ?? $weights[$defaultLevel] ?? 1.0;
        $weight = (float) $weight;

        if (!is_finite($weight) || $weight <= 0) {
            return 1.0;
        }

        return $weight;
    }
}
