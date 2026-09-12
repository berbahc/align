# 5. Phase 2 — Nutzerforschung

**Zeitraum:** 21. Mai bis 29. Juni 2026 · **Iterationen 2 und 3**, Betreuungsgespräche am
8. und 29. Juni

## 5.1 Der zweistufige Forschungsansatz

Auf Annes Anregung hin haben wir die Nutzerforschung zweistufig angelegt. Zuerst kamen
qualitative Leitfadeninterviews, um den Problemraum zu erschließen und Hypothesen zu bilden,
anschließend eine quantitative Online-Umfrage, um diese Hypothesen in der Breite zu prüfen
und unsere Features zu priorisieren.

| Stufe | Methode | Umfang | Ziel |
|---|---|---|---|
| **1** | Leitfadeninterviews | n = 6 Studierende | Hypothesen zu Problemen und Bedürfnissen bilden, Grundlage für den Fragebogen |
| **2** | Online-Umfrage (LimeSurvey) | n = 25 abgeschlossene Antworten | Validierung der Hypothesen, Feature-Priorisierung |

Diese Reihenfolge war wichtig. Hätten wir die Umfrage zuerst entworfen, wären die Fragen aus
unseren eigenen Annahmen entstanden. So testete jede Frage etwas, das vorher jemand
tatsächlich gesagt hatte.

---

# Iteration 2 — Interviews und Personas

## 5.2 Der Interview-Leitfaden

Der Leitfaden umfasste dreizehn Fragen in fünf Phasen und war auf 20 bis 30 Minuten ausgelegt:

1. **Intro und Warm-up.** Einstieg über einen konkreten Tagesablauf mit der Frage „Wie sieht
   aktuell ein ganz normaler Dienstag bei dir im Semester aus?"
2. **Status quo und bisherige Lösungsversuche.** Aktuell verfolgte Gewohnheiten und der
   letzte konkrete Versuch, eine Gewohnheit durchzuziehen.
3. **Schmerzpunkt-Check.** Konkrete Situationen des Scheiterns und der Wiedereinstieg nach
   einer Unterbrechung.
4. **Community und soziale Verbindlichkeit.** Erfahrungen mit gemeinsamen Gewohnheiten und
   mit Apps, die den Fortschritt von Freunden zeigen.
5. **Cool-down.** Offene Frage nach Erwartungen an eine Habit-App für den Studienalltag.

Wir sind bewusst vom Konkreten ins Allgemeine gegangen. Wenn jemand zuerst seinen
tatsächlichen Dienstag beschreibt, fallen die Antworten auf die späteren Fragen weniger
idealisiert aus.

## 5.3 Durchführung und Auswertung

Zwischen dem 1. und 7. Juni haben wir sechs Interviews mit Studierenden unterschiedlicher
Studiengänge und Semester geführt, je zwei pro Person, mit **Alissa, Hannah, Aylin, Danial,
Felix und Ngoc Anh**.

Für die Auswertung haben wir uns auf ein einheitliches Vorgehen geeinigt. Die Gespräche haben
wir aufgezeichnet, transkribiert und anschließend nach einem gemeinsamen Raster ausgewertet.
Die Leitfragen waren für alle gleich. Stützen die Aussagen die bereits angedachten Features,
tauchen neue Ideen auf, und welche Zitate belegen das? Die Einzelauswertungen haben wir zu
einem Dokument zusammengeführt, das die Befunde nach Themen ordnet.

## 5.4 Zentrale Erkenntnisse aus den Interviews

**Kontext steuert Verhalten stärker als Uhrzeiten.**

> „Wenn ich dann im Bett bin, kann ich es direkt machen."

Alissa knüpft das Lesen an die Schlafenszeit, Felix bewegt sich an der Uni automatisch mehr,
würde aber nicht extra für ein Schrittziel spazieren gehen. Das Muster war über alle
Interviews hinweg konsistent. Verhalten wurde durch konkrete Situationen ausgelöst und nicht
durch abstrakte Vorsätze.

**Der Domino-Effekt und ganze Gewohnheitsketten.**

> „Dein Körper ist wie ein Auto, und wenn du diesem Auto dreckiges Benzin gibst, dann
> performt es schlecht." *(Danial)*

