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

Die Agenten kennen dabei den Kontext, in dem sie gefragt werden, also die Gewohnheiten der
Person, ihren Schlafrahmen und ihren Stundenplan. Ein Vorschlag für einen neuen Platz im Tag
entsteht damit nicht im luftleeren Raum, sondern für genau diesen Tag.

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

Für diesen Bericht haben wir den Code-Stand vom 10. August noch einmal gestartet und zwei
Bildschirme nachträglich aufgenommen. Datum und Beispieldaten stammen deshalb aus der Aufnahme,
Aufbau und Funktionen aus dem damaligen Stand.

![Übersicht am 10. August](screenshots/verlauf/v01-1008-uebersicht.png) ![Kalender am 10. August](screenshots/verlauf/v02-1008-kalender.png)

*Abb. 7.1 und 7.2: Die Anwendung zum Betreuungsgespräch am 10. August. Links die Übersicht mit
Prozentwert und großer Serien-Karte, „Ich komm nicht rein" war der damalige Name der
Starthilfe. Rechts der Kalender, der den Tag noch als Liste nach Situationen ordnete.*

## 7.7 Feedback von Anne

Anne nannte drei Punkte. Die Dokumentation parallel weiterführen, das Design fertigstellen
und dabei priorisieren, die technische Umsetzung weitertreiben.

---

# Iteration 6 — Ausbau und Härtung

**11. August bis 7. September 2026 · 100 Commits**

Unsere Zielsetzung haben wir im Protokoll knapp festgehalten. Die App sollte intuitiver werden,
damit sie nicht selbst zum Hindernis wird, und das Bestehende sollte so robust werden, dass es
in unterschiedlichen Kontexten zuverlässig funktioniert. Daneben steht die ehrliche
Randbemerkung: *„Schwierigkeit war, diese beiden Ziele zu vereinbaren."*

Der Stand aus Iteration 5 hatte viele Funktionen, aber sie griffen noch nicht ineinander.
Abbildung 7.2 zeigt das deutlich. Der Kalender ordnete den Tag nach Situationen, weil die
Gewohnheiten keine Dauer hatten und sich deshalb keiner Uhrzeit zuordnen ließen. Er wusste
nicht, wann der Tag beginnt und endet, und er wusste nichts von Vorlesungen. Jede der folgenden
Änderungen schließt eine dieser Lücken. Wir stellen sie in der Reihenfolge vor, in der sie
aufeinander aufbauen, und beschreiben jeweils, wie es vorher war, was beim Benutzen auffiel und
was wir geändert haben.

| Abschnitt | Vorher | Nachher |
|---|---|---|
| **7.8 Katalog** | freie Eingabe, Gewohnheiten ohne Dauer | fester Katalog, jede Gewohnheit mit Dauer |
| **7.9 Schlafplan** | der Tag hatte keinen Anfang und kein Ende | Aufstehen und Schlafengehen spannen den Tag auf |
| **7.10 Zeitraster** | der Tag als Liste nach Situationen | der Tag als Zeitraster mit verschiebbaren Blöcken |
| **7.11 Stundenplan** | Semesterplan als eigener Bereich | Kurse liegen direkt im Kalender |
| **7.12 Navigation** | Seitenleiste mit sechs Einträgen | fünf Tabs am unteren Rand |
| **7.13 Gewohnheiten** | Karten mit Schaltern, Fortschritt in Prozent | Wochenblatt, Tage statt Prozent |

## 7.8 Gewohnheiten bekommen eine Dauer: der Katalog

**Vorher.** Bis Ende August wählte man im zweiten Schritt des Assistenten aus Vorschlägen oder
trug über „Etwas anderes" eine eigene Gewohnheit ein. Unter den Vorschlägen standen auch
Gewohnheiten wie „Treppe statt Aufzug" oder „Eine Station früher aussteigen", die keine Dauer
haben und keinen Platz im Tag belegen (Abb. 7.3).

**Was beim Benutzen auffiel.** Eine Gewohnheit ohne Dauer lässt sich nicht in einen Tag
einplanen. Man weiß nicht, wie viel Platz sie braucht, und eine angehängte Gewohnheit weiß
nicht, wann die vorige fertig ist. Solange der Kalender eine Liste war, fiel das kaum auf.
Sobald wir den Tag als Zeitraster zeigen wollten, wurde es zum Hindernis.

**Nachher.** Gewohnheiten kommen aus einem festen Katalog. Aufgenommen wird nur, was drei
Bedingungen erfüllt: planbar sein, eine Dauer haben, am Stück stattfinden. Der Katalog ist in
vier Bereiche des Studienalltags sortiert, und jeder Eintrag zeigt seine Dauer, die sich beim
Anlegen anpassen lässt (Abb. 7.4).

![Schritt 2 am 31. August](screenshots/verlauf/v04-3108-anlegen-schritt2.png) ![Schritt 2 heute](screenshots/abb04-katalog-auswahl.png)

*Abb. 7.3 und 7.4: Derselbe Schritt im selben Bereich vorher und nachher. Links der Stand vom
31. August mit Vorschlägen ohne Dauer und einem freien Feld, rechts der Katalog, in dem jeder
Eintrag seine Dauer trägt.*

