<?php

namespace App\Http\Controllers;

use App\Actions\CreateHabit;
use App\Actions\RememberSuggestions;
use App\Ai\Agents\SuggestSmallestStep;
use App\Ai\UserContext;
use App\Enums\BehaviorType;
use App\Enums\SuggestionKind;
use App\Models\Habit;
use App\Models\User;
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
 *
 * Beide merken sich außerdem, was vorgeschlagen wurde. Erst dadurch kann der
 * nächste Aufruf etwas anderes anbieten, statt bei null zu beginnen.
 */
class SmallestStepController extends Controller
{
    public function __construct(
        private readonly RememberSuggestions $remember,
    ) {}

    /**
     * Vorschläge für eine Gewohnheit, die es noch nicht gibt.
     *
     * Deshalb kommen Titel, Richtung und Situation aus dem Request und nicht
     * aus der Datenbank — im Wizard ist noch nichts gespeichert. Aus demselben
     * Grund werden die Vorschläge ohne `habit_id` gemerkt:
     * {@see CreateHabit} holt die Verknüpfung nach, wenn einer
     * davon unverändert übernommen wird.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'behavior_type' => ['required', Rule::enum(BehaviorType::class)],
            'trigger_situation' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();

        return $this->answer(
            new SuggestSmallestStep(
                title: $validated['title'],
                behaviorType: BehaviorType::from($validated['behavior_type']),
                situation: $validated['trigger_situation'] ?? null,
                context: UserContext::for($user, SuggestionKind::SmallestStep),
            ),
            $user,
        );
    }

    /**
     * Ein noch kleinerer Schritt zu einer bestehenden Gewohnheit.
     *
     * Das Ergebnis wird nicht an der Gewohnheit gespeichert: es gilt für diesen
     * einen Moment. Der vorbereitete Schritt bleibt, wie er ist — sonst würde
     * ein schwacher Tag die Planung dauerhaft nach unten ziehen.
     *
     * Im Gedächtnis landet er trotzdem. Nicht als Plan, sondern als Beobachtung:
     * Ein Schritt, der zum dritten Mal zerlegt wird, war von Anfang an zu groß,
     * und das ist etwas, das die KI beim nächsten Mal wissen sollte.
     */
    public function smaller(Request $request, Habit $habit): JsonResponse
    {
        Gate::authorize('complete', $habit);

        $validated = $request->validate([
            'current' => ['nullable', 'string', 'max:160'],
        ]);

        $user = $request->user();

        return $this->answer(
            new SuggestSmallestStep(
                title: $habit->title,
                behaviorType: $habit->behavior_type,
                situation: $habit->trigger_situation,
                tooBig: $validated['current'] ?? $habit->smallest_step,
                context: UserContext::for($user, SuggestionKind::SmallestStep, $habit),
            ),
            $user,
            $habit,
        );
    }

    /**
     * Fragt den Agenten, merkt sich die Antwort und übersetzt einen Ausfall in
     * eine ruhige Absage.
     *
     * Bewusst ohne Ersatzvorschläge: was als KI-Vorschlag aussieht, muss auch
     * einer sein. Ein erfundener Schritt wäre bequemer, würde aber genau die
     * Vertrauensfrage verspielen, um die es bei diesem Feature geht.
     *
     * 503 statt 500, weil der Fehler vorübergehend ist — die Oberfläche bietet
     * daraufhin einen neuen Versuch an.
     */
    private function answer(SuggestSmallestStep $agent, User $user, ?Habit $habit = null): JsonResponse
    {
        try {
            $steps = $agent->suggest();
        } catch (Throwable $exception) {
            Log::warning('Vorschlag für den kleinsten Schritt fehlgeschlagen.', [
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Die Vorschläge lassen sich gerade nicht laden.',
            ], 503);
        }

        $this->remember->steps($user, $steps, $habit);

        return response()->json(['steps' => $steps]);
    }
}
