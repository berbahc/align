<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

/**
 * Ein Kurs gehört dem, dem sein Semester gehört.
 *
 * Der Weg führt über die Beziehung, weil `courses` keine `user_id` trägt: Ein
 * zweiter Besitzvermerk wäre eine zweite Wahrheit, und die beiden könnten
 * auseinanderlaufen.
 */
class CoursePolicy
{
    public function update(User $user, Course $course): bool
    {
        return $user->id === $course->semester->user_id;
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->id === $course->semester->user_id;
    }
}
