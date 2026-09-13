# 8. Die fertige App

Dieses Kapitel führt durch die lauffähige Anwendung, in der Reihenfolge, in der man sie beim
ersten Benutzen kennenlernt. Die Bildschirmaufnahmen zeigen den Stand vom 12. und 13. September
2026 in einer Mobilansicht mit 390 Pixeln Breite, also in der Ansicht, für die wir Align
gestaltet haben. Als Beispiel dient ein Demokonto, das nach unserer ersten Persona Lena heißt.

Die Anwendung gliedert sich über eine Navigationsleiste am unteren Rand in fünf Bereiche,
**Übersicht**, **Gewohnheiten**, **Kalender**, **Schlafplan** und **Community**. Für jeden
Bildschirm galt derselbe Maßstab wie für die Konzeption (Abschnitt 1.6). Er sollte sich ohne
Erklärung bedienen lassen und so wenige Schritte wie möglich verlangen.

---

## 8.1 Der Auftakt

![Auftakt](screenshots/app/app01-onboarding.png)

*Abb. 8.1: Der Auftakt erklärt die Anwendung, bevor die erste Frage gestellt wird.*

Vor dem Onboarding steht ein kurzer Auftakt über sechs Bildschirme. Er benennt eine typische
Situation aus dem Studienalltag, statt Funktionen aufzuzählen, und stellt erst danach die
erste Frage. Diese Ergänzung ist in den Tagen nach dem Abschlussgespräch entstanden
(Abschnitt 7.16).

## 8.2 Die Übersicht

![Übersicht](screenshots/kapitel8/k01-uebersicht.png)

*Abb. 8.2: Die Übersicht zeigt nur, was heute ansteht.*

Die Startseite beantwortet eine einzige Frage, nämlich was heute ansteht. Sie zeigt oben den
Tagesfortschritt, darunter die heutigen Gewohnheiten von früh nach spät und unten den
Tagesrahmen.

Drei Entscheidungen aus der Nutzerforschung sind hier unmittelbar sichtbar.

**Die Fortschrittskarte nennt zwei Zahlen.** „0 von 2 Gewohnheiten" für heute und „25 von 102
Mal erledigt" für die letzten 30 Tage. Die zweite Zahl ordnet einen Tag, an dem noch nichts
erledigt ist, in einen Verlauf ein, statt ihn für sich zu bewerten (Abschnitt 5.10).

**Offene und erledigte Gewohnheiten unterscheiden sich nur durch den Haken.** Offene tragen einen
hohlen Kreis, erledigte einen gefüllten Haken. Ein rotes Kreuz oder eine Mahnung gibt es nicht,
das ist der bewusste Verzicht auf Bestrafung (Schuldwert ø 3,92 in der Umfrage).

**Zwei Angebote unter jeder offenen Gewohnheit.** „Zu zweit?" führt in den
Verabredungsmechanismus, „Kleinen ersten Schritt" ruft die KI-Assistenz auf. Beide stehen
nebeneinander direkt unter der Gewohnheit, also dort, wo man sie braucht, und nicht in einem
eigenen Menü.

## 8.3 Eine Gewohnheit anlegen

Am Anlegen einer Gewohnheit zeigt sich unser Anspruch an eine einfache Bedienung am
deutlichsten. Der Assistent stellt fünf Fragen, jede auf einem eigenen Bildschirm, und bietet
bei jeder eine Auswahl an, statt ein leeres Feld zu zeigen. Im Beispiel legt Lena „Aufräumen"
an, direkt im Anschluss an „Essen vorkochen".

![Schritt 1](screenshots/kapitel8/k02-schritt1.png) ![Schritt 2](screenshots/kapitel8/k03-schritt2.png)

*Abb. 8.3 und 8.4: Schritt 1 fragt nach dem Bereich, Schritt 2 zeigt die Einträge des Katalogs
mit ihrer Dauer.*

**Schritt 1 und 2 fragen, was man sich vornimmt.** Zuerst wählt man einen der vier Bereiche des
Studienalltags, danach einen Eintrag aus dem Katalog. Jeder Eintrag trägt eine Dauer, die sich
darunter anpassen lässt. „Essen vorkochen" ist ausgegraut und als „läuft schon" markiert, damit
dieselbe Gewohnheit nicht zweimal entsteht. Warum es kein freies Eingabefeld gibt, erklärt
Abschnitt 7.8.

