<?php

namespace App\Http\Controllers;

use App\Actions\CreateHabit;
use App\Http\Requests\AdoptHabitRequest;
use App\Models\Appointment;
use App\Models\AppointmentNotice;
use App\Support\AppointmentFit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class HabitAdoptionController extends Controller
{
    /**
     * Eine fremde Gewohnheit für sich selbst übernehmen.
     *
     * Wer gefragt wird, ob er einen Tag lang mitmacht, sieht dabei eine
     * Gewohnheit, die er selbst nicht führt — und manchmal ist die Antwort
     * weder ja noch nein, sondern „das will ich auch". Bis hierher gab es dafür
     * keinen Weg: Die Verabredung gilt einen Tag, danach ist sie fort, und die
     * Gewohnheit blieb die der anderen Person.
     *
     * Angelegt wird trotzdem eine **eigene**, keine geteilte: Sie zählt gegen
     * die eigenen fünf Plätze, beginnt bei Tag eins und wird im selben
     * Assistenten geplant wie jede andere. Silas' Lauf um sechs ist selten der
     * eigene. Ein gemeinsamer Eintrag, den zwei Menschen teilen, wäre der
     * Dauerstatus, den community_feature3.md §6 ausschließt.
     *
     * Deshalb kein eigener Validierungsweg: Es ist ein ganz normales Anlegen,
     * nur mit vorbelegten Feldern. `StoreHabitRequest` hält damit auch die
     * Grenze von fünf Gewohnheiten serverseitig — sie ließe sich sonst über
     * diesen Weg umgehen.
     */
    public function store(AdoptHabitRequest $request, CreateHabit $createHabit): RedirectResponse
    {
        $habit = $createHabit->handle($request->user(), $request->habitAttributes());

        $this->dismissNotice($request);
        $accepted = $this->answerRequest($request);

        return to_route('dashboard')->with('success', $accepted === null
            ? sprintf('„%s" gehört jetzt auch dir.', $habit->title)
            : $accepted);
    }

    /**
     * Die Absage-Notiz, aus der heraus übernommen wurde, ist beantwortet.
     *
     * Sie stehen zu lassen hieße, dieselbe Frage ein zweites Mal zu stellen —
     * nachdem die Antwort schon in der eigenen Liste steht.
     */
    private function dismissNotice(AdoptHabitRequest $request): void
    {
        $noticeId = $request->integer('notice_id');

        if ($noticeId < 1) {
            return;
        }

        $notice = AppointmentNotice::query()->find($noticeId);

        if ($notice === null) {
            return;
        }

        Gate::authorize('delete', $notice);

        $notice->delete();
    }

    /**
     * Übernehmen ist eine Zusage.
     *
     * Wer die Gewohnheit zu seiner macht, macht an dem gefragten Tag ohnehin
     * mit — die Anfrage ein zweites Mal zu stellen wäre eine Frage, deren
     * Antwort schon in der eigenen Liste steht. Sie verschwindet deshalb nicht
     * still, sondern als das, was sie ist: ein Ja.
     *
     * Nur eine Doppelbuchung geht nicht durch: Steht zur selben Zeit schon
     * etwas Eigenes, bleibt die Anfrage offen — mit ihrem Hinweis und dem Weg,
     * den eigenen Tag dafür einmal umzustellen.
     *
     * @return string|null Die Rückmeldung, wenn es eine Anfrage gab
     */
    private function answerRequest(AdoptHabitRequest $request): ?string
    {
        $appointmentId = $request->integer('appointment_id');

        if ($appointmentId < 1) {
            return null;
        }

        $appointment = Appointment::query()->with(['habit', 'requester'])->find($appointmentId);

        if ($appointment === null) {
            return null;
        }

        Gate::authorize('accept', $appointment);

        $name = $appointment->requester->name;

        if (AppointmentFit::conflict($appointment, $request->user()) !== null) {
            return sprintf(
                'Die Gewohnheit gehört jetzt auch dir. Die Anfrage von %s steht noch offen — um diese Zeit hast du schon etwas vor.',
                $name,
            );
        }

        $appointment->accepted_at = Carbon::now();
        $appointment->save();

        return sprintf(
            'Die Gewohnheit gehört jetzt auch dir — und %s weiß, dass du dabei bist.',
            $name,
        );
    }
}
