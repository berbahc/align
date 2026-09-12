# 7. Phase 4 — Technische Umsetzung

**Zeitraum:** 21. Juli bis 7. September 2026 · **Iterationen 5 und 6**, Betreuungsgespräche
am 10. August und 7. September

Mit der abgeschlossenen Konzeptphase hatten wir eine empirisch abgesicherte Feature-Auswahl.
In dieser Phase haben wir sie in lauffähige Software überführt — zunächst als tragfähige
Grundlage, anschließend als benutzbare Anwendung.

## 7.1 Die Entscheidung für den Tech-Stack

In der Konzeptphase waren wir von einer nativen mobilen Anwendung mit React Native und Expo
ausgegangen, mit Supabase als Backend-Dienst. Umgesetzt haben wir etwas anderes: eine
**mobil-first Web-App auf Basis von Laravel**.

Diese Entscheidung ist ausdrücklich als **Entwicklungsentscheidung** zu lesen, nicht als
Produktstrategie. Align ist ein MVP im Rahmen eines Studienprojekts und kein marktfähiges
Produkt; unsere langfristige Produktidee bleibt die mobile Anwendung. Für die Entwicklung
sprachen drei Gründe:

**Eine Codebase, kein Freigabeprozess.** Die Anwendung wird über den Browser aufgerufen.
Zwischen einer Änderung und dem Nutzer steht kein Store-Review, es gibt keine getrennten
Builds für zwei Plattformen und keine laufenden Betriebskosten. Für unser Projekt mit
mehreren Iterationen war das die passende Form: kurze Zyklen, sofort prüfbare Stände.

**Responsivität bleibt durchgehend überprüfbar.** Align ist inhaltlich mobil-first gedacht,
entwickelt haben wir aber am Laptop. Weil die Anwendung im Browser läuft, lässt sich die
Mobilansicht direkt in den Entwicklerwerkzeugen prüfen — Änderung speichern, Gerätebreite
umschalten, Ergebnis sofort sehen. Ohne Simulator, ohne Build, ohne zweites Gerät. Unser
Mobile-First-Anspruch blieb damit während der gesamten Entwicklung messbar statt erst am
Ende.

**Ein Framework, das viel mitbringt.** Laravel liefert Routing, Validierung,
Authentifizierung und ein ORM aus einer Hand, dazu ein Testgerüst. Die lokale Umgebung über
Laravel Herd war ohne Konfigurationsaufwand einsatzbereit.

Bei der Implementierung haben wir KI-gestützte Entwicklungswerkzeuge eingesetzt. Sie haben
den in der verfügbaren Zeit erreichten Funktionsumfang wesentlich ermöglicht; die fachlichen
Entscheidungen — Datenmodell, Regeln, Interaktionslogik — haben wir selbst getroffen und
begründen sie in den folgenden Abschnitten.

## 7.2 Der Stack im Überblick

| Schicht | Technologie | Begründung |
|---|---|---|
| Sprache / Runtime | PHP 8.4, Node 25 | kommen beide von Herd, keine separate Einrichtung |
| Backend | **Laravel 13** | Routing, Validierung, Auth und ORM aus einer Hand |
| Bridge | **Inertia.js 3** | verbindet Controller direkt mit React-Seiten — SPA-Gefühl ohne eigene API-Schicht |
| Frontend | **React 19** + TypeScript 5.7 | Komponenten mit Typprüfung über die gesamte Oberfläche |
| UI | **shadcn/ui** (Radix), **Tailwind CSS 4** | Komponenten liegen als Quelltext im Projekt und sind frei an die Designsprache anpassbar |
| Build | Vite 8 | Hot Reload im Betrieb, gebündelte Assets für die Auslieferung |
| Routen im Frontend | `laravel/wayfinder` | erzeugt TypeScript-Funktionen aus den Laravel-Routen — Tippfehler fallen beim Kompilieren auf |
| Auth | `laravel/fortify` mit Passkeys und 2FA | geprüfter Baustein statt Eigenbau |
| Datenbank | **SQLite** | eine Datei, kein Server; Cache, Queue und Session laufen ebenfalls darüber |
| KI | `laravel/ai` über die **OpenRouter-API** | siehe 7.4 |
| Qualität | Pest 5, Larastan, Pint, ESLint, Prettier | siehe 7.5 |
| Umgebung | Laravel Herd → `https://align.test` | HTTPS lokal, ohne eigene Serverkonfiguration |

