<?php

namespace App\Http\Requests\Concerns;

use App\Models\Habit;
use App\Models\SleepSchedule;
use Illuminate\Validation\Validator;

/**
 * Eine feste Uhrzeit muss in den wachen Teil des Tages fallen.
 *
 * Geteilt zwischen dem Formular und der KI-Anpassung, weil beide dieselbe
 * Grenze einhalten müssen: Der Schlafplan ist der Rahmen, in dem geplant wird,
 * und ein Vorschlag der KI darf ihn so wenig überschreiten wie eine Eingabe
 * von Hand.
 */
trait ChecksSleepWindow
{
    /**
     * Prüft die gesendete Uhrzeit gegen den Rahmen jedes gewählten Wochentags.
     *
     * Einzeln je Tag, weil Dienstag und Samstag selten derselbe Tag sind:
     * 6:00 kann werktags im Rahmen liegen und am Wochenende weit davor.
     */
    protected function validateSleepWindow(Validator $validator): void
    {
        if (! $this->filled('scheduled_time')) {
            return;
        }

        $time = $this->string('scheduled_time')->toString();
        $windows = $this->user()->sleepWindows();

        foreach ($this->array('scheduled_days') as $day) {
            $window = $windows[(int) $day] ?? null;

            if ($window === null) {
                continue;
            }

            if (! SleepSchedule::containsTime($window['wakeTime'], $window['bedtime'], $time)) {
                $validator->errors()->add('scheduled_time', sprintf(
                    'Um %s Uhr schläfst du laut deinem Schlafplan (%s: Aufstehen %s, Schlafen %s). Passe die Uhrzeit an — oder deinen Schlafplan.',
                    $time,
                    Habit::WeekdayAbbreviations[(int) $day],
                    $window['wakeTime'],
                    $window['bedtime'],
                ));

                return;
            }
        }
    }
}
