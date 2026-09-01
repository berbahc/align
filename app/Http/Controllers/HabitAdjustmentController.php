<?php

namespace App\Http\Controllers;

use App\Actions\RememberSuggestions;
use App\Ai\Agents\SuggestBetterAnchor;
use App\Ai\UserContext;
use App\Enums\SuggestionKind;
use App\Http\Requests\AdjustHabitRequest;
use App\Models\AiSuggestion;
use App\Models\Habit;
use App\Models\User;
use App\Support\DayPlan;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

/**
 * Einen Block im Tag verschieben — vorgeschlagen von der KI, entschieden vom Nutzer.
 *
 * Zwei Schritte, bewusst getrennt: erst fragen, dann übernehmen. Dazwischen
 * liegt die Entscheidung, und sie gehört niemand anderem. Die KI ändert nichts
 * von selbst (ki-assistent-design.md §2: „Nie ohne Bestätigung").
 */
class HabitAdjustmentController extends Controller
{
    /**
     * Wie weit die KI zurückschaut, um ihren Vorschlag zu begründen.
     *
     * Zwei Wochen: lang genug, dass ein Muster sichtbar wird, kurz genug, dass
     * es das aktuelle Leben beschreibt und nicht das vom letzten Monat.
     */
    private const int LookbackDays = 14;

    /**
     * Beobachtung und Alternativen.
     *
     * Die Beobachtung steht vor dem Vorschlag — die KI sagt erst, was ihr
     * aufgefallen ist, dann was sie daraus schließt (Transparenz-light). Ohne
     * diesen Satz wäre der Vorschlag eine Ansage aus dem Nichts.
     */
    public function suggestions(Request $request, Habit $habit, RememberSuggestions $remember): JsonResponse
    {
        Gate::authorize('update', $habit);

        $misses = $this->misses($habit);

        try {
            $alternatives = (new SuggestBetterAnchor(
                habit: $habit,
                misses: $misses,
                // Der Rahmen des Tages: Ein Vorschlag außerhalb würde beim
                // Übernehmen abgewiesen — die KI soll ihn deshalb gar nicht
                // erst machen.
                sleepWindows: $request->user()->sleepWindows(),
                // Die freien Momente: Jede Situation trägt genau eine
                // Gewohnheit, und die KI wählt aus dem, was übrig ist — statt
                // sich einen Moment auszudenken, den es im Tag nicht gibt.
                availableSituations: array_column(array_filter(
                    Habit::situationChoicesFor($request->user(), $habit),
                    fn (array $choice): bool => $choice['takenBy'] === null,
                ), 'situation'),
                // Und die Fenster, in die die Dauer wirklich passt.
                freeWindows: $this->freeWindows($request->user(), $habit),
                // Was Align über die Person weiß: ihr Warum, ihr Tagesablauf,
                // ihr Rhythmus — und welche Zeitpunkte sie schon einmal
                // angeboten bekam, ohne sie zu nehmen.
                context: UserContext::for($request->user(), SuggestionKind::Anchor, $habit),
            ))->alternatives();
        } catch (Throwable $exception) {
            Log::warning('Vorschlag für einen anderen Zeitpunkt fehlgeschlagen.', [
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Die Vorschläge lassen sich gerade nicht laden.',
            ], 503);
        }

        // Erst merken, dann ausliefern: Die Oberfläche schickt beim Übernehmen
        // die ID zurück, und ohne sie ließe sich der gewählte Vorschlag später
        // nur über einen Textvergleich erraten.
        $remembered = $remember->anchors($request->user(), $habit, $alternatives);

        return response()->json([
            'observation' => $this->observation($habit, $misses),
            'alternatives' => $remembered->map(
                fn (AiSuggestion $suggestion, int $index): array => [
                    ...$alternatives[$index],
                    'id' => $suggestion->id,
                    // Damit die Oberfläche den Block an seine mögliche neue
                    // Stelle legen kann, bevor irgendetwas entschieden ist.
                    'anchorHour' => Habit::anchorHourFor(
                        situation: $alternatives[$index]['situation'] ?? null,
                        time: $alternatives[$index]['time'] ?? null,
                    ),
                ],
            )->values(),
        ]);
    }

