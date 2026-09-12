# 2. Problemraum und Markt

Bevor wir Features definierten, mussten wir klären, wen die Anwendung adressiert, welche
Informationen wir dafür erheben müssen und was der Markt bereits abdeckt. Dieses Kapitel fasst
beide Vorarbeiten zusammen, die Anforderungsanalyse und die Competitor-Analyse. Beide
entstanden in Phase 1 (Kapitel 4).

## 2.1 Stakeholder und Informationsbedarf

Primäre Stakeholder sind **Studierende zwischen 18 und 35 Jahren**, die Schwierigkeiten haben,
Routinen aufzubauen, oder sich von ihrem Alltag überfordert fühlen. Als sekundäre Gruppe haben
wir Fachleute aus Psychologie und Verhaltensforschung identifiziert, etwa die psychologische
Beratungsstelle der Hochschule. Ihre Perspektive ist für die Gestaltung relevant, auch wenn
sie die Anwendung nicht selbst nutzen.

Daraus haben wir abgeleitet, welche Informationen unsere Nutzerforschung erheben muss. Fünf
Bereiche waren zu klären:

| Bereich | Leitfragen |
|---|---|
| **Alltag und Routinen** | Wie sieht ein typischer Tag aus? Welche Routinen bestehen bereits, welche funktionieren und welche nicht? In welchem Bereich ist die Überforderung am größten? |
| **Gewohnheiten** | Welche Gewohnheiten wurden bereits versucht? Warum sind diese Versuche gescheitert? Was hat bei den erfolgreichen geholfen? |
| **Motivation** | Was motiviert grundsätzlich? Wie wird mit Rückschlägen umgegangen? |
| **Technologie** | Welche Anwendungen wurden ausprobiert? Was funktionierte, was hat gestört? Wie lange darf ein Check-in höchstens dauern? |
| **Zeit** | Wie viele neue Gewohnheiten sind gleichzeitig realistisch? |

Diese Fragen haben wir in Phase 2 über zwei Erhebungsmethoden beantwortet, über qualitative
Leitfadeninterviews und eine quantitative Online-Umfrage (Kapitel 5). Der letzte Punkt hat
dabei eine eigene Geschichte, denn aus ihm wurde später eine konkrete Produktregel
(Abschnitt 6.3).

## 2.2 Competitor-Analyse

### Ziel und Methodik

Die Competitor-Analyse beantwortet drei Fragen. Wer ist im Markt aktiv? Wo ist die Marktlücke?
Und was lässt sich für das eigene MVP lernen, welche Mechaniken funktionieren und welche
sollten vermieden werden?

Zum Stichtag 19. Mai 2026 haben wir **acht Anwendungen** entlang von vier Dimensionen
untersucht, nämlich Zielgruppe und Positionierung, Features, Gamification und UX sowie
KI-Integration. Grundlage waren App-Store-Einträge, unabhängige Rezensionen und
Marktanalysen, ergänzend flossen unsere eigenen Nutzungserfahrungen ein. Bewertet haben wir
qualitativ mit Belegen und nicht als reinen Feature-Vergleich.

Die Auswahl umfasst bewusst auch Anwendungen aus angrenzenden Kategorien, die Studierende
ohnehin im Alltag nutzen:

| Kategorie | Anwendungen | Warum ausgewählt |
|---|---|---|
| **Direkte Wettbewerber** | Habitica · Fabulous · Finch · Streaks | die zwei dominanten Habit-Apps mit gegensätzlichen Philosophien (Spiel vs. Coaching), die derzeit populärste App bei jüngeren Zielgruppen sowie der minimalistische Gegenpol |
| **Indirekte Wettbewerber** | Forest · Headspace · Notion · Athenify | Marktführer im Fokus-Segment · Wellness-Anbieter mit ernsthafter KI-Integration · das Werkzeug, mit dem Studierende ihre Routinen tatsächlich strukturieren · die einzige Lösung im deutschsprachigen Raum mit Studierendenfokus |

### Die untersuchten Anwendungen

**Habitica** übersetzt Gewohnheiten in eine Rollenspiel-Mechanik mit Avatar,
Erfahrungspunkten und Gruppenspiel. Die Mechanik ist einzigartig und die Community stark,
aber die Lernkurve ist steil und der Spielfokus lenkt von der eigentlichen Gewohnheit ab.
*Implikation:* Gamifizierung funktioniert, ist hier aber überdosiert. Align setzt auf subtile
Mechaniken, die unterstützen statt zu dominieren.

