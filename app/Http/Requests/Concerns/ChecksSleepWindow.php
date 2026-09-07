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

        $windows = $this->user()->sleepWindows();
        /** @var array<array-key, mixed> $perDay */
        $perDay = $this->array('scheduled_times');

        foreach ($this->array('scheduled_days') as $day) {
            $window = $windows[(int) $day] ?? null;

            if ($window === null) {
                continue;
            }

            // Die Uhrzeit **dieses** Tages: Seit jeder Wochentag eine eigene
            // haben kann, prüft ein einzelner Wert nur noch den Regelfall.
            $eigene = $perDay[(int) $day] ?? $perDay[(string) (int) $day] ?? null;
            $time = is_string($eigene)
                ? $eigene
                : $this->string('scheduled_time')->toString();

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
