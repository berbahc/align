# 7. Phase 4 — Technische Umsetzung

**Zeitraum:** 21. Juli bis 7. September 2026 · **Iterationen 5 und 6**, Betreuungsgespräche
am 10. August und 7. September

Nach der Konzeptphase stand fest, welche Features wir bauen wollten. In dieser Phase haben
wir sie in lauffähige Software übersetzt. Zuerst haben wir die technische Grundlage gebaut,
danach daraus eine Anwendung gemacht, die sich benutzen lässt.

## 7.1 Die Entscheidung für den Tech-Stack

Geplant hatten wir eine native mobile App. Gebaut haben wir eine **mobil-first Web-App mit
Laravel**.

Das ist eine **Entwicklungsentscheidung** und keine Produktstrategie. Align ist ein MVP aus
einem Studienprojekt und kein marktfähiges Produkt, unsere langfristige Produktidee bleibt
die mobile App. Für die Entwicklung sprachen drei Gründe.

**Eine Codebase, kein Freigabeprozess.** Die Anwendung läuft im Browser. Zwischen einer
Änderung und dem Nutzer steht kein Store-Review, es gibt keine getrennten Builds für zwei
Plattformen und keine laufenden Betriebskosten. Für ein Projekt mit mehreren Iterationen
passte das gut, weil wir jeden Stand sofort ansehen konnten.

**Responsivität bleibt überprüfbar.** Align ist mobil-first gedacht, entwickelt haben wir
aber am Laptop. Weil die App im Browser läuft, lässt sich die Mobilansicht direkt in den
Entwicklerwerkzeugen prüfen. Wir haben die Änderung gespeichert, die Gerätebreite
umgeschaltet und das Ergebnis gesehen, ohne Simulator und ohne einen eigenen Build für ein
zweites Gerät. Damit blieb unser Mobile-First-Anspruch während der ganzen Entwicklung
prüfbar und wurde nicht erst am Ende kontrolliert.

**Ein Framework, das viel mitbringt.** Laravel liefert Routing, Validierung,
Authentifizierung und ein ORM aus einer Hand, dazu ein Testgerüst. Die lokale Umgebung über
Laravel Herd lief ohne Konfigurationsaufwand.

Bei der Implementierung haben wir KI-gestützte Entwicklungswerkzeuge eingesetzt. Ohne sie
wäre der Funktionsumfang in der verfügbaren Zeit nicht zustande gekommen. Die fachlichen
Entscheidungen über Datenmodell, Regeln und Interaktionslogik haben wir selbst getroffen und
begründen sie in den folgenden Abschnitten.

## 7.2 Der Stack im Überblick

| Schicht | Technologie | Begründung |
|---|---|---|
| Sprache / Runtime | PHP 8.4, Node 25 | kommen beide von Herd, keine separate Einrichtung |
| Backend | **Laravel 13** | Routing, Validierung, Auth und ORM aus einer Hand |
| Bridge | **Inertia.js 3** | verbindet Controller direkt mit React-Seiten, SPA-Gefühl ohne eigene API-Schicht |
| Frontend | **React 19** + TypeScript 5.7 | Komponenten mit Typprüfung über die gesamte Oberfläche |
| UI | **shadcn/ui** (Radix), **Tailwind CSS 4** | Komponenten liegen als Quelltext im Projekt und sind frei an die Designsprache anpassbar |
| Build | Vite 8 | Hot Reload im Betrieb, gebündelte Assets für die Auslieferung |
| Routen im Frontend | `laravel/wayfinder` | erzeugt TypeScript-Funktionen aus den Laravel-Routen, Tippfehler fallen beim Kompilieren auf |
| Auth | `laravel/fortify` mit Passkeys und 2FA | geprüfter Baustein statt Eigenbau |
| Datenbank | **SQLite** | eine Datei, kein Server; Cache, Queue und Session laufen ebenfalls darüber |
| KI | `laravel/ai` über die **OpenRouter-API** | siehe 7.4 |
| Qualität | Pest 5, Larastan, Pint, ESLint, Prettier | siehe 7.5 |
| Umgebung | Laravel Herd, `https://align.test` | HTTPS lokal, ohne eigene Serverkonfiguration |

