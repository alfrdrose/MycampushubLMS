<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class QuestionContentNormalizer
{
    public function normalize(array $question, array $rawQuestion = [], string $errorPrefix = 'question'): array
    {
        $type = (string) ($question['type'] ?? '');
        $text = trim((string) ($question['text'] ?? ''));
        $points = isset($question['points']) ? max(0, (int) $question['points']) : 1;

        $options = (isset($question['option']) && is_array($question['option']))
            ? array_values(array_filter($question['option'], fn ($value) => trim((string) $value) !== ''))
            : [];
        $options = array_values(array_map(fn ($value) => trim((string) $value), $options));

        $pairs = (isset($question['pairs']) && is_array($question['pairs']))
            ? array_values($question['pairs'])
            : [];

        $matchLeft = (isset($question['match_left']) && is_array($question['match_left']))
            ? array_values(array_filter($question['match_left'], fn ($value) => trim((string) $value) !== ''))
            : [];
        $matchRight = (isset($question['match_right']) && is_array($question['match_right']))
            ? array_values(array_filter($question['match_right'], fn ($value) => trim((string) $value) !== ''))
            : [];

        $matchLeft = array_values(array_map(fn ($value) => trim((string) $value), $matchLeft));
        $matchRight = array_values(array_map(fn ($value) => trim((string) $value), $matchRight));

        $errors = [];
        if ($text === '') {
            $errors[$this->errorKey($errorPrefix, 'text')] = 'Please enter a question.';
        }

        $answerKey = null;
        $optionPayload = $options;
        $settingPayload = null;

        if ($type === 'multiple_choice') {
            if (count($options) < 2) {
                $errors[$this->errorKey($errorPrefix, 'option')] = 'Multiple choice needs at least 2 options.';
            }

            $normalizedOptions = array_map(fn ($value) => strtolower(trim((string) $value)), $options);
            if (count($normalizedOptions) !== count(array_unique($normalizedOptions))) {
                $errors[$this->errorKey($errorPrefix, 'option')] = 'Multiple choice options cannot be the same.';
            }

            $selectedRaw = $question['answer_key'] ?? [];
            if (is_array($selectedRaw)) {
                $selected = array_values($selectedRaw);
            } elseif ($selectedRaw === null) {
                $selected = [];
            } else {
                $selected = [(string) $selectedRaw];
            }

            $selected = array_values(array_filter($selected, fn ($value) => trim((string) $value) !== ''));
            if (count($selected) < 1) {
                $errors[$this->errorKey($errorPrefix, 'answer_key')] = 'Please select at least 1 correct answer.';
            } else {
                foreach ($selected as $selectedIndex) {
                    $selectedIndex = (int) $selectedIndex;
                    if (!array_key_exists($selectedIndex, $options)) {
                        $errors[$this->errorKey($errorPrefix, 'answer_key')] = 'Selected answer is invalid.';
                        break;
                    }
                }
            }

            $answerKey = array_values(array_unique(array_map(function ($selectedIndex) use ($options) {
                return $options[(int) $selectedIndex] ?? null;
            }, $selected)));
            $answerKey = array_values(array_filter($answerKey, fn ($value) => $value !== null && $value !== ''));
        } elseif ($type === 'true_false') {
            if (!isset($question['answer_key']) || !in_array($question['answer_key'], ['true', 'false'], true)) {
                $errors[$this->errorKey($errorPrefix, 'answer_key')] = 'Please choose True or False.';
            }

            $answerKey = isset($question['answer_key'])
                ? filter_var($question['answer_key'], FILTER_VALIDATE_BOOLEAN)
                : null;
        } elseif ($type === 'numerical') {
            if (isset($question['answer_key']) && $question['answer_key'] !== '' && !is_numeric($question['answer_key'])) {
                $errors[$this->errorKey($errorPrefix, 'answer_key')] = 'Numerical answer must be a number.';
            }

            $answerKey = (isset($question['answer_key']) && is_numeric($question['answer_key']))
                ? (float) $question['answer_key']
                : null;
        } elseif ($type === 'matching') {
            if (count($matchLeft) < 1) {
                $errors[$this->errorKey($errorPrefix, 'match_left')] = 'Matching needs at least 1 left item.';
            }

            if (count($matchRight) < 1) {
                $errors[$this->errorKey($errorPrefix, 'match_right')] = 'Matching needs at least 1 right item.';
            }

            $normalizedLeft = array_map(fn ($value) => strtolower(trim((string) $value)), $matchLeft);
            if (count($normalizedLeft) !== count(array_unique($normalizedLeft))) {
                $errors[$this->errorKey($errorPrefix, 'match_left')] = 'Matching left items cannot be the same.';
            }

            $normalizedRight = array_map(fn ($value) => strtolower(trim((string) $value)), $matchRight);
            if (count($normalizedRight) !== count(array_unique($normalizedRight))) {
                $errors[$this->errorKey($errorPrefix, 'match_right')] = 'Matching right items cannot be the same.';
            }

            $selectedRaw = $rawQuestion['answer_key'] ?? ($question['answer_key'] ?? $pairs);
            $selectedRaw = is_array($selectedRaw) ? array_values($selectedRaw) : [];

            $completePairs = array_filter($selectedRaw, function ($pair) use ($matchLeft, $matchRight) {
                if (!is_array($pair) || !isset($pair['left'], $pair['right'])) {
                    return false;
                }

                $left = trim((string) $pair['left']);
                $right = trim((string) $pair['right']);
                if ($left === '' || $right === '') {
                    return false;
                }

                if (!empty($matchLeft) && !in_array($left, $matchLeft, true)) {
                    return false;
                }

                if (!empty($matchRight) && !in_array($right, $matchRight, true)) {
                    return false;
                }

                return true;
            });

            if (count($completePairs) < 1 && isset($rawQuestion['match_map']) && is_array($rawQuestion['match_map'])) {
                $rebuiltPairs = [];
                foreach ($rawQuestion['match_map'] as $rowIndex => $columnIndex) {
                    $rowIndex = is_numeric($rowIndex) ? (int) $rowIndex : -1;
                    $columnIndex = is_numeric($columnIndex) ? (int) $columnIndex : -1;

                    if ($rowIndex < 0 || $columnIndex < 0) {
                        continue;
                    }

                    if (!array_key_exists($rowIndex, $matchLeft) || !array_key_exists($columnIndex, $matchRight)) {
                        continue;
                    }

                    $left = trim((string) ($matchLeft[$rowIndex] ?? ''));
                    $right = trim((string) ($matchRight[$columnIndex] ?? ''));
                    if ($left === '' || $right === '') {
                        continue;
                    }

                    $rebuiltPairs[] = [
                        'left' => $left,
                        'right' => $right,
                    ];
                }

                $completePairs = $rebuiltPairs;
            }

            if (count($completePairs) < 1 && count($matchLeft) >= 1 && count($matchRight) >= 1) {
                $generatedPairs = [];
                $pairCount = min(count($matchLeft), count($matchRight));

                for ($index = 0; $index < $pairCount; $index++) {
                    $left = trim((string) ($matchLeft[$index] ?? ''));
                    $right = trim((string) ($matchRight[$index] ?? ''));
                    if ($left === '' || $right === '') {
                        continue;
                    }

                    $generatedPairs[] = [
                        'left' => $left,
                        'right' => $right,
                    ];
                }

                $completePairs = $generatedPairs;
            }

            if (count($completePairs) < 1) {
                $errors[$this->errorKey($errorPrefix, 'answer_key')] = 'Please select at least 1 match.';
            }

            $optionPayload = [
                'left' => array_values($matchLeft),
                'right' => array_values($matchRight),
            ];
            $answerKey = array_values($completePairs);

            $pairPointsRaw = $rawQuestion['pair_points'] ?? ($question['pair_points'] ?? []);
            $pairPointsRaw = is_array($pairPointsRaw) ? $pairPointsRaw : [];
            $pairPoints = array_values(array_map(function ($value) {
                $value = (int) $value;
                return $value < 0 ? 0 : $value;
            }, $pairPointsRaw));

            $settingPayload = [
                'use_pair_points' => filter_var(($rawQuestion['use_pair_points'] ?? ($question['use_pair_points'] ?? false)), FILTER_VALIDATE_BOOLEAN),
                'pair_points' => $pairPoints,
            ];
        } elseif (in_array($type, ['essay', 'short_answer'], true)) {
            if (isset($question['answer_key']) && is_array($question['answer_key'])) {
                $errors[$this->errorKey($errorPrefix, 'answer_key')] = 'Answer key must be text.';
            }

            $answerKey = array_key_exists('answer_key', $question)
                ? trim((string) ($question['answer_key'] ?? ''))
                : null;
        } else {
            $answerKey = $question['answer_key'] ?? null;
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'type' => $type,
            'text' => $text,
            'option' => $optionPayload,
            'setting' => $settingPayload,
            'answer_key' => $answerKey,
            'required' => (bool) ($question['required'] ?? false),
            'points' => $points,
        ];
    }

    private function errorKey(string $prefix, string $field): string
    {
        $prefix = trim($prefix);
        if ($prefix === '') {
            return $field;
        }

        return $prefix . '.' . $field;
    }
}