Aylin beschrieb eine vollständige Kette rund um Meal Prep: abends vorbereiten → morgens
mitnehmen → Bibliothek → arbeiten. Fällt der erste Auslöser weg, bricht die gesamte Kette.
Daraus haben wir die Anforderung abgeleitet, Situations-Anker statt Uhrzeiten zu verwenden
und bei unterbrochener Kette einen Fallback anzubieten.

**Überforderung beim Einstieg.**

> „Ich weiß oft nicht, wo ich anfangen soll, dann werde ich überfordert und fange erst gar
> nicht an." *(Felix)*

> „Du brauchst so ein bisschen diesen leichten Dopaminschub von: ey, ich habe eine Sache
> abgehakt." *(Danial)*

**Kein Strafmechanismus, aber der Wunsch nach Selbstanalyse.**

> „Dann war das halt ein Ausrutscher. Und morgen machst du es halt dann wieder besser."
> *(Aylin)*

**Community ja, Vergleich nein.**

> „Wenn du eine Verabredung hast, dann gehst du da natürlich auch mit einem anderen
> Pflichtbewusstsein ran, als wenn du das einfach nur für dich selber machen würdest."
> *(Aylin)*

Dem stand die klare Ablehnung von Vergleich gegenüber:

> „Das löst dann kein positives Gefühl aus, dass ich mich für die Person freue, sondern eher
> so 'ne Kontrolle, bin ich auch soweit, muss ich noch was mehr tun."

Zusammenfassend bestätigten alle sechs Interviews unabhängig voneinander drei Muster.
Rankings verlieren langfristig ihre Wirkung, Community funktioniert nur mit vertrauten
Personen, und Prüfungsphasen verändern Routinen massiv.

## 5.5 Personas

Aus den sechs Interviews haben wir zwei Verhaltenstypen verdichtet. Beide sind vollständig
als Persona-Sheets ausgearbeitet und liegen diesem Bericht als Anhang bei.

### „Die Selbstregulierten", intrinsisch und selbstreguliert

*Verdichtet aus Alissa, Hannah, Aylin und Felix. Höhere Semester, flexible Tage, bereits
funktionierende kontextbasierte Routinen.*

> „Dann war das halt ein Ausrutscher. Morgen machst du es halt wieder besser."

Dieser Typ hat keine vorgegebene Tagesform, aber funktionierende Wege zur Selbststeuerung.
Struktur entsteht über selbst gesetzte Anker oder über den Kontext. Die Motivation kommt von
innen und nicht aus dem Vergleich, Streak-Apps wurden getestet und abgelegt.

| | |
|---|---|
| **Ziele** | Gewohnheiten an Anker knüpfen statt an Uhrzeiten · Verbindlichkeit über echte Verabredungen · Fortschritt ohne Konkurrenz · das Warum sichtbar halten |
| **Frustrationen** | Streaks verdrängen das eigentliche Ziel · zeitbasierte Trigger gehen am Alltag vorbei · Apps werten Nicht-Nutzung als Versagen · lose soziale Absichten halten nicht |
| **Align-Hebel** | Wenn-Dann-Ketten an Kontexte · Habit-Buddies über konkrete Termine · Konsistenzrate statt Streak · Fehltage neutral |

### „Die Einsteiger", überfordert und inkonsistent

*Verdichtet aus Danial und Ngoc Anh. Mittlere Semester, keine feste Routine, brauchen einen
klaren ersten Schritt.*

> „Ich weiß oft nicht, wo ich anfangen soll, dann werde ich überfordert und fange erst gar
> nicht an."

Dieser Typ weiß rational, was guttun würde, setzt es aber inkonsistent um. Der Tag steht und
fällt mit dem ersten Anker. Geht der Start schief, kippt der ganze Domino.

| | |
|---|---|
| **Ziele** | einen stabilen Tagesanker finden, der den Rest mitzieht · den ersten Schritt vorgegeben bekommen · sichtbare Meilensteine · auch in der Prüfungsphase eine Kernroutine halten |
| **Frustrationen** | ein schlechter Start zerlegt den Tag · Überforderung führt zum Aufschieben · Ranking motiviert kurz, bricht langfristig weg |
| **Align-Hebel** | KI-Assistent formuliert den nächsten Mikroschritt · Prüfungsmodus mit reduzierter Kernroutine · Meilensteine statt Ranking |

