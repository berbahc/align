<?php

namespace App\Enums;

use App\Models\SleepSchedule;

/**
 * Der feste Gewohnheitskatalog — jede Gewohnheit entsteht aus einer Vorlage.
 *
 * Die freie Eingabe ist bewusst weg. Sie hat die App unscharf gemacht: Für
 * „Treppe statt Aufzug" gibt es keine Uhrzeit, für „Wasser trinken" keine
 * Dauer — beides ließ sich nicht in ein Time-Blocking einbetten, das mit
 * Zeitfenstern plant, und beides zwang die übrigen Features zu Sonderfällen
 * (kein Anker, keine Quote, keine Erinnerung). Der Katalog enthält deshalb
 * nur, was drei Bedingungen erfüllt:
 *
 * 1. **Planbar** — es lässt sich ein Zeitpunkt oder eine Situation festlegen.
 * 2. **Mit Dauer** — es belegt eine Spanne im Tag, keine Gelegenheit.
 * 3. **Am Stück** — es passiert einmal, nicht verteilt über den Tag.
 *
 * Bewusst nicht im Katalog, mit Begründung:
 * - „Treppe statt Aufzug", „eine Station früher aussteigen": ergeben sich,
 *   wann sie wollen — nicht planbar, ohne Dauer.
 * - „Wasser trinken": verteilt sich über den Tag, kein Zeitfenster.
 * - „Zur gleichen Zeit ins Bett": ist kein Katalogeintrag mehr, sondern ein
 *   eigenes Feature — der Schlafplan setzt Aufsteh- und Schlafenszeit als
 *   Rahmen des Tages ({@see SleepSchedule}).
 *
 * Die Dauer ist der Startwert des Steppers, keine Vorgabe: Wer 45 statt 30
 * Minuten läuft, stellt um. Der Titel dagegen steht fest — er gehört zur
 * Vorlage, nicht zur Person, und bleibt dadurch zwischen allen Nutzern
 * vergleichbar (eine Verabredung zu „Joggen gehen" meint bei beiden dasselbe).
 */
enum HabitTemplate: string
{
    // Sport & Bewegung
    case Joggen = 'joggen';
    case Krafttraining = 'krafttraining';
    case Spazieren = 'spazieren';
    case Dehnen = 'dehnen';
    case Fahrrad = 'fahrrad';

    // Uni & Lernen
    case VorlesungNachbereiten = 'vorlesung-nachbereiten';
    case FokussiertLernen = 'fokussiert-lernen';
    case Karteikarten = 'karteikarten';
    case UniTagPlanen = 'uni-tag-planen';

    // Haushalt & Alltag
    case EssenVorkochen = 'essen-vorkochen';
    case Aufraeumen = 'aufraeumen';
    case Fruehstuecken = 'fruehstuecken';
    case Mittagessen = 'mittagessen';
    case Abendessen = 'abendessen';
    case WochePlanen = 'woche-planen';

    // Erholung & Achtsamkeit
    case Meditieren = 'meditieren';
    case Tagebuch = 'tagebuch';
    case Lesen = 'lesen';
    case OfflineAbend = 'offline-abend';

    public function title(): string
    {
        return match ($this) {
            self::Joggen => 'Joggen gehen',
            self::Krafttraining => 'Krafttraining',
            self::Spazieren => 'Spazieren gehen',
            self::Dehnen => 'Dehnen & Mobility',
            self::Fahrrad => 'Fahrrad fahren',
            self::VorlesungNachbereiten => 'Vorlesung nachbereiten',
            self::FokussiertLernen => 'Fokussiert lernen',
            self::Karteikarten => 'Karteikarten wiederholen',
            self::UniTagPlanen => 'Uni-Tag planen',
            self::EssenVorkochen => 'Essen vorkochen',
            self::Aufraeumen => 'Aufräumen',
            self::Fruehstuecken => 'Frühstücken',
            self::Mittagessen => 'Mittagessen',
            self::Abendessen => 'Abendessen',
            self::WochePlanen => 'Woche planen',
            self::Meditieren => 'Meditieren',
            self::Tagebuch => 'Tagebuch schreiben',
            self::Lesen => 'Lesen',
            self::OfflineAbend => 'Bildschirmfreier Abend',
        };
    }

