<?php

namespace App\Http\Controllers;

use App\Ai\Agents\SuggestSmallestStep;
use App\Enums\BehaviorType;
use App\Models\Habit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Der kleinste nächste Schritt, von Claude formuliert.
 *
 * Zwei Momente, dieselbe Frage: beim Anlegen einer Gewohnheit („womit fängt
 * das an?") und mitten im Tag, wenn der vorbereitete Schritt sich immer noch
 * zu groß anfühlt („noch kleiner").
 *
 * Beide Antworten sind bewusst reines JSON und keine Inertia-Seite: sie
 * erscheinen innerhalb eines Ablaufs, ohne ihn zu verlassen.
 */
class SmallestStepController extends Controller
{
    /**
     * Vorschläge für eine Gewohnheit, die es noch nicht gibt.
     *
     * Deshalb kommen Titel, Richtung und Situation aus dem Request und nicht
     * aus der Datenbank — im Wizard ist noch nichts gespeichert.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'behavior_type' => ['required', Rule::enum(BehaviorType::class)],
            'trigger_situation' => ['nullable', 'string', 'max:120'],
        ]);

        return $this->answer(new SuggestSmallestStep(
            title: $validated['title'],
            behaviorType: BehaviorType::from($validated['behavior_type']),
            situation: $validated['trigger_situation'] ?? null,
        ));
    }

    /**
     * Ein noch kleinerer Schritt zu einer bestehenden Gewohnheit.
     *
     * Das Ergebnis wird nicht gespeichert: es gilt für diesen einen Moment.
     * Der vorbereitete Schritt an der Gewohnheit bleibt, wie er ist — sonst
     * würde ein schwacher Tag die Planung dauerhaft nach unten ziehen.
     */
    public function smaller(Request $request, Habit $habit): JsonResponse
    {
        Gate::authorize('complete', $habit);

        $validated = $request->validate([
            'current' => ['nullable', 'string', 'max:160'],
        ]);

        return $this->answer(new SuggestSmallestStep(
            title: $habit->title,
            behaviorType: $habit->behavior_type,
            situation: $habit->trigger_situation,
            tooBig: $validated['current'] ?? $habit->smallest_step,
        ));
    }

    /**
     * Fragt den Agenten und übersetzt einen Ausfall in eine ruhige Absage.
     *
     * Bewusst ohne Ersatzvorschläge: was als KI-Vorschlag aussieht, muss auch
     * einer sein. Ein erfundener Schritt wäre bequemer, würde aber genau die
     * Vertrauensfrage verspielen, um die es bei diesem Feature geht.
     *
     * 503 statt 500, weil der Fehler vorübergehend ist — die Oberfläche bietet
     * daraufhin einen neuen Versuch an.
     */
    private function answer(SuggestSmallestStep $agent): JsonResponse
    {
        try {
            return response()->json(['steps' => $agent->suggest()]);
        } catch (Throwable $exception) {
            Log::warning('Vorschlag für den kleinsten Schritt fehlgeschlagen.', [
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Die Vorschläge lassen sich gerade nicht laden.',
            ], 503);
        }
    }
}
