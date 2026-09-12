# 6. Phase 3 — Konzeption und Design

**Zeitraum:** 30. Juni bis 20. Juli 2026 · **Iteration 4**, Betreuungsgespräch am 20. Juli

Nach Annes Aufforderung, dem roten Faden zu folgen, ging es uns in dieser Phase darum, aus
den priorisierten Erkenntnissen ausgearbeitete Features und gestaltete Screens zu machen, und
zwar so, dass sich jede Entscheidung auf einen Befund zurückführen lässt. Parallel haben wir
eine verbindliche Designsprache erarbeitet, damit unsere Entwürfe zusammenpassen.

## 6.1 Von der Priorisierung zu drei Kernfeatures

Aus der Umfrage ergab sich eine klare Rangfolge. Drei Features haben wir als Kern gesetzt,
ergänzt um eine übergreifende KI-Assistenz:

| Feature | Empirischer Anker |
|---|---|
| **Time Blocking** | Wenn-Dann-Anker ø 3,88 · 20/25 planen ohnehin mit Kalender · in allen sechs Interviews bestätigt |
| **Progress Tracking** | meistgewählte wichtigste Funktion (9/25) · Schuldwert 3,92 macht „vergebend" zur Pflicht |
| **Community** | 21/25 teilen mit engen Freunden, aber nur 3/25 nennen Soziales als wichtigste Funktion |
| **KI-Assistenz** | Starthilfe ø 4,16 (Bestwert) · dynamische Anpassung ø 4,04 |

Die **Habit Journey** haben wir an dieser Stelle gestrichen (Abschnitt 5.13).

## 6.2 Time Blocking

Time Blocking ist das zentrale Strukturierungsprinzip unserer Anwendung. Statt abstrakte
Gewohnheitsziele zu setzen, betten wir Gewohnheiten nach dem Prinzip der Wenn-Dann-Planung in
konkrete Zeitfenster ein.

