<?php

use App\Http\Controllers\AppointmentAvailabilityController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentNoticeController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\HabitAdjustmentController;
use App\Http\Controllers\HabitCompletionController;
use App\Http\Controllers\HabitController;
use App\Http\Controllers\HabitGraduationController;
use App\Http\Controllers\HabitReminderController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\SmallestStepController;
use App\Http\Middleware\EnsureOnboarded;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Bewusst außerhalb von EnsureOnboarded — sonst leitet die Weiche auf sich selbst.
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
    Route::post('onboarding/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');

    Route::middleware(EnsureOnboarded::class)->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('calendar', CalendarController::class)->name('calendar');

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

        // Die Notiz über eine Absage kennt nur einen Weg: weg. Gelesen heißt
        // gelöscht — ein „gesehen"-Feld wäre der Anfang einer Historie (§9).
        Route::delete('appointment-notices/{appointmentNotice}', [AppointmentNoticeController::class, 'destroy'])
            ->name('appointment-notices.destroy');

        Route::get('habits', [HabitController::class, 'index'])->name('habits.index');
        Route::get('habits/create', [HabitController::class, 'create'])->name('habits.create');
        Route::post('habits', [HabitController::class, 'store'])->name('habits.store');

        // Wie bei den Erinnerungen steht die feste Strecke vor `{habit}`,
        // sonst wird „smallest-step" als Modellschlüssel gelesen.
        //
        // Beide Wege sprechen mit der Claude API und werden deshalb gedrosselt:
        // ein ungebremster Endpunkt zu einem bezahlten Dienst ist eine
        // Rechnung, die jemand anderes schreiben kann.
        Route::post('habits/smallest-step/suggestions', [SmallestStepController::class, 'suggestions'])
            ->middleware('throttle:20,1')
            ->name('habits.smallest-step.suggestions');
        Route::post('habits/{habit}/smallest-step', [SmallestStepController::class, 'smaller'])
            ->middleware('throttle:20,1')
            ->name('habits.smallest-step.smaller');

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

        Route::delete('habits/{habit}', [HabitController::class, 'destroy'])->name('habits.destroy');
    });
});

require __DIR__.'/settings.php';
