# 9. Reflexion

Dieses Kapitel blickt auf vier Monate Projektarbeit zurück. Es beschreibt, welche
Entscheidungen getragen haben, an welchen Stellen wir umgekehrt sind, welche Rolle
KI-Werkzeuge dabei gespielt haben und wo die Grenzen unseres Vorgehens liegen. Wir halten
uns dabei an dieselbe Regel wie im übrigen Bericht. Wir berichten, was entschieden wurde und
warum, nicht, was wir uns im Nachhinein gewünscht hätten.

---

## 9.1 Was getragen hat

**Die Reihenfolge der Nutzerforschung war die wichtigste Weichenstellung.** Im ersten
Betreuungsgespräch riet uns Anne, zuerst qualitative Interviews zu führen und erst daraus
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

**Die freie Eingabe von Gewohnheiten mussten wir zurücknehmen.** Bis Ende August konnte jede
Gewohnheit frei formuliert werden. Beim wirklichen Benutzen zeigte sich, dass das nicht
trägt. Einer frei formulierten Gewohnheit fehlt die Dauer, und ohne Dauer lässt sich nichts
zuverlässig in einen Tag einplanen. Wir haben daraufhin auf einen Katalog mit festen
Dauerangaben umgestellt. Diese Änderung ist der Wendepunkt unserer Umsetzung, weil aus einem
Tracker ein Planungswerkzeug wurde, und zugleich unsere größte bewusste Einschränkung des
Funktionsumfangs (Abschnitt 7.9).

**Auch das Datenmodell ist gewachsen, nicht entworfen worden.** Die Reihenfolge unserer
Migrationen zeichnet den Weg der Anwendung nach; bemerkenswert sind dabei jene, die etwas
entfernen. Punktuelle Gewohnheiten, geratene Situationen und eine überflüssige Kursart sind
nach dem tatsächlichen Gebrauch wieder verschwunden (Abschnitt 7.14).

Die letzte Iteration hat uns die Grenzen dieses Vorgehens gezeigt. Im Protokoll steht die
knappe Notiz, die Schwierigkeit habe darin bestanden, zwei Ziele zu vereinbaren, nämlich die
Anwendung intuitiver zu machen und gleichzeitig das Bestehende zu härten. Nach Iteration 5
hatten wir viele Funktionen, aber sie griffen noch nicht ineinander. Wer in kurzen Zyklen
Funktionen ergänzt, erzeugt Verbindungsarbeit, die selbst Zeit kostet.

## 9.3 Der Wechsel der Plattform

Geplant hatten wir eine native mobile Anwendung. Gebaut haben wir eine mobil-first Web-App
mit Laravel. Die Gründe stehen in Abschnitt 7.1. Es gab eine Codebase statt zweier Builds,
keinen Freigabeprozess zwischen Änderung und Nutzer und ein Framework, das Routing,
Validierung, Authentifizierung und ein Testgerüst mitbringt.

Rückblickend war das für ein Projekt mit sechs dreiwöchigen Iterationen die richtige
Entscheidung, und sie war weniger radikal, als sie klingt. Unsere eigene Marktanalyse hatte
als zweite Lücke notiert, dass Planen am Laptop stattfindet und fast alle spezialisierten
Anwendungen das ignorieren (Abschnitt 2.3). In der Vergleichsmatrix stand Align von Anfang an
mit voller Bewertung in der Spalte Web.

Offen bleiben muss allerdings, dass unsere Leitfrage weiterhin von „einer mobilen
Applikation" spricht. Der Wechsel ist eine Entwicklungsentscheidung, keine
Produktentscheidung; die langfristige Produktidee bleibt die mobile Anwendung, und Kapitel 10
kommt darauf zurück. Die Konsequenzen sind an einer Stelle spürbar. Erinnerungen bleiben in
der Anwendung gefangen, wo das Konzept eine Benachrichtigung vor dem Auslöser vorsah.

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

