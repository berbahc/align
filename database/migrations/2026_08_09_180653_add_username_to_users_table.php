<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Ein Handle, unter dem man gefunden wird.
     *
     * Bisher lief das Hinzufügen über die E-Mail-Adresse. Das hatte eine
     * Nebenwirkung, die in AddFriendRequest als offener Punkt vermerkt war:
     * Die Antwort „unter dieser Adresse ist niemand bei Align" bestätigt, ob
     * eine Adresse ein Konto hat. Ein Handle ist als öffentliche Kennung
     * gedacht — dass er existiert, verrät nichts Vergleichbares.
     *
     * Gesucht wird ausschließlich exakt. Keine Teiltreffer, keine
     * Vervollständigung, kein durchblätterbares Verzeichnis: Sonst würde aus
     * dem Handle eine Personensuche, und die widerspricht dem engen Kreis, den
     * die Umfrage trägt (21/25 nur enge Freunde, Rangliste nicht gewünscht).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique()->after('name');
        });

        $this->backfillFromNames();

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable(false)->change();
        });
    }

    /**
     * Bestehende Konten bekommen einen Handle aus ihrem Namen.
     *
     * Niemand soll ausgesperrt sein, nur weil es das Feld vorher nicht gab.
     * Der Wert ist ein Vorschlag, kein Urteil — er lässt sich im Profil ändern.
     */
    private function backfillFromNames(): void
    {
        $taken = [];

        foreach (DB::table('users')->select('id', 'name')->orderBy('id')->get() as $user) {
            $base = Str::of($user->name)
                ->lower()
                ->ascii()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->limit(24, '')
                ->value();

            // Namen aus Zeichen, die nichts übriglassen, brauchen trotzdem
            // einen gültigen Handle.
            if (mb_strlen($base) < 3) {
                $base = 'nutzer'.$user->id;
            }

            $candidate = $base;
            $suffix = 2;

            while (in_array($candidate, $taken, strict: true)) {
                $candidate = $base.$suffix;
                $suffix++;
            }

            $taken[] = $candidate;

            DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