## 7.3 Architektur

Ein Aufruf nimmt immer denselben Weg:

```
Browser → Route (routes/web.php) → Controller → Inertia::render() → React-Seite
```

Der Controller lädt die Daten und benennt die React-Seite, die sie darstellen soll — **eine
separate REST-API entfällt**, weil Inertia die Props direkt übergibt. Jede Entität besteht
aus einem Eloquent-Model und einer Migration, die die Tabelle beschreibt. Eingaben prüfen
Form Requests, Zugriffsrechte regeln Policies, feste Wertebereiche liegen als Enums vor.

Die Oberfläche baut auf shadcn/ui-Komponenten, die die Design-Tokens aus der zentralen
Stylesheet-Datei tragen: Dort haben wir Schrift (Sora) und Farben — unter anderem `primary`
`#775A19` — aus Figma als CSS-Variablen hinterlegt. Unsere Designsprache aus Phase 3 ist
damit nicht nachgebaut, sondern als Quelle im Code verankert.

## 7.4 Die KI-Anbindung

Die KI-Funktionen sprechen nicht direkt mit einem Anbieter, sondern über das Laravel-AI-SDK
mit der OpenRouter-API. Anbieter und Modell stehen in der Umgebungskonfiguration und lassen
sich ohne Codeänderung austauschen.

Jede Funktion ist eine eigene Agent-Klasse mit festem Prompt, Zeitlimit und einem
JSON-Schema für die Antwort — etwa der Agent für den kleinsten nächsten Schritt. Die
zugehörigen Routen sind gedrosselt, weil hinter ihnen ein kostenpflichtiger Dienst steht.

Eine Entscheidung verdient besondere Erwähnung: Fällt ein Aufruf aus, antwortet unsere
Anwendung mit einer **ehrlichen Absage** statt mit einem regelbasierten Ersatzvorschlag. Was
wie ein KI-Vorschlag aussieht, muss auch einer sein. Ein Fallback, der KI simuliert, wäre
gegenüber dem Nutzer eine Täuschung — und im Rahmen dieser Arbeit auch gegenüber unserer
Leitfrage, die kontextsensitive KI ausdrücklich zum Gegenstand hat.

## 7.5 Qualitätssicherung

Feature-Tests mit **Pest** decken die zentralen Abläufe ab: Onboarding, Gewohnheiten,
Erinnerungen, KI-Vorschläge, Verabredungen. **Larastan** prüft die Typen im Backend, der
TypeScript-Compiler die im Frontend, **Pint** und **ESLint/Prettier** den Stil. Ein einzelner
Befehl führt alles in einem Durchlauf aus.

---

# Iteration 5 — Aufbau der Anwendung

**2. bis 10. August 2026 · 28 Commits**

Unser Ziel war ein lauffähiger Stand, der die drei Kernfeatures erkennbar abbildet — nicht
vollständig, aber echt genug, um ihn vorführen zu können.

## 7.6 Was entstand

Der erste Commit fiel am **2. August**.

**Grundgerüst und Designsprache (2.–4. August).** Zunächst ein Dashboard, das noch mit
Beispieldaten arbeitete. Am 3. August folgte der Schritt, der den Rest prägte: Die
Designsprache aus Phase 3 wurde auf die Anwendung angewendet, und das Platzhalter-Widget wich
echten Gewohnheitsdaten. Noch am selben Abend kamen Onboarding, das Abhaken von Gewohnheiten
und die drei Feature-Bereiche dazu.

**Kernfunktionen (8.–10. August).** In drei Tagen entstanden feste Uhrzeiten und Erinnerungen
zehn Minuten vor dem Termin, die **KI-Anbindung** mit dem Vorschlag des kleinsten nächsten
Schritts, der Tageskalender samt der Möglichkeit, dass die KI einen Block verschiebt, der
**Freundschafts-Layer** mit gemeinsam übernommenen Gewohnheiten, die Umschaltung zwischen
Light und Dark Mode, eine Serienzählung, die ein Wochenende und einen verpassten Tag
übersteht, sowie die Ansicht, die zeigt, was mit wem verabredet ist — samt der Möglichkeit,
eine Verabredung abzusagen, ohne dass eine Lücke zurückbleibt.