Der Einrichtungsflow läuft in zwei getrennten Schritten. Zuerst wird das Ziel definiert („Ich
will regelmäßig laufen gehen"), danach eine konkrete Situation als Auslöser gewählt („Wenn
ich von der Uni nach Hause komme, dann gehe ich laufen"). Das Format „Wenn X, dann Y" wird
intern gespeichert und ist Grundlage für Erinnerungen und KI-Anpassungen.

Drei Mechanismen gehören dazu:

- **Situations-Picker statt Zeitpicker.** Angeboten werden vorgefertigte Alltagssituationen
  wie „nach dem Aufstehen", „nach der Morgenvorlesung" oder „wenn ich nach Hause komme". Eine
  Situation löst Verhalten automatisch aus, während eine Uhrzeit aktiv im Kopf behalten
  werden muss.
- **Domino-Prinzip / Habit Chains.** Eine Gewohnheit wird zum Auslöser der nächsten. Die KI
  kann solche Ketten aus dem bestehenden Alltag ableiten und vorschlagen.
- **Erinnerung vor dem Trigger**, nicht danach.

**Wissenschaftliche Grundlage.** Faude-Koivisto und Gollwitzer (2009) zeigen, dass das Format
„Wenn X, dann Y" Verhaltenskontrolle an die Situation statt an die Selbstdisziplin überträgt
und dass die Spezifität des Wenn-Teils für die Wirkung entscheidend ist. Becker (2024) nennt
den konkreten Auslöser als Voraussetzung jeder Gewohnheit und beschreibt das Domino-Prinzip.
Lally et al. (2010) halten fest, dass situative Cues effektiver sind als Uhrzeiten, weil sie
externe Auslösung ermöglichen.

## 6.3 Progress Tracking

Sichtbarer Fortschritt ist einer der wirksamsten Motivationsverstärker und in unserer
Zielgruppe zugleich die empfindlichste Stelle. Wir folgen bei der Gestaltung deshalb einem
einzigen Grundsatz, nämlich ehrlicher Transparenz ohne Druck.

- Es werden bis zu fünf Gewohnheiten täglich verfolgt.
- Der Verlauf erscheint als Kalenderansicht mit farblicher Abstufung, Tage ohne Eintrag
  bleiben neutral statt rot markiert.
- Ein vergessener Tag löst keine Schuldnachricht, kein Kreuz und keinen Reset aus.
- Ist eine Gewohnheit gefestigt, kann sie durch eine neue ersetzt werden.

**Wissenschaftliche Grundlage.** Becker (2024) beschreibt im „Tagebuch der Tugenden", dass
sichtbarer Fortschritt die zukünftige Leistung um bis zu 20 % erhöht. Lally et al. (2010)
zeigen, dass Konsistenz der wichtigste Prädiktor für den Gewohnheitsstatus ist und nicht die
absolute Anzahl der Ausführungen. Einzelne Aussetzer haben dort keine messbaren
Langzeitkosten, fehlende Tage dürfen also nie bestraft oder prominent angezeigt werden. Rund
die Hälfte der motivierten Teilnehmenden in dieser Studie erreichte keinen
Gewohnheitsstatus, weil die Konsistenz zu niedrig war. Sanfte Konsistenzhinweise sind
deshalb wichtiger als reine Streak-Zählung.

### Annes Frage nach der Höchstzahl

In Iteration 2 hatte Anne gefragt, ob es eine Obergrenze für gleichzeitig verfolgte
Gewohnheiten gibt. Die Antwort haben wir bei **Becker (2024, Kap. 14.7.2)** gefunden. Dort
werden täglich bis zu fünf Gewohnheiten bewertet, und die Liste entwickelt sich iterativ
weiter, indem gefestigte Gewohnheiten durch neue ersetzt werden.

Aus einer Betreuungsfrage wurde damit eine belegte Produktregel, die **Grenze von fünf
aktiven Gewohnheiten**. Wir haben sie in Phase 4 implementiert, wo sie mehrere Ausbaustufen
durchlief (Kapitel 7).

## 6.4 Community

Der Community-Aspekt bringt einen sozialen Layer in die Gewohnheitsbildung. Soziale
Verbindlichkeit wirkt als Verstärker, ohne Druck oder Scham zu erzeugen.

Die Umfrage hatte eine feine Unterscheidung offengelegt. Die Teilbereitschaft mit engen
Freunden ist hoch (21/25), die Priorität als eigenständige Funktion aber gering (3/25), und
aufdringliche Mechaniken werden deutlich abgelehnt (gemeinsamer Kalender ø 3,04, Live-Bilder
ø 2,83). Daraus haben wir die Positionierung als **dezente Opt-in-Ebene** abgeleitet und
nicht als Headline unserer Anwendung.

### Eine explizite Entscheidungsvorlage

Für Community haben wir zwei Wirkmechanismen gegeneinander abgewogen und als eigene
Vergleichsfolie ausgearbeitet:

| | „Community Dashboard" | „Die Verabredung" |
|---|---|---|
| Prinzip | Rangliste, Gruppenstatistiken, Feed | konkrete, terminbasierte Verbindlichkeit zwischen 1 bis 3 Personen |
| Empirie | Rangliste explizit nicht gewünscht; Rankings verlieren laut Interviews langfristig ihre Wirkung | „Wenn du eine Verabredung hast, gehst du mit einem anderen Pflichtbewusstsein ran" |

Wir haben uns datenbasiert für den **Verabredungsmechanismus** entschieden. Damit hatten wir
auch Annes Anregung aus Iteration 1 aufgenommen, den sozialen Aspekt im Sinne von „ich bin
nicht allein", ohne in Vergleich umzuschlagen.

**Wissenschaftliche Grundlage.** Becker (2024) beschreibt, dass das soziale Umfeld über den
Gewohnheitserfolg mitentscheidet und dass soziale Verbindlichkeit („ich verabrede mich mit
jemandem zum Sport") zu den wirksamsten Starthilfen für neue Gewohnheiten gehört.

## 6.5 KI-Assistenz

Ergänzend zu den drei Kernfeatures haben wir die über die Claude API angebundene KI als
übergreifende Ebene konzipiert. Sie formuliert bei Überforderung den kleinsten nächsten
Schritt, schlägt Habit Chains auf Basis bestehender Alltagsroutinen vor und passt Zeitfenster
an, wenn ein Slot wiederholt verpasst wurde.

Diese Rolle ist empirisch am besten abgesichert. „Starthilfe bei Überforderung" erzielte mit
ø 4,16 die höchste Nützlichkeitsbewertung aller abgefragten Funktionen, dynamische Anpassung
ø 4,04.

Für die acht Screens dieses Bereichs haben wir ein eigenes Begründungsdokument angelegt, das
für jeden Screen festhält, was zu sehen ist, warum wir es so entschieden haben und worauf es
aus Umfrage, Interviews und Literatur zielt.

## 6.6 Die Designsprache

Aus der Prototypenarbeit ist ein Dokument entstanden, das Farben, Typografie, Abstände,
Formen und Komponenten festhält. Es diente uns von hier an als visuelle Referenz für alle
Features und später als Vorlage für die Implementierung. Verbindlich blieb allerdings die
Figma-Datei, denn das Dokument war aus Screenshots abgeleitet und weicht an einzelnen Stellen
ab.

### Die Farbentscheidung

Unsere allererste Designrichtung hatte auf eine dunkle, blau akzentuierte Farbwelt gesetzt.
Im Vergleich empfanden wir sie als zu dominant und haben uns für eine ruhigere, wärmere
Sprache entschieden, mit **Gold als durchgängiger Akzentfarbe**, kombiniert mit Schwarz im
Dark Mode und Weiß im Light Mode. Beide Modi folgen derselben Designsprache. Diese Palette
hielt bis zum Projektende.

Als Schrift haben wir **Sora** festgelegt. Die Navigation gliedert die Anwendung in vier
Hauptbereiche.

## 6.7 Von statischen Entwürfen zu interaktiven Prototypen

Im Verlauf dieser Phase sind wir von rein statischen Entwürfen zu interaktiven Prototypen in
HTML gewechselt. Der Grund war praktischer Natur. Ein Entwurf, der sich anklicken lässt,
zeigt Abläufe wie den mehrstufigen Einrichtungsflow einer Gewohnheit deutlich besser als eine
Abfolge einzelner Bildschirme, und Änderungen daran lassen sich gezielt vornehmen, statt
Screens neu zu zeichnen.

Verbindliche Designquelle blieb dabei durchgehend unsere Figma-Datei. Die Prototypen haben
Farben, Typografie und Abstände von dort übernommen, wo sie abwichen, galt Figma. Diese klare
Rangfolge war notwendig, weil wir parallel an mehreren Features gearbeitet haben und die
Ergebnisse zusammenpassen mussten.

Rückblickend war dieser Wechsel für uns der Übergang von der Gestaltung zur Umsetzung. Die
interaktiven Prototypen dieser Phase sind unmittelbar in unsere spätere Implementierung
eingegangen.

## 6.8 Prototypen

Bis zum Betreuungsgespräch lagen Entwürfe für alle drei Kernfeatures vor. Jede und jeder von
uns hat einen Bereich verantwortet, sodass die Gestaltungsarbeit gleichmäßig verteilt war:

- **Time Blocking und KI-Assistent** als Wireframe-Sequenz von neun Screens, vom Ziel über
  die Wahl des Ankers und den persönlichen Warum-Satz bis zur Kollisionsmeldung, wenn ein
  neuer Slot mit einem bestehenden Termin zusammenfällt
- **Progress Tracking** als Low-Fidelity-Wireframes und finale High-Fidelity-Screens mit
  Übersicht, Wachstum und Konsistenz, Insights, Hindernissen und Meilensteinen
- **Community** mit Habit-Erstellung, Community-Screens und der Vergleichsfolie zur
  Mechanismus-Entscheidung

## 6.9 Feedback von Anne

Das Feedback fiel kurz aus. Wir hätten drei Features herausgesucht und im Design umgesetzt.
Anne wurde im Anschluss in die Figma-Datei eingeladen, um die Entwürfe direkt einsehen zu
können.

## 6.10 Was daraus folgte

Wenige Stunden nach dem Gespräch haben wir die Entscheidung getroffen, die den Rest unseres
Projekts bestimmte. Wir haben mit der technischen Umsetzung begonnen.

Wir hatten zu diesem Zeitpunkt eine empirisch abgesicherte Feature-Auswahl, zwei Personas,
eine Designsprache und gestaltete Screens für drei Kernfeatures. Nichts davon haben wir
verworfen, Prototypen und Designsprache sind unmittelbar in die Implementierung eingegangen.