## 7.3 Architektur

Ein Aufruf nimmt immer denselben Weg:

```
Browser → Route (routes/web.php) → Controller → Inertia::render() → React-Seite
```

Der Controller lädt die Daten und benennt die React-Seite, die sie darstellen soll. Eine
separate REST-API brauchen wir nicht, weil Inertia die Props direkt übergibt. Jede Entität
besteht aus einem Eloquent-Model und einer Migration, die die Tabelle beschreibt. Eingaben
prüfen Form Requests, Zugriffsrechte regeln Policies, feste Wertebereiche liegen als Enums
vor.

Die Oberfläche baut auf shadcn/ui-Komponenten, die die Design-Tokens aus der zentralen
Stylesheet-Datei tragen. Dort liegen Schrift (Sora) und Farben aus Figma als CSS-Variablen,
darunter `primary` mit `#775A19`. Unsere Designsprache aus Phase 3 ist damit nicht
nachgebaut, sondern als Quelle im Code verankert.

## 7.4 Die KI-Anbindung

Die KI-Funktionen sprechen nicht direkt mit einem Anbieter, sondern über das Laravel-AI-SDK
mit der OpenRouter-API. Anbieter und Modell stehen in der Umgebungskonfiguration und lassen
sich ohne Codeänderung austauschen.

Jede Funktion ist eine eigene Agent-Klasse mit festem Prompt, Zeitlimit und einem
JSON-Schema für die Antwort, etwa der Agent für den kleinsten nächsten Schritt. Die
zugehörigen Routen sind gedrosselt, weil hinter ihnen ein kostenpflichtiger Dienst steht.

Eine Entscheidung ist uns dabei wichtig. Fällt ein Aufruf aus, antwortet die Anwendung mit
einer **ehrlichen Absage** und nicht mit einem regelbasierten Ersatzvorschlag. Ein Vorschlag
soll nur dann als KI-Vorschlag erscheinen, wenn er auch von der KI stammt. Ein Fallback, der
KI nur simuliert, wäre gegenüber dem Nutzer eine Täuschung und im Rahmen dieser Arbeit auch
gegenüber unserer Leitfrage, in der kontextsensitive KI ausdrücklich vorkommt.

## 7.5 Qualitätssicherung

Feature-Tests mit **Pest** decken die zentralen Abläufe ab: Onboarding, Gewohnheiten,
Erinnerungen, KI-Vorschläge, Verabredungen. **Larastan** prüft die Typen im Backend, der
TypeScript-Compiler die im Frontend, **Pint** und **ESLint/Prettier** den Stil. Ein einzelner
Befehl führt alles in einem Durchlauf aus.

---

# Iteration 5 — Aufbau der Anwendung

**2. bis 10. August 2026 · 28 Commits**

Unser Ziel war ein lauffähiger Stand, der die drei Kernfeatures erkennbar abbildet. Er
musste nicht vollständig sein, aber weit genug, um ihn vorführen zu können.

## 7.6 Was entstand

Der erste Commit fiel am **2. August**.

**Grundgerüst und Designsprache (2. bis 4. August).** Zunächst ein Dashboard, das noch mit
Beispieldaten arbeitete. Am 3. August kam der Schritt, der den Rest prägte. Die Designsprache
aus Phase 3 wurde auf die Anwendung angewendet, und das Platzhalter-Widget wich echten
Gewohnheitsdaten. Noch am selben Abend kamen Onboarding, das Abhaken von Gewohnheiten und die
drei Feature-Bereiche dazu.

**Kernfunktionen (8. bis 10. August).** In drei Tagen entstanden feste Uhrzeiten und
Erinnerungen zehn Minuten vor dem Termin, die KI-Anbindung mit dem Vorschlag des kleinsten
nächsten Schritts und der Tageskalender, in dem die KI einen Block verschieben kann. Dazu
kamen der Freundschafts-Layer mit gemeinsam übernommenen Gewohnheiten, die Umschaltung
zwischen Light und Dark Mode und eine Serienzählung, die ein Wochenende und einen verpassten
Tag übersteht. Zuletzt kam die Ansicht dazu, die zeigt, was mit wem verabredet ist, und die
Möglichkeit, eine Verabredung abzusagen, ohne dass eine Lücke zurückbleibt.