![Schritt 3](screenshots/kapitel8/k04-schritt3.png) ![Schritt 4](screenshots/kapitel8/k05-schritt4-ki.png)

*Abb. 8.5 und 8.6: Schritt 3 verankert die Gewohnheit im Tag, Schritt 4 schlägt einen ersten
Handgriff vor.*

**Schritt 3 fragt, wann.** Hier liegt der Kern des Time-Blocking-Konzepts. Der Assistent bietet
drei Wege an, eine Gewohnheit im Tag zu verankern:

| Anker | Beschreibung in der App |
|---|---|
| **Situation** | „Hängt an einem Moment im Tag." |
| **Feste Uhrzeit** | „Steht ohnehin im Kalender." |
| **Nach einer Gewohnheit** | „Hängt an einer, die schon läuft." |

Die Situation steht oben, weil situative Anker laut Lally et al. (2010) zuverlässiger auslösen
als Uhrzeiten. Der dritte Weg bildet das Domino-Prinzip ab. Für jede Gewohnheit, an die sich die
neue hängen lässt, steht gleich dabei, ab wann sie liefe, bei „Essen vorkochen" etwa „danach ab
17:45". Rückt die eine, rückt die andere mit.

**Schritt 4 fragt, womit es anfängt.** Beim Betreten dieses Schritts fragt die KI im Hintergrund
nach und schlägt drei Handgriffe vor, die in einer Minute getan sind und zur gewählten
Gewohnheit passen. Für „Aufräumen" war einer davon „Falte eine Decke oder ein Kissen, das gerade
nicht da liegt, wo es hingehört, und leg es an seinen Platz". Man übernimmt einen Vorschlag,
formuliert einen eigenen oder geht ohne weiter. Diese Starthilfe erhielt in der Umfrage mit
ø 4,16 die höchste Nützlichkeitsbewertung aller abgefragten Funktionen.

![Schritt 5](screenshots/kapitel8/k06-schritt5.png) ![Fast fertig](screenshots/kapitel8/k07-fast-fertig.png)

*Abb. 8.7 und 8.8: Schritt 5 fasst den Vorsatz zusammen, danach bietet die Anwendung an, die
Gewohnheit zu zweit anzugehen.*

**Schritt 5 fasst den Vorsatz zusammen.** Auslöser, Gewohnheit und erster Schritt stehen
untereinander. Darunter folgt die einzige offene Frage des ganzen Ablaufs, „Warum ist dir das
wichtig?", und sie ist ausdrücklich optional. Der Knopf heißt nicht „Speichern", sondern „Ich
nehme mir das vor". Er ist die bewusste Zusage, die Faude-Koivisto und Gollwitzer (2009) als
Voraussetzung wirksamer Wenn-Dann-Pläne beschreiben.

**Danach ist die Gewohnheit angelegt**, und die Anwendung fragt einmal, ob man sie mit jemandem
aus dem eigenen Kreis angehen möchte, für einen einzelnen Tag. „Später" steht gleichwertig
daneben, niemand wird zu einer Verabredung gedrängt.

## 8.4 Verschieben, und die Kette rückt mit

Im Kalender ist ein Tag ein Zeitraster, und jede Gewohnheit ist ein Block darin. Blöcke lassen
sich verschieben. Man hält einen Block kurz gedrückt, bis er sich löst, und zieht ihn an eine
andere Stelle. Was dabei passiert, zeigt sich am besten an zwei Gewohnheiten, die aneinander
hängen.

![Vor dem Ziehen](screenshots/kapitel8/k08-tag-mit-kette.png) ![Beim Ziehen](screenshots/kapitel8/k09-beim-ziehen.png) ![Neuer Platz](screenshots/kapitel8/k10-neuer-platz.png)

*Abb. 8.9 bis 8.11: Vor dem Ziehen, beim Ziehen und nach dem Loslassen.*

Vorher liegt „Essen vorkochen" von 17:00 bis 17:40 und „Aufräumen" direkt dahinter von 17:45 bis
18:00 (Abb. 8.9). Beim Ziehen zeigt eine Marke die Uhrzeit, an der der Block landen würde, hier
18:30, und „Aufräumen" wandert schon während der Bewegung mit (Abb. 8.10). Nach dem Loslassen
fragt die Anwendung nach und nennt dabei, was sich außerdem ändert, nämlich dass „Aufräumen" mit
auf 19:15 rutscht (Abb. 8.11). Erst dann entscheidet man, ob die Änderung **nur heute** oder
**immer** gelten soll.