Anne hatte in Iteration 1 nach einem Vorher-Nachher-Vergleich anhand der Personas gefragt.
Die Spalte „Align-Hebel" ist unsere Antwort darauf. Sie stellt jeder Frustration die konkrete
Funktion gegenüber, die sie auffangen soll.

## 5.6 Feedback von Anne

Das Gespräch verlief ausgesprochen positiv, unsere Notiz im Iterationsprotokoll hält fest,
dass Anne überzeugt war. Inhaltlich kamen vier Punkte:

- **Eine offene Fachfrage.** Gibt es eine Höchstzahl an Gewohnheiten, auf die sich ein Mensch
  gleichzeitig konzentrieren kann, ohne überfordert zu werden? In den Quellen prüfen.
- **Persona-Fokus.** Auf welchen der beiden Typen zielen die Features?
- **Nicht zu früh einschränken.** Bei Mockups und Features größer denken und sich erst bei
  der technischen Umsetzung auf das Machbare beschränken. „Es muss nicht alles perfekt sein."
- **Design-System.** Farben, Typografie und Formen in einer gemeinsamen Figma-Datei
  festlegen.

Die Frage nach der Höchstzahl hat sich als der langlebigste Impuls unseres Projekts erwiesen.
Sie führte zur festen **Grenze von fünf aktiven Gewohnheiten**, deren Entwicklung in
Kapitel 7 nachgezeichnet ist. Die Frage nach dem Persona-Fokus haben wir nicht exklusiv
beantwortet, sondern über die Feature-Zuordnung. Die Einsteiger brauchen die KI-gestützte
Starthilfe, die Selbstregulierten die Situations-Anker und eine nicht bestrafende
Fortschrittsanzeige.

---

# Iteration 3 — Online-Umfrage und Priorisierung

## 5.7 Konzeption des Fragebogens

Zunächst haben wir zwei Varianten entworfen, eine lange Fassung mit zwanzig Fragen, die auch
das aktuelle Verhalten erhob, und eine Kurzfassung mit zwölf Fragen, die ausschließlich
validierte, was die Interviews offen gelassen hatten. Für die kurze Variante sprach ein
reales Risiko, denn längere Umfragen werden häufiger abgebrochen.

In einem gemeinsamen Call sind wir beide Entwürfe durchgegangen, haben schwache Fragen
markiert und überarbeitet. Jede Frage musste eine konkrete Design-Entscheidung testen, etwa
ob die Mehrheit Streaks oder Konsistenzraten bevorzugt, ob sozialer Druck motiviert und wie
lang ein Check-in sein darf. Annes Hinweis, fremde Features vor eigenen abzufragen, um Bias
zu vermeiden, haben wir in der Reihenfolge der Fragen umgesetzt.

## 5.8 Werkzeugwahl und Durchführung

Google Forms schied aus, weil Anne aus Datenschutzgründen davon abgeraten hatte. Wir haben
die Umfrage in **LimeSurvey** aufgesetzt und vor der Veröffentlichung mehrfach überarbeitet.
Wir haben sie auch für Nicht-Studierende geöffnet, die das zu Beginn angeben mussten, und um
Alter und Geschlecht ergänzt, um Muster in der Stichprobe erkennen zu können.

Verteilt haben wir sie am 14. Juni über zwei Kanäle innerhalb der Hochschule, über die
E-Commerce-Kohorte und die Erstsemester. Der Rücklauf war schnell, innerhalb von rund zwei
Stunden lagen **25 vollständige Antworten** vor. Damit war die Kapazität des eingesetzten
Umfragewerkzeugs erreicht und die Erhebung endete (siehe Abschnitt 5.11).

## 5.9 Ergebnisse

**Stichprobe.** N = 25 abgeschlossene Antworten, davon 24 eingeschrieben. 16 weiblich,
9 männlich, 19 im Alter von 21 bis 25 Jahren. Der Schwerpunkt lag im 5. bis 6. Semester (12),
gefolgt vom 1. bis 2. Semester (5).

**Wie organisieren sich Studierende heute?**

