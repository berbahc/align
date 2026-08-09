<?php

namespace App\Http\Controllers;

use App\Models\AppointmentNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AppointmentNoticeController extends Controller
{
    /**
     * Gelesen — und damit fort.
     *
     * Es gibt kein „gesehen"-Feld: Eine Zeile, die nach dem Lesen bleibt, wäre
     * der Anfang genau der Historie, die §9 ausschließt. Wegklicken löscht.
     */
    public function destroy(AppointmentNotice $appointmentNotice): RedirectResponse
    {
        Gate::authorize('delete', $appointmentNotice);

        $appointmentNotice->delete();

        return back();
    }
}
