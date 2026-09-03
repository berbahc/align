# Align

Eine mobil-first Web-App, die Studierenden hilft, Alltagsgewohnheiten
aufzubauen: kurze Check-ins, Time-Blocking im Tagesraster, KI-Vorschläge über
die Claude API — und bewusst keine Bestrafung für verpasste Tage.

**Laravel 13 · Inertia + React + TypeScript · Tailwind 4 · shadcn/ui · SQLite ·
laravel/ai**

---

## Voraussetzungen

| | |
|---|---|
| PHP | 8.3 oder neuer (`composer.json` verlangt `^8.3`) |
| Composer | aktuell |
| Node | mit npm; alles Weitere zieht `npm install` |
| Laravel Herd | die lokale Umgebung des Projekts — die App läuft unter `https://align.test` |

Herd ist nicht zwingend, aber der Weg, für den das Projekt eingerichtet ist. Wer
es nutzt: `~/Herd` muss als *parked path* mit TLD `test` konfiguriert sein, und
`herd secure` richtet das Zertifikat für HTTPS ein. **Läuft Herd nicht, ist
`align.test` nicht erreichbar** — erkennbar am `H` in der Menüleiste.

---

## Einmalig einrichten

```bash
git clone https://github.com/berbahc/align.git ~/Herd/align
cd ~/Herd/align

# Die SQLite-Datei liegt nicht in git und muss existieren, bevor migriert wird.
touch database/database.sqlite

composer setup            # install · .env · key:generate · migrate · npm install · build
php artisan migrate --seed
```

`composer setup` legt die `.env` aus `.env.example` an, erzeugt den App-Key,
migriert und baut das Frontend. `--seed` füllt danach ein brauchbares Testkonto:

```
test@example.com / password
```

Es bringt einen Schlafplan und vier Gewohnheiten aus dem Katalog mit — feste
Uhrzeiten und Situationen, über den Tag verteilt.

### KI-Funktionen

Die Vorschläge (anderer Zeitpunkt, kleiner erster Schritt, Tag neu ordnen)
sprechen mit einem echten Modell. Ohne Schlüssel antworten sie mit einer
ehrlichen Absage statt mit erfundenen Vorschlägen — die App läuft also, die
✦-Knöpfe sagen nur nichts Sinnvolles.

In die `.env` eintragen:

```
AI_PROVIDER=openrouter
AI_MODEL=anthropic/claude-sonnet-4.6
OPENROUTER_API_KEY=…
```

Jede und jeder braucht einen **eigenen** Schlüssel. Für Anthropic direkt siehe
den Kommentar dazu in `.env.example`.

---

## Täglich

```bash
git pull
php artisan migrate       # ← nicht überspringen, siehe unten
composer dev
```

`composer dev` startet die Entwicklungsprozesse (Vite und den Rest). Danach:
**https://align.test**

### Warum `migrate` nach jedem Pull

Der Code kommt über git, die Datenbank nicht — `database/*.sqlite` steht in
`.gitignore`. Fehlt eine Migration, wirft die App keinen freundlichen Hinweis,
sondern einen 500er:

```
SQLSTATE[HY000]: General error: 1 no such table: habit_day_shifts
```

Das ist der häufigste „bei mir läuft es aber"-Fall in diesem Projekt.

---

## Was lokal entsteht und nicht in git liegt

Damit klar ist, warum ein frischer Checkout allein nicht reicht:

| Was | Wie es entsteht |
|---|---|
| `database/database.sqlite` | `touch` + `php artisan migrate` |
| `.env` | `composer setup` kopiert `.env.example`; der KI-Schlüssel kommt von Hand dazu |
| `resources/js/routes`, `.../actions`, `.../wayfinder` | erzeugt Wayfinder beim Start von Vite — also durch `composer dev` oder `npm run build` |
| `public/build` | `npm run build` |
| `vendor`, `node_modules` | `composer install`, `npm install` |

Die Wayfinder-Dateien sind der zweite Stolperstein: Ohne sie findet das Frontend
Routen wie `@/routes/calendar` nicht, und der Build bricht ab. Einmal
`composer dev` genügt.

---

## Prüfen, bevor gepusht wird

```bash
vendor/bin/pest                                  # Testlauf
vendor/bin/pint --dirty                          # Formatierung (PHP)
vendor/bin/phpstan analyse --memory-limit=1G     # statische Analyse
npm run types:check                              # tsc
npm run lint:check                               # ESLint
```

Oder alles auf einmal: `composer ci:check`.

**Zwei Dinge, die man wissen muss:**

- **PHPStan braucht `--memory-limit=1G`.** Ohne die Angabe bricht die Analyse ab.
- **PHPStan meldet derzeit 17 Fehler, und die sind alt.** Sie stammen nicht aus
  neuen Änderungen — es ist der Ausgangswert, nicht das Ziel. `composer ci:check`
  schlägt deshalb fehl, obwohl die Tests grün sind. Wer etwas beiträgt, achtet
  darauf, dass die Zahl nicht *steigt*.

Die Namen der Skripte überschneiden sich unglücklich: `composer lint:check` ist
Pint (PHP), `npm run lint:check` ist ESLint (TypeScript). Dasselbe bei
`types:check` — Composer meint PHPStan, npm meint `tsc`.

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
```

## Arbeitsweise

Committet wird direkt auf `main` — kein Feature-Branch, kein PR. Die
Commit-History muss nicht schön sein.