| Methode | Nennungen |
|---|---|
| Kalender oder Planer | 20 |
| Erinnerungen am Handy | 14 |
| Freunde / Lernpartner | 7 |
| Nichts Bestimmtes | 7 |
| **Habit-App** | **2** |

Nur zwei von 25 nutzen aktuell eine dedizierte Habit-App, während 20 von 25 ohnehin mit
Kalender oder Planer arbeiten. Das ist ein doppelter Befund. Bestehende Lösungen haben eine
geringe Marktdurchdringung, und eine Kalender- und Time-Blocking-Logik knüpft an vorhandenes
Verhalten an, statt neues zu verlangen.

Inhaltlich führen „Lernen und Uni" (20), „Bewegung und Sport" (18) und „Schlaf und Erholung"
(16) die Bereiche an, in denen Gewohnheiten aufgebaut werden.

**Was hält Studierende auf?** Stress und Prüfungsphase sind der mit Abstand größte
Habit-Killer (17/25). Auffällig ist die Reaktion darauf, denn 15 von 25 **reduzieren** dann,
statt ganz aufzugeben. Das spricht für einen Minimal- oder Prüfungsphasen-Modus statt einer
Pausenfunktion.

| Aussage (1 bis 5) | Ø |
|---|---|
| „Wenn ich eine Gewohnheit nicht einhalten konnte, fühle ich mich schuldig/enttäuscht." | **3,92** |
| „Ich weiß, was ich ändern will, aber es wird selten zur Routine." | 3,80 |
| „Wenn ich aus einer Routine rausgefallen bin, fällt mir der Wiedereinstieg schwer." | 3,68 |

Der hohe Schuldwert zusammen mit dem schweren Wiedereinstieg stützt die
Anti-Bestrafungs-Philosophie direkt. Eine bestrafende Mechanik würde genau die wundeste
Stelle treffen.

**Welche Funktionen werden gewünscht?**

| Funktion | Ø Nützlichkeit |
|---|---|
| Starthilfe, kleinster nächster Schritt | **4,16** |
| Erinnerung vor der Gewohnheit | 4,04 |
| Dynamische Anpassung bei Nichteinhaltung | 4,04 |
| Wenn-Dann / Situationsanker | 3,88 |
| Gemeinsamer Kalender mit Freunden | 3,04 |
| Bilder/Nachrichten *während* der Gewohnheit | **2,83** |

Bei der Frage nach der wichtigsten Einzelfunktion lag Fortschrittstracking vorn (9/25), vor
dynamischer Anpassung (7/25) und Starthilfe (6/25). Soziales nannten nur 3 von 25.

**Soziales, differenziert betrachtet.** Der schwache erste Eindruck täuscht. Die Bereitschaft
zum Teilen ist vorhanden, aber leise. 21 von 25 würden Gewohnheiten mit **engen Freunden**
teilen, mit deutlichem Abstand vor Partner:in (12) und Familie (11). Anonyme Personen nannten
nur 2. Gewünscht wird also leichtes, passives Opt-in-Teilen mit ein bis drei Vertrauten und
weder erzwungene Synchronisation noch Live-Kommunikation während der Ausführung.

## 5.10 Ein Befund, der die Interviews korrigierte

Aus den Interviews stammte die Annahme, dass Streaks schlecht sind und demotivieren. In der
Breite hielt das nicht.

| Präferenz | Stimmen |
|---|---|
| Streak | 9 |
| Konsistenzrate | 5 |
| offen für beides | 11 |

Nur 5 von 25 bevorzugen klar die Konsistenzrate, 9 tendieren zum Streak, 11 sind offen. Das
ist der einzige Punkt, an dem die quantitative Erhebung einer qualitativen Hypothese
widersprochen hat, und genau dafür war sie da.

Wir haben uns nicht für eine Seite entschieden, sondern die Spannung auseinandergenommen. Der
Streak wirkt motivierend, aber sein **Bruch** darf nicht bestrafen. In unserer fertigen
Anwendung sind deshalb beide Ansichten vorhanden, die Konsistenzrate als ruhige Kennzahl auf
der Übersicht, Serien an eigener Stelle und verpasste Tage neutral dargestellt.

## 5.11 Methodische Einordnung

Die Ergebnisse sind **richtungsweisend, nicht repräsentativ**. Drei Einschränkungen sind zu
nennen.

