# Align — Projektbericht

> **Gemeinsames Dokument.** Das ist die Datei, an der wir alle am Bericht arbeiten. Jede
> Änderung am Berichtstext kommt hier hinein, nicht in die einzelnen Kapiteldateien, nicht in
> Google Docs und nicht in Notion. Wer was geändert hat, zeigt die Git-Historie dieser Datei.
>
> **Ablauf:** vor dem Schreiben `git pull` · Änderung in dieser Datei · kurzer Commit, der sagt,
> was geändert wurde · sofort `git push`. Vorher im Gruppenchat sagen, an welchem Kapitel man
> sitzt. Bilder liegen in `screenshots/` neben dieser Datei.
>
> **Schreibregeln:** Wir-Perspektive · keine Namen im Fließtext, die Schwerpunkte stehen in 3.1 ·
> auf Projektebene bleiben, keine Werkzeugpannen oder Terminfragen · KI nur bei der
> Implementierung und als Funktion der App erwähnen · Abbildungen kapitelweise nummeriert
> („Abb. 8.3") mit kursiver Bildunterschrift · Querverweise als „Abschnitt 7.8".

# 1. Einleitung

## 1.1 Was entwickelt wurde

**Align** ist eine Anwendung, die Studierende dabei unterstützt, gute Gewohnheiten in ihren
Alltag einzubauen. Sie soll mehr Struktur, Fokus und Balance in den Studienalltag bringen, der
oft weniger feste Abläufe vorgibt als zum Beispiel die Schulzeit.

Bei Align geht es vor allem um die praktische Umsetzung. Gewohnheiten werden nicht nur
festgelegt, sondern direkt als konkrete Zeitblöcke in den Tag eingeplant. Dabei berücksichtigt
die Anwendung den eigenen Schlafrhythmus und den Stundenplan. Zusätzlich gibt es eine
KI-Unterstützung, die vor allem dann hilft, wenn der Einstieg schwerfällt. Außerdem schlägt sie
einen neuen Platz im Tag vor, wenn sich der Alltag verändert, etwa weil ein neuer Stundenplan
eine Gewohnheit verdrängt.

Umgesetzt haben wir Align als funktionsfähige **MVP-Version** in Form einer
Mobile-First-Web-App. Warum wir uns für diese Form entschieden haben, erklären wir in
Kapitel 7.

## 1.2 Ausgangssituation und Motivation

Die Idee zu Align entstand aus unseren eigenen Erfahrungen im Studium. Im Studienalltag ist man
für die Planung seiner Zeit größtenteils selbst verantwortlich. Dadurch kommt es immer wieder
vor, dass man ohne richtigen Plan in den Tag startet oder Vorhaben auf einen anderen Tag
verschiebt. Am Abend merkt man dann, dass man weniger geschafft hat, als man eigentlich wollte.

Dabei geht es nicht nur um Aufgaben für das Studium. Auch Sport, ausreichend Schlaf oder
regelmäßige Pausen möchte man in den Alltag einbauen. Meistens weiß man, was einem guttut.
Trotzdem ist es nicht einfach, solche Vorhaben über einen längeren Zeitraum beizubehalten.

Bei anderen Studierenden in unserem Umfeld haben wir ähnliche Erfahrungen mitbekommen. Oft fehlt
nicht die Motivation. Das größere Problem ist, dass gute Vorsätze im Alltag schnell vergessen
oder aufgeschoben werden, besonders wenn sich der Tagesablauf häufig ändert.

Bestehende Anwendungen haben uns dabei nicht wirklich überzeugt. Einige bieten sehr viele
Funktionen und wirken dadurch unübersichtlich. Andere arbeiten stark mit Erinnerungen oder fühlen
sich nach einiger Zeit wie eine weitere Aufgabe an, die man erledigen muss. Mit Align wollten wir
deshalb eine Anwendung entwickeln, die sich einfach in den Alltag einbauen lässt, keinen
zusätzlichen Druck erzeugt und trotzdem genug Struktur gibt, um dranzubleiben.

## 1.3 Problemstellung

Aus dieser Ausgangssituation haben wir drei Probleme abgeleitet, die den Anstoß für unser Projekt
gegeben haben. Alle drei haben wir später in unserer Nutzerforschung untersucht und bestätigt
gefunden (Kapitel 5).

### 1.3.1 Fehlende Alltagsstruktur im Studium

Im Studium gibt es oft keine feste Tagesstruktur mehr, wie man sie aus der Schule kennt.
Vorlesungszeiten ändern sich von Semester zu Semester, es gibt Freistunden, und für das
Selbststudium ist man selbst verantwortlich. Dadurch werden Aufgaben schnell aufgeschoben, oder
der Tag vergeht ohne richtigen Plan. Besonders in vorlesungs- und prüfungsfreien Phasen ist es
schwierig, eine feste Struktur beizubehalten.

### 1.3.2 Gute Vorsätze werden selten zu Routinen

Das Problem liegt oft nicht darin, sich etwas vorzunehmen, sondern darin, daraus eine feste
Routine zu machen. Bestehende Habit-Tracker sind teilweise sehr komplex, stark auf spielerische
Elemente ausgelegt oder zu allgemein gehalten. Dadurch helfen sie nicht jedem, langfristig
dranzubleiben.

Dazu kommt, dass diese Anwendungen kaum auf den tatsächlichen Alltag eingehen. Sie behandeln
Gewohnheiten unabhängig davon, wie ein Tag aussieht. Ändert sich der Tag, etwa durch einen neuen
Stundenplan, bleibt der Plan trotzdem derselbe. Je weiter Plan und Alltag auseinandergehen, desto
eher legt man die Anwendung irgendwann ganz beiseite.

### 1.3.3 Keine Anwendung, die sich dem eigenen Alltag anpasst

Bei bestehenden Lösungen fehlt uns vor allem, dass sich die Planung an den eigenen Alltag
anpasst. Nicht nur der Nutzer sollte sich nach der Anwendung richten, sondern auch die Anwendung
nach dem Alltag der Person. Wichtig ist zum Beispiel, wie viel Zeit an einem Tag zur Verfügung
steht, in welcher Phase des Semesters man sich befindet und welche Termine ohnehin feststehen.

Genau hier sehen wir den sinnvollen Einsatz von KI. Sie soll helfen, die Planung an die jeweilige
Situation anzupassen, und unterstützen, wenn es einmal nicht nach Plan läuft. Das ist zugleich
der Punkt, durch den sich Align von bestehenden Anwendungen unterscheiden soll.

## 1.4 Zielsetzung und Leitfrage

Aus den beschriebenen Problemen ergibt sich die Leitfrage unseres Projekts:

> **„Wie kann eine mobile Applikation Studierende durch minimalistisches Design,
> kontextsensitive KI und verhaltenspsychologisch fundierte Mechanismen dabei unterstützen,
> nachhaltige Alltagsgewohnheiten zu etablieren?"**

Um diese Frage zu beantworten, haben wir auf Grundlage unserer Nutzerforschung eine
funktionsfähige MVP-Version entwickelt und Schritt für Schritt verbessert. Die drei Bereiche der
Leitfrage dienten uns dabei als Auftrag für Gestaltung und Entwicklung.

| Begriff | Was daraus folgte |
|---|---|
| **Minimalistisches Design** | wenige, klar getrennte Bereiche · eine durchgängige Designsprache in Light und Dark Mode · keine visuelle Überladung (Kapitel 6) |
| **Kontextsensitive KI** | Vorschläge, die den tatsächlichen Tag kennen, also Schlafrhythmus, Stundenplan und bestehende Gewohnheiten (Kapitel 7) |
| **Verhaltenspsychologisch fundierte Mechanismen** | Wenn-Dann-Pläne, Trigger- und Kontextbindung, Konsistenz statt Serie; jede Funktion auf eine Quelle zurückführbar (Kapitel 4 und 6) |

Der dritte Bereich hat unser Vorgehen am stärksten geprägt. Uns war wichtig, dass Nutzer ihre
Gewohnheiten nicht wegen Punkten, Belohnungen oder anderen Spielelementen einhalten. Wir wollten
verstehen, **warum** Gewohnheiten entstehen und **woran** sie scheitern.

Deshalb haben wir uns zu Beginn des Projekts mit Verhaltens- und Motivationspsychologie
beschäftigt. Dazu gehörten Implementation Intentions, bei denen ein Verhalten mit einer
konkreten Situation verbunden wird. Außerdem haben wir uns angesehen, wie Auslöser und Kontext
helfen, eine Gewohnheit regelmäßig auszuführen. Weitere Themen waren das Domino-Prinzip und
Befunde dazu, wie lange der Aufbau einer Gewohnheit dauert und welche Rolle regelmäßige
Wiederholung dabei spielt.

Aus diesen Grundlagen sind die wichtigsten Funktionen von Align entstanden, nicht aus
Belohnungslogiken. Dazu gehören Situations-Anker als Alternative zu festen Uhrzeiten, Gewohnheitsketten, eine
Konsistenzrate statt einer Serie und der bewusste Verzicht auf Bestrafung, wenn eine Gewohnheit
einmal nicht klappt. Die Quellen und wie wir sie umgesetzt haben, beschreibt Kapitel 4.

## 1.5 Zielgruppe

Die Zielgruppe von Align sind Studierende im deutschsprachigen Raum, unabhängig vom Semester. In
unserer Nutzerforschung haben wir deshalb bewusst Studierende aus verschiedenen Studienphasen
einbezogen, vom ersten bis über das siebte Semester hinaus.

Aus der Nutzerforschung haben sich zwei typische Gruppen ergeben, die wir als Personas für die
weitere Entwicklung genutzt haben (Abschnitt 5.5):

- **„Die Selbstregulierten"** sind Studierende aus höheren Semestern, die ihren Alltag bereits
  selbstständig organisieren und funktionierende Routinen haben. Belastend ist für sie vor allem
  der Druck durch Streaks und der Vergleich mit anderen.
- **„Die Einsteiger"** sind Studierende aus mittleren Semestern ohne feste Routinen. Ihre größte
  Schwierigkeit ist, überhaupt einen Einstieg zu finden, ohne sich zu überfordern.

## 1.6 Anspruch an das Endprodukt

Zu Beginn haben wir sechs Ansprüche festgelegt, an denen sich alle späteren Entscheidungen messen
lassen mussten. Über allen stand der erste: Jede Funktion sollte so einfach zu bedienen sein,
dass sie ohne Erklärung verständlich ist. Eine Anwendung, deren Bedienung selbst Überwindung
kostet, verstärkt genau das Problem, das sie lösen soll. Deshalb haben wir bei jeder Entscheidung
gefragt, ob sie einen zusätzlichen Schritt, eine zusätzliche Frage oder eine zusätzliche
Erklärung verlangt, und solche Schritte so weit wie möglich vermieden.

- einfache und verständliche Bedienung, damit die Anwendung nicht selbst zum Hindernis wird
- langfristige Motivation statt Druck, ohne Bestrafung
- klare Struktur und wenig visuelle Ablenkung
- einheitlicher Aufbau aller Bereiche, in Light und Dark Mode
- Anpassung an den eigenen Alltag, mit Situations-Ankern als Alternative zu festen Uhrzeiten
- Gestaltung auf Grundlage der Verhaltens- und Motivationspsychologie

## 1.7 Aufbau dieses Berichts

Der Bericht folgt dem Ablauf unseres Projekts und ist in drei Teile gegliedert. Die sechs
Iterationen sind den jeweiligen Entwicklungsphasen zugeordnet.

**Teil I** beschreibt den Rahmen des Projekts, also Problemraum und Marktumfeld (Kapitel 2) sowie
unser Vorgehen und die Zusammenarbeit im Team (Kapitel 3).

**Teil II** bildet den Hauptteil und folgt dem Projektverlauf: Analyse und Grundlagen
(Kapitel 4), Nutzerforschung (Kapitel 5), Konzeption und Design (Kapitel 6) und technische
Umsetzung (Kapitel 7). Zu jeder Phase gehören ihre Ergebnisse und das Feedback aus den
Betreuungsgesprächen.

**Teil III** stellt das Ergebnis vor (Kapitel 8) und schließt mit Reflexion und Ausblick
(Kapitel 9).

---

# 2. Problemraum und Markt

Bevor wir einzelne Funktionen geplant haben, mussten wir grundlegende Fragen klären: Für wen
entwickeln wir die Anwendung, welche Informationen brauchen wir über unsere Zielgruppe, und
welche ähnlichen Anwendungen gibt es bereits? Dieses Kapitel fasst deshalb die
Anforderungsanalyse und die Competitor-Analyse zusammen. Beide sind in Phase 1 entstanden
(Kapitel 4).

## 2.1 Stakeholder und Informationsbedarf

Primäre Stakeholder von Align sind **Studierende zwischen 18 und 35 Jahren**, denen es
schwerfällt, Routinen aufzubauen, oder die sich in ihrem Alltag überfordert fühlen.

Als sekundäre Gruppe haben wir Fachleute aus Psychologie und Verhaltensforschung betrachtet. Auch
wenn sie die Anwendung nicht selbst nutzen, ist ihre fachliche Sicht für die Gestaltung wichtig.
In unser Projekt ist sie über die wissenschaftliche Literatur eingeflossen (Kapitel 4).

Auf dieser Grundlage haben wir festgelegt, was unsere Nutzerforschung herausfinden sollte. Dabei
haben sich fünf Bereiche ergeben:

| Bereich | Leitfragen |
|---|---|
| **Alltag und Routinen** | Wie sieht ein typischer Tag aus? Welche Routinen bestehen bereits, welche funktionieren und welche nicht? In welchem Bereich ist die Überforderung am größten? |
| **Gewohnheiten** | Welche Gewohnheiten wurden bereits versucht? Warum sind diese Versuche gescheitert? Was hat bei den erfolgreichen geholfen? |
| **Motivation** | Was motiviert grundsätzlich? Wie wird mit Rückschlägen umgegangen? |
| **Technologie** | Welche Anwendungen wurden ausprobiert? Was funktionierte, was hat gestört? Wie lange darf ein Check-in höchstens dauern? |
| **Zeit** | Wie viele neue Gewohnheiten sind gleichzeitig realistisch? |

Diese Fragen haben wir in Phase 2 mit zwei Methoden untersucht, mit qualitativen
Leitfadeninterviews und einer quantitativen Online-Umfrage (Kapitel 5). Aus dem letzten Punkt
entstand später eine konkrete Regel für unser Produkt (Abschnitt 6.3).

## 2.2 Competitor-Analyse

### Ziel und Methodik

Mit der Competitor-Analyse wollten wir herausfinden, welche ähnlichen Anwendungen es bereits gibt
und wo noch Platz für Align ist. Außerdem wollten wir sehen, welche Funktionen und Ansätze bei
bestehenden Anwendungen gut funktionieren und was wir bei unserem MVP besser vermeiden.

Zum Stand vom 19. Mai 2026 haben wir **acht Anwendungen** untersucht. Dabei haben wir uns vier
Bereiche genauer angesehen: Zielgruppe und Positionierung, Funktionen, Gamification und UX sowie
den Einsatz von KI. Grundlage waren App-Store-Einträge, unabhängige Rezensionen und
Marktanalysen, dazu unsere eigenen Erfahrungen mit den Anwendungen. Uns ging es nicht nur um
einen Vergleich der Funktionen, sondern auch darum zu verstehen, welche Ansätze für Align
interessant sind.

Bei der Auswahl haben wir bewusst nicht nur direkte Konkurrenten betrachtet, sondern auch
Anwendungen aus angrenzenden Bereichen, die Studierende im Alltag ohnehin nutzen:

| Kategorie | Anwendungen | Warum ausgewählt |
|---|---|---|
| **Direkte Wettbewerber** | Habitica · Fabulous · Finch · Streaks | die zwei dominanten Habit-Apps mit gegensätzlichen Philosophien (Spiel vs. Coaching), die derzeit populärste App bei jüngeren Zielgruppen sowie der minimalistische Gegenpol |
| **Indirekte Wettbewerber** | Forest · Headspace · Notion · Athenify | Marktführer im Fokus-Segment · Wellness-Anbieter mit ernsthafter KI-Integration · das Werkzeug, mit dem Studierende ihre Routinen tatsächlich strukturieren · die einzige Lösung im deutschsprachigen Raum mit Studierendenfokus |

### Die untersuchten Anwendungen

**Habitica** verbindet den Aufbau von Gewohnheiten mit einer Rollenspiel-Mechanik, mit eigenem
Avatar, Erfahrungspunkten und gemeinsamem Spielen. Das kann motivieren, der starke Fokus auf das
Spiel lenkt aber auch von der eigentlichen Gewohnheit ab. Für Align haben wir daraus
mitgenommen, spielerische Elemente nur dezent einzusetzen.

**Fabulous** kombiniert Habit Stacking mit Audio-Coaching und geführten Programmen und beruht auf
wissenschaftlichen Ansätzen. Die Anwendung bietet aber sehr viele Inhalte und wirkt eher wie eine
Bibliothek als wie eine persönliche Begleitung. Für Align haben wir mitgenommen, dass
wissenschaftliche Grundlagen helfen, die Unterstützung sich aber am tatsächlichen Alltag
orientieren sollte und nicht an vorgefertigten Programmen.

**Finch** arbeitet mit einem virtuellen Begleiter, der mit den eigenen Fortschritten wächst, und
verzichtet bewusst auf Druck durch Serien. Die freundliche, warme Ansprache trifft die Zielgruppe
gut, der wissenschaftliche Anspruch bleibt aber gering. Auch Align soll unterstützen, ohne Druck
aufzubauen, allerdings mit einer fundierteren Grundlage.

**Streaks** ist sehr einfach und minimalistisch aufgebaut und konzentriert sich auf das Tracken
von Gewohnheiten. Dadurch bleibt die Anwendung übersichtlich, bietet aber kaum persönliche
Unterstützung. Für Align heißt das: Ein einfacher Aufbau ist sinnvoll, ein reiner Tracker reicht
aber nicht aus.

**Forest** verbindet Fokuszeiten mit einer virtuellen Pflanze, die beim konzentrierten Arbeiten
wächst. Das einfache Motivationsprinzip kommt bei Studierenden gut an. Die Anwendung beschränkt
sich aber auf Fokuszeiten und hilft nicht beim Aufbau verschiedener Gewohnheiten.

**Headspace** konzentriert sich auf mentale Gesundheit und hat mit dem Begleiter „Ebb" die
ausgereifteste KI-Integration unter den untersuchten Anwendungen. Die Planung von Gewohnheiten
steht dort aber nicht im Mittelpunkt. Für unsere eigene KI-Assistenz war Headspace die wichtigste
Referenz, vor allem dafür, dass KI persönlich und einfühlsam wirken muss und nicht klinisch.

**Notion** ist kein direkter Konkurrent, wird aber von vielen Studierenden genutzt, um ihren
Alltag zu organisieren. Eigene Routinen und Tracker lassen sich flexibel erstellen, brauchen aber
viel Einrichtung und müssen selbst gepflegt werden. Die meisten dieser selbstgebauten Tracker
scheitern daran, dass man sie nicht regelmäßig nutzt. Align soll diesen Aufwand verringern und
mehr Unterstützung bei der Planung bieten.

**Athenify** richtet sich gezielt an Studierende im deutschsprachigen Raum und unterstützt vor
allem bei der Organisation von Lernzeiten, mit datenschutzkonformer Infrastruktur. Der
Schwerpunkt liegt dadurch stark auf dem Lernen. Align verfolgt einen breiteren Ansatz und bezieht
neben dem Studium auch Schlaf, Bewegung und Balance ein.

### Vergleichsmatrix

| Anwendung | Zielgruppe | Habit-Logik | Gamification | KI | DACH-Fit |
|---|---|---|---|---|---|
| Habitica | Gamer | ★★★★ | ★★★★★ (Rollenspiel) | – | ★★ |
| Fabulous | Wellness-Interessierte | ★★★★ | ★★ (mild) | ★★ (Content) | ★★ |
| Finch | Gen Z, neurodivergent | ★★★ | ★★★ (Begleiter) | ★ | ★★ |
| Streaks | Apple-Nutzer | ★★★ | ★ (Serien) | – | ★ |
| Forest | Studierende | ★ (nur Fokus) | ★★★ | – | ★★★ |
| Headspace | breite Wellness | ★★ | ★ | ★★★★ | ★★★ |
| Notion | Studierende, Power-User | ★★ (Eigenbau) | ★ | ★★ | ★★★★ |
| Athenify | DACH-Studierende | ★★ (nur Lernen) | ★★ | ★★ (analytisch) | ★★★★★ |
| **Align (Ziel)** | **DACH-Studierende** | **★★★★** | **★★★ (subtil)** | **★★★★** | **★★★★★** |

## 2.3 Vier Marktlücken

Aus der Analyse haben sich vier Bereiche ergeben, die keine der untersuchten Anwendungen
vollständig abdeckt:

**1 · Die Lebensrealität Studierender als Produktlogik.** Keine der acht Anwendungen
berücksichtigt den Studienalltag mit Semestern, Prüfungsphasen und wechselnden Vorlesungszeiten.
Athenify kommt diesem Ansatz am nächsten, konzentriert sich aber auf das Lernen. → *Align kann
den Stundenplan und den Ablauf eines Semesters zur Grundlage der Planung machen.*

**2 · Planen findet am Laptop statt.** Fast alle spezialisierten Anwendungen sind vor allem für
das Smartphone gebaut. Studierende verbringen aber viel Zeit am Laptop, besonders beim Lernen und
Planen. Notion zeigt, dass das Planen und Tracken von Gewohnheiten auch im Browser funktioniert.

**3 · KI als echter Begleiter statt als Content-Bibliothek.** Bei Habit-Trackern wird KI bisher
kaum eingesetzt. Wo es sie gibt, liefert sie meist vorgefertigte Inhalte, statt auf die
Situation der Person einzugehen. → *Align kann Vorschläge machen, die den tatsächlichen Tag
kennen.*

**4 · Subtile statt aggressiver Gamifizierung.** Starke Gamifizierung wie bei Habitica wird
schnell zu viel, und reine Serien erzeugen Druck, weil schon ein ausgelassener Tag den
Fortschritt unterbricht. Finch zeigt, dass ein nicht bestrafender Ansatz bei jüngeren
Zielgruppen besser ankommt. → *Align setzt auf einfache Fortschrittsanzeigen ohne Verlustdruck.*

## 2.4 Positionierung

Daraus haben wir folgende Positionierung abgeleitet:

> Für Studierende, die Struktur und Balance im Studienalltag suchen, ist Align die einzige
> Anwendung zum Gewohnheitsaufbau, die ihre Lebensrealität versteht, mit einem
> kontextsensitiven KI-Begleiter und verhaltenspsychologisch fundierten Mechanismen, die
> ohne Druck zur Konsistenz führen.

Im Vergleich dazu ist Habitica sehr stark auf Spielelemente ausgerichtet, Fabulous eher allgemein
gehalten, Notion verlangt viel eigene Einrichtung, und Athenify konzentriert sich auf das Lernen.
Align soll diese Bereiche in einer einfachen, übersichtlichen Anwendung verbinden und dabei auf
wissenschaftlichen Grundlagen aufbauen, die den regionalen Alternativen fehlen.

## 2.5 Was daraus für das MVP folgte

Die Analyse endete mit einer Priorisierung. Dabei haben wir festgelegt, welche Bestandteile für
Align gesetzt sind, welche später dazukommen können und worauf wir bewusst verzichten:

| | |
|---|---|
| **Gesetzt** | Stundenplan-Integration als Alleinstellungsmerkmal · kurzer täglicher Check-in · KI an wenigen, gezielten Berührungspunkten · Fortschrittsanzeige mit Schutz vor Abbruchdruck |
| **Später** | soziale Funktionen, erst wenn der Einzelnutzen klar ist · wenige, gezielte Erfolgsmarken · ein einfacher Fokusmodus |
| **Bewusst nicht** | eigene Meditationen oder Workouts produzieren · komplexe Rollenspielmechanik · verlustaversive Mechanismen, die dem eigenen Tonalitätsziel widersprechen |

Zwei dieser frühen Entscheidungen waren für den weiteren Projektverlauf besonders wichtig.

Die **Stundenplan-Integration** entstand aus der Marktanalyse und war zu diesem Zeitpunkt nur eine
Annahme. Unsere Nutzerforschung hat sie später indirekt gestützt. 17 von 25 Befragten nannten
Stress und Prüfungsphasen als größten Grund, eine Gewohnheit aufzugeben, und 20 von 25 planen
ohnehin mit einem Kalender oder Planer. In Phase 4 haben wir sie als Semesterplan umgesetzt
(Abschnitt 7.11). Damit hat dieser Gedanke unser Projekt von der ersten Analyse bis in die
fertige Anwendung begleitet.

Auch auf **verlustaversive Mechanismen** wollten wir von Anfang an verzichten. Die
Nutzerforschung hat diese Entscheidung doppelt bestätigt, durch die Interviews und durch den
hohen Schuldwert in der Umfrage. In der Anwendung zeigt sie sich an neutral dargestellten
Fehltagen, an einer Serie, die bei einem verpassten Tag nicht zusammenbricht, und an einer
Konsistenzrate, die nur die Tage zählt, an denen eine Gewohnheit tatsächlich geplant war.

---

# 3. Vorgehen und Zusammenarbeit

## 3.1 Das Team

Align haben wir zu dritt entwickelt, **Berkay**, **Silas** und **Ngoc Ha**. Wir studieren alle
E-Commerce an der Technischen Hochschule Würzburg-Schweinfurt. Eine Kommilitonin, Prabjot Kaur,
war zu Projektbeginn eingeplant, konnte wegen eines parallel beginnenden Praktikums aber nicht
mitarbeiten. Betreut wurde das Projekt von **Frau Heß**.

Die Recherche, die Interviews und alle wichtigen Konzeptentscheidungen haben wir gemeinsam
erarbeitet. Im Laufe des Projekts haben sich nach Interessen und Vorkenntnissen unterschiedliche
Schwerpunkte entwickelt, eine strikte Aufgabenverteilung gab es aber nicht. Alle drei waren an
Konzeption, Gestaltung und technischer Umsetzung beteiligt.

| | Schwerpunkt |
|---|---|
| **Berkay** | Competitor-Analyse, Personas, Interview-Leitfaden, technische Umsetzung |
| **Silas** | Literaturrecherche/-analyse, Endfassung der Online-Umfrage, Feature-Ableitung, technische Umsetzung |
| **Ngoc Ha** | Wireframes und Design, Erstfassung der Umfrage, Zwischenpräsentationen, Dokumentation |

## 3.2 Phasen und Iterationen

An Align haben wir vom 17. Mai bis zum 7. September 2026 gearbeitet. Das Projekt war in vier
Entwicklungsphasen und **sechs Iterationen** gegliedert. In jeder Iteration haben wir uns
bestimmte Aufgaben und Ziele vorgenommen, an denen wir bis zum nächsten Betreuungsgespräch
gearbeitet haben. Dort haben wir unseren Stand vorgestellt, offene Fragen besprochen und Feedback
bekommen. Danach haben wir gemeinsam entschieden, was wir anpassen und worauf wir uns in der
nächsten Iteration konzentrieren. Zwischen zwei Gesprächen lagen meist etwa drei Wochen.

| Phase | Iterationen | Gespräche | Schwerpunkt |
|---|---|---|---|
| **1 · Analyse und Grundlagen** | Iteration 1 | 20.05. | Literatur, Competitor-Analyse, erste Wireframes |
| **2 · Nutzerforschung** | Iterationen 2 und 3 | 08.06. · 29.06. | Interviews, Personas, Online-Umfrage, Feature-Priorisierung |
| **3 · Konzeption und Design** | Iteration 4 | 20.07. | Feature-Ausarbeitung, Designsprache, Prototypen |
| **4 · Technische Umsetzung** | Iterationen 5 und 6 | 10.08. · 07.09. | Aufbau der Anwendung, Ausbau, zuverlässigere Abläufe |

Wir sind bewusst Schritt für Schritt vorgegangen. Statt den gesamten Projektverlauf von Anfang an
festzulegen, haben wir nach jeder Iteration geschaut, welche Ergebnisse vorliegen und was als
Nächstes sinnvoll ist. Deshalb brauchten manche Phasen eine Iteration und andere zwei.

Die Nutzerforschung haben wir auf zwei Iterationen verteilt. Zuerst haben wir qualitative
Interviews geführt, um Annahmen zu bilden und neue Erkenntnisse zu sammeln. Danach folgte eine
quantitative Umfrage, mit der wir diese Ergebnisse in einer größeren Gruppe überprüft haben. Auch
die technische Umsetzung lief über zwei Iterationen. Zuerst ging es um eine funktionierende
Grundlage, danach haben wir darauf aufgebaut und die Anwendung weiter ausgearbeitet.

Durch die Betreuungsgespräche hatten wir etwa alle drei Wochen einen festen Termin, zu dem wir
einen Stand zeigen mussten. Dadurch haben wir Ideen nicht lange nur diskutiert, sondern umgesetzt
und überprüft. So haben wir einige Entscheidungen früh angepasst, zum Beispiel die
Fortschrittsanzeige und die Farbwelt.

## 3.3 Zusammenarbeit und Dokumentation

Für die Abstimmung im Team haben wir einen gemeinsamen Gruppenchat und regelmäßige Videocalls
genutzt, die Betreuungsgespräche fanden über Zoom statt. Das hat gut funktioniert, ein
zusätzliches Projektmanagement-Werkzeug brauchten wir nicht.

Als gemeinsame Arbeitsumgebung haben wir **Notion** verwendet. Dort haben wir für jede Iteration
das Feedback aus dem Betreuungsgespräch, die nächsten Aufgaben und wichtige Zwischenergebnisse
festgehalten. Uns war wichtig, die Dokumentation während des Projekts zu führen, statt am Ende
alles nachträglich zusammenzutragen. Dadurch konnten wir später viele Entscheidungen und
Entwicklungsschritte nachvollziehen. Mit Beginn der technischen Umsetzung haben wir die
Dokumentation in Markdown-Dateien im Projektverzeichnis weitergeführt, wo sie gemeinsam mit dem
Code versioniert wird.

Zu jedem Betreuungsgespräch haben wir eine kurze Präsentation vorbereitet, die unseren Stand und
die Ergebnisse der Iteration zusammenfasst. So hatte jede Iteration einen klaren Abschluss, und
der Fortschritt über das gesamte Projekt blieb nachvollziehbar.

## 3.4 Eingesetzte Werkzeuge

| Zweck | Werkzeug |
|---|---|
| Abstimmung und Betreuungsgespräche | Gruppenchat, Videocalls, Zoom |
| Projektdokumentation | Notion, später Markdown im Projektverzeichnis |
| Zwischenpräsentationen | Canva |
| Design und Prototyping | Figma |
| Online-Umfrage | LimeSurvey |
| Literaturrecherche | THWS-Bibliothek, Springer, ResearchRabbit |
| Entwicklung | Laravel Herd, VS Code, GitHub, KI-gestützte Entwicklungswerkzeuge |

Drei Entscheidungen zu Werkzeugen waren für unsere Arbeit besonders wichtig.

**LimeSurvey statt Google Forms.** Für unsere Umfrage wollten wir zuerst Google Forms verwenden.
Im ersten Betreuungsgespräch wies uns Frau Heß jedoch darauf hin, besonders auf Datenschutz und
Anonymität zu achten. Deshalb haben wir die Umfrage in LimeSurvey erstellt (Kapitel 5).

**Figma als verbindliche Designquelle.** Ab Iteration 2 haben wir in einer gemeinsamen
Figma-Datei gearbeitet und dort Farben, Typografie und Komponenten festgelegt. Sie blieb bis zum
Projektende unsere Grundlage für alle Gestaltungsfragen, auch gegenüber den später daraus
abgeleiteten Prototypen.

**KI-gestützte Entwicklungswerkzeuge.** In Phase 4 haben wir beim Programmieren mit einem
KI-gestützten Entwicklungswerkzeug gearbeitet. Es hat uns geholfen, den Funktionsumfang in der
verfügbaren Zeit umzusetzen. Seine Konfiguration liegt offen im Quellcode-Repository. Abschnitt
7.1 ordnet den Einsatz in die technische Umsetzung ein, Abschnitt 9.4 reflektiert ihn.

---

# 4. Phase 1 — Analyse und Grundlagen

**Zeitraum:** 17. bis 20. Mai 2026 · **Iteration 1**, Betreuungsgespräch am 20. Mai

In der ersten Phase ging es uns vor allem darum, unsere Projektidee einzuordnen und eine erste
Grundlage für die weitere Entwicklung zu schaffen. Zwischen der Bildung unserer Arbeitsgruppe und
dem ersten Betreuungsgespräch lagen nur drei Tage. In dieser kurzen Zeit wollten wir noch kein
fertiges Konzept entwickeln, sondern eine sinnvolle Projektidee vorstellen und eine erste
Richtung für Align festlegen.

## 4.1 Literaturrecherche

Für die inhaltliche Grundlage haben wir gezielt nach wissenschaftlichen Quellen aus der
Verhaltens- und Motivationspsychologie gesucht. Populärwissenschaftliche Literatur haben wir
dabei bewusst ausgeschlossen. Uns war wichtig, spätere Entscheidungen für Align auf
wissenschaftlich fundierte Quellen stützen zu können.

Aus der Recherche haben sich drei Quellen ergeben, die für Align besonders wichtig waren:

| Quelle | Kernbefund | Wirkung im Produkt |
|---|---|---|
| **Lally et al. (2010)** | Median 66 Tage bis zur stabilen Gewohnheit (Spanne 18 bis 254). Konsistenz ist der wichtigste Prädiktor, nicht die absolute Anzahl der Ausführungen; einzelne Aussetzer haben keine messbaren Langzeitkosten. | Konsistenzrate statt Streak · kein Bestrafungsmechanismus · realistische Erwartungen |
| **Faude-Koivisto & Gollwitzer (2009)** | Wenn-Dann-Pläne (*Implementation Intentions*) verlagern die Verhaltenskontrolle von der Selbstdisziplin auf die Situation. Ein einziger bewusster Willensakt kann automatische Auslösung anstoßen. | Time Blocking · Situations-Anker als Alternative zu festen Uhrzeiten |
| **Becker (2024)** | Trigger- und Kontextbindung, Domino-Prinzip (eine Gewohnheit wird zum Auslöser der nächsten), Wirkung sozialer Unterstützung, Effekt sichtbaren Fortschritts | Habit Chains · Community · Progress Tracking |

Diese Erkenntnisse haben wir nicht erst im Nachhinein unseren Funktionen zugeordnet. Sie haben
schon während der Konzeption mitentschieden, welche Ansätze wir übernehmen. Auf Grundlage von
Lally et al. haben wir uns zum Beispiel gegen Streaks als wichtigste Kennzahl entschieden, und
Faude-Koivisto und Gollwitzer waren die Grundlage für unsere Situations-Anker.

## 4.2 Competitor-Analyse

Für die Competitor-Analyse haben wir acht Anwendungen untersucht und uns dabei Zielgruppe und
Positionierung, Funktionen, Gamification und UX sowie den Einsatz von KI angesehen. Vier davon
sind direkte Wettbewerber aus dem Bereich Habit Tracking. Die anderen vier stammen aus
angrenzenden Bereichen und wurden ausgewählt, weil Studierende sie im Alltag ohnehin nutzen.
Zusätzlich sind unsere eigenen Erfahrungen mit Finch, Habit Tracker und HabitShare eingeflossen.

| Kategorie | Anwendungen |
|---|---|
| Direkte Wettbewerber | Habitica · Fabulous · Finch · Streaks |
| Indirekte Wettbewerber | Forest · Headspace · Notion · Athenify |

Die vollständige Analyse mit den einzelnen Anwendungen und der Vergleichsmatrix steht in
Kapitel 2. Für die weitere Entwicklung von Align waren vor allem vier Erkenntnisse wichtig:

1. **Keine der untersuchten Anwendungen berücksichtigt den Ablauf eines Studiums.** Semester,
   Prüfungsphasen und wechselnde Vorlesungszeiten spielen kaum eine Rolle. Athenify kommt unserer
   Zielgruppe am nächsten, konzentriert sich aber auf das Lernen.
2. **KI wird im Habit Tracking kaum als persönliche Unterstützung eingesetzt.** Meist werden
   vorgefertigte Inhalte angeboten. Eine Ausnahme ist Headspace mit dem KI-Begleiter „Ebb".
3. **Zu starke Gamification erzeugt Druck statt Motivation.** Habitica setzt sehr stark auf
   Spielelemente, und bei reinen Streaks wird ein verpasster Tag schnell als Misserfolg
   empfunden. Für Align wollten wir deshalb einen zurückhaltenden Ansatz.
4. **Studierende planen nicht nur am Smartphone.** Zum Planen und Lernen nutzen sie häufig den
   Laptop. Notion zeigt, dass das Tracken von Gewohnheiten auch im Browser funktioniert, während
   fast alle spezialisierten Anwendungen vor allem für das Smartphone gebaut sind.

Besonders der erste Punkt wurde im Projektverlauf immer wichtiger. Daraus entstand später der
Semesterplan, das wichtigste Alleinstellungsmerkmal von Align (Kapitel 7).

## 4.3 Erste Visualisierungen und Arbeitsorganisation

Parallel zur Analyse haben wir erste Wireframes und einen klickbaren Entwurf erstellt. Konkrete
Funktionen standen dabei noch nicht im Mittelpunkt. Die Entwürfe sollten uns vor allem eine
gemeinsame Vorstellung davon geben, wie Align aussehen und aufgebaut sein könnte. Dabei entstand
schon die Farbrichtung, die uns bis zum Ende begleitet hat, also Beige, Schwarz und Gold, und
auch Light und Dark Mode waren von Anfang an vorgesehen.

Für die Zusammenarbeit haben wir in dieser Phase zwei Dinge festgelegt: Aufgaben verteilen wir
vor jeder Iteration gemeinsam, und zu jedem Betreuungsgespräch erstellen wir eine kurze
Präsentation.

## 4.4 Feedback von Frau Heß

Im ersten Betreuungsgespräch haben wir mehr Feedback bekommen, als wir erwartet hatten, und es
hat unser weiteres Vorgehen stark beeinflusst:

- **Reihenfolge der Nutzerforschung.** Zuerst Interviews führen und die Umfrage danach auf
  Grundlage der Ergebnisse erstellen, statt beides parallel zu entwickeln.
- **Datenschutz und Anonymität** bei der Umfrage beachten und dafür das Umfragewerkzeug der
  Hochschule nutzen.
- **Bias vermeiden.** In der Umfrage zuerst allgemeine Funktionen abfragen und unsere eigenen
  Ideen erst am Schluss.
- **Persona erarbeiten** und daran einen Vorher-Nachher-Vergleich durchspielen.
- **Sozialer Aspekt.** Nutzern das Gefühl geben, mit ihren Schwierigkeiten nicht allein zu sein,
  und gegenseitige Motivation ermöglichen.
- **Dokumentation.** Nicht nur Ergebnisse festhalten, sondern auch das Vorgehen bei den einzelnen
  Arbeitsschritten.
- **Nicht zu früh einschränken.** Gute Ideen nicht verwerfen, nur weil die technische Umsetzung
  zunächst schwierig erscheint.

## 4.5 Was daraus folgte

Der Hinweis zur Reihenfolge der Nutzerforschung war die folgenreichste Rückmeldung unseres
gesamten Projekts. Wir haben die nächste Phase deshalb in zwei Schritte aufgeteilt: zuerst
qualitative Interviews, danach eine quantitative Umfrage, die auf den Erkenntnissen der
Interviews aufbaut. Die Ergebnisse wurden zur Grundlage für die Auswahl unserer Funktionen.

Die Idee eines sozialen Bereichs haben wir direkt aufgegriffen und als eigene Funktion
vorgesehen. Daraus entstand unser Community-Feature (Kapitel 6).

Außerdem haben wir nach dem Gespräch begonnen, Feedback und nächste Schritte in Notion
festzuhalten. Damit startete unsere projektbegleitende Dokumentation (Abschnitt 3.3).

---

# 5. Phase 2 — Nutzerforschung

**Zeitraum:** 21. Mai bis 29. Juni 2026 · **Iterationen 2 und 3**, Betreuungsgespräche am
8. und 29. Juni

## 5.1 Der zweistufige Forschungsansatz

Auf den Hinweis von Frau Heß aus dem ersten Betreuungsgespräch haben wir unsere Nutzerforschung in zwei
Schritte aufgeteilt. Zuerst haben wir qualitative Leitfadeninterviews geführt, um mehr über die
Probleme und Erfahrungen von Studierenden zu erfahren und erste Annahmen zu bilden. Auf dieser
Grundlage haben wir anschließend eine quantitative Online-Umfrage erstellt. Mit ihr wollten wir
prüfen, ob sich die Erkenntnisse aus den Interviews auch in einer größeren Gruppe zeigen, und
herausfinden, welche Funktionen für unsere Zielgruppe am wichtigsten sind.

| Stufe | Methode | Umfang | Ziel |
|---|---|---|---|
| **1** | Leitfadeninterviews | n = 6 Studierende | Hypothesen zu Problemen und Bedürfnissen bilden, Grundlage für den Fragebogen |
| **2** | Online-Umfrage (LimeSurvey) | n = 25 abgeschlossene Antworten | Validierung der Hypothesen, Feature-Priorisierung |

Diese Reihenfolge war wichtig. Hätten wir die Umfrage zuerst erstellt, wären die Fragen vor allem
aus unseren eigenen Annahmen entstanden. So konnten wir uns an den tatsächlichen Erfahrungen der
Befragten orientieren, und jede Frage prüfte etwas, das vorher jemand wirklich gesagt hatte.

---

# Iteration 2 — Interviews und Personas

## 5.2 Der Interview-Leitfaden

Vor den Interviews haben wir gemeinsam einen Leitfaden mit dreizehn Fragen vorbereitet. Ein
Gespräch sollte etwa 20 bis 30 Minuten dauern. Die Fragen haben wir in fünf Phasen aufgeteilt:

1. **Intro und Warm-up.** Wir haben mit einer einfachen, konkreten Frage begonnen: „Wie sieht
   aktuell ein ganz normaler Dienstag bei dir im Semester aus?" So wollten wir zuerst verstehen,
   wie der Alltag der Person wirklich aussieht.
2. **Status quo und bisherige Lösungsversuche.** Welche Gewohnheiten verfolgt die Person gerade,
   und wie ist ihr letzter Versuch verlaufen, eine Gewohnheit regelmäßig durchzuziehen?
3. **Schmerzpunkte.** In welchen Situationen scheitert eine Gewohnheit, und wie geht es nach
   einer Unterbrechung weiter?
4. **Community und soziale Verbindlichkeit.** Helfen gemeinsame Gewohnheiten, und wie findet die
   Person Apps, die den Fortschritt von Freunden zeigen?
5. **Cool-down.** Zum Schluss eine offene Frage, was sie sich von einer Habit-App für den
   Studienalltag wünschen würde.

Wir sind bewusst vom Konkreten zum Allgemeinen gegangen. Wer zuerst seinen tatsächlichen Dienstag
beschreibt, antwortet auf die späteren Fragen weniger idealisiert. Außerdem konnten wir bei
interessanten Antworten gezielt nachfragen.

## 5.3 Durchführung und Auswertung

Zwischen dem 1. und 7. Juni haben wir sechs Interviews mit Studierenden aus verschiedenen
Studiengängen und Semestern geführt. Jede und jeder von uns hat zwei Interviews übernommen.
Gesprochen haben wir mit **Alissa, Hannah, Aylin, Danial, Felix und Ngoc Anh**.

Für die Auswertung haben wir uns auf ein gemeinsames Vorgehen geeinigt. Wir haben die Gespräche
aufgezeichnet, transkribiert und alle nach demselben Raster ausgewertet. Dabei haben wir vor allem
darauf geachtet, ob sich unsere bisherigen Ideen in den Gesprächen wiederfinden, ob neue Themen
auftauchen und welche Zitate das belegen. Zum Schluss haben wir die einzelnen Auswertungen in
einem gemeinsamen Dokument zusammengeführt und nach Themen sortiert. So konnten wir erkennen,
welche Punkte häufiger vorkamen.

## 5.4 Zentrale Erkenntnisse aus den Interviews

Aus den sechs Interviews haben sich mehrere Themen ergeben, die immer wieder vorkamen.

**Kontext beeinflusst Gewohnheiten stärker als feste Uhrzeiten.**

> „Wenn ich dann im Bett bin, kann ich es direkt machen." *(Alissa)*

Alissa verbindet das Lesen mit dem Moment, in dem sie ins Bett geht. Bei Felix zeigte sich etwas
Ähnliches: An Tagen, an denen er an der Uni ist, bewegt er sich automatisch mehr, für ein
Schrittziel würde er aber nicht extra spazieren gehen. Auch in den anderen Interviews waren
Gewohnheiten häufig an bestimmte Situationen gebunden und nicht an eine genaue Uhrzeit.

**Gewohnheiten hängen oft miteinander zusammen.**

> „Ich konnte alle anderen Sachen, diesen Domino-Effekt nicht beibehalten, weil einfach schon
> der Schlaf, das erste, schon schlecht angefangen hat." *(Danial)*

Besonders deutlich wurde das bei Aylin. Sie beschrieb eine ganze Kette rund um ihr Meal Prep:
abends vorbereiten → morgens mitnehmen → Bibliothek → arbeiten. Fällt der erste Teil weg, wirkt
sich das auf den ganzen restlichen Ablauf aus. Daraus haben wir die Anforderung abgeleitet, mit
Situations-Ankern als Alternative zu festen Uhrzeiten zu arbeiten und bei einer unterbrochenen Kette eine Alternative
anzubieten.

**Der Einstieg fällt oft schwer.**

> „Ich weiß oft nicht, wo ich anfangen soll, dann werde ich überfordert und fange erst gar
> nicht an." *(Ngoc Anh)*

> „Du brauchst so ein bisschen diesen leichten Dopaminschub von: ey, ich habe eine Sache
> abgehakt." *(Danial)*

In den Gesprächen wurde deutlich, dass nicht immer die Motivation das Problem ist. Oft fehlt ein
klarer erster Schritt, und kleine Erfolge helfen dabei, überhaupt anzufangen und weiterzumachen.

**Kein Druck, aber der Wunsch nach Selbstanalyse.**

> „Dann war das halt ein Ausrutscher. Und morgen machst du es halt dann wieder besser."
> *(Aylin)*

Ein verpasster Tag wurde nicht als Scheitern gesehen. Gleichzeitig wünschte sich Aylin, die
eigenen Gewohnheiten besser zu verstehen, wenn man sie „mal so vor Augen gehalten bekommt". Für
uns hieß das: Align soll ausgelassene Gewohnheiten nicht bestrafen, den eigenen Verlauf aber
sichtbar machen.

**Community ja, direkter Vergleich nein.**

> „Wenn du eine Verabredung hast, dann gehst du da natürlich auch mit einem anderen
> Pflichtbewusstsein ran, als wenn du das einfach nur für dich selber machen würdest."
> *(Aylin)*

Gemeinsame Gewohnheiten mit vertrauten Personen wurden positiv gesehen, ein direkter Vergleich mit
anderen dagegen kritisch:

> „Das löst dann kein positives Gefühl aus, dass ich mich für die Person freue, sondern eher so
> 'ne Kontrolle, bin ich auch soweit, muss ich noch was mehr tun." *(Hannah)*

Zusammengefasst haben sich über alle sechs Interviews drei Muster wiederholt. Rankings und
Vergleiche motivieren nicht langfristig, Community funktioniert vor allem mit Personen, die man
kennt, und Prüfungsphasen verändern bestehende Routinen stark.

## 5.5 Personas

Aus den sechs Interviews haben wir zwei unterschiedliche Verhaltenstypen abgeleitet und beide als
Persona-Sheets ausgearbeitet. Sie haben uns im weiteren Projektverlauf immer wieder als
Orientierung gedient.

### „Die Selbstregulierten", intrinsisch und selbstreguliert

![Persona „Die Selbstregulierten"](screenshots/personas/persona-1.png)

*Abb. 5.1: Das Persona-Sheet „Die Selbstregulierten", verdichtet aus vier Interviews.*

*Verdichtet aus Alissa, Hannah, Aylin und Felix. Höhere Semester, flexible Tage, bereits
funktionierende Routinen, die an Situationen hängen.*

> „Dann war das halt ein Ausrutscher. Morgen machst du es halt wieder besser." *(Aylin)*

Die Selbstregulierten haben keinen festen Tagesablauf, kommen mit dieser Flexibilität aber gut
zurecht. Ihre Gewohnheiten verbinden sie mit bestimmten Situationen oder Abläufen. Die Motivation
kommt von ihnen selbst und nicht aus dem Vergleich mit anderen. Apps mit Streaks haben sie
teilweise ausprobiert, aber nicht dauerhaft genutzt.

| | |
|---|---|
| **Ziele** | Gewohnheiten an Anker knüpfen statt an Uhrzeiten · Verbindlichkeit über echte Verabredungen · Fortschritt ohne Konkurrenz · das Warum sichtbar halten |
| **Frustrationen** | Streaks verdrängen das eigentliche Ziel · zeitbasierte Trigger gehen am Alltag vorbei · Apps werten Nicht-Nutzung als Versagen · lose soziale Absichten halten nicht |
| **Align-Hebel** | Wenn-Dann-Ketten an Kontexte · Habit-Buddies über konkrete Termine · Konsistenzrate statt Streak · Fehltage neutral |

### „Die Einsteiger", überfordert und inkonsistent

![Persona „Die Einsteiger"](screenshots/personas/persona-2.png)

*Abb. 5.2: Das Persona-Sheet „Die Einsteiger", verdichtet aus zwei Interviews.*

*Verdichtet aus Danial und Ngoc Anh. Mittlere Semester, keine feste Routine, brauchen einen
klaren ersten Schritt.*

> „Ich weiß oft nicht, wo ich anfangen soll, dann werde ich überfordert und fange erst gar
> nicht an." *(Ngoc Anh)*

Den Einsteigern fehlt weniger das Wissen darüber, was ihnen guttun würde. Schwierig ist es,
tatsächlich anzufangen und dranzubleiben. Besonders der Start in den Tag spielt eine große Rolle.
Läuft er nicht wie geplant, werden auch die nächsten Vorhaben aufgeschoben, und der ganze Domino
kippt.

| | |
|---|---|
| **Ziele** | einen stabilen Tagesanker finden, der den Rest mitzieht · den ersten Schritt vorgegeben bekommen · sichtbare Meilensteine · auch in der Prüfungsphase eine Kernroutine halten |
| **Frustrationen** | ein schlechter Start zerlegt den Tag · Überforderung führt zum Aufschieben · Ranking motiviert kurz, bricht langfristig weg |
| **Align-Hebel** | KI-Assistent formuliert den nächsten Mikroschritt · Gewohnheitsketten, die am ersten Anker des Tages hängen · Meilensteine statt Ranking |

Frau Heß hatte uns in Iteration 1 vorgeschlagen, anhand der Personas einen Vorher-Nachher-Vergleich
zu erstellen. Die Zeile „Align-Hebel" ist unsere Antwort darauf. Sie stellt jeder Frustration die
Funktion gegenüber, die sie auffangen soll.

## 5.6 Feedback von Frau Heß

Das Betreuungsgespräch verlief sehr positiv, Frau Heß war von unserem Stand überzeugt. Inhaltlich
kamen vier Punkte:

- **Eine offene Fachfrage.** Gibt es eine Höchstzahl an Gewohnheiten, auf die man sich
  gleichzeitig konzentrieren kann, ohne überfordert zu sein? Das sollten wir in unseren Quellen
  prüfen.
- **Persona-Fokus.** Für welche der beiden Personas entwickeln wir Align eigentlich?
- **Nicht zu früh einschränken.** Wir hatten teilweise schon früh überlegt, ob bestimmte
  Funktionen technisch machbar sind. Frau Heß riet uns, bei Ideen und Mockups zunächst größer zu
  denken und erst bei der Umsetzung zu entscheiden, was wir tatsächlich bauen. „Es muss nicht
  alles perfekt sein."
- **Design-System.** Farben, Typografie und Formen in einer gemeinsamen Figma-Datei festlegen,
  damit alle in dieselbe Richtung gestalten.

Die Frage nach der Höchstzahl hat uns am längsten begleitet. Aus ihr entstand die **Grenze von
fünf aktiven Gewohnheiten**, deren Umsetzung Kapitel 7 beschreibt. Beim Persona-Fokus wollten wir
uns nicht auf eine Gruppe festlegen, weil wir bei beiden Probleme gesehen haben, bei denen Align
helfen kann. Beantwortet haben wir die Frage deshalb über die Funktionen. Die Einsteiger brauchen
vor allem Hilfe beim Anfangen, also die KI-gestützte Starthilfe. Für die Selbstregulierten stehen
Situations-Anker und eine Fortschrittsanzeige ohne Druck im Vordergrund.

---

# Iteration 3 — Online-Umfrage und Priorisierung

## 5.7 Konzeption des Fragebogens

Nach den Interviews wollten wir die wichtigsten Erkenntnisse in einer größeren Gruppe überprüfen.
Dafür haben wir zuerst zwei Versionen der Umfrage erstellt: eine längere mit zwanzig Fragen, die
auch das aktuelle Verhalten erfasste, und eine kürzere mit zwölf Fragen, die sich auf die Punkte
konzentrierte, die nach den Interviews noch offen waren. Für die kurze Version sprach, dass
längere Umfragen häufiger abgebrochen werden.

In einem gemeinsamen Call haben wir beide Entwürfe durchgesprochen und schwache Fragen gestrichen
oder neu formuliert. Jede Frage sollte eine konkrete Entscheidung für Align prüfen, zum Beispiel
ob Streaks oder eine Konsistenzrate besser ankommen, ob soziale Funktionen gewünscht sind und wie
lang ein Check-in sein darf. Den Hinweis von Frau Heß, zuerst allgemeine Funktionen und erst danach unsere
eigenen Ideen abzufragen, haben wir in der Reihenfolge der Fragen umgesetzt. So wollten wir
vermeiden, die Antworten durch unsere eigenen Vorschläge zu beeinflussen.

## 5.8 Werkzeugwahl und Durchführung

Da Frau Heß aus Datenschutzgründen von Google Forms abgeraten hatte, haben wir die Umfrage in
**LimeSurvey** erstellt und vor der Veröffentlichung mehrfach überarbeitet. Die Umfrage konnten
auch Personen ausfüllen, die nicht studieren. Das haben wir gleich zu Beginn abgefragt. Außerdem
haben wir Alter und Geschlecht erfasst, damit wir die Zusammensetzung der Teilnehmenden
einschätzen können.

Am 14. Juni haben wir die Umfrage verteilt. Innerhalb der Hochschule haben wir sie in unserer
E-Commerce-Kohorte und bei den Erstsemestern geteilt, außerdem in anderen Hochschulgruppen und in
Gruppen von Studentenwohnheimen. So wollten wir möglichst unterschiedliche Teilnehmende
erreichen. Die Antworten kamen schneller als erwartet. Nach rund zwei Stunden hatten
**25 Personen** den Fragebogen vollständig ausgefüllt. Damit war die Kapazität des eingesetzten
Umfragewerkzeugs erreicht, und die Erhebung endete (Abschnitt 5.11).

## 5.9 Ergebnisse

**Stichprobe.** An der Umfrage haben 25 Personen vollständig teilgenommen, davon 24 Studierende.
16 waren weiblich und 9 männlich, 19 zwischen 21 und 25 Jahre alt. Bei den Semestern lag der
Schwerpunkt im 5. bis 6. Semester (12), gefolgt vom 1. bis 2. Semester (5).

**Wie organisieren sich Studierende heute?**

| Methode | Nennungen |
|---|---|
| Kalender oder Planer | 20 |
| Erinnerungen am Handy | 14 |
| Freunde / Lernpartner | 7 |
| Nichts Bestimmtes | 7 |
| **Habit-App** | **2** |

Nur 2 von 25 nutzen aktuell eine Habit-App, aber 20 von 25 arbeiten ohnehin mit einem Kalender
oder Planer, und 14 von 25 lassen sich vom Handy erinnern. Für uns hieß das zweierlei.
Bestehende Habit-Apps sind wenig verbreitet, und eine Planung mit Zeitblöcken knüpft an etwas
an, das Studierende bereits tun, statt ein neues Verhalten zu verlangen. Dieser Befund ist der
Ausgangspunkt für unser Time Blocking (Abschnitt 6.2). Später haben wir daraus den Kalender von
Align entwickelt, in dem jede Gewohnheit als Block mit Anfang und Ende im Tag liegt
(Abschnitt 7.10).

Bei den Gewohnheiten selbst standen **Lernen und Uni** (20), **Bewegung und Sport** (18) sowie
**Schlaf und Erholung** (16) im Vordergrund.

**Was macht es schwierig, Gewohnheiten beizubehalten?** Mit 17 von 25 Nennungen waren Stress und
Prüfungsphasen der mit Abstand häufigste Grund, eine Gewohnheit aufzugeben. Auffällig war, wie
die Befragten damit umgehen: 15 von 25 **reduzieren** ihre Gewohnheiten in solchen Phasen, statt
sie ganz aufzugeben. Daraus haben wir eine allgemeine Erkenntnis mitgenommen: Gewohnheiten
sollten klein genug sein, dass sie auch in vollen Wochen noch Platz haben, und der Einstieg
sollte leicht fallen. Eine eigene Funktion für Prüfungsphasen haben wir nicht umgesetzt,
Abschnitt 9.9 greift die Idee im Ausblick auf. Der dritthäufigste Grund war mit 10 Nennungen
schlicht „Ich vergesse es". Auch das spricht dafür, Gewohnheiten fest im Tag einzuplanen und
vorher an sie zu erinnern.

| Aussage (1 bis 5) | Ø |
|---|---|
| „Wenn ich eine Gewohnheit nicht einhalten konnte, fühle ich mich schuldig/enttäuscht." | **3,92** |
| „Ich weiß, was ich ändern will, aber es wird selten zur Routine." | 3,80 |
| „Wenn ich aus einer Routine rausgefallen bin, fällt mir der Wiedereinstieg schwer." | 3,68 |

Vor allem der hohe Wert beim Schuldgefühl war auffällig. Zusammen mit dem schwierigen
Wiedereinstieg hat er uns darin bestätigt, auf jede Form von Bestrafung zu verzichten. Eine
bestrafende Mechanik würde genau die empfindlichste Stelle treffen.

**Welche Funktionen werden gewünscht?**

| Funktion | Ø Nützlichkeit |
|---|---|
| Starthilfe, kleinster nächster Schritt | **4,16** |
| Erinnerung vor der Gewohnheit | 4,04 |
| Dynamische Anpassung bei Nichteinhaltung | 4,04 |
| Wenn-Dann / Situationsanker | 3,88 |
| Warum-Satz (Motivationsanker) | 3,56 |
| Sehen, ob Freund:in es gemacht hat | 3,55 |
| Feste Uhrzeit (Time-Block) | 3,50 |
| Gemeinsamer Kalender mit Freunden | 3,04 |
| Bilder/Nachrichten *während* der Gewohnheit | **2,83** |

Für das Time Blocking war vor allem das Mittelfeld aufschlussreich. Die Erinnerung vor der
Gewohnheit (ø 4,04) gehört zu den drei bestbewerteten Funktionen. Gewohnheiten an eine Situation
im Tag zu knüpfen (ø 3,88), fanden die Befragten nützlicher als eine feste Uhrzeit (ø 3,50).
Beides zusammen stützt, eine Gewohnheit an einen konkreten Platz im Tag zu binden, und zwar
bevorzugt an einen Moment wie „nach dem Aufstehen" und erst in zweiter Linie an eine Uhrzeit.
Genau so haben wir das Anlegen einer Gewohnheit später aufgebaut (Abschnitt 8.3).

Die Starthilfe wurde mit ø 4,16 am besten bewertet. Bei der Frage nach der wichtigsten einzelnen
Funktion lag dagegen das Fortschrittstracking vorn (9 von 25), vor der dynamischen Anpassung (7)
und der Starthilfe (6). Soziale Funktionen nannten nur 3 von 25 als wichtigste Funktion.

**Soziales, genauer betrachtet.** Beim Thema Community war das Ergebnis trotzdem nicht negativ.
21 von 25 würden ihre Gewohnheiten mit **engen Freunden** teilen, deutlich weniger mit Partner
oder Partnerin (12) und Familie (11), mit anonymen Personen nur 2. Soziale Funktionen sind also
gefragt, aber im kleinen, vertrauten Kreis, mit freiwilligem Teilen und ohne erzwungenen
Austausch während der Gewohnheit.

## 5.10 Ein Befund, der die Interviews korrigierte

Aus den Interviews stammte die Annahme, dass Streaks demotivieren. In der Umfrage hat sich das so
nicht bestätigt.

| Präferenz | Stimmen |
|---|---|
| Streak | 9 |
| Konsistenzrate | 5 |
| offen für beides | 11 |

Nur 5 von 25 bevorzugten eindeutig die Konsistenzrate, 9 entschieden sich für den Streak und 11
waren für beides offen. Das ist der einzige Punkt, an dem die Umfrage einer Annahme aus den
Interviews widersprochen hat, und genau dafür war sie gedacht.

Wir wollten uns deshalb nicht für eine Seite entscheiden. Der Streak kann motivieren, aber sein
**Bruch** darf nicht bestrafen. In der fertigen Anwendung gibt es deshalb beides: die
Konsistenzrate als ruhige Kennzahl auf der Übersicht, Serien an einer eigenen Stelle und verpasste
Tage, die neutral dargestellt werden.

## 5.11 Methodische Einordnung

Die Ergebnisse unserer Umfrage geben eine **Richtung vor, sind aber nicht repräsentativ**. Drei
Einschränkungen müssen dabei berücksichtigt werden.

**Kleine und ungleich verteilte Stichprobe.** Angestrebt waren mehr als 50 Antworten, erreicht
haben wir 25. Viele Teilnehmende kamen aus dem Studiengang E-Commerce und aus dem 5. bis 6.
Semester, und das Verhältnis von 16 Frauen zu 9 Männern war nicht ausgeglichen. Als Hilfe zur
Priorisierung ist die Umfrage belastbar, als Beweis nicht. Dass die 25 Antworten in rund zwei
Stunden eingingen, zeigt aber auch, dass mehr Teilnehmende möglich gewesen wären. Die Grenze lag
beim Umfragewerkzeug, nicht bei der Bereitschaft der Zielgruppe. Bei einer erneuten Umfrage
würden wir das Werkzeug deshalb vorher genauer prüfen.

**Unterschiede zwischen Planung und fertiger Umfrage.** Der finale Fragebogen enthielt neun
Bewertungen zu Funktionen, darunter „gemeinsamer Kalender" und „Bilder während der Gewohnheit
schicken". Die ursprünglich geplanten Skalen zur Habit Journey und zur Konsistenzrate fehlen
dagegen als eigene Bewertung. Das muss man beim Vergleich mit unserem ursprünglichen Plan
berücksichtigen.

**Keine belastbare Auswertung nach Personas.** Interessant wäre gewesen, die Ergebnisse getrennt
nach Einsteigern und Selbstregulierten auszuwerten. Bei nur 25 Antworten wären die Gruppen dafür
aber zu klein gewesen.

## 5.12 Abgeleitete Produktentscheidungen

| Erkenntnis | Entscheidung |
|---|---|
| Nur 2/25 nutzen eine Habit-App, 20/25 planen mit Kalender | Time Blocking statt eigener Tracker-Welt; Gewohnheiten liegen später als Blöcke im Kalender (Abschnitt 7.10) |
| Erinnerung vor der Gewohnheit ø 4,04, Situationsanker ø 3,88 vor fester Uhrzeit ø 3,50, „Ich vergesse es" 10/25 | Gewohnheiten an einen Moment im Tag knüpfen, feste Uhrzeit als zweiter Weg, Erinnerung vor dem Termin |
| Stress/Prüfungsphase ist Hauptgrund fürs Aufgeben (17/25), 15/25 reduzieren statt aufzugeben | Gewohnheiten klein halten und den Einstieg erleichtern; eine eigene Funktion für Prüfungsphasen ist nicht umgesetzt (Abschnitt 9.9) |
| Schuldgefühl nach Scheitern ø 3,92 | kein Straf- oder Bestrafungsmechanismus, Fehltage neutral |
| Starthilfe bei Überforderung ist die bestbewertete Funktion (ø 4,16) | KI-gestützter Erster-Schritt-Assistent als Kern-Anwendungsfall der Claude API |
| Fortschrittstracking meistgewählte wichtigste Funktion (9/25) | Progress Tracking als Kernfeature, nicht bestrafend gestaltet |
| Streak-Präferenz uneinheitlich (9 : 5 : 11) | beide Ansichten anbieten, Bruch vergebend gestalten |
| 21/25 teilen mit engen Freunden, nur 3/25 nennen Soziales als wichtigste Funktion | Habit-Buddies als dezente Opt-in-Ebene, keine öffentliche Rangliste |
| Gemeinsamer Kalender (3,04) und Live-Bilder (2,83) schwach bewertet | beide verworfen |

## 5.13 Eine Entscheidung gegen ein eigenes Feature

Ein Ergebnis der Auswertung war, dass wir unsere geplante **Habit Journey** nicht weiterverfolgt
haben. Die Idee war, den langfristigen Aufbau einer Gewohnheit als Fortschrittskurve darzustellen.
Nach der Umfrage waren uns andere Funktionen wichtiger. Wir wollten die Idee aber nicht einfach
verschwinden lassen. Deshalb haben wir sie bewusst in unserer Präsentation gezeigt und anhand der
Umfragedaten erklärt, warum wir uns dagegen entschieden haben.

## 5.14 Feedback von Frau Heß

Das Feedback war dieses Mal kurz und bestätigend. Die wichtigsten Funktionen hätten wir aus der
Umfrage herausgearbeitet. Jetzt gehe es darum, sie umzusetzen und die Erkenntnisse auch visuell zu
zeigen. Vor allem sollten wir **dem roten Faden folgen** und das Design auf den Ergebnissen der
Umfrage aufbauen.

Diesen Hinweis haben wir durch die gesamte nächste Phase mitgenommen. Unsere Designs sollten nicht
aus Ideen entstehen, die wir persönlich gut finden, sondern auf Interviews, Umfrage oder Literatur
zurückgehen. Für die Screens, die ab Juli entstanden sind, haben wir deshalb in einem eigenen
Begründungsdokument festgehalten, auf welcher Erkenntnis die jeweilige Entscheidung beruht.

---

# 6. Phase 3 — Konzeption und Design

**Zeitraum:** 30. Juni bis 20. Juli 2026 · **Iteration 4**, Betreuungsgespräch am 20. Juli

Nach dem Hinweis von Frau Heß wollten wir in dieser Phase den roten Faden aus der Nutzerforschung
beibehalten. Aus den Ergebnissen sollten konkrete Funktionen und ausgearbeitete Screens entstehen,
und jede Entscheidung sollte sich mit einem Befund aus Interviews, Umfrage oder Recherche
begründen lassen. Gleichzeitig haben wir eine gemeinsame Designsprache festgelegt, damit die
verschiedenen Screens nicht wie einzelne Entwürfe wirken, sondern zusammenpassen.

## 6.1 Von der Priorisierung zu drei Kernfeatures

Auf Grundlage der Umfrage haben wir drei Kernfeatures festgelegt, auf die wir uns konzentrieren
wollten. Ergänzt werden sie durch eine KI-Assistenz, die an mehreren Stellen unterstützt:

| Feature | Empirischer Anker |
|---|---|
| **Time Blocking** | Wenn-Dann-Anker ø 3,88 · 20/25 planen ohnehin mit Kalender · in allen sechs Interviews bestätigt |
| **Progress Tracking** | meistgewählte wichtigste Funktion (9/25) · Schuldwert 3,92 macht „vergebend" zur Pflicht |
| **Community** | 21/25 teilen mit engen Freunden, aber nur 3/25 nennen Soziales als wichtigste Funktion |
| **KI-Assistenz** | Starthilfe bei Überforderung ø 4,16, der Bestwert aller abgefragten Funktionen |

Die ursprünglich geplante **Habit Journey** haben wir nicht weiterverfolgt (Abschnitt 5.13).

Für alle Features galt derselbe Maßstab. Sie sollten sich ohne Erklärung bedienen lassen und so
wenige Hürden wie möglich erzeugen. Eine Gewohnheit anzulegen, abzuhaken oder zu verschieben
durfte nicht selbst zu einer Aufgabe werden, die man aufschiebt. Wir haben deshalb bei jedem
Screen geprüft, welche Angabe wirklich nötig ist, und alles andere weggelassen oder mit einem
sinnvollen Vorschlag vorbelegt.

## 6.2 Time Blocking

Time Blocking ist die Grundlage für die Planung in Align. Statt nur ein allgemeines Ziel wie
„Ich möchte regelmäßig laufen gehen" festzulegen, wird eine Gewohnheit nach dem Prinzip der
Wenn-Dann-Planung mit einer konkreten Situation und einem Zeitfenster im Alltag verbunden.

Die Einrichtung haben wir in zwei Schritte aufgeteilt. Zuerst legt man fest, welche Gewohnheit man
aufbauen möchte. Danach wählt man eine passende Situation als Auslöser, zum Beispiel
„Wenn ich von der Uni nach Hause komme, dann gehe ich laufen". Dieses Wenn-Dann-Format wird
gespeichert und ist Grundlage für die Planung im Tag und für Erinnerungen.

Dazu gehören drei Mechanismen:

- **Situations-Picker statt Zeitpicker.** Man wählt eine Situation wie „nach dem Aufstehen",
  „nach der Morgenvorlesung" oder „wenn ich nach Hause komme". So hängt die Gewohnheit an einem
  Moment, der ohnehin im Alltag vorkommt. Eine Situation löst das Verhalten aus, während man sich
  eine Uhrzeit aktiv merken muss.
- **Domino-Prinzip und Habit Chains.** Eine Gewohnheit wird zum Auslöser der nächsten.
  Verschiebt sich die erste, rückt die zweite mit.
- **Erinnerung vor dem Auslöser.** Die Erinnerung kommt vor der Situation und nicht erst dann,
  wenn die Gewohnheit eigentlich schon erledigt sein sollte.

![Anker wählen](screenshots/figma/fig04-anker-dynamisch.png) ![Warum-Satz](screenshots/figma/fig05-warum-satz.png)

*Abb. 6.1 und 6.2: Der Einrichtungsflow im Entwurf. Links die Wahl des Ankers, rechts der
Warum-Satz in eigenen Worten.*

**Wissenschaftliche Grundlage.** Faude-Koivisto und Gollwitzer (2009) zeigen, dass das Format
„Wenn X, dann Y" die Kontrolle über ein Verhalten von der Selbstdisziplin auf die Situation
verlagert und dass es dabei vor allem auf einen genau festgelegten Wenn-Teil ankommt. Becker
(2024) nennt einen konkreten Auslöser als Voraussetzung jeder Gewohnheit und beschreibt das
Domino-Prinzip. Lally et al. (2010) halten fest, dass situative Auslöser wirksamer sind als
Uhrzeiten, weil sie das Verhalten von außen anstoßen.

## 6.3 Progress Tracking

Sichtbarer Fortschritt motiviert, ist in unserer Zielgruppe aber auch eine empfindliche Stelle.
Viele Befragte fühlen sich nach einem verpassten Tag schuldig oder enttäuscht. Wir haben deshalb
einen Grundsatz verfolgt: Fortschritt ehrlich zeigen, ohne Druck aufzubauen. Daraus ergaben sich
folgende Regeln:

- Es können höchstens fünf Gewohnheiten gleichzeitig aktiv sein.
- Der Verlauf erscheint als Kalenderansicht. Tage ohne Eintrag bleiben neutral und werden nicht
  rot markiert.
- Ein verpasster Tag löst weder eine negative Nachricht noch ein Kreuz oder einen Reset aus.
- Ist eine Gewohnheit gefestigt, kann sie durch eine neue ersetzt werden.

![Übersicht](screenshots/figma/fig08-progress-uebersicht.png) ![Insights](screenshots/figma/fig09-progress-insights.png)

*Abb. 6.3 und 6.4: Progress Tracking im Entwurf. Die Übersicht zeigt den Stand des Tages, die
Insights-Ansicht den Verlauf über mehrere Wochen.*

**Wissenschaftliche Grundlage.** Becker (2024) beschreibt im „Tagebuch der Tugenden", dass
sichtbarer Fortschritt die zukünftige Leistung um bis zu 20 % erhöhen kann. Lally et al. (2010)
zeigen, dass für den Aufbau einer Gewohnheit vor allem die Regelmäßigkeit zählt und nicht die
absolute Anzahl der Ausführungen. Einzelne ausgelassene Tage hatten dort keinen messbaren
langfristigen Einfluss. Fehltage sollten also weder bestraft noch hervorgehoben werden. Rund die
Hälfte der motivierten Teilnehmenden dieser Studie hat keine feste Gewohnheit aufgebaut, weil sie
zu unregelmäßig war. Sanfte Hinweise auf die Regelmäßigkeit sind deshalb wichtiger als das reine
Zählen von Serien.

### Die Frage von Frau Heß nach der Höchstzahl

In Iteration 2 hatte Frau Heß gefragt, ob es eine Grenze dafür gibt, wie viele Gewohnheiten man
gleichzeitig verfolgen sollte. Die Antwort haben wir bei **Becker (2024, Kap. 14.7.2)** gefunden.
Dort werden täglich bis zu fünf Gewohnheiten bewertet, und sobald sich eine gefestigt hat, kann
sie durch eine neue ersetzt werden.

Aus einer Frage im Betreuungsgespräch wurde so eine belegte Regel für unser Produkt, die **Grenze
von fünf aktiven Gewohnheiten**. Umgesetzt haben wir sie in Phase 4 (Kapitel 7).

## 6.4 Community

Mit der Community wollten wir Gewohnheiten eine soziale Seite geben, ohne daraus einen Vergleich
zwischen Nutzern zu machen. Soziale Verbindlichkeit soll unterstützen, ohne Druck oder Scham zu
erzeugen.

Die Umfrage hat uns dafür eine klare Richtung gegeben. 21 von 25 Befragten würden ihre
Gewohnheiten mit engen Freunden teilen, aber nur 3 von 25 nannten den sozialen Bereich als
wichtigste Funktion. Aufdringliche Mechaniken wurden deutlich schwächer bewertet, der gemeinsame
Kalender mit ø 3,04 und Bilder während einer Gewohnheit mit ø 2,83. Deshalb haben wir die
Community als **freiwillige, ergänzende Ebene** geplant und nicht als Hauptfunktion der Anwendung.
Sie beschränkt sich auf Personen, die man bereits kennt.

![Verabredung vorschlagen](screenshots/figma/fig10-verabredung-vorschlagen.png)

*Abb. 6.5: Eine Verabredung vorschlagen. Der Entwurf zeigt eine einzelne Gewohnheit und eine
einzelne Person, keine Gruppe und keine Liste.*

### Eine explizite Entscheidungsvorlage

Für die Community haben wir zwei mögliche Mechanismen gegeneinander abgewogen und auf einer
eigenen Vergleichsfolie gegenübergestellt:

| | „Community Dashboard" | „Die Verabredung" |
|---|---|---|
| Prinzip | Rangliste, Gruppenstatistiken, Feed | konkrete, terminbasierte Verbindlichkeit zwischen 1 bis 3 Personen |
| Empirie | Rangliste explizit nicht gewünscht; Rankings verlieren laut Interviews langfristig ihre Wirkung | „Wenn du eine Verabredung hast, gehst du mit einem anderen Pflichtbewusstsein ran" |

![Vergleichsfolie](screenshots/figma/fig11-vergleich-community.png)

*Abb. 6.6: Die Vergleichsfolie, mit der wir die Entscheidung begründet haben.*

Auf Grundlage der Daten haben wir uns für den **Verabredungsmechanismus** entschieden. Damit haben
wir auch die Anregung von Frau Heß aus Iteration 1 aufgegriffen, den sozialen Aspekt im Sinne von „ich bin
nicht allein" umzusetzen, ohne dass daraus ein Vergleich wird.

**Wissenschaftliche Grundlage.** Becker (2024) beschreibt, dass das soziale Umfeld über den Erfolg
einer Gewohnheit mitentscheidet und dass soziale Verbindlichkeit („ich verabrede mich mit jemandem
zum Sport") zu den wirksamsten Starthilfen für neue Gewohnheiten gehört.

## 6.5 KI-Assistenz

Ergänzend zu den drei Kernfeatures haben wir die KI als übergreifende Ebene geplant, angebunden
über die Claude API. Wenn jemand nicht weiß, wie er anfangen soll, schlägt sie einen möglichst
kleinen nächsten Schritt vor. Außerdem schlägt sie einen neuen Platz im Tag vor, wenn eine
Gewohnheit mit einem anderen Termin zusammenfällt.

![Starthilfe-Sheet](screenshots/figma/fig06-starthilfe-sheet.png)

*Abb. 6.7: Der kleinste nächste Schritt im Entwurf.*

Diese Rolle ist durch die Umfrage am besten abgesichert. Die **Starthilfe bei Überforderung**
erhielt mit ø 4,16 die höchste Bewertung aller abgefragten Funktionen.

Für die acht Screens, in denen die KI eine Rolle spielt, haben wir ein eigenes Begründungsdokument
angelegt. Darin steht für jeden Screen, was dort passiert, warum wir uns dafür entschieden haben
und auf welche Erkenntnisse aus Interviews, Umfrage oder Literatur wir uns beziehen.

## 6.6 Die Designsprache

Aus der Arbeit an den Prototypen entstand ein Dokument, das Farben, Typografie, Abstände, Formen
und wiederkehrende Komponenten festhält. An ihm konnten wir uns bei allen weiteren Screens
orientieren, und später diente es als Vorlage für die technische Umsetzung. Verbindlich blieb
allerdings unsere Figma-Datei. Das Dokument wurde aus Screenshots abgeleitet und weicht deshalb an
einzelnen Stellen von Figma ab.

### Die Farbentscheidung

In unseren ersten Entwürfen haben wir mit einer dunklen Farbwelt und blauen Akzenten gearbeitet.
Im Vergleich verschiedener Varianten wirkte diese Kombination auf uns aber zu dominant. Deshalb
haben wir uns für eine ruhigere, wärmere Farbwelt entschieden, mit **Gold als durchgängiger
Akzentfarbe**, kombiniert mit Schwarz im Dark Mode und Weiß im Light Mode. Beide Modi folgen
derselben Designsprache, und an dieser Farbwelt haben wir bis zum Projektende festgehalten.

![Iteration 1](screenshots/figma/fig01-startseite-iteration1.png) ![Iteration 2](screenshots/figma/fig02-startseite-iteration2.png) ![Iteration 3](screenshots/figma/fig03-startseite-iteration3.png)

*Abb. 6.8 bis 6.10: Dieselbe Startseite über drei Iterationen. Links die erste, dunkelblaue
Fassung mit Cyan-Akzent, in der Mitte der Zwischenstand, rechts die warme Fassung mit Gold
und Sora, die bis zum Projektende hielt.*

Als Schrift haben wir **Sora** festgelegt. Im Entwurf gliedert die Navigation die Anwendung in
vier Hauptbereiche, in der gebauten Anwendung sind daraus fünf geworden (Kapitel 8).

## 6.7 Von statischen Entwürfen zu interaktiven Prototypen

In dieser Phase haben wir angefangen, neben statischen Entwürfen auch interaktive Prototypen in
HTML zu bauen. Abläufe mit mehreren Schritten, wie das Einrichten einer neuen Gewohnheit, lassen
sich so viel besser ausprobieren als in einzelnen Screens nebeneinander. Änderungen konnten wir
direkt im Ablauf testen, ohne jedes Mal neue Screens zu zeichnen.

Unsere Figma-Datei blieb dabei die verbindliche Grundlage. Farben, Typografie und Abstände haben
die Prototypen von dort übernommen, und wo ein Prototyp abwich, galt Figma. Das war wichtig, weil
wir parallel an verschiedenen Features gearbeitet haben und am Ende alles zusammenpassen sollte.

Rückblickend war der Wechsel zu interaktiven Prototypen unser erster Schritt in Richtung
Umsetzung. Viele Abläufe aus dieser Phase sind direkt in die spätere Entwicklung eingeflossen.

## 6.8 Prototypen

Bis zum Betreuungsgespräch lagen Entwürfe für alle drei Kernfeatures vor. Die Bereiche haben wir
untereinander aufgeteilt, sodass die Gestaltungsarbeit gleichmäßig verteilt war:

- **Time Blocking und KI-Assistenz** als Abfolge von neun Screens, vom Ziel über die Wahl des
  Situations-Ankers und den persönlichen Warum-Satz bis zur Meldung, wenn ein neues Zeitfenster
  mit einem bestehenden Termin zusammenfällt
- **Progress Tracking** zuerst als einfache Wireframes, danach als ausgearbeitete Screens mit
  Übersicht, Wachstum und Konsistenz, Insights, Hindernissen und Meilensteinen
- **Community** mit dem Anlegen gemeinsamer Gewohnheiten, den Community-Screens und der
  Vergleichsfolie zur Entscheidung für die Verabredung

## 6.9 Feedback von Frau Heß

Im Betreuungsgespräch haben wir Frau Heß die drei ausgearbeiteten Features und ihre Designs
vorgestellt. Das Feedback fiel kurz aus: Wir hätten drei Features herausgesucht und im Design
umgesetzt. Nach dem Gespräch haben wir Frau Heß in unsere Figma-Datei eingeladen, damit sie sich die
Entwürfe dort direkt ansehen konnte.

## 6.10 Was daraus folgte

Nach dem Betreuungsgespräch haben wir entschieden, mit der technischen Umsetzung von Align zu
beginnen. Zu diesem Zeitpunkt hatten wir eine durch Interviews und Umfrage abgesicherte Auswahl an
Funktionen, zwei Personas, eine gemeinsame Designsprache und ausgearbeitete Entwürfe für drei
Kernfeatures.

Nichts davon mussten wir neu denken. Prototypen und Designsprache sind direkt in die
Implementierung eingegangen.

---

# 7. Phase 4 — Technische Umsetzung

**Zeitraum:** 21. Juli bis 7. September 2026 · **Iterationen 5 und 6**, Betreuungsgespräche
am 10. August und 7. September

Nach der Konzeptphase stand fest, welche Features wir bauen wollten. In dieser Phase haben
wir sie in lauffähige Software übersetzt. Zuerst haben wir die technische Grundlage gebaut,
danach daraus eine Anwendung gemacht, die sich benutzen lässt.

## 7.1 Die Entscheidung für den Tech-Stack

Wir haben Align als Mobile-First-Web-App mit Laravel gebaut. Warum wir diese
Entwicklungsentscheidung getroffen haben, erklären wir im Folgenden.

**Web-App statt nativer App.** In der Konzeptphase sind wir von einer nativen App ausgegangen.
Eine Web-App läuft dagegen im Browser, auf dem Smartphone genauso wie am Laptop. Sie muss
nicht getrennt für iOS und Android gebaut und nicht über einen App-Store veröffentlicht werden.
Dadurch war jede Änderung sofort sichtbar, und wir konnten jeden Zwischenstand direkt
ausprobieren.

**Mobile First.** Align ist für das Smartphone gedacht. Deshalb haben wir jede Ansicht zuerst
für die Breite eines Smartphones gestaltet und erst danach für größere Bildschirme angepasst.
Entwickelt haben wir am Laptop. Im Browser lässt sich die Bildschirmbreite umstellen, sodass
wir die Mobilansicht laufend prüfen konnten, ohne Simulator und ohne eigenes Testgerät.

**Laravel.** Laravel ist ein PHP-Framework, in dem viele Grundfunktionen einer Webanwendung
schon enthalten sind, zum Beispiel die Anmeldung, der Zugriff auf die Datenbank und die
Prüfung von Eingaben. Diese Teile mussten wir nicht selbst programmieren. So kamen wir
schneller zu einer lauffähigen Anwendung und konnten uns auf die eigentlichen Funktionen von
Align konzentrieren. Die Oberfläche haben wir mit React gebaut, weil sich die Bildschirme damit
aus wiederverwendbaren Bausteinen zusammensetzen lassen. Welche weiteren Werkzeuge wir
verwendet haben und wofür, zeigt Abschnitt 7.2.

Die Web-App ist eine Entscheidung für die Entwicklung und keine für das spätere Produkt. Align
ist ein MVP aus einem Studienprojekt und kein marktfähiges Produkt. Unsere langfristige
Produktidee bleibt eine native App (Abschnitt 9.10).

Beim Programmieren haben wir KI-gestützte Werkzeuge eingesetzt. Was die App können soll,
welche Daten sie speichert und wie sie sich in welcher Situation verhält, haben wir selbst
entschieden. Unsere Gründe dafür beschreiben wir in den folgenden Abschnitten.

## 7.2 Der Stack im Überblick

| Schicht | Technologie | Begründung |
|---|---|---|
| Sprache / Runtime | PHP 8.4, Node 25 | kommen beide von Herd, keine separate Einrichtung |
| Backend | **Laravel 13** | Routing, Validierung, Anmeldung und Datenbankzugriff sind bereits enthalten |
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

Unser Ziel war ein lauffähiger Stand, der die drei Kernfeatures erkennbar abbildet. Er
musste nicht vollständig sein, aber weit genug, um ihn vorführen zu können.

## 7.6 Was entstand

**Grundgerüst und Designsprache.** Am Anfang stand ein Dashboard, das noch mit Beispieldaten
arbeitete. Den Rest prägte der nächste Schritt: Wir haben die Designsprache aus Phase 3 in die
Anwendung übernommen, und das Platzhalter-Widget wich echten Gewohnheitsdaten. Danach kamen
Onboarding, das Abhaken von Gewohnheiten und die drei Feature-Bereiche dazu.

**Kernfunktionen.** Darauf aufbauend entstanden feste Uhrzeiten und Erinnerungen zehn Minuten
vor dem Termin, die KI-Anbindung mit dem Vorschlag des kleinsten nächsten Schritts und der
Tageskalender, in dem die KI einen Block verschieben kann. Dazu kamen der Freundschafts-Layer
mit gemeinsam übernommenen Gewohnheiten, die Umschaltung zwischen Light und Dark Mode und eine
Serienzählung, die ein Wochenende und einen verpassten Tag übersteht. Außerdem entstand eine
Ansicht, die zeigt, was mit wem verabredet ist, und die Möglichkeit, eine Verabredung
abzusagen, ohne dass eine Lücke zurückbleibt.

**Die Fünf-Gewohnheiten-Grenze.** Aus der in Phase 3 belegten Regel wurde eine Funktion.
Gewohnheiten lassen sich beenden, statt bei fünf festzustecken, und die Grenze wird dort
erklärt, wo sie tatsächlich greift, nicht als abstrakte Regel im Onboarding.

Parallel ist ein eigenes Logo entstanden, das wir in einer hellen und einer dunklen Variante
eingebunden haben.

Für diesen Bericht haben wir den Code-Stand vom 10. August noch einmal gestartet und zwei
Bildschirme nachträglich aufgenommen. Datum und Beispieldaten stammen deshalb aus der Aufnahme,
Aufbau und Funktionen aus dem damaligen Stand.

![Übersicht im Code-Stand vom 10. August](screenshots/verlauf/v01-1008-uebersicht.png) ![Kalender im Code-Stand vom 10. August](screenshots/verlauf/v02-1008-kalender.png)

*Abb. 7.1 und 7.2: Die Anwendung im Code-Stand vom 10. August. Links die Übersicht mit
Prozentwert und großer Serien-Karte, „Ich komm nicht rein" war der damalige Name der
Starthilfe. Rechts der Kalender, der den Tag noch als Liste nach Situationen ordnete, darunter
„nach dem Mittagessen" und „wenn ich nach Hause komme".*

## 7.7 Feedback von Frau Heß

Frau Heß nannte drei Punkte. Die Dokumentation parallel weiterführen, das Design fertigstellen
und dabei priorisieren, die technische Umsetzung weitertreiben.

---

# Iteration 6 — Ausbau und Zuverlässigkeit

Für diese Iteration hatten wir uns zwei Ziele gesetzt. Die App sollte intuitiver werden, damit
sie nicht selbst zum Hindernis wird, und das Bestehende sollte so robust werden, dass es in
unterschiedlichen Kontexten zuverlässig funktioniert. Diese beiden Ziele zu vereinbaren, fiel
uns schwer. Damit sich die App intuitiv bedienen lässt, muss sie in jeder Situation
verlässlich reagieren. Jede Gewohnheit muss dabei aber anders behandelt werden, je nachdem, ob
sie zum Beispiel am Aufstehen, an einer Vorlesung oder an einer festen Uhrzeit hängt. Außerdem
ist der Kontext oft ein anderer, etwa an einem Tag mit Vorlesungen oder am Wochenende. Alles so
umzusetzen, dass es trotzdem immer zuverlässig funktioniert, war deshalb nicht einfach.

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
| **7.10 Zeitraster** | der Tag als Liste nach Situationen | der Tag als Zeitraster mit verschiebbaren Blöcken, nur noch Situationen mit berechenbarer Uhrzeit |
| **7.11 Stundenplan** | Semesterplan als eigener Bereich | Kurse liegen direkt im Kalender und haben Vorrang vor Gewohnheiten |
| **7.12 Navigation** | Seitenleiste mit sechs Einträgen | fünf Tabs am unteren Rand |
| **7.13 Gewohnheiten** | Karten mit Schaltern, Fortschritt in Prozent | Wochenblatt, Tage statt Prozent |

## 7.8 Gewohnheiten bekommen eine Dauer: der Katalog

**Vorher.** Anfangs wählte man im zweiten Schritt des Assistenten aus Vorschlägen oder tippte
über „Etwas anderes" eine eigene Gewohnheit in ein Textfeld. Unter den Vorschlägen standen auch
Gewohnheiten wie „Treppe statt Aufzug" oder „Eine Station früher aussteigen", die keine Dauer
haben und keinen Platz im Tag belegen (Abb. 7.3).

**Was beim Benutzen auffiel.** Eine Gewohnheit ohne Dauer lässt sich nicht in einen Tag
einplanen. Man weiß nicht, wie viel Platz sie braucht, und eine angehängte Gewohnheit weiß
nicht, wann die vorige fertig ist. Solange der Kalender eine Liste war, fiel das kaum auf.
Sobald wir den Tag als Zeitraster zeigen wollten, wurde es zum Hindernis.

**Nachher.** Wir haben uns entschieden, zunächst nur mit fest vorgegebenen Gewohnheiten zu
arbeiten, damit die Anwendung jede Gewohnheit zuverlässig planen kann. Das Textfeld ist
entfallen, und Gewohnheiten kommen aus einem festen Katalog. Aufgenommen wird nur, was drei
Bedingungen erfüllt: planbar sein, eine Dauer haben, am Stück stattfinden. Der Katalog ist in
vier Bereiche des Studienalltags sortiert, und jeder Eintrag zeigt seine Dauer, die sich beim
Anlegen anpassen lässt (Abb. 7.4).

![Schritt 2 im Code-Stand vom 31. August](screenshots/verlauf/v04-3108-anlegen-schritt2.png) ![Schritt 2 heute](screenshots/abb04-katalog-auswahl.png)

*Abb. 7.3 und 7.4: Derselbe Schritt im selben Bereich vorher und nachher. Links der Code-Stand
vom 31. August mit Vorschlägen, von denen zwei keine Dauer haben, und dem Feld
„Etwas anderes" für eine eigene Gewohnheit. Rechts der Katalog, in dem jeder Eintrag seine
Dauer trägt.*

**Warum das die wichtigste Änderung war.** Aus einem Tracker wurde damit ein
Planungswerkzeug. Erst die Dauer macht aus einer Gewohnheit einen Block, der eine echte Spanne
im Tag belegt, und auf ihr bauen alle folgenden Abschnitte auf. Zugleich ist der Katalog unsere
größte bewusste Einschränkung, denn eigene Gewohnheiten lassen sich derzeit nicht eintragen.
Wie sich die Anwendung an dieser Stelle erweitern ließe, beschreibt Abschnitt 9.7.

## 7.9 Der Tag bekommt einen Rahmen: der Schlafplan

**Vorher.** Der Tag hatte keinen Anfang und kein Ende. Gewohnheiten, die „nach dem Aufstehen"
oder „vor dem Schlafengehen" stattfinden sollten, hingen an Momenten, deren Uhrzeit die
Anwendung nicht kannte.

**Was beim Benutzen auffiel.** Ohne Rahmen konnte die Anwendung weder sagen, wie viel Platz ein
Tag überhaupt bietet, noch Gewohnheiten an seinen Rändern sinnvoll einordnen. Auch ein
Vorschlag für einen neuen Platz hätte in der Nacht landen können.

**Nachher.** Aufsteh- und Schlafenszeit spannen den Tag auf, in dem alles andere stattfindet.
Beide legt man im Schlafplan fest, und zwar für jeden Wochentag einzeln, weil ein Samstag
anders aussieht als ein Dienstag (Abb. 7.5). Damit kennt die Anwendung die Uhrzeit von
„nach dem Aufstehen" und „vor dem Schlafengehen". Verschiebt sich der Rahmen, verschieben sich
die Gewohnheiten an seinen Rändern mit. Wie der Kalender den Rahmen darstellt, zeigt
Abschnitt 7.10.

![Schlafplan](screenshots/kapitel7/k7-01-schlafplan.png)

*Abb. 7.5: Der Schlafplan mit einem Balken je Wochentag. Darunter lassen sich Schlafens- und
Aufstehzeit des gewählten Tages einstellen, dazu der Wecker und die Erinnerung vor der
Schlafenszeit.*

**Zwei Aufgaben auf einmal.** In der Anwendung ist der Rahmen bewusst **keine Gewohnheit**. Er
wird nicht abgehakt, hat weder Serie noch Quote und belegt keinen der fünf Plätze. Er
beantwortet die Frage, die vor jeder Planung steht, nämlich wie lang der Tag überhaupt ist. Ein
regelmäßiger Schlafrhythmus ist aber selbst eine gute Gewohnheit. Wer im Schlafplan feste
Zeiten einträgt, nimmt sich damit schon vor, zu diesen Zeiten schlafen zu gehen und
aufzustehen. Damit das im Alltag klappt, erinnert die Anwendung auf Wunsch 20 Minuten vor der
Schlafenszeit daran, und zur Aufstehzeit kann ein Wecker klingeln, beides, solange die
Anwendung geöffnet ist. Der Schlafplan gibt der Planung also einen Rahmen und hilft zugleich,
einen festen Schlafrhythmus als Gewohnheit aufzubauen.

## 7.10 Aus der Liste wird ein Zeitraster

**Vorher.** Der Kalender zeigte einen Tag als Liste, gegliedert nach den Situationen, an denen
die Gewohnheiten hingen (Abb. 7.2). Man sah, was nach dem Aufstehen oder nach dem Mittagessen
anstand, aber nicht, wann genau, wie lange es dauert und ob zwei Dinge zeitlich zusammenpassen.

**Nachher.** Mit Dauer und Rahmen ließ sich der Tag als Zeitraster zeigen, das beim Aufstehen
beginnt und beim Schlafengehen endet (Abb. 7.6 und 7.7). Jede Gewohnheit ist ein Block mit
Anfang und Ende. Über der Tagesansicht liegt eine Monatsansicht, aus der man in jeden Tag
springt. Blöcke lassen sich per Langdruck greifen und verschieben. Hängt eine Gewohnheit an
einer anderen, rückt sie mit, und bevor die Änderung gilt, fragt die Anwendung, ob sie nur an
diesem Tag oder immer gelten soll. Wie das in der fertigen Anwendung aussieht, zeigt
Abschnitt 8.4.

![Tagesbeginn](screenshots/kapitel7/k7-02-tagesbeginn.png) ![Tagesende](screenshots/kapitel7/k7-03-tagesende.png)

*Abb. 7.6 und 7.7: Derselbe Tag am Anfang und am Ende. Er beginnt mit der Aufstehzeit um 07:00
und endet mit der Schlafenszeit um 23:00. „Frühstücken" hängt an „nach dem Aufstehen" und liegt
deshalb am Anfang des Tages, „Meditieren" hängt an „vor dem Schlafengehen" und liegt an seinem
Ende.*

**Nur Situationen, deren Uhrzeit sich berechnen lässt.** Im Zeitraster braucht jede Gewohnheit
eine Uhrzeit, auch wenn sie an einer Situation hängt. Anfangs gab es neben „nach dem Aufstehen",
„nach der Vorlesung" und „vor dem Schlafengehen" noch „nach dem Frühstück", „nach dem Mittagessen"
und „wenn ich nach Hause komme" (Abb. 7.2). Für diese drei Momente kannte die Anwendung keine
Uhrzeit und musste sie schätzen, zum Beispiel das Mittagessen um 13 Uhr. Das passt aber für kaum
jemanden, weil jede Person zu anderen Zeiten frühstückt, zu Mittag isst oder nach Hause kommt.
Noch weniger ließ sich mit einer selbst eingetippten Situation anfangen.
„Wenn ich aus der Bib komme" konnte die Anwendung nirgends einordnen, also landete die
Gewohnheit bei allen mittags. Im Kalender hätte eine Gewohnheit damit zu einer Uhrzeit
gestanden, die nicht stimmt. Wir haben diese Situationen und das Feld für eigene Situationen
deshalb herausgenommen. Angeboten werden nur noch die drei Situationen, deren Uhrzeit die Anwendung und
die KI aus den eigenen Angaben berechnen können: „nach dem Aufstehen" und
„vor dem Schlafengehen" aus dem Schlafplan und „nach der Vorlesung" aus dem Stundenplan, sofern
einer eingetragen ist (Abschnitt 7.11). Wer eine Gewohnheit nach dem Frühstück machen möchte,
hängt sie stattdessen an die Gewohnheit „Frühstücken", deren Platz im Tag feststeht. Wie sich
weitere Situationen zurückholen ließen, beschreibt Abschnitt 9.7.

**Luft zwischen zwei Blöcken.** Zwischen zwei Blöcken hält Align Luft, nämlich eine
Viertelstunde zwischen zwei Gewohnheiten und vor und nach einer Vorlesung, zum Hinkommen und
Umschalten, und fünf Minuten innerhalb einer Kette. Ohne diese Regel hätte das Raster Tage
erlaubt, die auf dem Bildschirm aufgehen, im Alltag aber nicht.

**„Immer" nur, wenn an allen Tagen Platz ist.** Beim Verschieben entscheidet man, ob der neue
Platz nur an diesem Tag oder immer gelten soll. „Immer" legt die Gewohnheit an jedem Wochentag,
an dem sie vorgesehen ist, auf die neue Uhrzeit. Deshalb prüft die Anwendung vorher jeden
dieser Wochentage, und zwar jeweils den nächsten Termin und, falls ein Semester bevorsteht,
auch den ersten Termin im Semester. Liegt an einem dieser Tage schon eine andere Gewohnheit auf
dem neuen Platz, lehnt die Anwendung „Immer" ab. Sie nennt die Gewohnheit, die im Weg liegt,
und die freien Zeiten davor und danach. Außerdem schlägt sie vor, die Gewohnheit als „danach"
an die andere zu hängen, und bietet an, direkt zu dem betroffenen Tag zu springen (Abb. 7.8).
Nur für den gewählten Tag lässt sich die Gewohnheit trotzdem verschieben, wenn dort Platz ist.

![Immer abgelehnt](screenshots/kapitel7/k7-04-immer-abgelehnt.png)

*Abb. 7.8: „Frühstücken" soll an einem Dienstag auf 07:30 rücken. An diesem Tag ist dort Platz,
für „Immer" aber nicht, weil montags um 07:30 schon „Joggen gehen" liegt.*

## 7.11 Der Stundenplan zieht in den Kalender

**Vorher.** Aus der Competitor-Analyse stammte der Befund, dass keine der untersuchten
Anwendungen in Semestern und Vorlesungsrhythmus denkt (Abschnitt 4.2). Dafür haben wir den
Semesterplan gebaut. Zunächst bekam er einen eigenen Bereich in der Navigation, in dem sich ein
Semester und seine Kurse als Karten je Wochentag eintragen ließen (Abb. 7.9).

**Was beim Benutzen auffiel.** Ein eigener Bereich trennt, was zusammengehört. Ein Stundenplan
ist keine eigene Aufgabe, sondern gibt vor, wo im Tag überhaupt Platz für Gewohnheiten ist. Wer
seinen Tag plant, will die Vorlesung dort sehen, wo auch die Gewohnheiten liegen.

**Nachher.** Der Semesterplan ist in den Kalender gewandert. Kurse liegen als Blöcke direkt im
Tag und blockieren ihn, damit weder man selbst noch die KI eine Gewohnheit in eine Vorlesung
legt (Abb. 8.15). Ein Kurs in einem kommenden Semester beansprucht seinen Platz erst ab
Semesterbeginn, und die Monatsansicht kündigt Konflikte an, bevor sie eintreten (Abb. 8.14).

![Semesterplan im Code-Stand vom 3. September](screenshots/verlauf/v06-0309-semesterplan.png)

*Abb. 7.9: Der Semesterplan im Code-Stand vom 3. September, noch als eigener Bereich mit einer
Liste von Kursen.*

**Vorlesungen haben Vorrang.** Eine Vorlesung gibt die Hochschule vor, sie lässt sich nicht
verschieben. Eine Gewohnheit dagegen schon. Treffen beide aufeinander, gibt deshalb immer die
Gewohnheit nach. Zieht man eine Gewohnheit in eine Vorlesung, lässt die Anwendung das nicht zu,
erklärt den Grund und nennt die freien Zeiten davor und danach (Abb. 7.10). Hängt eine
Gewohnheit an einer Situation, weicht sie innerhalb eines Zeitfensters von selbst auf die
nächste freie Stelle aus.

![Kurs hat Vorrang](screenshots/kapitel7/k7-05-kurs-vorrang.png)

*Abb. 7.10: „Frühstücken" soll an einem Mittwoch im Semester auf 10:30 rücken, mitten in
„Statistik I". Die Anwendung lässt das nicht zu, weil der Kurs nicht rückt, und nennt die
freien Zeiten bis 09:45 und ab 11:45.*

**Eine Verfeinerung.** Zunächst wurde ein Kurs abgewiesen, wenn an seiner Stelle schon eine
Gewohnheit lag. Das war im Einzelfall korrekt, aber nicht hilfreich. Wer zu Beginn eines
Semesters seinen Stundenplan einträgt, hätte vorher jede Gewohnheit, die im Weg liegt, von Hand
wegräumen müssen, obwohl sich nur die Gewohnheit verschieben lässt und nicht die Vorlesung.
Jetzt wird der Kurs eingetragen, und die Gewohnheit an seiner Stelle wird geparkt. Sie wird
nicht gelöscht, verliert aber ab Semesterbeginn ihren Platz im Tag. Im Kalender steht sie dann
unter dem Tag im Bereich „Ohne festen Platz", zusammen mit der Uhrzeit, zu der sie bisher lief
(Abb. 7.11). Von dort lässt sie sich ins Raster ziehen. Tippt man sie an, kann man sich über
„Anderer Zeitpunkt?" von der KI einen neuen Platz vorschlagen lassen (Abb. 7.12).

![Ohne festen Platz](screenshots/kapitel7/k7-06-ohne-festen-platz.png) ![Anderer Zeitpunkt](screenshots/kapitel7/k7-07-anderer-zeitpunkt.png)

*Abb. 7.11 und 7.12: Ab Semesterbeginn liegt montags „Analysis I" auf der Zeit von
„Joggen gehen". Die Gewohnheit steht deshalb unter dem Tag im Bereich „Ohne festen Platz".
Tippt man sie an, kann die KI einen anderen Zeitpunkt vorschlagen.*

## 7.12 Die Navigation wandert nach unten

**Vorher.** Auf dem Handy lag die Navigation in einer Seitenleiste mit sechs Einträgen, darunter
Semester und Schlaf als eigene Bereiche (Abb. 7.13). Um den Bereich zu wechseln, musste man die
Seitenleiste erst öffnen.

**Nachher.** Die Navigation steht als Leiste mit fünf Tabs am unteren Bildschirmrand, in
Reichweite des Daumens und auf jeder Seite sichtbar: Übersicht, Gewohnheiten, Kalender,
Schlafplan und Community. Der Semesterplan braucht keinen eigenen Tab mehr, weil er im Kalender
liegt, und die Mobilansicht bekam eine eigene Kopfzeile. Für eine Anwendung, die mehrmals am Tag
kurz geöffnet wird, ist das der direkteste Weg zu jedem Bereich.

![Seitenleiste im Code-Stand vom 3. September](screenshots/verlauf/v07-0309-seitenleiste.png)

*Abb. 7.13: Die Navigation im Code-Stand vom 3. September als Seitenleiste mit sechs Einträgen.
In der fertigen Anwendung steht sie als Tab-Leiste am unteren Rand, zu sehen etwa in Abb. 8.2.*

## 7.13 Gewohnheiten im Wochenblick: Tage statt Prozent

**Vorher.** Die Gewohnheiten-Seite hat drei Stufen durchlaufen. Zuerst bestand sie aus Karten
mit einem Schalter je Gewohnheit, und auf dem Handy wurden Titel und Auslöser abgeschnitten
(Abb. 7.14). Danach haben wir die Karten nach dem nächsten Termin sortiert und in
„Steht heute an" und „Steht später an" geteilt (Abb. 7.15). Der Fortschritt stand als
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

![Gewohnheiten im Code-Stand vom 10. August](screenshots/verlauf/v03-1008-gewohnheiten.png) ![Gewohnheiten im Code-Stand vom 3. September](screenshots/verlauf/v05-0309-gewohnheiten.png)

*Abb. 7.14 und 7.15: Die Gewohnheiten-Seite im Code-Stand vom 10. August mit Schaltern und
abgeschnittenen Titeln und im Code-Stand vom 3. September mit der Einteilung nach heute und
später. Den heutigen Stand zeigt Abb. 8.16.*

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

| Schritt | Was hinzukam | Wofür |
|---|---|---|
| 1 | `habits`, `habit_completions`, Onboarding-Marker, Motivation | Grundgerüst für Gewohnheiten und ihr Abhaken |
| 2 | feste Zeitpläne, Erinnerungen, kleinster Schritt | Time Blocking und KI-Assistenz |
| 3 | `friendships`, `appointments`, `appointment_notices` | Community und Verabredungen |
| 4 | `ai_suggestions`, Zielgröße, Ketten | KI-Gedächtnis und Habit Chains |
| 5 | `template_key`, Entfernung punktueller Gewohnheiten, `sleep_schedules` | Katalog und Tagesrahmen |
| 6 | `semesters`, `courses`, `course_exceptions` | Semesterplan |
| 7 | `sleep_day_overrides`, Zeitpläne je Wochentag | tageweise Anpassung des Rahmens |

Auffällig sind die Migrationen, die etwas **entfernen**: punktuelle Gewohnheiten, geratene
Situationen, eine Kursart, die sich als überflüssig erwies. Sie belegen, dass wir Konzepte
auch wieder zurückgenommen haben, wenn der tatsächliche Gebrauch dagegen sprach.

## 7.16 Das Abschlussgespräch

Im Abschlussgespräch haben wir Frau Heß die fertige Anwendung vorgeführt. Dabei sind wir beim
Verschieben von Gewohnheiten noch auf kleinere Fehler gestoßen, die wir anschließend behoben
haben.

---

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
erste Frage.

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
- Die Gewohnheit, die zuvor um 07:30 lag, erscheint an diesem Tag nicht mehr im Raster, weil die
  Vorlesung sie verdrängt hat. Sie steht unter dem Tag im Bereich „Ohne festen Platz"
  (Abb. 7.11). Genau darauf hatte die Monatsansicht hingewiesen.

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
Der Wecker ist werktags aktiv, am Wochenende nicht. Auf Wunsch erinnert die Anwendung außerdem
20 Minuten vor der Schlafenszeit daran, schlafen zu gehen (Abschnitt 7.9).

Bewegt sich dieser Rahmen, bewegen sich die Gewohnheiten an seinen Rändern mit.

## 8.8 Community

![Der Kreis](screenshots/kapitel8/k14-community-kreis.png)

*Abb. 8.18: Der Community-Bereich beginnt mit der Zusage, was nicht geteilt wird.*

Der Community-Bereich setzt die Entscheidung aus Abschnitt 6.4 um, also Verabredung statt
Rangliste. Wichtig ist der erste Satz der Seite:

> „Was ihr tut, sieht niemand. Nur, dass ihr euch kennt."

Die Seite beginnt mit dem, was **nicht** geteilt wird. Das ist die direkte Antwort auf den
Interviewbefund, dass Vergleich als Kontrolle empfunden wird, und auf die Umfrage, in der eine
Rangliste explizit nicht gewünscht war.

Verbindungen entstehen nur über einen exakt eingegebenen Namen. Die Anwendung schlägt keine
Personen vor und sucht nicht nach Ähnlichem. Wer gar keine Anfragen mehr möchte, schaltet
Verabredungen in den Einstellungen ab und behält seinen Kreis trotzdem.

![Zu zweit](screenshots/kapitel8/k15-zu-zweit-sheet.png)

*Abb. 8.19: Eine Gewohnheit zu zweit angehen. Person und Tag stehen fest, bevor gefragt wird.*

Der Weg dorthin beginnt nicht in diesem Bereich, sondern auf der Übersicht. Unter jeder
offenen Gewohnheit steht „Zu zweit?" neben der Starthilfe (Abschnitt 8.1). Das Blatt fragt
zwei Dinge und sonst nichts, nämlich wen und wann. Zur Auswahl stehen nur die Tage, an denen
die Gewohnheit ohnehin ansteht, im Beispiel Mittwoch und Freitag.

Der Satz unter dem Knopf nimmt die Sorge vorweg, die aus den Interviews stammt:

> „Jonas Winkler bekommt eine Anfrage. Bei einer Absage siehst du nur das, ohne Grund und
> ohne Zähler."

Eine Verabredung gilt für einen einzigen Tag. Sie verlängert sich nicht, sie wird nicht
gezählt, und sie taucht in keiner Statistik auf.

![Die Anfrage bei Jonas](screenshots/kapitel8/k16-anfrage-empfangen.png) ![Die Absage](screenshots/kapitel8/k17-absage.png)

*Abb. 8.20 und 8.21: Dieselbe Verabredung von beiden Seiten. Links, was Jonas bekommt. Rechts,
was zurückkommt, wenn er ablehnt.*

Bei Jonas liegt die Anfrage oben auf der Übersicht, und sie bietet drei Antworten statt zwei.
Neben „Passt mir" und „Lieber nicht" steht „Selbst übernehmen". Wer nicht mitkommen kann, die
Gewohnheit aber gut findet, nimmt sie in den eigenen Tag auf. Aus einer Absage wird dann eine
Übernahme.

Die Absage ist die Stelle, an der sich entscheidet, ob die Anwendung ihr Versprechen hält. Sie
nennt keinen Grund und führt keine Liste. Sie sagt „Passt Jonas Winkler diesmal nicht" und
stellt daneben die einzige Frage, die dann noch zählt, nämlich ob du es trotzdem machst. „Mach
ich trotzdem" trägt die Gewohnheit zurück in den Tag, „Alles gut" schließt die Notiz. Ein
rotes Kreuz gibt es auch hier nicht.

## 8.9 Dark Mode

![Dark Mode](screenshots/kapitel8/k13-uebersicht-dunkel.png)

*Abb. 8.22: Die Übersicht im Dark Mode.*

Alle Bereiche liegen in einem hellen und einem dunklen Modus vor, die derselben Designsprache
folgen. Gold bleibt in beiden Modi die Akzentfarbe, im Dark Mode trägt es zusätzlich die
Überschriften, weil ein reines Weiß auf dunklem Grund zu hart wirkt.

![Gewohnheiten im Dark Mode](screenshots/app/app11-habits-dark.png) ![Kalender im Dark Mode](screenshots/app/app12-calendar-dark.png)

*Abb. 8.23 und 8.24: Gewohnheiten und Kalender im Dark Mode. Das Sieben-Tage-Raster und die
Monatsansicht behalten ihre Struktur, nur die Flächen kehren sich um.*

## 8.10 Funktionsumfang im Überblick

| Bereich | Umgesetzt |
|---|---|
| **Gewohnheiten** | Anlegen in fünf Schritten aus dem Katalog (vier Bereiche) · Anker über Situation, feste Uhrzeit oder Kette · Wochentage und Uhrzeiten je Tag · Bearbeiten und Beenden · Grenze von fünf aktiven Gewohnheiten |
| **Tracking** | Abhaken per Tippen oder Wischen · Sieben-Tage-Raster mit Nachtragen · Konsistenz über 30 Tage, die nur vorgesehene Tage zählt · Serien ohne Bestrafung bei Unterbrechung |
| **Kalender** | Monatsansicht mit Tagespunkten · Tagesansicht als Zeitraster · Verschieben per Ziehen, nur heute oder immer · verkettete Gewohnheiten rücken mit · Semesterplan mit Kursen, Kollisionsprüfung und Parken verdrängter Gewohnheiten |
| **Tagesrahmen** | Schlaf- und Aufstehzeit je Wochentag · tageweise Ausnahmen · Wecker und Erinnerung vor der Schlafenszeit innerhalb der Anwendung |
| **KI-Assistenz** | kleinster erster Schritt · neue Zeiten vorschlagen · Tag neu ordnen · Kontextwissen über Gewohnheiten, Rahmen und Stundenplan |
| **Community** | Kontakte über exakten Namen · Verabredungen zu zweit für einen einzelnen Tag · Absagen mit Weiterführung · Übernehmen fremder Gewohnheiten |
| **Sonstiges** | Auftakt vor dem Onboarding · Light und Dark Mode · Registrierung mit Passkeys und Zwei-Faktor-Authentifizierung |

Was wir bewusst nicht umgesetzt haben und wie sich Align weiterentwickeln ließe, beschreiben die
Abschnitte 9.7 bis 9.10.

---

# 9. Reflexion und Ausblick

Dieses Kapitel blickt auf vier Monate Projektarbeit zurück. Es beschreibt, welche
Entscheidungen getragen haben, an welchen Stellen wir umgekehrt sind, welche Rolle
KI-Werkzeuge dabei gespielt haben und was wir für künftige Projekte gelernt haben. Wir halten
uns dabei an dieselbe Regel wie im übrigen Bericht. Wir berichten, was entschieden wurde und
warum, nicht, was wir uns im Nachhinein gewünscht hätten. Der zweite Teil des
Kapitels blickt nach vorn, auf das, was wir bewusst weggelassen haben, und darauf, wie sich
Align weiterentwickeln ließe.

---

## 9.1 Was getragen hat

**Die Reihenfolge der Nutzerforschung war die wichtigste Weichenstellung.** Im ersten
Betreuungsgespräch riet uns Frau Heß, zuerst qualitative Interviews zu führen und erst daraus
den Fragebogen zu entwickeln. Wir haben diesen Hinweis in Abschnitt 4.5 als „die
folgenreichste Rückmeldung unseres gesamten Projekts" bezeichnet, und die Rückschau
bestätigt das. Hätten wir die Umfrage zuerst entworfen, wären ihre Fragen aus unseren
eigenen Annahmen entstanden. So testete jede Frage etwas, das vorher jemand tatsächlich
gesagt hatte. Jede spätere Feature-Entscheidung ruht auf dieser Grundlage.

**Das Iterationsformat hat abstrakte Diskussionen verhindert.** Alle drei Wochen brauchten
wir ein vorzeigbares Ergebnis. Diese Terminstruktur hat mehr bewirkt als Planbarkeit. Weil
jede Iteration etwas Sichtbares abliefern musste, blieben Konzeptfragen nie lange
theoretisch. Die Ausgestaltung der Fortschrittsanzeige und der Wechsel der Farbwelt sind
beides Korrekturen, die früh kamen und deshalb wenig gekostet haben.

**Die Dokumentation lief mit, statt am Ende rekonstruiert zu werden.** Von Anfang an haben
wir Iterationsnotizen, Feedback und Entscheidungen festgehalten, zunächst in Notion, ab
Phase 4 als Markdown im Projektverzeichnis. Ohne diese Gewohnheit wäre dieser Bericht
deutlich lückenhafter ausgefallen, besonders bei der Nachzeichnung, warum eine Entscheidung
zu einem bestimmten Zeitpunkt fiel.

**Zwischen Konzeption und Umsetzung gab es keinen Bruch.** Am Ende von Phase 3 hatten wir
eine empirisch abgesicherte Feature-Auswahl, zwei Personas, eine Designsprache und gestaltete
Screens für drei Kernfeatures. Nichts davon haben wir verworfen. Die Designsprache ist in der
Implementierung nicht nachgebaut, sondern als Quelle im Code verankert. Farben, Abstände und
Bewegungsregeln stehen an einer Stelle und gelten für die gesamte Anwendung. Dass ein
Studienprojekt die Lücke zwischen Prototyp und lauffähigem Produkt ohne Konzeptverlust
überbrückt, hat uns in der letzten Phase selbst überrascht.

## 9.2 Wo wir umgekehrt sind

Drei Stellen im Projekt haben wir revidiert, nachdem wir sie bereits entschieden hatten.

**Die Umfrage hat eine unserer Interviewhypothesen widerlegt.** Aus den Interviews stammte
die Annahme, Streaks seien schädlich und demotivierend. In der Breite hielt das nicht. Nur 5
von 25 Befragten bevorzugten klar die Konsistenzrate, 9 tendierten zum Streak, 11 waren für
beides offen. Das ist der einzige Punkt, an dem die quantitative Erhebung einer qualitativen
Hypothese widersprochen hat, und genau dafür war sie da. Statt uns für eine Seite zu
entscheiden, haben wir die Spannung auseinandergenommen. Der Streak motiviert, aber sein
Bruch darf nicht bestrafen (Abschnitt 5.10). Beide Ansichten sind in der fertigen Anwendung
vorhanden.

**Die freie Eingabe haben wir zurückgenommen.** Anfangs konnte jede Gewohnheit frei
formuliert werden, und auch die Situation, an der eine Gewohnheit hängt, ließ sich frei
eintragen. Wir haben uns dann bewusst auf vordefinierte Gewohnheiten beschränkt, die für den
Studienalltag wirklich sinnvoll sind. Mit ihnen konnten wir besser planen und die Anwendung
gezielter entwickeln. Frei eingetragene Gewohnheiten sind sehr unterschiedlich. Zu unseren
Ideen gehörten zum Beispiel auch punktuelle Gewohnheiten wie „Treppe statt Aufzug", die keine
Dauer haben und deshalb im Kalender ganz anders behandelt werden müssten als ein Block im Tag.
Aus einem ähnlichen Grund haben wir die freie Eingabe der Situation herausgenommen. Bei einer
frei gewählten Situation weiß die Anwendung oft nicht, wann sie im Tag überhaupt eintritt, und
kann die Gewohnheit deshalb nur schwer im Kalender einplanen (Abschnitt 7.10). Beide
Entscheidungen haben wir getroffen, um eine stabil lauffähige Anwendung zu bekommen. Sie sind
zugleich die größte bewusste Einschränkung des Funktionsumfangs, auf der sich aber aufbauen
lässt (Abschnitte 7.8 und 9.7).

**Auch das Datenmodell ist gewachsen, nicht entworfen worden.** Die Reihenfolge unserer
Migrationen zeichnet den Weg der Anwendung nach; bemerkenswert sind dabei jene, die etwas
entfernen. Punktuelle Gewohnheiten, geratene Situationen und eine überflüssige Kursart sind
nach dem tatsächlichen Gebrauch wieder verschwunden (Abschnitt 7.15).

Die letzte Iteration hat uns die Grenzen dieses Vorgehens gezeigt. Wir wollten die Anwendung
intuitiver und gleichzeitig das Bestehende zuverlässiger machen. Beides zu vereinbaren war schwierig,
weil jede Gewohnheit anders behandelt werden muss und der Kontext oft ein anderer ist. Nach
Iteration 5 hatten wir viele Funktionen, aber sie griffen noch nicht ineinander. Wer in kurzen Zyklen
Funktionen ergänzt, erzeugt Verbindungsarbeit, die selbst Zeit kostet.

## 9.3 Der Wechsel der Plattform

Geplant hatten wir eine native App, gebaut haben wir eine Mobile-First-Web-App mit Laravel. Die
Gründe stehen in Abschnitt 7.1. Eine Web-App muss nicht getrennt für iOS und Android gebaut und
über einen App-Store veröffentlicht werden, und in Laravel sind viele Grundfunktionen einer
Webanwendung bereits enthalten.

Rückblickend war das für ein Projekt mit sechs dreiwöchigen Iterationen die richtige
Entscheidung, weil wir jeden Zwischenstand sofort ausprobieren konnten. Sie betrifft aber nur
die Entwicklung. Als Produkt soll Align langfristig eine native App für das Smartphone werden.
Deshalb spricht unsere Leitfrage weiterhin von „einer mobilen Applikation", und deshalb haben
wir jede Ansicht zuerst für das Smartphone gestaltet. Die Web-App ist ein Zwischenschritt, mit
dem wir das Konzept schnell und vollständig umsetzen konnten, und Abschnitt 9.10 kommt darauf
zurück.

Dass die Anwendung vorerst im Browser läuft, hat Folgen, vor allem an einer Stelle. Erinnerungen erscheinen
nur, solange die Anwendung geöffnet ist, während das Konzept eine Benachrichtigung vor dem
Auslöser vorsah.

## 9.4 KI als Werkzeug, und wo sie aufhört

KI kommt in diesem Projekt zweimal vor, und die beiden Fälle sind auseinanderzuhalten.

**In der Anwendung** ist sie eine eigene Ebene über den drei Kernfeatures. Sie formuliert den
kleinsten nächsten Schritt, schlägt neue Zeiten vor und ordnet den Tag neu. Diese Rolle ist
empirisch am besten abgesichert: „Starthilfe bei Überforderung" erzielte mit ø 4,16 die
höchste Nützlichkeitsbewertung aller abgefragten Funktionen. Technisch ist jede Funktion eine
eigene Agent-Klasse mit festem Prompt, Zeitlimit und einem Schema für die Antwort. Die
wichtigste Entscheidung dabei war eine Verzichtsentscheidung. Fällt ein Aufruf aus, antwortet
Align mit einer ehrlichen Absage statt mit einem regelbasierten Ersatzvorschlag. Was wie ein
KI-Vorschlag aussieht, muss auch einer sein (Abschnitt 7.4). Anzumerken ist, dass unser
Konzept in Phase 3 von der Claude API ausging, während die Umsetzung über `laravel/ai` an die
OpenRouter-API spricht; Anbieter und Modell stehen in der Umgebungskonfiguration und lassen
sich ohne Codeänderung austauschen.

**Bei der Entwicklung** haben wir KI-gestützte Entwicklungswerkzeuge eingesetzt. Sie haben uns
geholfen, den Funktionsumfang in der verfügbaren Zeit umzusetzen. Die Konfiguration dieser
Werkzeuge liegt offen im Repository und ist als Teil des Arbeitsprozesses gekennzeichnet.

Die Grenze verlief bei den Entscheidungen darüber, was die Anwendung tun soll und wie, und das
lässt sich im Projektverlauf belegen. Die Umstellung auf den Katalog kam daher, dass wir die Anwendung
selbst benutzt und dabei gemerkt haben, dass eine Gewohnheit ohne Dauer nicht planbar ist.
Die Grenze von fünf aktiven Gewohnheiten geht auf die Frage von Frau Heß nach einer Höchstzahl zurück
und wurde mit Umfragedaten begründet. Der Verzicht auf einen simulierten KI-Fallback ist eine
Haltungsentscheidung gegenüber dem Nutzer. Keine dieser drei Entscheidungen stammt aus einem
Werkzeug. Ein Werkzeug kann eine Regel umsetzen, aber nicht bestimmen, welche Regel richtig
ist; diese Arbeit bleibt vollständig bei uns, und sie ist der eigentliche Inhalt der Kapitel
5 bis 7.

## 9.5 Was wir für die Nutzerforschung gelernt haben

Unsere Interviews und die Umfrage haben uns eine klare Richtung für die Funktionen von Align
gegeben. Beim Rückblick auf die Erhebung haben wir einiges gelernt, das wir bei einer nächsten
Befragung von Anfang an einplanen würden.

**Das Umfragewerkzeug früh auf Kapazität prüfen.** Die 25 Antworten kamen in rund zwei Stunden
zusammen. Die Zielgruppe war also sehr bereit mitzumachen, begrenzt hat nur die Kapazität des
Werkzeugs. Mit mehr Antworten und Teilnehmenden aus weiteren Studiengängen und Semestern ließen
sich die Ergebnisse noch breiter absichern. Als Grundlage für unsere Priorisierung waren sie gut
geeignet (Abschnitt 5.11).

**Den Fragebogen gegen den Leitfaden abgleichen.** Beim Überarbeiten sind zwei geplante Skalen
aus dem Fragebogen gefallen, die zur Habit Journey und zur Konsistenzrate. Für eine nächste
Befragung nehmen wir mit, die fertige Fassung vor dem Versand noch einmal Punkt für Punkt mit dem
Leitfaden zu vergleichen.

**Auswertungen nach Personas einplanen.** Eine getrennte Auswertung nach Einsteigern und
Selbstregulierten wäre spannend gewesen. Dafür braucht es von vornherein genügend Antworten je
Gruppe, und das würden wir bei der Planung der Stichprobe berücksichtigen.

**Die fertige Anwendung mit Nutzern erproben.** Während der Umsetzung haben wir die Anwendung
laufend technisch geprüft, mit Tests, statischer Analyse und einer Pipeline, die bei jedem Push
läuft. Feedback von außen kam in dieser Phase aus den Betreuungsgesprächen. Der nächste
sinnvolle Schritt ist, die fertige Anwendung von Studierenden ausprobieren zu lassen, die das
Projekt nicht kennen. Abschnitt 9.9 beschreibt, wie das aussehen könnte.

## 9.6 Was wir mitnehmen

**Eine begründete Entscheidung ist mehr wert als eine gute Idee.** Der Hinweis, dem roten
Faden zu folgen, kam im dritten Betreuungsgespräch und hat unsere gesamte Konzeptionsphase
bestimmt. Jeden Screen, der ab Juli entstand, haben wir mit einem Bezug zu einem Interview-,
Umfrage- oder Literaturbefund versehen. Der Nutzen zeigte sich nicht beim Gestalten, sondern
beim Streichen. Die Habit Journey ließ sich mit Daten aus der Diskussion nehmen, statt
stillschweigend zu verschwinden.

**Feedback wirkt nur, wenn es eine Adresse bekommt.** Alle sechs Betreuungsgespräche lassen
sich im Bericht bis zu einer konkreten Umsetzung verfolgen. Aus der Anregung zum sozialen
Aspekt wurde das Community-Feature, aus der Frage nach einer Höchstzahl die Grenze von fünf
aktiven Gewohnheiten, aus dem Hinweis auf Datenschutz die Entscheidung für LimeSurvey. Dass
das durchgängig gelang, liegt an einer kleinen Gewohnheit. Wir haben die Folgerungen aus
einem Gespräch festgehalten, solange es noch frisch war. Die erste Notion-Seite mit Feedback
und weiterem Vorgehen entstand direkt im Anschluss an das erste Gespräch, und die
Entscheidung, mit der technischen Umsetzung zu beginnen, fiel wenige Stunden nach dem
vierten.

**Für eine Dreiergruppe genügt wenig Organisation, solange die Termine stehen.** Wir haben
mit einem Gruppenchat für den Alltag und festen Terminen für inhaltliche Abstimmungen
gearbeitet. Ein eigenes Projektmanagement-Werkzeug haben wir nie gebraucht. Getragen hat
stattdessen die Regel, Aufgaben vor jeder Iteration ausdrücklich zu verteilen und zu jedem
Gespräch eine kurze Präsentation zu erstellen. Beides zusammen hat dem Projekt eine Form
gegeben, die im Nachhinein nachvollziehbar ist.

**Umsetzbarkeit darf nicht am Anfang stehen.** Frau Heß hat uns zweimal geraten, gute Konzepte
nicht aufzugeben, nur weil ihre technische Umsetzung aufwendig erscheint. Genau das ist
eingetreten. Die Stundenplan-Integration war im Mai eine unbelegte Annahme aus der
Marktanalyse und wirkte technisch am teuersten. Sie ist als Semesterplan der Gedanke, der
unseren gesamten Projektverlauf überdauert hat, und zugleich das Merkmal, das Align von einem
Gewohnheitstracker unterscheidet.

---

# Ausblick

Ein Ausblick lässt sich als Wunschliste schreiben oder aus dem heraus, was bereits belegt
ist. Wir wählen den zweiten Weg. Unsere Migrationen zeigen, dass wir Konzepte nicht nur
ergänzt, sondern nach dem tatsächlichen Gebrauch auch zurückgenommen haben (Abschnitt 7.15).
Jede dieser Rücknahmen trägt einen Grund, und jeder dieser Gründe beschreibt zugleich, was
nötig wäre, um sie aufzuheben. Wir beginnen mit den Funktionen, die wir bei der
Umsetzung bewusst weggelassen haben, ordnen danach die verworfenen Konzeptideen ein und
benennen, was als Nächstes käme.

---

## 9.7 Was wir bewusst weggelassen haben

Vier Funktionen fehlen in der fertigen Anwendung bewusst. Wir haben sie weggelassen, damit der
Umfang beherrschbar bleibt und die übrigen Funktionen zuverlässig laufen. Drei davon passen
nicht zu dem, was unser Planungsmodell voraussetzt, nämlich eine bekannte Dauer oder Uhrzeit.
Für die vierte reichte die Zeit der Umsetzung nicht mehr.

| Weggelassen | Warum | Was es bräuchte |
|---|---|---|
| **Punktuelle Gewohnheiten** wie „Treppe statt Aufzug" | haben keine Dauer und belegen kein Zeitfenster | ein zweiter Gewohnheitstyp, der ohne Platz im Tag auskommt und nur gezählt wird |
| **Situative Anker ohne planbare Uhrzeit** wie „nach dem Frühstück" | jede Person frühstückt oder isst zu einer anderen Zeit, die Anwendung müsste die Uhrzeit raten (Abschnitt 7.10) | eine Möglichkeit, die Uhrzeit eines solchen Moments je Wochentag einmal selbst festzulegen |
| **Eigene Gewohnheiten eintragen** | wir haben uns zunächst auf vordefinierte, sinnvolle Gewohnheiten beschränkt, mit denen sich besser planen und entwickeln ließ (Abschnitte 7.8 und 9.2) | ein eigener Eintrag, den die KI auswertet und dem sie Dauer, Bereich und eine passende Tageszeit zuordnet |
| **Blocker** als eigene Kategorie für feste Termine | im Rahmen dieser Umsetzung nicht mehr erreicht | ein dritter Blocktyp neben Kurs und Gewohnheit, der den Tag belegt, ohne abgehakt zu werden |

Der dritte Punkt ist der wichtigste, weil er die spürbarste Einschränkung der fertigen
Anwendung ist. Wer eine Gewohnheit vorhat, die im Katalog fehlt, kann sie derzeit nicht
anlegen. Mit dem festen Katalog haben wir bewusst begonnen, damit die Planung zuverlässig
funktioniert. Darauf lässt sich aufbauen. Nutzer könnten wieder eigene Gewohnheiten eintragen,
und die KI könnte einen solchen Eintrag auswerten und ihm eine Dauer, einen Bereich und eine
passende Tageszeit zuordnen. Die Anwendung könnte die Gewohnheit dann genauso planen wie einen
Eintrag aus dem Katalog. Das wäre kein Rückschritt zum alten Zustand, sondern ein nächster
Schritt auf einer Grundlage, die inzwischen stabil läuft.

## 9.8 Verworfenes, das wiederkommen könnte

**Die Habit Journey.** Wir haben sie nach der Umfrage gestrichen und die Streichung bewusst
in die Zwischenpräsentation aufgenommen, statt sie stillschweigend verschwinden zu lassen
(Abschnitt 5.13). Die methodische Einordnung in Abschnitt 5.11 zwingt uns hier zu einer
Einschränkung. Der ausgelieferte Fragebogen enthielt keine eigene Skala zur Habit Journey.
Wir haben also ein Konzept gestrichen, ohne es je erhoben zu haben. Das Konzept selbst liegt
vollständig ausgearbeitet vor und beruht unmittelbar auf Lally et al. (2010). Vorgesehen
waren eine Automatisierungskurve pro Gewohnheit mit gemessenem und geschätztem Verlauf, eine
Phasenanzeige von Aufbau über Festigung bis Gewohnheit und Erfolgsmarken nach 30, 66 und 100
Tagen. Die Zahl 66 ist dabei kein Spielelement, sondern der in der Studie gemessene
Durchschnitt. Für eine Anwendung, die Gewohnheitsbildung als Prozess über Wochen versteht,
schließt das eine Lücke, die unsere Fortschrittsanzeige heute offen lässt. Sie zeigt die
letzten 30 Tage, aber nicht den Weg.

**Das Freiwerden eines Platzes.** Eng damit verbunden ist ein Mechanismus, den wir konzipiert
und nicht gebaut haben. Läuft eine Gewohnheit über Wochen zuverlässig, könnte die Anwendung
anbieten, sie als gefestigt zu markieren und damit einen der fünf aktiven Plätze freizugeben.
Heute gibt es nur „Beenden", und das liest sich wie ein Abbruch. Die Grenze von fünf
Gewohnheiten ist unsere am besten begründete Produktregel; sie hat aber keinen vorgesehenen
Ausgang nach oben.

**Eine KI, die über längere Zeit mitlernt.** Als Idee hatten wir auch eine KI, die das
Verhalten über Wochen beobachtet und daraus Muster erkennt. Sie könnte zum Beispiel bemerken,
dass eine Gewohnheit zu einer bestimmten Uhrzeit immer wieder liegen bleibt, und von sich aus
eine besser passende Uhrzeit vorschlagen. Wir haben diese Idee für die Umsetzung verworfen, weil
sie auf einer Grundlage aufbaut, die erst stabil laufen muss: Gewohnheiten mit festem Platz im
Tag, ein verlässlicher Tagesrahmen und eine Planung, die auch bei Verschiebungen zuverlässig
funktioniert. Heute macht die KI Vorschläge, wenn man sie fragt, und kennt dabei den aktuellen
Tag. Steht der Rest zuverlässig, ließe sich darauf aufbauen und die KI um ein Gedächtnis über
einen längeren Zeitraum erweitern.

**Zurückhaltende Erweiterungen der Community.** Gebaut ist die Verabredung für einen
einzelnen Tag. Ausgearbeitet, aber nicht umgesetzt sind drei kleinere Bausteine. Der erste
ist ein Signal, dass jemand heute aktiv ist, ohne zu zeigen, woran. Der zweite ist eine
einzelne Reaktion auf eine erledigte Gewohnheit. Der dritte ist eine gemeinsame Gewohnheit
für eine kleine Gruppe, etwa eine WG oder eine Lerngruppe. Alle drei folgen derselben Regel
wie der bestehende Bereich, nach der Aussetzer für andere unsichtbar bleiben.

**Was verworfen bleibt.** Die Rangliste und das Community Dashboard nehmen wir nicht wieder
auf. In den Interviews wurde Vergleich als Kontrolle beschrieben, in der Umfrage war eine
Rangliste ausdrücklich nicht gewünscht, und beide Mechaniken widersprechen der Zusage, mit
der der Community-Bereich beginnt. Ebenso bleiben der gemeinsame Kalender (ø 3,04) und
Live-Bilder während einer Gewohnheit (ø 2,83) gestrichen. Sie waren die beiden schwächsten
Bewertungen der gesamten Umfrage.

## 9.9 Was als Nächstes käme

**Ein Modus für die Prüfungsphase.** Das ist der stärkste Befund unserer Nutzerforschung, den
die fertige Anwendung nicht bedient. Stress und Prüfungsphase sind mit 17 von 25 Nennungen
der mit Abstand größte Grund, Gewohnheiten aufzugeben. Wichtiger noch ist die Reaktion
darauf. 15 von 25 reduzieren in dieser Zeit, statt ganz aufzuhören (Abschnitt 5.9). Align kennt
heute den Stundenplan, aber keine Prüfungsphase; es kann einen Tag umsortieren, aber nicht
kleiner machen. Ein solcher Modus würde den Tag auf eine Kernroutine zusammenziehen und die
übrigen Gewohnheiten sichtbar beiseitestellen, statt sie zu löschen, mit demselben Weg
zurück. Reduzieren statt pausieren, als Funktion statt als Vorsatz. Von allen offenen Punkten
hat dieser die beste Datengrundlage.

**Erinnerungen, die auch bei geschlossener App ankommen.** Unser Konzept sah eine Erinnerung
vor dem Auslöser vor, nicht danach. Umgesetzt sind Erinnerungen und ein Wecker, die nur
erscheinen, solange die Anwendung geöffnet ist. Der Grund ist, dass wir Align als Web-App
entwickelt haben (Abschnitt 7.1). Eine Web-App kann den Nutzer nicht zuverlässig erreichen,
wenn sie nicht geöffnet ist. Als native App, die über den App Store veröffentlicht wird,
könnte Align dagegen Push-Benachrichtigungen schicken. Erinnerungen und Wecker würden dann auch
bei geschlossener App funktionieren. Dieser Schritt wird also mit der Umsetzung als native App
möglich (Abschnitt 9.10).

**Eine KI, die noch mehr weiß.** Beim Bau der KI-Funktionen haben wir vor allem eines gelernt:
Damit die KI wirklich hilft, muss sie immer den richtigen Kontext bekommen. Ein Vorschlag für
einen neuen Platz im Tag ist nur dann gut, wenn die KI die Gewohnheiten der Person, ihren
Schlafrhythmus und ihren Stundenplan kennt (Abschnitt 7.4). Dieser Kontext lässt sich weiter
ausbauen. Zum einen könnte die KI auch die verhaltenspsychologischen Grundlagen kennen, auf
denen Align beruht, etwa Wenn-Dann-Pläne, das Domino-Prinzip oder die Befunde zur Konsistenz.
Ihre Vorschläge würden dann nicht nur in den Tag passen, sondern auch dem folgen, was
Gewohnheiten nachweislich stabil macht. Zum anderen könnte sie, wie in Abschnitt 9.8
beschrieben, über längere Zeit mitlernen und aus dem bisherigen Verlauf erkennen, was einer
Person hilft. Beides zusammen würde aus der KI einen Begleiter machen, der den Nutzer noch
gezielter unterstützt.

**Eine Erprobung mit Nutzern.** Wie in Abschnitt 9.5 beschrieben, ist das der nächste
sinnvolle Schritt. Dafür bieten sich zwei Wege an. Ein Usability-Test des Einrichtungsflows und der
Tagesansicht mit Studierenden, die das Projekt nicht kennen, würde zeigen, ob die Anwendung
ihren ersten Anspruch einlöst. Eine Folgebefragung mit mindestens 50 Teilnehmenden über die
eigene Fachrichtung hinaus würde die Befunde, die uns schon eine klare Richtung gegeben haben,
noch breiter absichern und zugleich die beiden Skalen nachholen, die im ausgelieferten Fragebogen
fehlten. Besonders offen ist dabei die Frage, ob der Koordinationsaufwand einer Verabredung
im Alltag tragbar ist. Das lässt sich nur mit echten Paaren prüfen, nicht mit
Einzelpersonen.

## 9.10 Die langfristige Richtung

Am Anfang dieses Projekts stand eine Beobachtung aus unserem eigenen Studium: Man weiß meistens,
was einem guttut, und trotzdem bleibt die Sporttasche neben der Tür liegen. Daraus wurde unsere
Leitfrage, wie eine mobile Applikation Studierende durch minimalistisches Design, kontextsensitive
KI und verhaltenspsychologisch fundierte Mechanismen dabei unterstützen kann, nachhaltige
Alltagsgewohnheiten zu etablieren. Nach vier Monaten können wir darauf eine konkrete Antwort
geben, und sie läuft.

**Was wir erreicht haben.** Die Ansprüche aus Abschnitt 1.6 und die Bausteine unserer Leitfrage
haben uns durch das ganze Projekt begleitet, und sie sind in der fertigen Anwendung sichtbar:

- **Einfache Bedienung.** Eine Gewohnheit entsteht in fünf kurzen Fragen, jede mit einer
  Auswahl statt eines leeren Feldes. Verschieben geht mit einem langen Fingerdruck, und die Anwendung
  sagt vorher, was sich dabei ändert.
- **Motivation statt Druck.** Es gibt kein rotes Kreuz, keine Mahnung und keine Serie, die bei
  einem verpassten Tag zusammenbricht. Gezählt werden nur die Tage, an denen eine Gewohnheit
  wirklich anstand.
- **Klare Struktur.** Die Übersicht beantwortet eine einzige Frage, nämlich was heute ansteht.
- **Einheitliche Gestaltung.** Alle Bereiche folgen derselben Designsprache, in Light und Dark
  Mode.
- **Anpassung an den eigenen Alltag.** Schlafrhythmus und Stundenplan geben den Rahmen vor, und
  Gewohnheiten hängen an Momenten wie „nach dem Aufstehen" oder an einer festen Uhrzeit.
- **KI als Begleiter.** Die KI hilft dort, wo Gewohnheiten oft scheitern. Fällt der Anfang
  schwer, schlägt sie einen kleinen ersten Schritt vor, und verändert sich der Tag, sucht sie
  einen neuen Platz für die Gewohnheit. Dabei kennt sie Gewohnheiten, Schlafrhythmus und
  Stundenplan der Person.
- **Wissenschaftliche Grundlage.** Jede zentrale Funktion lässt sich auf Interviews, Umfrage oder
  Literatur zurückführen, vom Wenn-Dann-Plan bis zur Grenze von fünf Gewohnheiten.

Align ist damit mehr als ein Habit-Tracker. Eine Gewohnheit ist bei uns kein Eintrag in einer
Liste, sondern ein Platz in einem echten Tag, der sich mitbewegt, wenn sich der Tag verändert.
In dieser Form haben wir das bei keiner der Anwendungen gefunden, die wir zu Beginn untersucht
haben.

**Wohin es geht.** Align ist heute ein MVP aus einem Studienprojekt und bewusst noch kein
marktfähiges Produkt. Die Web-App war der schnellste Weg, unser Konzept vollständig und
lauffähig umzusetzen. Langfristig soll Align eine native App für das Smartphone werden. Dann
erreichen Erinnerungen die Nutzer auch bei geschlossener App, und Align ist dort, wo der Alltag
stattfindet. Auf der stabilen Grundlage, die jetzt steht, lassen sich die nächsten Schritte
aufbauen: eigene Gewohnheiten, die die KI auswertet, ein Modus für Prüfungsphasen und eine KI,
die über Wochen mitlernt. Vorher steht die Erprobung mit Studierenden, die Align noch nicht
kennen.

Die Positionierung aus Abschnitt 2.4 bleibt dabei unser Ziel, eine Anwendung zum
Gewohnheitsaufbau, die die Lebensrealität Studierender versteht und ohne Druck zur Konsistenz
führt. Mit diesem Projekt haben wir gezeigt, dass sich eine solche Anwendung aus echten
Gesprächen, einer Umfrage und wissenschaftlichen Quellen Schritt für Schritt begründen und bauen
lässt. Die Sporttasche liegt vielleicht noch neben der Tür. Aber jetzt hat sie einen festen
Platz im Tag.