**Die Fünf-Gewohnheiten-Grenze.** Am 9. August haben wir die in Phase 3 belegte Regel zur
Funktion gemacht: Gewohnheiten lassen sich beenden, statt bei fünf festzustecken, und die Grenze wird
dort erklärt, wo sie tatsächlich greift — nicht als abstrakte Regel im Onboarding.

Parallel ist ein eigenes Logo entstanden, das wir in einer hellen und einer dunklen Variante
eingebunden haben.

## 7.7 Feedback von Anne

Das Feedback bündelte sich in drei Punkten: die Dokumentation parallel weiterführen, das
Design fertigstellen und dabei priorisieren, die technische Umsetzung weitertreiben.

---

# Iteration 6 — Ausbau und Härtung

**11. August bis 7. September 2026 · 100 Commits**

Unsere Zielsetzung haben wir im Protokoll knapp festgehalten: die App intuitiver machen,
damit sie nicht selbst zum Hindernis wird — und gleichzeitig das Bestehende so härten, dass
es in unterschiedlichen Kontexten zuverlässig funktioniert. Mit der ehrlichen Randbemerkung:
*„Schwierigkeit war, diese beiden Ziele zu vereinbaren."*

Das ist der Kern dieser Iteration. Nach Iteration 5 hatten wir viele Funktionen; sie griffen
aber noch nicht ineinander. Vier Wochen später war Align eine Anwendung, die einen Tag als
Ganzes versteht. Die folgenden Abschnitte zeigen, wie wir schrittweise dorthin gekommen sind
— und warum wir jeweils geändert haben, was wir geändert haben.

## 7.8 Ordnung im Bestand

Unsere Gewohnheitslisten liefen nach Anlegedatum. Das ist die Reihenfolge, in der eine
Datenbank denkt, nicht die, in der ein Mensch seinen Tag sieht. Wir haben sie auf die
naheliegende Achse umgestellt: nach nächstem Termin, innerhalb eines Tages nach Tageszeit —
dieselbe Achse wie der Kalender.

Die Gewohnheiten-Seite bekam die Zweiteilung „Steht heute an" / „Steht später an", mit einer
Zeile pro Eintrag, wann er das nächste Mal dran ist. Bewusst **kein** Block pro Wochentag:
Bei höchstens fünf Gewohnheiten wären sieben Überschriften mehr Gliederung als Inhalt.

Zwei Lücken in unserer Verabredungslogik haben wir geschlossen. Eine abgesagte Verabredung endete
bis dahin im Nichts — nach der Absage blieb nur ein Satz stehen. Neu bietet sie einen Weg an,
je nachdem, wem die Gewohnheit gehört: sie ohne Begleitung weiterführen oder sie selbst
übernehmen. Und fremde Gewohnheiten lassen sich übernehmen, ohne dass es erst einer Absage
bedarf. Dabei entsteht eine eigene Gewohnheit: Sie zählt gegen die eigenen fünf Plätze und
beginnt bei Tag eins. Der Warum-Satz und der kleinste Schritt kommen nicht mit — die gehören
zu einer Person, nicht zu einer Gewohnheit.

Ebenfalls in dieser Woche ist das **KI-Gedächtnis** entstanden: Die Agenten wissen, mit wem
sie sprechen, statt jeden Vorschlag im luftleeren Raum zu formulieren.

## 7.9 Die grundlegendste Änderung: Katalog statt freier Eingabe

Bis Ende August konnten Gewohnheiten frei eingegeben werden. Beim wirklichen Benutzen zeigte
sich, dass das nicht trägt: Eine frei formulierte Gewohnheit lässt sich nicht zuverlässig in einen
Tag einplanen, weil ihr die Angaben fehlen, die Planung überhaupt erst möglich machen — vor
allem eine **Dauer**.