**Die Fünf-Gewohnheiten-Grenze.** Am 9. August wurde aus der in Phase 3 belegten Regel eine
Funktion. Gewohnheiten lassen sich beenden, statt bei fünf festzustecken, und die Grenze wird
dort erklärt, wo sie tatsächlich greift, nicht als abstrakte Regel im Onboarding.

Parallel ist ein eigenes Logo entstanden, das wir in einer hellen und einer dunklen Variante
eingebunden haben.

## 7.7 Feedback von Anne

Anne nannte drei Punkte. Die Dokumentation parallel weiterführen, das Design fertigstellen
und dabei priorisieren, die technische Umsetzung weitertreiben.

---

# Iteration 6 — Ausbau und Härtung

**11. August bis 7. September 2026 · 100 Commits**

Unser Ziel stand knapp im Protokoll. Die App sollte intuitiver werden, damit sie nicht selbst
zum Hindernis wird, und das Bestehende sollte so hart werden, dass es in unterschiedlichen
Kontexten zuverlässig funktioniert. Daneben die ehrliche Randbemerkung: *„Schwierigkeit war,
diese beiden Ziele zu vereinbaren."*

Das ist der Kern dieser Iteration. Nach Iteration 5 hatten wir viele Funktionen, sie griffen
aber noch nicht ineinander. Vier Wochen später verstand Align einen Tag als Ganzes. Die
folgenden Abschnitte zeigen, wie wir schrittweise dorthin gekommen sind und warum wir jeweils
geändert haben, was wir geändert haben.

## 7.8 Ordnung im Bestand

Unsere Gewohnheitslisten liefen nach Anlegedatum. Für die Planung eines Tages ist das die
falsche Reihenfolge. Wir haben die Listen deshalb nach dem nächsten Termin sortiert und
innerhalb eines Tages nach der Tageszeit, also nach derselben Achse wie im Kalender.

Die Gewohnheiten-Seite bekam die Zweiteilung „Steht heute an" und „Steht später an", mit
einer Zeile pro Eintrag, wann er das nächste Mal dran ist. Einen Block pro Wochentag haben
wir bewusst weggelassen. Bei höchstens fünf Gewohnheiten wären sieben Überschriften mehr
Gliederung als Inhalt.

Zwei Lücken in unserer Verabredungslogik haben wir geschlossen. Eine abgesagte Verabredung
endete bis dahin im Nichts, nach der Absage blieb nur ein Satz stehen. Neu bietet sie einen
Weg an, je nachdem, wem die Gewohnheit gehört. Man führt sie ohne Begleitung weiter oder
übernimmt sie selbst. Fremde Gewohnheiten lassen sich außerdem übernehmen, ohne dass es erst
einer Absage bedarf. Dabei entsteht eine eigene Gewohnheit. Sie zählt gegen die eigenen fünf
Plätze und beginnt bei Tag eins. Der Warum-Satz und der kleinste Schritt kommen nicht mit,
die gehören zu einer Person und nicht zu einer Gewohnheit.

In derselben Woche ist das **KI-Gedächtnis** entstanden. Die Agenten wissen jetzt, mit wem
sie sprechen, statt jeden Vorschlag im luftleeren Raum zu formulieren.

## 7.9 Die grundlegendste Änderung: Katalog statt freier Eingabe

Bis Ende August konnten Gewohnheiten frei eingegeben werden. Beim wirklichen Benutzen zeigte
sich, dass das nicht trägt. Eine frei formulierte Gewohnheit lässt sich nicht zuverlässig in
einen Tag einplanen, weil ihr die Angaben fehlen, die Planung erst möglich machen. Vor allem
fehlt eine **Dauer**.

Unsere Konsequenz war ein fester **Gewohnheitskatalog**. Aufgenommen wird nur, was drei
Bedingungen erfüllt: planbar sein, eine Dauer haben, am Stück stattfinden. Der Katalog ist in
vier Bereiche des Studienalltags sortiert.

