<?php

namespace App\Http\Controllers;

use App\Enums\ScheduleType;
use App\Http\Requests\AddFriendRequest;
use App\Models\Appointment;
use App\Models\AppointmentNotice;
use App\Models\Friendship;
use App\Models\Habit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FriendshipController extends Controller
{
    /**
     * Der Freundeskreis — und was mit ihm gerade ausgemacht ist.
     *
     * Bewusst keine Statusanzeige: community_feature3.md §2 hält fest, dass
     * man nie sieht, was andere tun. Was hier steht, ist entweder ein Name
     * oder eine Verabredung, die beide Seiten selbst getroffen haben.
     *
     * Die Verabredungen standen bislang nur auf der Übersicht. Dieser Bereich
     * verspricht, „mit wem du dich verabreden kannst" — zeigte aber als
     * einziger nicht, mit wem gerade etwas ausgemacht ist.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('community', [
            'appointmentsEnabled' => $user->appointments_enabled,
            'appointmentRequests' => Appointment::pendingFor($user),
            // Für das Übernehmen einer fremden Gewohnheit: dieselbe Wahl wie
            // beim Anlegen, weil es dasselbe Anlegen ist — nur mit vorbelegten
            // Feldern. Ein fremder Zeitpunkt passt selten in den eigenen Tag.
            'scheduleTypes' => ScheduleType::options(),
            'triggerSuggestions' => array_keys(Habit::TriggerSuggestions),
            // Was jemand abgesagt hat — einmal, bis es weggeklickt ist (§5).
            'appointmentNotices' => AppointmentNotice::forUser($user),
            // Anders als die Übersicht bleibt hier stehen, was heute an der
            // eigenen Gewohnheit hängt: Diese Seite hat keine Habit-Zeile, die
            // es sonst trüge. Offene Anfragen an mich fehlen auch hier — sie
            // stehen als Karte mit beiden Knöpfen darüber.
            'upcomingAppointments' => Appointment::upcomingFor($user, Carbon::today())
                ->reject(fn (Appointment $appointment): bool => $appointment->awaitsAnswerFrom($user))
                ->map(fn (Appointment $appointment): array => $appointment->present($user))
                ->values()
                ->all(),
            // Der eigene Handle steht auf der Seite, weil man ihn weitergeben
            // muss, um gefunden zu werden — und ihn sonst nirgends sieht.
            'username' => $user->username,
            'friends' => Friendship::query()
                ->accepted()
                ->involving($user)
                ->with(['requester', 'addressee'])
                ->get()
                ->map(fn (Friendship $friendship): array => $friendship->present($friendship->counterpart($user)))
                ->sortBy('name')
                ->values()
                ->all(),
            'incoming' => Friendship::pendingFor($user),
            'outgoing' => Friendship::query()
                ->pending()
                ->where('requester_id', $user->id)
                ->with('addressee')
                ->get()
                ->map(fn (Friendship $friendship): array => $friendship->present($friendship->addressee))
                ->all(),
        ]);
    }

    /**
     * Eine Anfrage stellen.
     *
     * Liegt bereits eine Anfrage der anderen Seite offen, wird sie angenommen,
     * statt eine zweite in Gegenrichtung anzulegen. Zwei Menschen, die
     * gleichzeitig aneinander denken, sollen nicht in einem Zustand landen, in
     * dem beide auf den jeweils anderen warten.
     */
    public function store(AddFriendRequest $request): RedirectResponse
    {
        $user = $request->user();
        $addressee = $request->addressee();

        $reverse = $user->pendingRequestFrom($addressee);

        if ($reverse !== null) {
            $reverse->accepted_at = now();
            $reverse->save();

            return back()->with('success', sprintf('Ihr seid jetzt verbunden — %s hatte dich schon gefragt.', $addressee->name));
        }

        Friendship::query()->create([
            'requester_id' => $user->id,
            'addressee_id' => $addressee->id,
        ]);

        return back()->with('success', sprintf('%s hat deine Anfrage.', $addressee->name));
    }

    /**
     * Eine Anfrage annehmen.
     */
    public function update(Friendship $friendship): RedirectResponse
    {
        Gate::authorize('accept', $friendship);

        $friendship->accepted_at = now();
        $friendship->save();

        return back()->with('success', sprintf('Ihr seid jetzt verbunden mit %s.', $friendship->requester->name));
    }

    /**
     * Absagen, zurückziehen oder eine Freundschaft beenden.
     *
     * Alle drei löschen denselben Eintrag. Es bleibt keine Notiz darüber, dass
     * jemand abgelehnt hat — eine Absage-Historie wäre bei Schuldgefühl
     * ø 3,92 die schärfste denkbare Bestrafung (community_feature3.md §9).
     */
    public function destroy(Friendship $friendship): RedirectResponse
    {
        Gate::authorize('delete', $friendship);

        $friendship->delete();

        return back();
    }
}
