# Align

Eine Web-App, die Studierenden hilft, Gewohnheiten im Uni-Alltag aufzubauen:
Gewohnheiten hängen an Situationen statt an Uhrzeiten, ein Stundenraster zeigt
den Tag, eine KI schlägt den kleinsten ersten Schritt vor — und für einen
verpassten Tag gibt es keine Strafe.

> **Leitfrage des Projekts:** Wie kann eine mobile Applikation Studierende
> durch minimalistisches Design, kontextsensitive KI und subtile
> Gamifizierung dabei unterstützen, nachhaltige Alltagsgewohnheiten zu
> etablieren?

**Laravel 13 · Inertia 3 · React 19 · TypeScript · Tailwind 4 · SQLite ·
Pest 5 · laravel/ai**

---

## Worum es geht

Im Studium gibt es keine feste Tagesstruktur mehr. Vorlesungszeiten wechseln,
Freistunden häufen sich, und gute Vorsätze gehen im Alltag unter. Nicht am
Willen liegt das, sondern daran, dass ein Vorsatz keinen festen Platz im Tag
hat.

Sechs Interviews und eine Online-Umfrage mit **N = 25** Studierenden haben die
Annahmen geprüft. Vier Befunde haben die App geformt:

| Befund                                        | Wert          | Was daraus folgt                                     |
| --------------------------------------------- | ------------- | ---------------------------------------------------- |
| Stress und Prüfungsphase als Grund aufzugeben | **17 von 25** | Kleiner werden statt pausieren                       |
| Schuldgefühl nach einem verpassten Tag        | ø **3,92**    | Keine Strafe, keine Alarmfarbe, keine Streak-Drohung |
| „Kleinster nächster Schritt" als Hilfe        | ø **4,16**    | Die stärkste Einzelfunktion der ganzen Umfrage       |
| Teilen nur mit engen Freunden                 | **21 von 25** | Community leise und freiwillig, nie öffentlich       |

Nur **1 von 25** nutzt bisher überhaupt eine Habit-App — der Mehrwert musste
also begründet werden, nicht vorausgesetzt.

Die vollständige Auswertung liegt in der schriftlichen Projektarbeit.

## Was die App kann

