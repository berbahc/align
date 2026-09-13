# 1. Einleitung

## 1.1 Was entwickelt wurde

**Align** ist eine Anwendung, die Studierende dabei unterstützt, im Alltag tragfähige
Gewohnheiten aufzubauen. Sie soll mehr Struktur, Fokus und Balance in eine Lebensphase
bringen, die von sich aus wenig vorgibt.

Der Ansatz setzt bewusst nicht bei einer Ursachenanalyse an, sondern bei der praktischen
Umsetzung. Gewohnheiten werden nicht nur benannt, sondern als konkrete Blöcke in einen
konkreten Tag eingeplant, innerhalb des eigenen Schlafrhythmus und um den eigenen Stundenplan
herum. Eine kontextsensitive KI-Assistenz unterstützt dort, wo Menschen erfahrungsgemäß
scheitern, nämlich beim ersten Schritt. Und sie hilft, einen neuen Platz zu finden, wenn sich
der Tag verändert, etwa weil ein neuer Stundenplan eine Gewohnheit verdrängt.

Umgesetzt wurde Align als lauffähige **MVP-Version** in Form einer mobil-first Web-Anwendung.
Die Gründe für diese Form sind in Kapitel 7 dargestellt.

## 1.2 Ausgangssituation und Motivation

Die Idee zu Align entstand aus unserer eigenen Erfahrung als Studierende. Wir kennen das
Gefühl, morgens ohne klare Struktur in den Tag zu starten, wichtige Vorhaben immer wieder
aufzuschieben und abends festzustellen, dass der Tag irgendwie an einem vorbeigezogen ist.
Ob regelmäßiger Sport, ausreichend Schlaf oder eine bewusste Pause zwischen zwei Vorlesungen,
vielen Studierenden ist klar, was ihnen guttun würde. An der konsequenten Umsetzung im Alltag
scheitert es trotzdem.

In unserem Umfeld und bei uns selbst haben wir beobachtet, dass das meist nicht an mangelndem
Willen liegt. Gute Vorsätze gehen im Alltagsstress unter. Kleine, regelmäßige Gewohnheiten
helfen dabei, Struktur zurückzugewinnen. Das Schwierige daran ist nicht das Wissen, sondern
die konsequente Umsetzung.

Bestehende Anwendungen haben uns dabei wenig überzeugt. Viele wirken überladen, setzen auf
aggressive Erinnerungen oder fühlen sich wie ein weiterer Punkt auf der To-do-Liste an. Wir
wollten deshalb von Beginn an etwas entwickeln, das sich in den Alltag einfügt, ohne
zusätzlichen Druck aufzubauen, aber mit genug Struktur, um dranzubleiben.

## 1.3 Problemstellung

Aus dieser Ausgangslage haben wir drei Probleme abgeleitet, die den Anstoß für das Projekt
gaben. Alle drei haben wir in der späteren Nutzerforschung geprüft und bestätigt gefunden
(Kapitel 5).

### 1.3.1 Fehlende Alltagsstruktur im Studium

Im Studium gibt es keine feste Tagesstruktur mehr wie in der Schule. Vorlesungszeiten wechseln
von Semester zu Semester, Freistunden häufen sich, und das Selbststudium bleibt liegen, weil
niemand es einfordert. Ohne eine bewusst gewählte Struktur läuft der Tag ins Leere, besonders
in vorlesungs- und prüfungsfreien Phasen.

### 1.3.2 Gute Vorsätze werden selten zu Routinen

Der eigentliche Knackpunkt liegt nicht beim Vorsatz, sondern beim Übergang vom Vorsatz zur
täglichen Routine. Bestehende Habit-Tracker wirken entweder zu komplex, zu stark gamifiziert
oder zu generisch, um langfristig zu motivieren.

Hinzu kommt ein struktureller Mangel. Diese Anwendungen sind statisch. Sie behandeln
Gewohnheiten unabhängig davon, wie ein Tag tatsächlich aussieht, und ändert sich der Tag, etwa
durch einen neuen Stundenplan, bleibt der Plan derselbe. Je weiter Plan und Alltag
auseinanderdriften, desto eher wird die Anwendung ganz beiseitegelegt.

### 1.3.3 Keine Anwendung, die sich dem eigenen Alltag anpasst

Was bestehenden Lösungen fehlt, ist die Fähigkeit, sich dem Alltag anzupassen statt umgekehrt.
Wir suchten eine Anwendung, die auf den persönlichen Kontext eingeht. Wie viel Zeit ist gerade
verfügbar? In welcher Phase des Semesters befindet sich die Person? Welche Termine stehen ohnehin fest?
Genau hier sehen wir das Potenzial einer gezielten KI-Unterstützung und den
Kern dessen, was Align von bestehenden Anwendungen unterscheiden sollte.

## 1.4 Zielsetzung und Leitfrage

Daraus ergibt sich die leitende Gestaltungsfrage des Projekts:

> **„Wie kann eine mobile Applikation Studierende durch minimalistisches Design,
> kontextsensitive KI und verhaltenspsychologisch fundierte Mechanismen dabei unterstützen,
> nachhaltige Alltagsgewohnheiten zu etablieren?"**