    /**
     * Den gewählten Zeitpunkt übernehmen.
     *
     * Die Rückmeldung trägt den **bisherigen** Anker mit: Gewohnheiten lassen
     * sich in Align sonst nirgends bearbeiten, und ein Weg, der nur vorwärts
     * führt, wäre bei einem Vorschlag der KI die falsche Richtung.
     */
    public function store(AdjustHabitRequest $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('update', $habit);

        $previousLabel = $habit->scheduleLabel();
        $previous = [
            'trigger_situation' => $habit->trigger_situation,
            'scheduled_time' => $habit->scheduled_time?->format('H:i'),
            'scheduled_days' => $habit->scheduled_days,
        ];

        $habit->update($request->anchor());

        // Ohne diesen Eintrag bliebe der Vorschlag im Gedächtnis offen — und
        // die KI würde ihn beim nächsten Mal als „nicht genommen" lesen,
        // obwohl er gerade übernommen wurde.
        $request->acceptedSuggestion()?->markAccepted();

        Inertia::flash('habitAdjusted', [
            'title' => $habit->title,
            'anchor' => $habit->refresh()->scheduleLabel(),
            'previousLabel' => $previousLabel,
            'previous' => array_filter(
                $previous,
                fn (mixed $value): bool => $value !== null,
            ),
            'habitId' => $habit->id,
        ]);

        return back();
    }

    /**
     * Wo im Tag noch Platz für genau diese Gewohnheit ist.
     *
     * Gerechnet für den nächsten Tag, an dem sie ansteht — und ohne sie
     * selbst: Ihr eigener bisheriger Platz ist kein Hindernis, sie soll ja
     * gerade von dort weg.
     *
     * @return list<string>
     */
    private function freeWindows(User $user, Habit $habit): array
    {
        $day = $habit->nextOccurrence() ?? Carbon::today();

        $habits = $user->habits()->active()->get();
        $habits->each(fn (Habit $other) => $other->setRelation('user', $user));

        $plan = DayPlan::for(
            $habits->filter(fn (Habit $other): bool => $other->isScheduledOn($day)),
            $day->dayOfWeekIso,
            $user->sleepWindows(),
        );

        return $plan->freeWindowLabels($habit->durationMinutes() ?? 0, $habit);
    }

    /**
     * Die Tage, an denen die Gewohnheit anstand und nichts geschah.
     *
     * @return list<array{date: string, label: string}>
     */
    private function misses(Habit $habit): array
    {
        $habit->load(['completions' => fn (Relation $query) => $query
            ->where('completed_on', '>=', Carbon::today()->subDays(self::LookbackDays - 1)->startOfDay()),
        ]);

        return $habit->recentMisses(self::LookbackDays);
    }

    /**
     * Der Satz, mit dem die Karte beginnt.
     *
     * Beobachtend, nie wertend: benannt wird, was war, ohne Zahl der Versäumnis
     * und ohne Vorwurf. Gibt es nichts zu beobachten, sagt die Karte das auch —
     * die Frage nach einem besseren Zeitpunkt darf man auch stellen, wenn
     * bisher alles lief.
     *
     * @param  list<array{date: string, label: string}>  $misses
     */
    private function observation(Habit $habit, array $misses): string
    {
        $count = count($misses);

        if ($count === 0) {
            return sprintf('„%s" läuft bisher ohne Ausfall.', $habit->title);
        }

        return sprintf(
            $count === 1
                ? '„%s" stand in den letzten zwei Wochen einmal an, ohne dass etwas geschah.'
                : '„%s" stand in den letzten zwei Wochen %d× an, ohne dass etwas geschah.',
            $habit->title,
            $count,
        );
    }
}
