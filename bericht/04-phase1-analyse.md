# 4. Phase 1 — Analyse und Grundlagen

**Zeitraum:** 17. bis 20. Mai 2026 · **Iteration 1**, Betreuungsgespräch am 20. Mai

In der ersten Phase ging es uns darum, unsere Projektidee wissenschaftlich und marktseitig
einzuordnen. Zwischen der Gründung der Arbeitsgruppe und dem ersten Betreuungsgespräch lagen
nur drei Tage, entsprechend bescheiden war unser Anspruch. Wir wollten eine tragfähige Idee
vorstellen und zeigen, dass wir eine Richtung haben.

## 4.1 Literaturrecherche

Für die konzeptionelle Fundierung haben wir gezielt nach Quellen aus der Verhaltens- und
Motivationspsychologie gesucht. Populärwissenschaftliche Titel zum Thema haben wir dabei
bewusst ausgeschlossen, denn wir haben uns früh darauf festgelegt, Produktentscheidungen nur
auf peer-reviewte Literatur zu stützen.

Drei Quellen bildeten von hier an unsere wissenschaftliche Grundlage:

| Quelle | Kernbefund | Wirkung im Produkt |
|---|---|---|
| **Lally et al. (2010)** | Median 66 Tage bis zur stabilen Gewohnheit (Spanne 18 bis 254). Konsistenz ist der wichtigste Prädiktor, nicht die absolute Anzahl der Ausführungen; einzelne Aussetzer haben keine messbaren Langzeitkosten. | Konsistenzrate statt Streak · kein Bestrafungsmechanismus · realistische Erwartungen |
| **Faude-Koivisto & Gollwitzer (2009)** | Wenn-Dann-Pläne (*Implementation Intentions*) verlagern die Verhaltenskontrolle von der Selbstdisziplin auf die Situation. Ein einziger bewusster Willensakt kann automatische Auslösung anstoßen. | Time Blocking · Situations-Anker statt fester Uhrzeiten |
| **Becker (2024)** | Trigger- und Kontextbindung, Domino-Prinzip (eine Gewohnheit wird zum Auslöser der nächsten), Wirkung sozialer Unterstützung, Effekt sichtbaren Fortschritts | Habit Chains · Community · Progress Tracking |

Diese Zuordnung ist kein nachträglicher Beleg, sie hat unsere Feature-Auswahl tatsächlich
gesteuert. Der Verzicht auf Streaks als primäre Kennzahl geht direkt auf Lally et al. zurück,
die Entscheidung für Situations-Anker auf Faude-Koivisto und Gollwitzer.

## 4.2 Competitor-Analyse

Wir haben acht Anwendungen entlang von vier Dimensionen untersucht, nämlich Zielgruppe und
Positionierung, Features, Gamification und UX sowie KI-Integration. Vier davon sind direkte
Wettbewerber aus dem Habit-Segment, vier indirekte aus angrenzenden Kategorien, die
Studierende ohnehin nutzen. Ergänzend sind unsere eigenen Nutzungserfahrungen mit Finch,
Habit Tracker und HabitShare eingeflossen.

| Kategorie | Anwendungen |
|---|---|
| Direkte Wettbewerber | Habitica · Fabulous · Finch · Streaks |
| Indirekte Wettbewerber | Forest · Headspace · Notion · Athenify |

Die vollständige Analyse mit Einzelprofilen und Vergleichsmatrix steht in Kapitel 2. Vier
Ergebnisse waren für unseren weiteren Verlauf bestimmend:

1. **Keine der untersuchten Anwendungen denkt in Semestern, Prüfungsphasen und
   Vorlesungsrhythmus.** Athenify kommt der Zielgruppe am nächsten, verengt sich aber auf
   das Lernen.
2. **KI wird im Habit-Markt kaum als adaptiver Coach eingesetzt**, sondern allenfalls als
   vorgefertigte Content-Bibliothek. Einzige Ausnahme ist Headspace mit „Ebb".
3. **Aggressive oder verlustaversive Gamification erzeugt Druck statt Motivation**, besonders
   bei jüngeren Zielgruppen. Habitica überfordert, reine Streak-Logiken machen anfällig für
   Aufgabe nach dem ersten Fehltag.
4. **Studierende planen am Laptop.** Notion zeigt, dass Habit-Tracking im Browser
   funktioniert, obwohl fast alle spezialisierten Anwendungen mobile-first sind.

Der erste dieser Punkte wurde später zum wichtigsten Alleinstellungsmerkmal unserer Anwendung
und ist als Semesterplan umgesetzt (Kapitel 7).

## 4.3 Erste Visualisierungen und Arbeitsorganisation

Parallel zur Analyse sind erste Wireframes und ein klickbarer Entwurf entstanden, noch ohne
Bezug zu konkreten Features. Sie sollten uns eine gemeinsame Vorstellung davon geben, worüber
wir sprechen. Aus ihnen stammt bereits die Farbrichtung, die uns bis zum Ende begleitet hat,
also Beige, Schwarz und Gold, mit der Idee eines Dark- und Light-Modes.

Für die Zusammenarbeit haben wir in dieser Phase zwei Festlegungen getroffen, die unseren
weiteren Verlauf strukturiert haben. Aufgaben verteilen wir vor jeder Iteration explizit, und
zu jedem Betreuungsgespräch erstellen wir eine kurze Präsentation.

## 4.4 Feedback von Anne

Das Gespräch lieferte uns deutlich mehr Steuerung, als wir erwartet hatten:

- **Reihenfolge der Nutzerforschung.** Erst Interviews führen und die Online-Umfrage daraus
  ableiten, nicht beides parallel entwickeln.
- **Datenschutz und Anonymität** bei der Umfrage beachten, dazu der Hinweis auf das
  Umfragewerkzeug der Hochschule.
- **Bias vermeiden.** In der Umfrage zuerst allgemeine Features abfragen, eigene erst am
  Schluss.
- **Persona erarbeiten** und daran einen Vorher-Nachher-Vergleich durchspielen.
- **Sozialer Aspekt** als Anregung, im Sinne von „ich bin nicht allein", gegenseitiger
  Motivation und Community.
- **Dokumentation.** Die gesamte Arbeit festhalten, einschließlich des Vorgehens bei den
  einzelnen Arbeitspaketen.
- **Nicht zu früh einschränken.** Gute Konzepte nicht aufgeben, nur weil ihre technische
  Umsetzung aufwendig wäre.

## 4.5 Was daraus folgte

Der Hinweis zur Reihenfolge der Nutzerforschung war die folgenreichste Rückmeldung unseres
gesamten Projekts. Er begründete das zweistufige Vorgehen der folgenden Phase mit
qualitativen Interviews zur Hypothesenbildung und einer quantitativen Umfrage zur
Validierung. Damit begründete er auch die empirische Grundlage, auf der später jede unserer
Feature-Entscheidungen ruht.

Die Anregung zum sozialen Aspekt haben wir unmittelbar aufgegriffen und noch am selben Abend
als eigenes Feature vorgesehen. Aus ihr entstand unser Community-Feature (Kapitel 6).

Direkt nach dem Gespräch entstand die erste Notion-Seite mit Feedback und weiterem Vorgehen,
der Beginn unserer projektbegleitenden Dokumentation (Abschnitt 3.3).
