<?php

namespace App\Http\Controllers;

use App\Actions\CreateHabit;
use App\Http\Requests\AdoptHabitRequest;
use App\Models\AppointmentNotice;
use Illuminate\Http\RedirectResponse;
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
     * die eigenen fünf Plätze, beginnt bei Tag eins und lässt sich vorher in
     * Tagen und Zeitpunkt anpassen. Silas' Lauf um sechs ist selten der eigene.
     * Ohne diese Anpassung wäre die Übernahme meistens unbrauchbar — und ein
     * gemeinsamer Eintrag, den zwei Menschen teilen, wäre der Dauerstatus, den
     * community_feature3.md §6 ausschließt.
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

        return back()->with('success', sprintf('„%s" gehört jetzt auch dir.', $habit->title));
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
}