|                  |                                                                                                                                |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| **Auftakt**      | Ein kurzer Film im Onboarding zeigt die drei Probleme und die drei Antworten, bevor die erste Frage kommt                      |
| **Gewohnheiten** | Aus einem Katalog, mit Dauer und einem Platz im Tag — entweder an einer Uhrzeit oder an einer Situation („nach der Vorlesung") |
| **Kalender**     | Monatsansicht und Tagesraster mit Semesterplan; Blöcke lassen sich verschieben, Kollisionen werden verhindert                  |
| **Schlafplan**   | Aufsteh- und Schlafenszeit je Wochentag — der Rahmen, in dem alles andere geplant wird                                         |
| **Community**    | Eine Gewohnheit für einen einzelnen Tag mit einer Person zusammen angehen. Was ihr tut, sieht niemand sonst                    |
| **KI-Assistent** | Vier Fragen ans Modell: kleinster erster Schritt, besserer Anker, Tag neu ordnen, neue Plätze finden                           |
| **Konto**        | Anmeldung, Zwei-Faktor, Passkeys — über Laravel Fortify                                                                        |

### Warum Laravel und nicht React Native

Die schriftliche Projektarbeit beschreibt React Native mit Expo und Supabase.
Gebaut ist eine Web-App mit Laravel und Inertia. Der Wechsel fiel früh: Das
Stundenraster, die Kollisionsprüfung und der Semesterplan sind Serverlogik, und
sie in einer Sprache zu halten war mehr wert als ein App-Store-Paket. Die App
ist mobile-first und läuft im Browser des Telefons.

---

## Voraussetzungen

|          |                           |
| -------- | ------------------------- |
| PHP      | 8.3 oder neuer            |
| Composer | aktuell                   |
| Node     | 20.19 oder neuer, mit npm |

Eine Datenbank ist nicht einzurichten: Das Projekt läuft auf SQLite, und die
Datei legt `migrate` selbst an.

## Loslegen

```bash
git clone https://github.com/berbahc/align.git
cd align
composer setup
```

`composer setup` erledigt alles in einem Zug: Abhängigkeiten installieren,
`.env` aus `.env.example` anlegen, den App-Key erzeugen, migrieren, die
Demo-Daten einspielen und das Frontend bauen.

Danach starten — **einer der beiden Wege genügt**:

```bash
php artisan serve        # → http://localhost:8000
```

```bash
composer dev             # → https://align.test, wenn Laravel Herd läuft
```

`composer dev` ist der Weg, für den das Projekt eingerichtet ist: Herd mit
`~/Herd` als _parked path_, TLD `test` und `herd secure` für das Zertifikat.
Wer Herd nicht hat, nimmt `php artisan serve` — die `.env.example` ist bereits
darauf eingestellt.

### Zwei Zugänge zum Ausprobieren

|                  |                    |            |
| ---------------- | ------------------ | ---------- |
| **Eingerichtet** | `test@example.com` | `password` |
| **Frisch**       | `neu@example.com`  | `password` |

Das erste Konto sieht aus wie nach ein paar Wochen Benutzung: vier
Gewohnheiten, dreißig Tage Verlauf, ein Semester mit Stundenplan, ein
Schlafplan, zwei Bekannte, eine offene Anfrage und eine Verabredung.

Das zweite ist leer und landet im Onboarding — dort läuft der Auftakt.

### KI-Funktionen

Die vier Vorschläge sprechen mit einem echten Modell. **Ohne Schlüssel läuft
die App weiter**, die ✦-Knöpfe antworten dann mit einer ehrlichen Absage statt
mit erfundenen Vorschlägen. In die `.env`:

```
AI_PROVIDER=openrouter
AI_MODEL=anthropic/claude-sonnet-4.6
OPENROUTER_API_KEY=…
```

Jede und jeder braucht einen eigenen Schlüssel. Für Anthropic direkt siehe den
Kommentar in `.env.example`.

---

## Täglich

```bash
git pull
php artisan migrate       # ← nicht überspringen, siehe unten
composer dev
```

### Warum `migrate` nach jedem Pull

Der Code kommt über git, die Datenbank nicht — `database/*.sqlite` steht in
`.gitignore`. Fehlt eine Migration, wirft die App keinen freundlichen Hinweis,
sondern einen 500er:

```
SQLSTATE[HY000]: General error: 1 no such table: habit_day_shifts
```

Das ist der häufigste „bei mir läuft es aber"-Fall in diesem Projekt.

## Was lokal entsteht und nicht in git liegt

| Was                                                   | Wie es entsteht                                                                        |
| ----------------------------------------------------- | -------------------------------------------------------------------------------------- |
| `database/database.sqlite`                            | `php artisan migrate` legt die Datei selbst an                                         |
| `.env`                                                | `composer setup` kopiert `.env.example`; der KI-Schlüssel kommt von Hand dazu          |
| `resources/js/routes`, `.../actions`, `.../wayfinder` | erzeugt Wayfinder beim Start von Vite — also durch `composer dev` oder `npm run build` |
| `public/build`                                        | `npm run build`                                                                        |
| `vendor`, `node_modules`                              | `composer install`, `npm install`                                                      |

Die Wayfinder-Dateien sind der zweite Stolperstein: Ohne sie findet das
Frontend Routen wie `@/routes/calendar` nicht, und der Build bricht ab. Einmal
`composer dev` genügt.

---

## Prüfen

```bash
composer ci:check
```

Ein Befehl für alles: ESLint, Prettier, TypeScript, Pint, PHPStan (Stufe 7)
und **770 Tests** in 48 Dateien. Genau dasselbe läuft bei jedem Push über
GitHub Actions.

Einzeln, wenn etwas klemmt:

```bash
php artisan test                                 # Pest
vendor/bin/pint --dirty                          # Formatierung (PHP)
vendor/bin/phpstan analyse --memory-limit=1G     # statische Analyse
npm run types:check                              # tsc
npm run lint:check                               # ESLint
```

**PHPStan braucht `--memory-limit=1G`** — ohne die Angabe bricht die Analyse ab.

Die Namen der Skripte überschneiden sich unglücklich: `composer lint:check` ist
Pint (PHP), `npm run lint:check` ist ESLint (TypeScript). Dasselbe bei
`types:check` — Composer meint PHPStan, npm meint `tsc`.

Die Tests sprechen **nie** mit der Claude API: `tests/Pest.php` fälscht alle
vier Agenten global und lässt eine unerwartete Anfrage fehlschlagen, statt sie
ins Netz zu schicken. Die Suite läuft also ohne Schlüssel und kostet nichts.

---

## Wo was liegt

```
app/
  Ai/Agents/          Die Claude-Aufrufe, je einer pro Frage
  Support/DayPlan.php Der Tag als Folge belegter und freier Fenster
  Models/Habit.php    Anker, Ketten, Dauer — der Kern der Planung
resources/js/
  pages/              Eine Datei pro Inertia-Seite
  components/         Sheets, Karten, das Stundenraster
  lib/day-grid.ts     Die Rechnung hinter dem Kalender
tests/Feature/        Pest, nach Feature geschnitten
tests/Unit/           Die reine Rechnung, ohne Datenbank
```

## Verlauf des Projekts

[`Dokumentation.md`](Dokumentation.md) erzählt jeden Commit auf Deutsch — was
geändert wurde und warum. Die Datei wird nicht von Hand gepflegt, sondern aus
der Git-Historie erzeugt:

```bash
php artisan dokumentation:generate
```

## Team

Berkay Bahcekapili, Silas und Ngoc Ha. Nicht alle Beiträge stehen in der
Git-Historie — Konzept, Umfrage und Auswertung entstanden außerhalb des
Repositorys.

## Arbeitsweise

Committet wird direkt auf `main`.

Gearbeitet wurde mit KI-Unterstützung (Claude Code). Die Dateien dafür liegen
offen im Repository: `CLAUDE.md` beschreibt das Projekt für den Assistenten,
`.mcp.json` und `boost.json` konfigurieren seine Werkzeuge, und `.claude/skills/`
enthält fremde Anleitungen zu Laravel, Pest und Tailwind. Sie gehören zum
Arbeitsprozess, nicht zur Anwendung — wer den Code liest, kann sie überspringen.