**Bei der Entwicklung** haben wir KI-gestützte Entwicklungswerkzeuge eingesetzt. Sie haben
den in der verfügbaren Zeit erreichten Funktionsumfang wesentlich ermöglicht. Zwischen dem
ersten Commit am 2. August und dem Abschlussgespräch am 7. September lagen gut fünf Wochen,
in denen eine Anwendung mit fünf Bereichen, einer Testsuite und statischer Analyse entstanden
ist. Die Konfiguration dieser
Werkzeuge liegt offen im Repository und ist als Teil des Arbeitsprozesses gekennzeichnet.

Die Grenze verlief bei den fachlichen Entscheidungen, und das ist keine Behauptung, sondern
im Projektverlauf belegbar. Die Umstellung auf den Katalog kam daher, dass wir die Anwendung
selbst benutzt und dabei gemerkt haben, dass eine Gewohnheit ohne Dauer nicht planbar ist.
Die Grenze von fünf aktiven Gewohnheiten geht auf Annes Frage nach einer Höchstzahl zurück
und wurde mit Umfragedaten begründet. Der Verzicht auf einen simulierten KI-Fallback ist eine
Haltungsentscheidung gegenüber dem Nutzer. Keine dieser drei Entscheidungen stammt aus einem
Werkzeug. Ein Werkzeug kann eine Regel umsetzen, aber nicht bestimmen, welche Regel richtig
ist; diese Arbeit bleibt vollständig bei uns, und sie ist der eigentliche Inhalt der Kapitel
5 bis 7.

## 9.5 Grenzen unseres Vorgehens

Unsere empirische Grundlage ist richtungsweisend, nicht repräsentativ. Drei Einschränkungen
haben wir bereits in Abschnitt 5.11 benannt und wiederholen sie hier, weil sie die Reichweite
aller Aussagen dieses Berichts begrenzen.

**Die Stichprobe ist klein und schief.** Angestrebt waren über 50 Antworten, erreicht wurden
25. Sie ist stark E-Commerce- und 5.-bis-6.-Semester-lastig, das Geschlechterverhältnis mit
16 zu 9 unausgewogen. Als Priorisierungshilfe ist sie belastbar, als Beweis nicht. Dass die
25 Antworten in rund zwei Stunden eingingen, zeigt zugleich, dass die Begrenzung an der
Kapazität des Umfragewerkzeugs lag und nicht an der Bereitschaft der Zielgruppe.

**Der ausgelieferte Fragebogen wich vom Leitfaden ab.** Die ursprünglich geplanten Skalen zur
Habit Journey und zum Konsistenzrate-Tracking fehlen als eigene Bewertung. Über zwei
Konzepte, die wir anschließend verworfen beziehungsweise umgebaut haben, liegen damit keine
direkten Daten vor.

**Eine Auswertung nach Personas ist nicht belastbar.** Eine Trennung nach Einsteigern und
Selbstregulierten, die unseren beiden Personas entsprochen hätte, ist bei dieser
Stichprobengröße nicht aussagekräftig.

Dazu kommt eine Grenze, die im Bericht bisher nur durch ihre Abwesenheit sichtbar wird.
**Die fertige Anwendung ist nie mit Nutzern getestet worden.** Nach Abschluss der Nutzerforschung
im Juni war die einzige Rückkopplung von außen das Betreuungsgespräch. Geprüft haben wir
technisch, mit Tests, statischer Analyse und einer Pipeline, die bei jedem Push läuft. Ob die
Anwendung ihren eigenen Anspruch erfüllt, nämlich nicht selbst zum Hindernis zu werden, ist
damit begründet, aber nicht gemessen. Das ist die deutlichste Lücke unseres Vorgehens und der
erste Punkt, den Kapitel 10 aufgreift.

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

**Umsetzbarkeit darf nicht am Anfang stehen.** Anne hat uns zweimal geraten, gute Konzepte
nicht aufzugeben, nur weil ihre technische Umsetzung aufwendig erscheint. Genau das ist
eingetreten. Die Stundenplan-Integration war im Mai eine unbelegte Annahme aus der
Marktanalyse und wirkte technisch am teuersten. Sie ist als Semesterplan der Gedanke, der
unseren gesamten Projektverlauf überdauert hat, und zugleich das Merkmal, das Align von einem
Gewohnheitstracker unterscheidet.