**Warum das die wichtigste Änderung war.** Aus einem Tracker wurde damit ein
Planungswerkzeug. Erst die Dauer macht aus einer Gewohnheit einen Block, der eine echte Spanne
im Tag belegt, und auf ihr bauen alle folgenden Abschnitte auf. Zugleich ist der Katalog unsere
größte bewusste Einschränkung, denn eigene Gewohnheiten lassen sich derzeit nicht eintragen.
Wie sich das ändern ließe, beschreibt Abschnitt 9.7.

## 7.9 Der Tag bekommt einen Rahmen: der Schlafplan

**Vorher.** Der Tag hatte keinen Anfang und kein Ende. Gewohnheiten, die „nach dem Aufstehen"
oder „vor dem Schlafengehen" stattfinden sollten, hingen an Momenten, deren Uhrzeit die
Anwendung nicht kannte.

**Was beim Benutzen auffiel.** Ohne Rahmen konnte die Anwendung weder sagen, wie viel Platz ein
Tag überhaupt bietet, noch Gewohnheiten an seinen Rändern sinnvoll einordnen. Auch ein
Vorschlag für einen neuen Platz hätte in der Nacht landen können.

**Nachher.** Aufsteh- und Schlafenszeit spannen den Tag auf, in dem alles andere stattfindet.
Der Rahmen ist bewusst **keine Gewohnheit**. Er wird nicht abgehakt und hat weder Serie noch
Quote, sondern beantwortet die Frage, die vor jeder Planung steht, nämlich wie lang der Tag
überhaupt ist. Er lässt sich für jeden Wochentag einzeln einstellen, weil ein Samstag anders
aussieht als ein Dienstag (Abb. 8.17). Verschiebt sich der Rahmen, verschieben sich die
Gewohnheiten an seinen Rändern mit.

## 7.10 Aus der Liste wird ein Zeitraster

**Vorher.** Der Kalender zeigte einen Tag als Liste, gegliedert nach den Situationen, an denen
die Gewohnheiten hingen (Abb. 7.2). Man sah, was nach dem Aufstehen oder nach dem Mittagessen
anstand, aber nicht, wann genau, wie lange es dauert und ob zwei Dinge zeitlich zusammenpassen.

**Nachher.** Mit Dauer und Rahmen ließ sich der Tag als Zeitraster zeigen, das beim Aufstehen
beginnt und beim Schlafengehen endet. Jede Gewohnheit ist ein Block mit Anfang und Ende. Über
der Tagesansicht liegt eine Monatsansicht, aus der man in jeden Tag springt. Blöcke lassen sich
per Langdruck greifen und verschieben. Hängt eine Gewohnheit an einer anderen, rückt sie mit,
und bevor die Änderung gilt, fragt die Anwendung, ob sie nur heute oder dauerhaft gelten soll.
Wie das in der fertigen Anwendung aussieht, zeigt Abschnitt 8.4.

**Eine Regel, die dabei entstand.** Zwischen zwei Blöcken hält Align Luft, nämlich eine
Viertelstunde zwischen zwei Gewohnheiten und vor und nach einer Vorlesung, zum Hinkommen und
Umschalten, und fünf Minuten innerhalb einer Kette. Ohne diese Regel hätte das Raster Tage
erlaubt, die auf dem Bildschirm aufgehen, im Alltag aber nicht.

## 7.11 Der Stundenplan zieht in den Kalender

**Vorher.** Aus der Competitor-Analyse stammte der Befund, dass keine der untersuchten
Anwendungen in Semestern und Vorlesungsrhythmus denkt (Abschnitt 4.2). Anfang September haben
wir dafür den Semesterplan gebaut. Zunächst bekam er einen eigenen Bereich in der Navigation,
in dem sich ein Semester und seine Kurse als Karten je Wochentag eintragen ließen (Abb. 7.5).

**Was beim Benutzen auffiel.** Ein eigener Bereich trennt, was zusammengehört. Ein Stundenplan
ist keine eigene Aufgabe, sondern gibt vor, wo im Tag überhaupt Platz für Gewohnheiten ist. Wer
seinen Tag plant, will die Vorlesung dort sehen, wo auch die Gewohnheiten liegen.

**Nachher.** Der Semesterplan ist in den Kalender gewandert. Kurse liegen als Blöcke direkt im
Tag und blockieren ihn, damit weder man selbst noch die KI eine Gewohnheit in eine Vorlesung
legt (Abb. 8.15). Ein Kurs in einem kommenden Semester beansprucht seinen Platz erst ab
Semesterbeginn, und die Monatsansicht kündigt Konflikte an, bevor sie eintreten (Abb. 8.14).

![Semesterplan am 3. September](screenshots/verlauf/v06-0309-semesterplan.png)

*Abb. 7.5: Der Semesterplan am 3. September, noch als eigener Bereich mit einer Liste von Kursen.*

