<?php

namespace App\Http\Requests;

use App\Models\Course;
use App\Models\Semester;

/**
 * Einen bestehenden Kurs ändern.
 *
 * Dieselben Regeln wie beim Anlegen, mit zwei Unterschieden: Das Semester ist
 * das des Kurses und nicht das laufende, und der Kurs zählt bei der
 * Überschneidungsprüfung nicht gegen sich selbst.
 */
class UpdateCourseRequest extends StoreCourseRequest
{
    public function semester(): ?Semester
    {
        return $this->course()->semester;
    }

    /**
     * Der Kurs aus der Strecke.
     */
    public function course(): Course
    {
        /** @var Course $course */
        $course = $this->route('course');

        return $course;
    }

    protected function ignoredCourseId(): ?int
    {
        return $this->course()->id;
    }
}