**Kleine und schiefe Stichprobe.** Angestrebt waren über 50 Antworten, erreicht wurden 25.
Die Stichprobe ist stark E-Commerce- und 5.-bis-6.-Semester-lastig, das
Geschlechterverhältnis mit 16 zu 9 unausgewogen. Als Priorisierungshilfe ist sie belastbar,
als Beweis nicht. Dass 25 Antworten in zwei Stunden eingingen, zeigt außerdem, dass wir eine
größere Stichprobe hätten erreichen können. Die Begrenzung lag an der Kapazität des
eingesetzten Werkzeugs und nicht an der Bereitschaft der Zielgruppe. Bei einer erneuten
Erhebung würden wir das Umfragewerkzeug deshalb früher und sorgfältiger auswählen.

**Abweichung zwischen Leitfaden und ausgelieferter Umfrage.** Unser finaler Fragebogen
enthielt neun Funktions-Skalen, darunter „gemeinsamer Kalender" und „Bilder während der
Gewohnheit schicken". Die ursprünglich geplanten Skalen zu „Habit Journey /
Fortschrittskurve" und zum Konsistenzrate-Tracking fehlen als eigene Bewertung. Vergleiche
mit unserem ursprünglichen Plan sind entsprechend einzuordnen.

**Segmentierung nicht belastbar.** Eine Auswertung nach „Anfängern" und „Fortgeschrittenen",
die unseren beiden Personas entsprochen hätte, ist bei dieser Stichprobengröße nicht
aussagekräftig.

## 5.12 Abgeleitete Produktentscheidungen

| Erkenntnis | Entscheidung |
|---|---|
| Nur 2/25 nutzen eine Habit-App, 20/25 planen mit Kalender | Time-Blocking-Logik statt eigener Tracker-Welt; niedrige Einstiegshürde |
| Stress/Prüfungsphase ist Hauptgrund fürs Aufgeben (17/25), 15/25 reduzieren statt aufzugeben | Minimal-/Prüfungsphasen-Modus mit reduzierter Kernroutine |
| Schuldgefühl nach Scheitern ø 3,92 | kein Straf- oder Bestrafungsmechanismus, Fehltage neutral |
| Starthilfe bei Überforderung ist die bestbewertete Funktion (ø 4,16) | KI-gestützter Erster-Schritt-Assistent als Kern-Anwendungsfall der Claude API |
| Fortschrittstracking meistgewählte wichtigste Funktion (9/25) | Progress Tracking als Kernfeature, nicht bestrafend gestaltet |
| Streak-Präferenz uneinheitlich (9 : 5 : 11) | beide Ansichten anbieten, Bruch vergebend gestalten |
| 21/25 teilen mit engen Freunden, nur 3/25 nennen Soziales als wichtigste Funktion | Habit-Buddies als dezente Opt-in-Ebene, keine öffentliche Rangliste |
| Gemeinsamer Kalender (3,04) und Live-Bilder (2,83) schwach bewertet | beide verworfen |

## 5.13 Eine Entscheidung gegen ein eigenes Feature

Aus der Auswertung haben wir gefolgert, die **Habit Journey** nicht weiterzuverfolgen, also
die Langzeitperspektive auf der Automatisierungskurve. Wir haben sie bewusst in die
Präsentation aufgenommen, um die Streichung anhand der Umfragedaten begründen zu können,
statt sie stillschweigend verschwinden zu lassen.

## 5.14 Feedback von Anne

Das Feedback war knapp und bestätigend. Die wichtigsten Features hätten wir aus der Umfrage
herausgearbeitet, jetzt gehe es darum, sie umzusetzen und die Erkenntnisse zu visualisieren.
Vor allem sollten wir **dem roten Faden folgen** und das Design auf der Auswertung der
Umfrage aufbauen.

Dieser Hinweis bestimmte unsere gesamte folgende Phase. Jeden Screen, der ab Juli entstand,
haben wir mit einem Bezug zu Interview-, Umfrage- oder Literaturbefund versehen,
niedergelegt in einem eigenen Begründungsdokument, das für jeden Screen festhält, worauf er
abzielt. Es war unser bewusster Versuch, nachweisen zu können, dass keine
Design-Entscheidung aus dem Bauch kam.