Das ist der Wendepunkt unserer gesamten Umsetzung. **Aus einem Tracker wurde ein
Planungswerkzeug.** Erst dadurch belegt jeder Block eine echte Spanne im Tag, und erst
dadurch weiß eine Kette, wann die vorige Gewohnheit fertig ist. Gleichzeitig ist es unsere
größte bewusste Einschränkung des Funktionsumfangs. Die Begründung dafür steht in
Abschnitt 7.13.

## 7.10 Der Semesterplan

Aus der Competitor-Analyse stammte der Befund, dass keine der acht untersuchten Anwendungen
in Semestern, Prüfungsphasen und Vorlesungsrhythmus denkt (Abschnitt 4.2). Genau das haben
wir Anfang September gebaut und damit das Alleinstellungsmerkmal umgesetzt, das unsere
Analyse gefunden hatte.

Der **Stundenplan blockiert den Tag**, damit die KI nicht in Vorlesungen plant. Der
4. September war mit 27 Commits der dichteste Entwicklungstag des Projekts, und der Weg
dorthin zeigt, wie wir in kleinen Schritten vorgegangen sind:

1. Der Semesterplan bekam zunächst einen **eigenen Tab**.
2. Kurz darauf wurde er in den Kalender gefaltet, denn ein eigener Tab für etwas, das den Tag
   strukturiert, trennt, was zusammengehört.
3. Aus zwei nebeneinander liegenden Kalendern wurde **ein Kalender** mit Monats- und
   Tagesebene.
4. Die Darstellung des Semesters wechselte von einer Kartenliste zu einer **Wochenansicht**,
   weil Studierende ihren Stundenplan in dieser Form gewohnt sind.

Parallel haben wir die **Kollisionsregeln** geschlossen. Eine Gewohnheit, die in einer
Vorlesung landen würde, wird auf jedem Weg abgelehnt, der eine Uhrzeit setzt. Ein Kurs in
einem zukünftigen Semester beansprucht seinen Platz erst, wenn das Semester beginnt. Ein
abgesagter Kurs gibt den Tag zurück.

Eine spätere Verfeinerung ist uns wichtig. Zunächst **lehnte** ein
Kurs eine kollidierende Gewohnheit schlicht ab. Das ist korrekt, aber nicht hilfreich, und es
forderte den Nutzer implizit auf, eine Vorlesung zu verschieben, die er nicht verschieben
kann. Jetzt **parkt** ein Kurs die Gewohnheit unter sich, statt sie abzuweisen.

In dieser Phase ist außerdem die Navigation auf dem Handy an den unteren Rand gewandert, und
die Anwendung bekam eine eigene Kopfzeile für die Mobilansicht.

## 7.11 Der Tagesrahmen

Aufsteh- und Schlafenszeit spannen den Tag auf, in dem alles andere stattfindet. Der Rahmen
ist bewusst **keine Gewohnheit**. Er wird nicht abgehakt, hat keine Serie und keine Quote. Er
beantwortet die Frage, die vor jeder Planung steht, nämlich *wie lang mein Tag überhaupt
ist.*

Er lässt sich tageweise anpassen, weil ein Samstag anders aussieht als ein Dienstag. Bewegt
sich der Rahmen, bewegt sich der Tag mit ihm. Gewohnheiten an den Rändern folgen der
Verschiebung, statt außerhalb des Tages liegen zu bleiben.

Rahmen und Inhalt hatten wir zuvor nicht sauber getrennt und an mehreren Stellen im Code
unterschiedlich behandelt. Am 7. September haben wir die Unterscheidung an genau einer Stelle
festgelegt.

## 7.12 Konsistenz statt Prozent

Die Fortschrittsanzeige zählt **Tage statt Prozente** und sagt dazu, welche Tage überhaupt
zählen. Das sind nur die, an denen die Gewohnheit tatsächlich anstand. Eine Gewohnheit, die
montags und mittwochs läuft, wird nicht dafür bestraft, dass sie dienstags nicht stattfand.