**Fabulous** kombiniert Habit Stacking mit Audio-Coaching und geführten Programmen und ist
wissenschaftlich fundiert. Es wirkt jedoch sehr breit angelegt und eher wie eine
Content-Bibliothek als wie eine adaptive Begleitung. *Implikation:* Wissenschaftliche
Fundierung hilft wirklich, Personalisierung sollte aber tatsächlich adaptiv sein.

**Finch** setzt auf einen virtuellen Begleiter, der mit den eigenen Fortschritten wächst, und
verzichtet bewusst auf harte Serien. Die Tonalität ist warm und trifft die Zielgruppe genau,
der wissenschaftliche Anspruch und die Tracking-Tiefe bleiben gering. *Implikation:* Tonalität
zählt mehr als Feature-Breite, allerdings mit ernsterer Methodik dahinter.

**Streaks** ist der minimalistische Gegenpol mit schönem Design und ohne Abo-Pflicht, aber
auch ohne Personalisierung und ohne Begleitung. *Implikation:* Minimalismus ist ein Trumpf,
ein reiner Tracker ist aber zu wenig.

**Forest** verknüpft Fokuszeit mit einer wachsenden virtuellen Pflanze und hat einen sehr
starken Studierendenbezug, bleibt aber ein reines Fokus-Werkzeug ohne breitere
Gewohnheitslogik. *Implikation:* Hier ist kein Wettbewerb nötig, die Bindung an etwas Größeres
wirkt jedoch nachweislich motivierend.

**Headspace** ist der Wellness-Anbieter mit der bislang ernsthaftesten KI-Integration, einem
konversationellen Begleiter mit adaptiven Empfehlungen. Eine echte Gewohnheitslogik fehlt.
*Implikation:* die direkte Referenz für unsere eigene KI-Assistenz, mit der Lehre, dass KI
empathisch wirken muss und nicht klinisch.

**Notion** ist kein Wettbewerber im engeren Sinn, aber das Werkzeug, mit dem viele Studierende
ihre Routinen heute tatsächlich abbilden. Der Preis dafür ist ein hoher Einrichtungsaufwand
ohne jede Begleitung. *Implikation:* Die meisten dieser selbstgebauten Tracker scheitern an
der Konsistenz, und genau dort setzt Align an.

**Athenify** ist die einzige Lösung im deutschsprachigen Raum mit explizitem
Studierendenfokus, inklusive datenschutzkonformer Infrastruktur und analytischer
Lernzeit-Prognose. Sie verengt sich allerdings stark auf das Lernen. *Implikation:* der
nächste regionale Wettbewerber, aber zu schmal. Align kann Lernen, Schlaf, Bewegung und
Balance zusammen denken.

### Vergleichsmatrix

| Anwendung | Zielgruppe | Habit-Logik | Gamification | KI | Web | DACH-Fit |
|---|---|---|---|---|---|---|
| Habitica | Gamer | ★★★★ | ★★★★★ (Rollenspiel) | – | – | ★★ |
| Fabulous | Wellness-Interessierte | ★★★★ | ★★ (mild) | ★★ (Content) | – | ★★ |
| Finch | Gen Z, neurodivergent | ★★★ | ★★★ (Begleiter) | ★ | – | ★★ |
| Streaks | Apple-Nutzer | ★★★ | ★ (Serien) | – | – | ★ |
| Forest | Studierende | ★ (nur Fokus) | ★★★ | – | ★ | ★★★ |
| Headspace | breite Wellness | ★★ | ★ | ★★★★ | ★★★ | ★★★ |
| Notion | Studierende, Power-User | ★★ (Eigenbau) | ★ | ★★ | ★★★★★ | ★★★★ |
| Athenify | DACH-Studierende | ★★ (nur Lernen) | ★★ | ★★ (analytisch) | ★★★ | ★★★★★ |
| **Align (Ziel)** | **DACH-Studierende** | **★★★★** | **★★★ (subtil)** | **★★★★** | **★★★★★** | **★★★★★** |

## 2.3 Vier Marktlücken

Aus der Analyse haben sich vier Bereiche ergeben, in denen keine der untersuchten Anwendungen
überzeugt:

