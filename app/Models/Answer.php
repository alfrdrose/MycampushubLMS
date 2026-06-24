<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = [
        'response_id',
        'question_id',
        'answer'
    ];
    protected $casts = [
        'answer' => 'array',
    ];
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function response()
    {
        return $this->belongsTo(Response::class);
    }

    public function isCorrect()
    {
        if (!$this->question || !$this->question->answer_key) return false;

        if (($this->question->type ?? '') === 'matching') {
            $correct = $this->question->answer_key ?? [];
            if (is_string($correct)) {
                $decoded = json_decode($correct, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $correct = $decoded;
                }
            }
            $correct = is_array($correct) ? array_values($correct) : [];

            $userAnswer = $this->answer ?? [];
            $userAnswer = is_array($userAnswer) ? array_values($userAnswer) : [];

            $pairKey = function ($p) {
                if (!is_array($p)) return '';
                $l = trim((string) ($p['left'] ?? ''));
                $r = trim((string) ($p['right'] ?? ''));
                if ($l === '' || $r === '') return '';
                return $l . '=>' . $r;
            };

            $correctPairs = array_values(array_filter(array_map($pairKey, $correct), fn($v) => $v !== ''));
            $userPairs = array_values(array_filter(array_map($pairKey, $userAnswer), fn($v) => $v !== ''));

            $correctPairs = array_values(array_unique($correctPairs));
            $userPairs = array_values(array_unique($userPairs));

            if (empty($correctPairs)) return false;

            return empty(array_diff($correctPairs, $userPairs)) && empty(array_diff($userPairs, $correctPairs));
        }

        $questionType = (string) ($this->question->type ?? '');
        $normalizeValue = function ($value) use ($questionType) {
            if ($questionType === 'true_false') {
                if ($value === true || $value === 'true' || $value === 1 || $value === '1') {
                    return 'true';
                }
                if ($value === false || $value === 'false' || $value === 0 || $value === '0') {
                    return 'false';
                }
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }
            if (is_numeric($value)) {
                return (string)(0 + $value);
            }
            return trim((string)$value);
        };

        $correct = $this->question->answer_key;
        if (is_string($correct)) {
            $decoded = json_decode($correct, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $correct = $decoded;
            }
        }

        $userAnswer = $this->answer;

        $correctNormalized = is_array($correct) ? array_map($normalizeValue, $correct) : [$normalizeValue($correct)];
        $userNormalized = is_array($userAnswer) ? array_map($normalizeValue, $userAnswer) : [$normalizeValue($userAnswer)];

        $correctNormalized = array_values(array_unique($correctNormalized));
        $userNormalized = array_values(array_unique($userNormalized));

        if (empty($correctNormalized)) return false;

        return empty(array_diff($correctNormalized, $userNormalized)) && empty(array_diff($userNormalized, $correctNormalized));
    }

    public function scoreValue(): int
    {
        $question = $this->question;
        if (!$question) {
            return 0;
        }

        $points = max(0, (int) ($question->points ?? 1));
        $type = (string) ($question->type ?? '');

        if ($type !== 'matching') {
            return $this->isCorrect() ? $points : 0;
        }

        return $this->matchingScore($question, $points);
    }

    private function matchingScore(Question $question, int $points): int
    {
        $options = $question->option ?? [];
        if (is_string($options)) {
            $decoded = json_decode($options, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $options = $decoded;
            }
        }
        $options = is_array($options) ? $options : [];

        $leftPool = [];
        $rightPool = [];

        if (array_key_exists('left', $options) && array_key_exists('right', $options)) {
            $leftPool = is_array($options['left']) ? array_values($options['left']) : [];
            $rightPool = is_array($options['right']) ? array_values($options['right']) : [];
        } elseif (is_array($options)) {
            $pairs = array_values(array_filter($options, fn($p) => is_array($p)));
            $leftPool = array_values(array_unique(array_map(fn($p) => (string) ($p['left'] ?? ''), $pairs)));
            $rightPool = array_values(array_unique(array_map(fn($p) => (string) ($p['right'] ?? ''), $pairs)));
        }

        $leftPool = array_values(array_filter(array_map(fn($v) => trim((string) $v), $leftPool), fn($v) => $v !== ''));
        $rightPool = array_values(array_filter(array_map(fn($v) => trim((string) $v), $rightPool), fn($v) => $v !== ''));

        if (empty($leftPool) || empty($rightPool)) {
            return 0;
        }

        $correctPairs = $question->answer_key ?? [];
        if (is_string($correctPairs)) {
            $decoded = json_decode($correctPairs, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $correctPairs = $decoded;
            }
        }
        $correctPairs = is_array($correctPairs) ? array_values($correctPairs) : [];

        $userPairs = $this->answer ?? [];
        $userPairs = is_array($userPairs) ? array_values($userPairs) : [];

        $correctMap = $this->buildMatchingMap($correctPairs, $leftPool, $rightPool);
        if (empty($correctMap)) {
            return 0;
        }
        $userMap = $this->buildMatchingMap($userPairs, $leftPool, $rightPool);

        $setting = $question->setting ?? [];
        if (is_string($setting)) {
            $decoded = json_decode($setting, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $setting = $decoded;
            }
        }
        $setting = is_array($setting) ? $setting : [];

        $usePairPoints = (bool) ($setting['use_pair_points'] ?? false);
        $pairPoints = $setting['pair_points'] ?? [];
        $pairPoints = is_array($pairPoints) ? array_values($pairPoints) : [];

        $hasPairPoints = false;
        foreach ($pairPoints as $v) {
            if ((int) $v > 0) {
                $hasPairPoints = true;
                break;
            }
        }
        // Backward-compat: older data may have pair_points filled but use_pair_points not persisted.
        if (!$usePairPoints && $hasPairPoints) {
            $usePairPoints = true;
        }

        $correctCount = 0;
        $score = 0;

        foreach ($correctMap as $rowIndex => $colIndex) {
            if (!array_key_exists($rowIndex, $userMap)) {
                continue;
            }
            if ((int) $userMap[$rowIndex] !== (int) $colIndex) {
                continue;
            }

            if ($usePairPoints && $hasPairPoints) {
                $score += max(0, (int) ($pairPoints[$rowIndex] ?? 0));
            } else {
                $correctCount++;
            }
        }

        if ($usePairPoints && $hasPairPoints) {
            return $score;
        }

        if ($correctCount < 1) {
            return 0;
        }

        $perMatch = $points / max(1, count($correctMap));
        return (int) round($correctCount * $perMatch);
    }

    private function buildMatchingMap(array $pairs, array $leftPool, array $rightPool): array
    {
        $map = [];
        foreach ($pairs as $p) {
            if (!is_array($p)) continue;
            $l = trim((string) ($p['left'] ?? ''));
            $r = trim((string) ($p['right'] ?? ''));
            if ($l === '' || $r === '') continue;
            $rowIndex = array_search($l, $leftPool, true);
            $colIndex = array_search($r, $rightPool, true);
            if ($rowIndex === false || $colIndex === false) continue;
            $map[(int) $rowIndex] = (int) $colIndex;
        }
        return $map;
    }
}