![Nach dem Verschieben](screenshots/kapitel8/k11-nach-dem-ziehen.png) ![Übersicht danach](screenshots/kapitel8/k12-uebersicht-mit-kette.png)

*Abb. 8.12 und 8.13: Nach der Entscheidung „Nur heute", im Kalender und auf der Übersicht.*

Nach „Nur heute" liegen beide Blöcke an ihrer neuen Stelle, und „Essen vorkochen" trägt den
Hinweis „nur heute" (Abb. 8.12). Auch die Übersicht ordnet sich neu. „Essen vorkochen" steht dort
um 18:30 mit dem Vermerk „nur an diesem Tag", und „Aufräumen" folgt mit dem vorbereiteten ersten
Schritt (Abb. 8.13). Morgen gilt wieder der gewohnte Plan.

In diesem kleinen Ablauf steckt, was Align von einem Gewohnheitstracker unterscheidet. Eine
Gewohnheit ist kein Eintrag in einer Liste, sondern ein Platz in einem echten Tag. Ändert sich
der Tag, ändert sich der Plan mit, und die Anwendung sagt vorher, was dabei passiert.

## 8.5 Der Stundenplan im Kalender

![Monatsansicht](screenshots/abb06-kalender-monat.png) ![Tagesansicht](screenshots/abb07-tagesansicht.png)

*Abb. 8.14 und 8.15: Die Monatsansicht kündigt einen Konflikt mit dem Stundenplan an, die
Tagesansicht zeigt den ersten Vorlesungstag.*

Der Kalender hat zwei Ebenen. Die **Monatsansicht** zeigt für jeden Tag Punkte, einen je
vorgesehener Gewohnheit, und markiert die laufende Woche. Oben steht ein Hinweis, der die
Semesterlogik sichtbar macht:

> „Eine Gewohnheit verliert ab dem 12. Oktober durch deinen Stundenplan ihren Platz. Bis
> dahin läuft alles wie bisher. Ein neuer Platz lässt sich schon jetzt finden."

Die Anwendung weiß, dass zum Semesterbeginn eine Vorlesung in einen bereits belegten Platz
fällt, und sagt es, bevor der Konflikt eintritt. Der Weg aus dem Konflikt wird direkt
angeboten. Man springt zum betroffenen Tag oder lässt sich neue Zeiten von der KI vorschlagen.

Die **Tagesansicht** zeigt Montag, den 12. Oktober, den ersten Tag des Wintersemesters, und
darin das Zusammenspiel von Stundenplan und Gewohnheiten:

- **„Analysis I", 07:00 bis 08:30,** ist ein Block aus dem Stundenplan. Er ist gefüllt
  dargestellt, weil er sich nicht verschieben lässt.
- **„Nach der Vorlesung · Vorlesung nachbereiten"** hängt unmittelbar daran. Der gestrichelte
  Rand kennzeichnet einen situativen Anker, die Gewohnheit hat also keine feste Uhrzeit, sondern
  folgt einem Ereignis.
- Die Gewohnheit, die zuvor um 07:30 lag, erscheint an diesem Tag nicht mehr, weil die Vorlesung
  sie verdrängt hat. Genau darauf hatte die Monatsansicht hingewiesen.

## 8.6 Gewohnheiten im Wochenblick

![Gewohnheiten](screenshots/abb02-gewohnheiten.png)

*Abb. 8.16: Alle Gewohnheiten mit den letzten sieben Tagen.*

Die Gewohnheiten-Seite legt alle aktiven Gewohnheiten auf ein Blatt und zeigt für jede die
letzten sieben Tage. Das Raster kennt drei Zustände, die unten erklärt werden: **erledigt**
(gefüllt), **offen** (hohl) und **nicht vorgesehen** (gestrichelt). Ein Tag lässt sich antippen
und damit nachtragen.

Die Zahl unter jedem Titel ist die entscheidende Gestaltungsentscheidung. „8 von 13 Tagen" steht
bei einer Gewohnheit, die nur montags, mittwochs und freitags läuft, denn gezählt werden
ausschließlich Tage, an denen die Gewohnheit tatsächlich anstand. Eine Gewohnheit wird nicht
dafür abgewertet, dass sie dienstags nicht vorgesehen war.

## 8.7 Schlafplan

![Schlafplan](screenshots/abb08-schlafplan.png)

*Abb. 8.17: Der Tagesrahmen, für jeden Wochentag einzeln einstellbar.*