Damit war auch der Streak-Befund aus unserer Umfrage (Abschnitt 5.10) umgesetzt. Die
Konsistenzrate liegt als ruhige Kennzahl auf der Übersicht, Serien haben einen eigenen Platz,
und ein Tag wird als Haken dargestellt, gefüllt wenn erledigt und hohl wenn offen. Ein rotes
Kreuz gibt es nicht.

## 7.13 Was bewusst weggelassen wurde

Zu dieser Phase gehört auch, was wir nicht gebaut haben. Vier Entscheidungen haben wir
explizit getroffen und begründet:

| Weggelassen | Begründung |
|---|---|
| **Punktuelle Gewohnheiten** (z. B. Treppe statt Aufzug) | haben keine Dauer und sind damit nicht planbar, sie passen nicht in ein Werkzeug, das einen Tag ordnet |
| **Situative Anker ohne planbare Uhrzeit** (z. B. „nach dem Frühstück") | zu individuell, um daraus eine verlässliche Planung abzuleiten |
| **Eigene Gewohnheiten eintragen** | bewusst auf den vordefinierten Katalog beschränkt, damit wir zuverlässig entwickeln konnten; später erweiterbar |
| **Blocker** als eigene Kategorie für feste Termine | im Rahmen dieser Umsetzung nicht mehr geschafft |

Diese Liste ist die Grundlage des Ausblicks in Kapitel 10.

## 7.14 Wie sich das Datenmodell entwickelte

Die Reihenfolge unserer Datenbank-Migrationen zeichnet den Weg der Anwendung genau nach. Sie
zeigt, dass wir nicht nach einem fertigen Modell gebaut, sondern schrittweise erweitert
haben. Jede Tabelle entstand, als die zugehörige Frage auftrat.

| Zeitraum | Was hinzukam | Wofür |
|---|---|---|
| 3. August | `habits`, `habit_completions`, Onboarding-Marker, Motivation | Grundgerüst für Gewohnheiten und ihr Abhaken |
| 8. bis 9. August | feste Zeitpläne, Erinnerungen, kleinster Schritt | Time Blocking und KI-Assistenz |
| 9. bis 10. August | `friendships`, `appointments`, `appointment_notices` | Community und Verabredungen |
| 15. bis 17. August | `ai_suggestions`, Zielgröße, Ketten | KI-Gedächtnis und Habit Chains |
| 31. August | `template_key`, Entfernung punktueller Gewohnheiten, `sleep_schedules` | Katalog und Tagesrahmen |
| 3. bis 4. September | `semesters`, `courses`, `course_exceptions` | Semesterplan |
| 7. September | `sleep_day_overrides`, Zeitpläne je Wochentag | tageweise Anpassung des Rahmens |

Auffällig sind die Migrationen, die etwas **entfernen**: punktuelle Gewohnheiten, geratene
Situationen, eine Kursart, die sich als überflüssig erwies. Sie belegen, dass wir Konzepte
auch wieder zurückgenommen haben, wenn der tatsächliche Gebrauch dagegen sprach.

## 7.15 Das Abschlussgespräch

Anne legte die Modalitäten für den Abschluss fest:

- **Bericht** ab 30 Seiten, mit Screenshots, und erkennbar, wer was gemacht hat
- **Abgabe am 16. September** mit Link zum Repository und Bericht
- **Abschlusspräsentation** von 30 bis 45 Minuten, voraussichtlich in der zweiten
  Oktoberwoche, davon etwa 15 Minuten Foliensatz und 15 Minuten Live-Demo
- Inhaltlich: Motivation, Anspruch, Herangehensweise, Umfrage, Personas, Kernfeatures,
  Ausblick

In den Tagen nach dem Gespräch sind unsere letzten funktionalen Änderungen entstanden. Wir
haben die Verabredungslogik geschärft, eine Verabredung hat eine Uhrzeit, und eine Zusage
belegt den Tag in beide Richtungen. Außerdem bekam die Anwendung einen **Auftakt**, der vor
der ersten Frage erklärt, worum es überhaupt geht.