Zur Beantwortung dieser Frage haben wir eine lauffähige MVP-Version der Anwendung entwickelt,
nutzerzentriert evaluiert und iterativ verbessert. Die drei Begriffe der Leitfrage haben wir
dabei als Gestaltungsauftrag gelesen.

| Begriff | Was daraus folgte |
|---|---|
| **Minimalistisches Design** | wenige, klar getrennte Bereiche · eine durchgängige Designsprache in Light und Dark Mode · keine visuelle Überladung (Kapitel 6) |
| **Kontextsensitive KI** | Vorschläge, die den tatsächlichen Tag kennen, also Schlafrhythmus, Stundenplan und bestehende Gewohnheiten (Kapitel 7) |
| **Verhaltenspsychologisch fundierte Mechanismen** | Wenn-Dann-Pläne, Trigger- und Kontextbindung, Konsistenz statt Serie; jede Funktion auf eine Quelle zurückführbar (Kapitel 4 und 6) |

Der dritte Punkt braucht eine Erläuterung, weil er unser Vorgehen am stärksten geprägt hat.
Wir wollten Gewohnheitsbildung nicht über Spielmechaniken erzwingen, sondern verstehen,
**warum** Gewohnheiten entstehen und **woran** sie scheitern. Deshalb stand am Anfang des
Projekts die Auseinandersetzung mit der Verhaltens- und Motivationspsychologie. Wir haben uns
mit Implementation Intentions beschäftigt und mit der Frage, wie sich Verhaltenskontrolle von
der Selbstdisziplin auf die Situation verlagern lässt. Dazu kamen Trigger- und
Kontextbindung, das Domino-Prinzip und die empirischen Befunde zur Dauer und zur Konsistenz
von Gewohnheitsbildung.

Aus diesen Grundlagen leiten sich die zentralen Funktionen von Align ab, nicht aus
Belohnungslogiken. Dazu gehören Situations-Anker statt fester Uhrzeiten, Gewohnheitsketten,
eine Konsistenzrate statt einer Serie und der bewusste Verzicht auf jeden
Bestrafungsmechanismus. Die Quellen und ihre jeweilige Wirkung im Produkt sind in Kapitel 4
dargestellt.

## 1.5 Zielgruppe

Zielgruppe von Align sind Studierende im deutschsprachigen Raum, unabhängig vom Semester. Mit
Interviews und Umfrage haben wir bewusst unterschiedliche Studienphasen abgedeckt, vom ersten
bis über das siebte Semester hinaus.

Aus der Nutzerforschung haben sich zwei charakteristische Verhaltenstypen herauskristallisiert,
die als Personas unsere weitere Konzeption geleitet haben (Abschnitt 5.5):

- **„Die Selbstregulierten"**, also höhere Semester mit flexiblen Tagen und bereits
  funktionierenden kontextbasierten Routinen. Ihr Hauptblocker ist Streak-Druck und
  Vergleich.
- **„Die Einsteiger"**, also mittlere Semester ohne feste Routine. Ihr Hauptblocker ist die
  Überforderung beim Einstieg.

## 1.6 Anspruch an das Endprodukt

Für die Gestaltung haben wir zu Beginn sechs Ansprüche formuliert, an denen sich alle späteren
Entscheidungen messen lassen mussten. Über allen stand der erste. Jede Funktion sollte so
einfach und intuitiv zu bedienen sein, dass sie ohne Erklärung verständlich ist und beim
Benutzen möglichst keine Hürden entstehen. Eine Anwendung, deren Bedienung selbst Überwindung
kostet, verstärkt genau das Problem, das sie lösen soll. Deshalb haben wir bei jeder
Entscheidung auch gefragt, ob sie einen zusätzlichen Schritt, eine zusätzliche Frage oder eine
zusätzliche Erklärung erfordert, und diese Schritte so weit wie möglich vermieden.

- intuitive und einfache Bedienung, denn die Anwendung darf nicht selbst zum Hindernis werden
- Fokus auf langfristige Motivation statt Druck, ohne Bestrafungsmechanismus
- klare Struktur und geringe visuelle Ablenkung
- konsistentes Nutzererlebnis über alle Bereiche hinweg, in Light und Dark Mode
- kontextsensitive Personalisierung statt starrer Uhrzeiten
- wissenschaftlich fundierte Gestaltung auf Basis der Verhaltens- und Motivationspsychologie

## 1.7 Aufbau dieses Berichts

Dieser Bericht folgt den vier Entwicklungsphasen unseres Projekts, die sechs Iterationen sind
darin eingeordnet.

**Teil I** beschreibt den Rahmen, also Problemraum und Marktumfeld (Kapitel 2) sowie das
Vorgehen und die Zusammenarbeit im Team (Kapitel 3).

**Teil II** bildet den Hauptteil und folgt dem Projektverlauf mit Analyse und Grundlagen
(Kapitel 4), Nutzerforschung (Kapitel 5), Konzeption und Design (Kapitel 6) sowie der
technischen Umsetzung (Kapitel 7). Jede Phase enthält ihren Verlauf einschließlich des
Feedbacks aus den Betreuungsgesprächen und ihre inhaltlichen Ergebnisse.

**Teil III** stellt das Ergebnis vor (Kapitel 8) und schließt mit Reflexion und Ausblick
(Kapitel 9).