**Eine Verfeinerung.** Zunächst lehnte ein Kurs eine kollidierende Gewohnheit einfach ab. Das
war korrekt, aber nicht hilfreich, denn es forderte indirekt dazu auf, eine Vorlesung zu
verschieben, die sich nicht verschieben lässt. Jetzt parkt ein Kurs die Gewohnheit unter sich
und bietet an, einen neuen Platz für sie zu finden.

## 7.12 Die Navigation wandert nach unten

**Vorher.** Auf dem Handy lag die Navigation in einer Seitenleiste mit sechs Einträgen, darunter
Semester und Schlaf als eigene Bereiche (Abb. 7.6). Um den Bereich zu wechseln, musste man die
Seitenleiste erst öffnen.

**Nachher.** Die Navigation steht als Leiste mit fünf Tabs am unteren Bildschirmrand, in
Reichweite des Daumens und auf jeder Seite sichtbar: Übersicht, Gewohnheiten, Kalender,
Schlafplan und Community. Der Semesterplan braucht keinen eigenen Tab mehr, weil er im Kalender
liegt, und die Mobilansicht bekam eine eigene Kopfzeile. Für eine Anwendung, die mehrmals am Tag
kurz geöffnet wird, ist das der direkteste Weg zu jedem Bereich.

![Seitenleiste am 3. September](screenshots/verlauf/v07-0309-seitenleiste.png)

*Abb. 7.6: Die Navigation am 3. September als Seitenleiste mit sechs Einträgen. In der fertigen
Anwendung steht sie als Tab-Leiste am unteren Rand, zu sehen etwa in Abb. 8.2.*

## 7.13 Gewohnheiten im Wochenblick: Tage statt Prozent

**Vorher.** Die Gewohnheiten-Seite hat drei Stufen durchlaufen. Im August bestand sie aus
Karten mit einem Schalter je Gewohnheit, und auf dem Handy wurden Titel und Auslöser
abgeschnitten (Abb. 7.7). Mitte August haben wir die Karten nach dem nächsten Termin sortiert
und in „Steht heute an" und „Steht später an" geteilt (Abb. 7.8). Der Fortschritt stand als
Prozentwert auf der Übersicht, daneben eine große Karte mit der längsten Serie (Abb. 7.1).

**Was beim Benutzen auffiel.** Die Seite zeigte, welche Gewohnheiten es gibt, aber nicht, wie
es mit ihnen läuft. Und ein Prozentwert sagt nicht, worauf er sich bezieht. Zählen alle Tage
mit, erscheint eine Gewohnheit, die nur montags, mittwochs und freitags läuft, schwächer, als
sie ist.

**Nachher.** Die Seite legt alle Gewohnheiten auf ein Blatt und zeigt für jede die letzten
sieben Tage als Haken, gefüllt, wenn erledigt, hohl, wenn offen, und gestrichelt, wenn nicht
vorgesehen. Ein Tag lässt sich antippen und nachtragen. Der Fortschritt zählt **Tage statt
Prozente** und nur die Tage, an denen die Gewohnheit tatsächlich anstand, etwa „8 von 13 Tagen"
(Abb. 8.16). Damit ist auch der Streak-Befund aus der Umfrage umgesetzt (Abschnitt 5.10). Die
Konsistenz steht als ruhige Kennzahl im Vordergrund, Serien haben einen eigenen, kleineren
Platz, und ein verpasster Tag ist kein rotes Kreuz.

![Gewohnheiten am 10. August](screenshots/verlauf/v03-1008-gewohnheiten.png) ![Gewohnheiten am 3. September](screenshots/verlauf/v05-0309-gewohnheiten.png)

*Abb. 7.7 und 7.8: Die Gewohnheiten-Seite am 10. August mit Schaltern und abgeschnittenen Titeln
und am 3. September mit der Einteilung nach heute und später. Den heutigen Stand zeigt Abb. 8.16.*

## 7.14 Verabredungen zu Ende gedacht

Parallel dazu haben wir die Verabredungen vervollständigt. Eine abgesagte Verabredung endete
anfangs im Nichts, nach der Absage blieb nur ein Satz stehen. Jetzt bietet sie einen Weg an.
Wer die Gewohnheit führt, macht allein weiter, und wer eingeladen war, kann sie als eigene
übernehmen. Fremde Gewohnheiten lassen sich außerdem direkt übernehmen. Dabei entsteht eine
eigene Gewohnheit, die gegen die eigenen fünf Plätze zählt und bei Tag eins beginnt. Den
Warum-Satz und den ersten Schritt übernimmt man nicht mit, weil sie zu einer Person gehören und
nicht zu einer Gewohnheit.

## 7.15 Wie sich das Datenmodell entwickelte

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

## 7.16 Das Abschlussgespräch

Im Abschlussgespräch am 7. September haben wir Anne die fertige Anwendung vorgeführt. Dabei sind
wir beim Verschieben von Gewohnheiten noch auf kleinere Fehler gestoßen, die wir in den Tagen
nach dem Gespräch behoben haben. In diesen Tagen bekam die Anwendung außerdem einen Auftakt, der
vor der ersten Frage erklärt, worum es geht (Abschnitt 8.1).