Unsere Konsequenz war ein fester **Gewohnheitskatalog**. Aufgenommen wird nur, was drei
Bedingungen erfüllt: planbar sein, eine Dauer haben, am Stück stattfinden. Der Katalog ist in
vier Bereiche des Studienalltags sortiert.

Diese Änderung ist der Wendepunkt unserer gesamten Umsetzung: **Aus einem Tracker wurde ein
Planungswerkzeug.** Erst dadurch belegt jeder Block eine echte Spanne im Tag, und erst
dadurch weiß eine Kette, wann die vorige Gewohnheit fertig ist. Gleichzeitig ist sie unsere größte bewusste
Einschränkung des Funktionsumfangs — die Begründung dafür steht in Abschnitt 7.13.

## 7.10 Der Semesterplan

Aus der Competitor-Analyse stammte der Befund, dass keine der acht untersuchten Anwendungen
in Semestern, Prüfungsphasen und Vorlesungsrhythmus denkt (Abschnitt 4.2). Genau das haben wir Anfang
September gebaut — und damit das Alleinstellungsmerkmal umgesetzt, das unsere Analyse
identifiziert hatte.

Der **Stundenplan blockiert den Tag**, damit die KI nicht in Vorlesungen plant. Der
4. September war mit 27 Commits der dichteste Entwicklungstag des Projekts, und der Weg
dorthin ist ein gutes Beispiel für schrittweise Verbesserung:

1. Der Semesterplan bekam zunächst einen **eigenen Tab**.
2. Kurz darauf wurde er in den Kalender gefaltet — ein eigener Tab für etwas, das den Tag
   strukturiert, trennt, was zusammengehört.
3. Aus zwei nebeneinander liegenden Kalendern wurde **ein Kalender** mit Monats- und
   Tagesebene.
4. Die Darstellung des Semesters wechselte von einer Kartenliste zu einer **Wochenansicht** —
   ein Stundenplan sieht aus wie ein Stundenplan.

Parallel haben wir die **Kollisionsregeln** geschlossen: Eine Gewohnheit, die in einer Vorlesung
landen würde, wird auf jedem Weg abgelehnt, der eine Uhrzeit setzt. Ein Kurs in einem
zukünftigen Semester beansprucht seinen Platz erst, wenn das Semester beginnt. Ein abgesagter
Kurs gibt den Tag zurück.

Eine Verfeinerung verdient Erwähnung, weil sie die Haltung unserer Anwendung zeigt: Zunächst
**lehnte** ein Kurs eine kollidierende Gewohnheit schlicht ab. Das ist korrekt, aber nicht
hilfreich — und es forderte den Nutzer implizit auf, eine Vorlesung zu verschieben, die er
nicht verschieben kann. Wir haben es so geändert, dass ein Kurs die Gewohnheit **unter sich
parkt**, statt sie abzuweisen.

Ebenfalls in dieser Phase ist die Navigation auf dem Handy an den unteren Rand gewandert, und
die Anwendung bekam eine eigene Kopfzeile für die Mobilansicht.

## 7.11 Der Tagesrahmen

Aufsteh- und Schlafenszeit spannen den Tag auf, in dem alles andere stattfindet. Der Rahmen
ist bewusst **keine Gewohnheit**: Er wird nicht abgehakt, hat keine Serie und keine Quote. Er
beantwortet die Frage, die vor jeder Planung steht — *wie lang ist mein Tag überhaupt?*

Er lässt sich tageweise anpassen, weil ein Samstag anders aussieht als ein Dienstag. Bewegt
sich der Rahmen, bewegt sich der Tag mit ihm: Gewohnheiten an den Rändern folgen der
Verschiebung, statt außerhalb des Tages liegen zu bleiben.

Diese Unterscheidung — Rahmen versus Inhalt — hatten wir zuvor nicht sauber getrennt und an
mehreren Stellen im Code unterschiedlich behandelt. Am 7. September haben wir sie an genau
einer Stelle festgelegt.

## 7.12 Konsistenz statt Prozent

Die Fortschrittsanzeige zählt **Tage statt Prozente** und sagt dazu, welche Tage überhaupt
zählen — nur solche, an denen die Gewohnheit tatsächlich anstand. Eine Gewohnheit, die
montags und mittwochs läuft, wird nicht dafür bestraft, dass sie dienstags nicht stattfand.

