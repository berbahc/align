<?php

use App\Support\DayPlan;

/**
 * Die Umrechnung zwischen Uhrzeit und Minute.
 *
 * Sie steht unter jedem Block im Kalender, unter jedem freien Fenster und unter
 * jeder Kollisionsprüfung — und sie ist reine Rechnung ohne Datenbank. Deshalb
 * steht sie hier und nicht in einem Feature-Test: Ein Fehler in dieser Funktion
 * fiele dort erst über drei Ecken auf, und dann als falsch gezeichneter Block.
 */
test('a time of day becomes the minute it starts at', function (string $time, int $minute) {
    expect(DayPlan::toMinutes($time))->toBe($minute);
})->with([
    'Mitternacht' => ['00:00', 0],
    'halb acht' => ['07:30', 450],
    'Mittag' => ['12:00', 720],
    'kurz vor Mitternacht' => ['23:59', 1439],
    // Ohne Minutenteil: Der Schlafplan schreibt Zeiten immer zweistellig, die
    // Eingabe eines Menschen nicht.
    'ohne Minuten' => ['9', 540],
]);

test('a minute becomes the time of day it belongs to', function (int $minute, string $time) {
    expect(DayPlan::toTime($minute))->toBe($time);
})->with([
    'Mitternacht' => [0, '00:00'],
    'halb acht' => [450, '07:30'],
    'kurz vor Mitternacht' => [1439, '23:59'],
]);

/**
 * Über den Tagesrand hinaus wird umgebrochen.
 *
 * Das ist kein Sonderfall, sondern der Regelfall am Abend: Wer um 23:30 ins
 * Bett geht und eine halbe Stunde davor etwas vorhat, landet rechnerisch bei
 * Minute 1440 — und das ist Mitternacht, nicht „24:00".
 */
test('minutes past midnight wrap into the next day', function (int $minute, string $time) {
    expect(DayPlan::toTime($minute))->toBe($time);
})->with([
    'genau Mitternacht' => [1440, '00:00'],
    'eine Stunde danach' => [1500, '01:00'],
    'eine Minute davor' => [-1, '23:59'],
    'eine Stunde davor' => [-60, '23:00'],
]);

test('the two directions are each other reversed', function (string $time) {
    expect(DayPlan::toTime(DayPlan::toMinutes($time)))->toBe($time);
})->with(['00:00', '06:15', '13:45', '23:59']);

/**
 * Freie Fenster als Zeilen für den Prompt.
 *
 * Die KI bekommt den Tag nicht als Zahlen, sondern als Sätze — was hier
 * schiefgeht, steht wörtlich in der Anfrage an das Modell.
 */
test('free windows are written out as readable spans', function () {
    expect(DayPlan::windowLabels([
        ['from' => 420, 'to' => 585],
        ['from' => 705, 'to' => 1020],
    ]))->toBe([
        '07:00 bis 09:45',
        '11:45 bis 17:00',
    ]);
});

test('an empty day produces no spans at all', function () {
    expect(DayPlan::windowLabels([]))->toBe([]);
});
