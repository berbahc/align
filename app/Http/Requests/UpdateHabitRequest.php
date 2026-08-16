<?php

namespace App\Http\Requests;

/**
 * Eine bestehende Gewohnheit ändern.
 *
 * Dieselben Felder wie beim Anlegen ({@see HabitFormRequest}), aber ohne die
 * Grenze von fünf aktiven Gewohnheiten: Die bearbeitete Gewohnheit zählt
 * bereits mit, und wer fünf hat, könnte sonst keine davon mehr anfassen.
 *
 * Die Berechtigung prüft der Controller über `HabitPolicy::update` — hier ist
 * die Gewohnheit noch nicht aufgelöst.
 */
class UpdateHabitRequest extends HabitFormRequest {}
