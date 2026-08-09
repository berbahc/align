<?php

namespace App\Http\Controllers;

use App\Ai\Agents\SuggestBetterAnchor;
use App\Http\Requests\AdjustHabitRequest;
use App\Models\Habit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
    public function suggestions(Habit $habit): JsonResponse
    {
        Gate::authorize('update', $habit);

        $misses = $this->misses($habit);

        try {
            $alternatives = (new SuggestBetterAnchor(
                habit: $habit,
                misses: $misses,
                otherAnchors: $this->otherAnchors($habit),
            ))->alternatives();
        } catch (Throwable $exception) {
            Log::warning('Vorschlag für einen anderen Zeitpunkt fehlgeschlagen.', [
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Die Vorschläge lassen sich gerade nicht laden.',
            ], 503);
        }

        return response()->json([
            'observation' => $this->observation($habit, $misses),
            'alternatives' => array_map(
                fn (array $alternative): array => [
                    ...$alternative,
                    // Damit die Oberfläche den Block an seine mögliche neue
                    // Stelle legen kann, bevor irgendetwas entschieden ist.
                    'anchorHour' => Habit::anchorHourFor(
                        situation: $alternative['situation'] ?? null,
                        time: $alternative['time'] ?? null,
                    ),
                ],
                $alternatives,
            ),
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

    /**
     * Die Anker der übrigen aktiven Gewohnheiten.
     *
     * Grundlage für einen Ketten-Vorschlag: eine bestehende Gewohnheit ist der
     * zuverlässigste Auslöser, den es gibt (Domino-Prinzip, time-blocking.md).
     *
     * @return list<string>
     */
    private function otherAnchors(Habit $habit): array
    {
        /** @var list<string> $anchors */
        $anchors = $habit->user->habits()
            ->active()
            ->whereKeyNot($habit->getKey())
            ->get()
            ->map(fn (Habit $other): string => $other->title.' → '.$other->scheduleLabel())
            ->values()
            ->all();

        return $anchors;
    }
}