Damit hatten wir auch den Streak-Befund aus unserer Umfrage (Abschnitt 5.10) umgesetzt: Die
Konsistenzrate liegt als ruhige Kennzahl auf der Übersicht, Serien haben einen eigenen Platz,
und ein Tag wird als Haken dargestellt — gefüllt, wenn erledigt, hohl, wenn offen. Kein rotes
Kreuz.

## 7.13 Was bewusst weggelassen wurde

Diese Phase ist auch dadurch gekennzeichnet, was wir nicht gebaut haben. Vier Entscheidungen
haben wir explizit getroffen und begründet:

| Weggelassen | Begründung |
|---|---|
| **Punktuelle Gewohnheiten** (z. B. Treppe statt Aufzug) | haben keine Dauer und sind damit nicht planbar — sie passen nicht in ein Werkzeug, das einen Tag ordnet |
| **Situative Anker ohne planbare Uhrzeit** (z. B. „nach dem Frühstück") | zu individuell, um daraus eine verlässliche Planung abzuleiten |
| **Eigene Gewohnheiten eintragen** | bewusst auf den vordefinierten Katalog beschränkt, um zuverlässig entwickeln zu können; später erweiterbar |
| **Blocker** als eigene Kategorie für feste Termine | im Rahmen dieser Umsetzung nicht mehr umgesetzt |

Diese Liste ist die Grundlage des Ausblicks in Kapitel 10.

## 7.14 Wie sich das Datenmodell entwickelte

Die Reihenfolge unserer Datenbank-Migrationen zeichnet den Weg der Anwendung präzise nach.
Sie zeigt, dass wir nicht nach einem fertigen Modell gebaut, sondern schrittweise erweitert
haben — jede Tabelle entstand, als die zugehörige Frage auftrat:

| Zeitraum | Was hinzukam | Wofür |
|---|---|---|
| 3. August | `habits`, `habit_completions`, Onboarding-Marker, Motivation | Grundgerüst: Gewohnheiten und ihr Abhaken |
| 8.–9. August | feste Zeitpläne, Erinnerungen, kleinster Schritt | Time Blocking und KI-Assistenz |
| 9.–10. August | `friendships`, `appointments`, `appointment_notices` | Community und Verabredungen |
| 15.–17. August | `ai_suggestions`, Zielgröße, Ketten | KI-Gedächtnis und Habit Chains |
| 31. August | `template_key`, Entfernung punktueller Gewohnheiten, `sleep_schedules` | Katalog und Tagesrahmen |
| 3.–4. September | `semesters`, `courses`, `course_exceptions` | Semesterplan |
| 7. September | `sleep_day_overrides`, Zeitpläne je Wochentag | tageweise Anpassung des Rahmens |

Bemerkenswert sind die Migrationen, die etwas **entfernen**: punktuelle Gewohnheiten,
geratene Situationen, eine Kursart, die sich als überflüssig erwies. Sie belegen, dass wir
Konzepte nicht nur ergänzt, sondern nach dem tatsächlichen Gebrauch auch zurückgenommen
haben.

## 7.15 Das Abschlussgespräch

Anne legte die Modalitäten für den Abschluss fest:

- **Bericht** ab 30 Seiten, mit Screenshots, und erkennbar, wer was gemacht hat
- **Abgabe am 16. September**: Link zum Repository sowie Bericht
- **Abschlusspräsentation** von 30 bis 45 Minuten, voraussichtlich in der zweiten
  Oktoberwoche: etwa 15 Minuten Foliensatz, 15 Minuten Live-Demo
- Inhaltlich: Motivation, Anspruch, Herangehensweise, Umfrage, Personas, Kernfeatures,
  Ausblick

In den Tagen nach dem Gespräch sind unsere letzten funktionalen Änderungen entstanden: Wir
haben die Verabredungslogik geschärft — eine Verabredung, eine Uhrzeit, und eine Zusage
belegt den Tag in beide Richtungen. Und die Anwendung bekam einen **Auftakt**, der vor der
ersten Frage erklärt, worum es überhaupt geht.