Der Schlafplan spannt den Rahmen auf, in dem geplant werden kann. Er ist bewusst **keine
Gewohnheit**, er wird nicht abgehakt und hat weder Serie noch Quote.

Die Wochenansicht zeigt für jeden Tag einen Balken. Werktags liegen acht Stunden Schlaf von
23:00 bis 07:00, am Wochenende verschiebt sich das Fenster nach hinten, in der App steht dazu
„8 Stunden bis 9,5 Stunden Schlaf, je nach Tag". Darunter lässt sich jeder Tag einzeln anpassen.
Der Wecker ist werktags aktiv, am Wochenende nicht.

Bewegt sich dieser Rahmen, bewegen sich die Gewohnheiten an seinen Rändern mit.

## 8.8 Community

![Community](screenshots/abb09-community.png)

*Abb. 8.18: Der Community-Bereich beginnt mit der Zusage, was nicht geteilt wird.*

Der Community-Bereich setzt die Entscheidung aus Abschnitt 6.4 um, also Verabredung statt
Rangliste. Wichtig ist der erste Satz der Seite:

> „Was ihr tut, sieht niemand. Nur, dass ihr euch kennt."

Die Seite beginnt mit dem, was **nicht** geteilt wird. Das ist die direkte Antwort auf den
Interviewbefund, dass Vergleich als Kontrolle empfunden wird, und auf die Umfrage, in der eine
Rangliste explizit nicht gewünscht war.

Verbindungen entstehen nur über einen exakt eingegebenen Namen, die Anwendung schlägt keine
Personen vor und sucht nicht nach Ähnlichem. Verabredungen lassen sich vollständig abschalten,
ohne den bestehenden Kreis zu verlieren.

## 8.9 Dark Mode

![Dark Mode](screenshots/kapitel8/k13-uebersicht-dunkel.png)

*Abb. 8.19: Die Übersicht im Dark Mode.*

Alle Bereiche liegen in einem hellen und einem dunklen Modus vor, die derselben Designsprache
folgen. Gold bleibt in beiden Modi die Akzentfarbe, im Dark Mode trägt es zusätzlich die
Überschriften, weil ein reines Weiß auf dunklem Grund zu hart wirkt.

![Gewohnheiten im Dark Mode](screenshots/app/app11-habits-dark.png) ![Kalender im Dark Mode](screenshots/app/app12-calendar-dark.png)

*Abb. 8.20 und 8.21: Gewohnheiten und Kalender im Dark Mode. Das Sieben-Tage-Raster und die
Monatsansicht behalten ihre Struktur, nur die Flächen kehren sich um.*

## 8.10 Funktionsumfang im Überblick

| Bereich | Umgesetzt |
|---|---|
| **Gewohnheiten** | Anlegen in fünf Schritten aus dem Katalog (vier Bereiche) · Anker über Situation, feste Uhrzeit oder Kette · Wochentage und Uhrzeiten je Tag · Bearbeiten und Beenden · Grenze von fünf aktiven Gewohnheiten |
| **Tracking** | Abhaken per Tippen oder Wischen · Sieben-Tage-Raster mit Nachtragen · Konsistenz über 30 Tage, die nur vorgesehene Tage zählt · Serien ohne Bestrafung bei Unterbrechung |
| **Kalender** | Monatsansicht mit Tagespunkten · Tagesansicht als Zeitraster · Verschieben per Ziehen, nur heute oder immer · verkettete Gewohnheiten rücken mit · Semesterplan mit Kursen, Kollisionsprüfung und Parken verdrängter Gewohnheiten |
| **Tagesrahmen** | Schlaf- und Aufstehzeit je Wochentag · tageweise Ausnahmen · Wecker innerhalb der Anwendung |
| **KI-Assistenz** | kleinster erster Schritt · neue Zeiten vorschlagen · Tag neu ordnen · Kontextwissen über Gewohnheiten, Rahmen und Stundenplan |
| **Community** | Kontakte über exakten Namen · Verabredungen zu zweit für einen einzelnen Tag · Absagen mit Weiterführung · Übernehmen fremder Gewohnheiten |
| **Sonstiges** | Auftakt vor dem Onboarding · Light und Dark Mode · Registrierung mit Passkeys und Zwei-Faktor-Authentifizierung |

Was wir bewusst nicht umgesetzt haben und wie sich Align weiterentwickeln ließe, beschreiben die
Abschnitte 9.7 bis 9.10.
