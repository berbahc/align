<?php

use App\Http\Controllers\DashboardController;
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

        // Noch ohne Daten — sie bekommen einen Controller, sobald sie welche
        // liefern; Route::inertia hält die Platzhalter ehrlich, statt einen
        // leeren Controller vorzutäuschen.
        Route::inertia('journey', 'journey')->name('journey');
        Route::inertia('community', 'community')->name('community');

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