    public function category(): HabitCategory
    {
        return match ($this) {
            self::Joggen,
            self::Krafttraining,
            self::Spazieren,
            self::Dehnen,
            self::Fahrrad => HabitCategory::Sport,

            self::VorlesungNachbereiten,
            self::FokussiertLernen,
            self::Karteikarten,
            self::UniTagPlanen => HabitCategory::Uni,

            self::EssenVorkochen,
            self::Aufraeumen,
            self::Fruehstuecken,
            self::Mittagessen,
            self::Abendessen,
            self::WochePlanen => HabitCategory::Alltag,

            self::Meditieren,
            self::Tagebuch,
            self::Lesen,
            self::OfflineAbend => HabitCategory::Erholung,
        };
    }

    /**
     * Der Verhaltenstyp für die Plateau-Schätzung aus habit-journey.md.
     *
     * Hängt an der Vorlage, nicht an der Kategorie: Lally et al. unterscheiden
     * nach der Art des Verhaltens, nicht nach dem Lebensbereich — „Essen
     * vorkochen" und „Aufräumen" stehen beide unter Alltag, bilden sich aber
     * verschieden schnell.
     */
    public function behaviorType(): BehaviorType
    {
        return match ($this) {
            self::Joggen,
            self::Krafttraining,
            self::Spazieren,
            self::Dehnen,
            self::Fahrrad => BehaviorType::Movement,

            self::VorlesungNachbereiten,
            self::FokussiertLernen,
            self::Karteikarten,
            self::UniTagPlanen,
            self::Lesen => BehaviorType::Learning,

            self::EssenVorkochen,
            self::Fruehstuecken,
            self::Mittagessen,
            self::Abendessen => BehaviorType::Nutrition,

            self::Aufraeumen,
            self::WochePlanen,
            self::Meditieren,
            self::Tagebuch,
            self::OfflineAbend => BehaviorType::Other,
        };
    }

    /**
     * Der Startwert des Dauer-Steppers, in Minuten.
     *
     * Klein angesetzt, wo klein reicht: Ngocanh in der Interviewauswertung —
     * „Ich weiß oft nicht, wo ich anfangen soll, dann werde ich überfordert
     * und fange erst gar nicht an."
     */
    /**
     * Die Tageszeit, zu der die Vorlage gehört — Minuten seit Mitternacht.
     *
     * `null` heißt: Der Tag entscheidet. Eine Spanne steht nur dort, wo das
     * Wort selbst eine Tageszeit trägt — „Frühstück" um 14:00 ist kein
     * Frühstück mehr, „Lesen" um 14:00 ist Lesen. Joggen, Lernen, Meditieren
     * eine Grenze zu geben hieße, etwas zu behaupten, das nicht stimmt, und die
     * KI um eine Wahl zu bringen, die sie besser trifft als eine Tabelle.
     *
     * Die Spanne ist eine Grenze und keine Empfehlung: Wer beim Semesterwechsel
     * einen neuen Platz sucht, sucht ihn nur hierin — sonst landete das
     * Frühstück mittags, nur weil dort Platz ist.
     *
     * Absolut in Uhrzeiten, obwohl der Tag einer Person das nicht ist. Wer um
     * elf aufsteht, frühstückt trotzdem; wie sich Fenster und Wachrahmen dann
     * vertragen, entscheidet der Aufrufer, nicht der Katalog.
     *
     * @return array{from: int, to: int}|null
     */
    public function dayBand(): ?array
    {
        return match ($this) {
            self::UniTagPlanen => ['from' => 300, 'to' => 660],     // 05:00 – 11:00
            self::Fruehstuecken => ['from' => 300, 'to' => 600],    // 05:00 – 10:00
            self::Mittagessen => ['from' => 660, 'to' => 900],      // 11:00 – 15:00
            self::Abendessen => ['from' => 1020, 'to' => 1290],     // 17:00 – 21:30
            self::OfflineAbend => ['from' => 1080, 'to' => 1440],   // 18:00 – 24:00
            default => null,
        };
    }

    public function defaultMinutes(): int
    {
        return match ($this) {
            self::Joggen => 30,
            self::Krafttraining => 45,
            self::Spazieren => 20,
            self::Dehnen => 10,
            self::Fahrrad => 30,
            self::VorlesungNachbereiten => 30,
            self::FokussiertLernen => 25,
            self::Karteikarten => 15,
            self::UniTagPlanen => 10,
            self::EssenVorkochen => 40,
            self::Aufraeumen => 15,
            self::Fruehstuecken => 15,
            self::Mittagessen => 30,
            self::Abendessen => 30,
            self::WochePlanen => 15,
            self::Meditieren => 10,
            self::Tagebuch => 10,
            self::Lesen => 20,
            self::OfflineAbend => 30,
        };
    }
}
