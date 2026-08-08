<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HabitCompletionController;
use App\Http\Controllers\HabitController;
use App\Http\Controllers\HabitReminderController;
use App\Http\Controllers\OnboardingController;
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
    });
});

require __DIR__.'/settings.php';
