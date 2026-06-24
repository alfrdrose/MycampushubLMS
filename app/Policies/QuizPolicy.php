<?php

namespace App\Policies;

use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuizPolicy
{
    public function view(User $user, Quiz $quiz): bool
    {
        if ($user->hasRole('teacher')) {
            return true;
        }

        return $quiz->status === QuizStatus::OPEN;
    }

    public function attempt(User $user, Quiz $quiz): bool
    {
        if ($user->hasRole('teacher')) {
            return true;
        }

        return $user->hasRole('student')
            && $quiz->status === QuizStatus::OPEN;
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->hasRole('teacher');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return false;
    }

    public function restore(User $user, Quiz $quiz): bool
    {
        return false;
    }

    public function forceDelete(User $user, Quiz $quiz): bool
    {
        return false;
    }
}
