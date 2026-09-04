<?php

namespace App\Console\Commands;

use App\Actions\RestoreDisplacedHabits;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Holt geparkte Gewohnheiten zurück, deren alter Platz wieder frei ist.
 *
 * Der Anlass ist das Semesterende: Sind die Kurse vorbei, gilt der
 * Stundenplan nicht mehr, und was er verdrängt hatte, darf zurück — ohne dass
 * jemand einen Kurs löschen müsste. Läuft täglich; die Rechnung selbst steht
 * in {@see RestoreDisplacedHabits} und holt nur, was wirklich frei ist.
 */
class RestoreParkedHabits extends Command
{
    protected $signature = 'habits:restore-displaced';

    protected $description = 'Holt geparkte Gewohnheiten zurück, deren alter Platz wieder frei ist';

    public function handle(RestoreDisplacedHabits $restore): int
    {
        $restored = 0;

        User::query()
            ->whereHas('habits', fn ($query) => $query->whereNotNull('displaced_at'))
            ->each(function (User $user) use ($restore, &$restored): void {
                $restored += count($restore->handle($user));
            });

        $this->info(sprintf('%d Gewohnheit(en) zurückgeholt.', $restored));

        return self::SUCCESS;
    }
}
