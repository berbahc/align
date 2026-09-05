<?php

use App\Http\Controllers\AppointmentAvailabilityController;
use App\Http\Controllers\AppointmentCompletionController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentNoticeController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseExceptionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DayOrderController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\HabitAdjustmentController;
use App\Http\Controllers\HabitAdoptionController;
use App\Http\Controllers\HabitCompletionController;
use App\Http\Controllers\HabitController;
use App\Http\Controllers\HabitDayShiftController;
use App\Http\Controllers\HabitGraduationController;
use App\Http\Controllers\HabitReminderController;
use App\Http\Controllers\NewPlaceController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\SleepScheduleController;
use App\Http\Controllers\SmallestStepController;
use App\Http\Middleware\EnsureOnboarded;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Bewusst außerhalb von EnsureOnboarded — sonst leitet die Weiche auf sich selbst.
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
    // Der Rahmen kommt vor der ersten Gewohnheit: Erst wenn der Tag Anfang
    // und Ende hat, gibt es etwas, worin sich planen lässt.
    Route::post('onboarding/sleep', [OnboardingController::class, 'storeSleep'])->name('onboarding.sleep');
    Route::post('onboarding/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');

    Route::middleware(EnsureOnboarded::class)->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        // Zwei Ebenen: Der Monat ist der Einstieg, der Tag die Achse darin.
        // Der Tag steht in der Adresse und nicht in einem Client-Zustand — so
        // übersteht er ein Neuladen und lässt sich teilen.
        Route::get('calendar', [CalendarController::class, 'index'])->name('calendar');

        // Den ganzen Tag neu ordnen — die Frage nach der Einzelanpassung.
        // Der Vorschlag kostet einen KI-Aufruf und wird deshalb gedrosselt.
        Route::post('calendar/order/suggestions', [DayOrderController::class, 'suggestions'])
            ->middleware('throttle:20,1')
            ->name('calendar.order.suggestions');
        Route::post('calendar/order', [DayOrderController::class, 'store'])
            ->name('calendar.order.store');

        // Der Stundenplan hat keine eigene Ansicht: Kurse werden im Monat
        // eingetragen und im Tag angefasst. Hier stehen nur die Daten — das
        // Semester ohne Kennung, weil es je Person genau eins gibt.
        //
        // Vor `calendar/{date}`, wie überall in dieser Datei die festen Worte
        // vor den Platzhaltern. Das Datumsmuster dort ließe „semester" ohnehin
        // nicht durch — die Reihenfolge steht trotzdem, damit sie nicht von
        // einer Regel abhängt, die jemand später lockert.
        Route::post('calendar/semester', [SemesterController::class, 'store'])
            ->name('calendar.semester.store');
        Route::put('calendar/semester', [SemesterController::class, 'update'])
            ->name('calendar.semester.update');
        Route::delete('calendar/semester', [SemesterController::class, 'destroy'])
            ->name('calendar.semester.destroy');

        // Neue Plätze für das, was der Stundenplan verdrängt hat. Der Vorschlag
        // kostet einen KI-Aufruf und wird gedrosselt; das Übernehmen nicht.
        // Die feste Strecke vor `courses/{course}`, wie überall in dieser Datei.
        Route::post('calendar/semester/places/suggestions', [NewPlaceController::class, 'suggestions'])
            ->middleware('throttle:20,1')
            ->name('calendar.semester.places.suggestions');
        Route::post('calendar/semester/places', [NewPlaceController::class, 'store'])
            ->name('calendar.semester.places.store');

        Route::post('calendar/semester/courses', [CourseController::class, 'store'])
            ->name('calendar.semester.courses.store');
        Route::put('calendar/semester/courses/{course}', [CourseController::class, 'update'])
            ->name('calendar.semester.courses.update');
        Route::delete('calendar/semester/courses/{course}', [CourseController::class, 'destroy'])
            ->name('calendar.semester.courses.destroy');

        // Ausfall und Ersatztermin sind eine Tabelle: `POST` setzt die Ausnahme
        // für ein Datum, `DELETE` nimmt sie zurück.
        Route::post('calendar/semester/courses/{course}/exceptions', [CourseExceptionController::class, 'store'])
            ->name('calendar.semester.courses.exceptions.store');
        Route::delete('calendar/semester/courses/{course}/exceptions', [CourseExceptionController::class, 'destroy'])
            ->name('calendar.semester.courses.exceptions.destroy');

        // Zuletzt, wie überall in dieser Datei: Die Strecke ohne festes Wort
        // dahinter würde jedes `calendar/…` darüber schlucken. Das Muster
        // grenzt zusätzlich ein — ein Wort, das kein Datum ist, soll hier
        // nicht ankommen.
        Route::get('calendar/{date}', [CalendarController::class, 'show'])
            ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
            ->name('calendar.day');

        // Der Schlafplan ist der Rahmen des Tages: Aufsteh- und Schlafenszeit
        // je Wochentag, Wecker und die Erinnerung vor der Schlafenszeit.
        Route::get('sleep', [SleepScheduleController::class, 'show'])->name('sleep.show');
        Route::put('sleep', [SleepScheduleController::class, 'update'])->name('sleep.update');

        // Der Freundeskreis ist der Unterbau der Verabredung: Screen 1 aus
        // community_feature3.md wählt aus Personen, die es vorher geben muss.
        Route::get('community', [FriendshipController::class, 'index'])->name('community');

        // Eine Anfrage kostet die andere Seite Aufmerksamkeit. Gedrosselt, weil
        // eine Absage bewusst keine Spur hinterlässt — gegen wiederholtes
        // Fragen schützt die Route, nicht ein Eintrag in der Datenbank.
        Route::post('friendships', [FriendshipController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('friendships.store');
        Route::patch('friendships/{friendship}', [FriendshipController::class, 'update'])
            ->name('friendships.update');
        Route::delete('friendships/{friendship}', [FriendshipController::class, 'destroy'])
            ->name('friendships.destroy');

        Route::put('appointments/availability', AppointmentAvailabilityController::class)
            ->name('appointments.availability');

        // Die Verabredung hängt an der Gewohnheit der fragenden Seite — ihr
        // Anker ist der Zeitpunkt, ihr Titel der Text der Anfrage.
        Route::post('habits/{habit}/appointments', [AppointmentController::class, 'store'])
            ->name('appointments.store');
        Route::patch('appointments/{appointment}', [AppointmentController::class, 'update'])
            ->name('appointments.update');
        Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy'])
            ->name('appointments.destroy');

        // Der Haken der gefragten Seite. Er hängt an der Verabredung und nicht
        // an der Gewohnheit: Die gehört der fragenden Person, und ein Haken
        // dort meldete deren Erfüllung statt der eigenen.
        Route::post('appointments/{appointment}/completion', [AppointmentCompletionController::class, 'store'])
            ->name('appointments.completion.store');
        Route::delete('appointments/{appointment}/completion', [AppointmentCompletionController::class, 'destroy'])
            ->name('appointments.completion.destroy');

        // Die Notiz über eine Absage kennt nur einen Weg: weg. Gelesen heißt
        // gelöscht — ein „gesehen"-Feld wäre der Anfang einer Historie (§9).
        Route::delete('appointment-notices/{appointmentNotice}', [AppointmentNoticeController::class, 'destroy'])
            ->name('appointment-notices.destroy');

        Route::get('habits', [HabitController::class, 'index'])->name('habits.index');
        Route::get('habits/create', [HabitController::class, 'create'])->name('habits.create');
        Route::post('habits', [HabitController::class, 'store'])->name('habits.store');

        // Der Wizard verspricht auf dem letzten Schritt „Du kannst das jederzeit
        // ändern" — hier wird das eingelöst. Angeboten wird der Weg nur im
        // Gewohnheitsverzeichnis: Auf der Übersicht geht es um den heutigen Tag,
        // nicht um die Gewohnheit an sich.
        //
        // Das Formular steht hier, das Speichern dagegen ganz unten: `PUT
        // habits/{habit}` verdeckt sonst `PUT habits/reminders`, genau wie es
        // die Kommentare weiter unten für die anderen festen Strecken
        // beschreiben.
        Route::get('habits/{habit}/edit', [HabitController::class, 'edit'])->name('habits.edit');

        // Übernehmen ist ein Anlegen mit vorbelegten Feldern, kein eigener
        // Gewohnheitstyp — nur der Rückweg ist ein anderer: zurück auf die
        // Seite, auf der gefragt oder abgesagt wurde, statt in den Wizard.
        Route::post('habits/adoptions', [HabitAdoptionController::class, 'store'])
            ->name('habits.adoptions.store');

        // Wie bei den Erinnerungen steht die feste Strecke vor `{habit}`,
        // sonst wird „smallest-step" als Modellschlüssel gelesen.
        //
        // Die beiden fragenden Wege sprechen mit der Claude API und werden
        // deshalb gedrosselt: ein ungebremster Endpunkt zu einem bezahlten
        // Dienst ist eine Rechnung, die jemand anderes schreiben kann.
        Route::post('habits/smallest-step/suggestions', [SmallestStepController::class, 'suggestions'])
            ->middleware('throttle:20,1')
            ->name('habits.smallest-step.suggestions');
        Route::post('habits/{habit}/smallest-step', [SmallestStepController::class, 'smaller'])
            ->middleware('throttle:20,1')
            ->name('habits.smallest-step.smaller');

        // Das Ergebnis behalten. Ohne Drossel, weil hier niemand gefragt wird
        // — geschrieben wird nur, was vorher schon auf dem Bildschirm stand.
        Route::patch('habits/{habit}/smallest-step', [SmallestStepController::class, 'update'])
            ->name('habits.smallest-step.update');

        // Eine Gewohnheit für einen einzigen Tag woanders hinlegen. Drei Verben
        // auf einer Strecke, weil es eine Tabelle ist: `POST` räumt Platz für
        // eine Verabredung, `PUT` verschiebt von Hand im Raster (und darf dabei
        // auch dauerhaft umstellen), `DELETE` nimmt die Ausnahme zurück.
        //
        // Steht bei den festen Strecken vor `{habit}`, aus demselben Grund wie
        // die Erinnerungen darunter.
        Route::post('habits/{habit}/shifts', [HabitDayShiftController::class, 'store'])
            ->name('habits.shifts.store');
        Route::put('habits/{habit}/shifts', [HabitDayShiftController::class, 'move'])
            ->name('habits.shifts.move');
        Route::delete('habits/{habit}/shifts', [HabitDayShiftController::class, 'destroy'])
            ->name('habits.shifts.destroy');

        // Erst fragen, dann übernehmen — dazwischen liegt die Entscheidung.
        // Nur der Vorschlag kostet einen KI-Aufruf und wird gedrosselt.
        Route::post('habits/{habit}/adjustment/suggestions', [HabitAdjustmentController::class, 'suggestions'])
            ->middleware('throttle:20,1')
            ->name('habits.adjustment.suggestions');
        Route::post('habits/{habit}/adjustment', [HabitAdjustmentController::class, 'store'])
            ->name('habits.adjustment.store');

        // Der Sammelschalter steht vor der Einzelroute, sonst liest
        // `{habit}` das Wort „reminders" als Modellschlüssel.
        Route::put('habits/reminders', [HabitReminderController::class, 'updateAll'])
            ->name('habits.reminders.update-all');
        Route::patch('habits/{habit}/reminder', [HabitReminderController::class, 'update'])
            ->name('habits.reminder.update');

        Route::post('habits/{habit}/completions', [HabitCompletionController::class, 'store'])
            ->name('habits.completions.store');
        Route::delete('habits/{habit}/completions', [HabitCompletionController::class, 'destroy'])
            ->name('habits.completions.destroy');

        // Beenden und Wiederaufnehmen als ein Zustand, der gesetzt und
        // zurückgenommen wird — nicht als zwei Aktionen mit eigenen Verben.
        Route::post('habits/{habit}/graduation', [HabitGraduationController::class, 'store'])
            ->name('habits.graduation.store');
        Route::delete('habits/{habit}/graduation', [HabitGraduationController::class, 'destroy'])
            ->name('habits.graduation.destroy');

        // Die beiden Strecken ohne festes Wort dahinter stehen zuletzt: Sie
        // würden jedes `habits/…` schlucken, das über ihnen steht.
        Route::put('habits/{habit}', [HabitController::class, 'update'])->name('habits.update');
        Route::delete('habits/{habit}', [HabitController::class, 'destroy'])->name('habits.destroy');
    });
});

require __DIR__.'/settings.php';