**1 · Die Lebensrealität Studierender als Produktlogik.** Keine der acht Anwendungen denkt in
Semestern, Prüfungsphasen und Vorlesungsrhythmus. Athenify kommt am nächsten, fokussiert aber
zu eng auf das Lernen. → *Align kann den Stundenplan und den Semesterzyklus zur zentralen
Achse machen.*

**2 · Planen findet am Laptop statt.** Fast alle spezialisierten Anwendungen sind
mobile-first. Studierende sitzen aber am Rechner, wenn sie planen und lernen. Notion zeigt,
dass Gewohnheitstracking im Browser funktioniert.

**3 · KI als echter Begleiter statt als Content-Bibliothek.** Im Habit-Markt ist KI
unterentwickelt. Wo sie vorkommt, liefert sie vorgefertigte Inhalte statt adaptiver
Vorschläge. → *Align kann kontextbewusste Vorschläge machen, die den tatsächlichen Tag
kennen.*

**4 · Subtile statt aggressiver Gamifizierung.** Habitica überfordert, reine Serien machen
anfällig für den Abbruch nach dem ersten Fehltag. Finch zeigt, dass sanfte, nicht bestrafende
Mechaniken bei jüngeren Zielgruppen besser wirken. → *Align setzt auf milde
Fortschrittsindikatoren ohne Verlustdruck.*

## 2.4 Positionierung

Daraus haben wir folgende Positionierung abgeleitet:

> Für Studierende, die Struktur und Balance im Studienalltag suchen, ist Align die einzige
> Anwendung zum Gewohnheitsaufbau, die ihre Lebensrealität versteht, mit einem
> kontextsensitiven KI-Begleiter und verhaltenspsychologisch fundierten Mechanismen, die
> ohne Druck zur Konsistenz führen.

Im Vergleich ist Habitica zu spielerisch, Fabulous zu generisch, Notion verlangt zu viel
Einrichtung und Athenify ist zu schmal. Align liefert das Passende in einer einfachen
Oberfläche, mit der wissenschaftlichen Fundierung, die den regionalen Alternativen fehlt.

## 2.5 Was daraus für das MVP folgte

Die Analyse endete mit einer Priorisierung, in der wir zwischen gesetzten Bestandteilen,
späteren Erweiterungen und bewussten Ausschlüssen unterschieden haben:

| | |
|---|---|
| **Gesetzt** | Stundenplan-Integration als Alleinstellungsmerkmal · kurzer täglicher Check-in · KI an wenigen, gezielten Berührungspunkten · Fortschrittsanzeige mit Schutz vor Abbruchdruck |
| **Später** | soziale Funktionen, erst wenn der Einzelnutzen klar ist · wenige, gezielte Erfolgsmarken · ein einfacher Fokusmodus |
| **Bewusst nicht** | eigene Meditationen oder Workouts produzieren · komplexe Rollenspielmechanik · verlustaversive Mechanismen, die dem eigenen Tonalitätsziel widersprechen |

Zwei dieser frühen Festlegungen sind besonders erwähnenswert, weil sie unseren weiteren
Projektverlauf getragen haben.

Die **Stundenplan-Integration** war zu diesem Zeitpunkt eine Annahme aus der Marktanalyse,
ohne empirischen Beleg. Unsere Nutzerforschung stützte sie später indirekt. Stress und
Prüfungsphasen erwiesen sich als größter Grund dafür, eine Gewohnheit aufzugeben (17 von 25),
und 20 von 25 Befragten planen ohnehin mit Kalender oder Planer. Umgesetzt haben wir sie in
Phase 4 als Semesterplan (Abschnitt 7.10). Damit ist sie der Gedanke, der unseren gesamten
Projektverlauf von der ersten Analyse bis in die fertige Anwendung überdauert hat.

Die Entscheidung gegen **verlustaversive Mechanismen** war ebenfalls zunächst eine Haltung.
Wir sahen sie in Phase 2 doppelt bestätigt, durch die Interviews und durch einen hohen
Schuldwert in der Umfrage. Heute ist sie an mehreren Stellen der Anwendung sichtbar, an
neutral dargestellten Fehltagen, an einer Serie, die bei einem verpassten Tag nicht
zusammenbricht, und an einer Konsistenzrate, die nur Tage zählt, an denen die Gewohnheit
tatsächlich anstand.
