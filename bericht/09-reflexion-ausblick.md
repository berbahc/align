# 9. Reflexion und Ausblick

> **Notiz für Berkay, vor der Abgabe entfernen.** Deine Kapitel 9 und 10 sind jetzt ein Kapitel
> „Reflexion und Ausblick", der Ausblick läuft als Abschnitte 9.7 bis 9.10 weiter. Bitte drei
> Stellen gegenlesen:
>
> 1. **Abschnitt 7.13 „Was bewusst weggelassen wurde" gibt es nicht mehr.** Sein Inhalt steht
>    jetzt nur noch in 9.7. Dort ist ergänzt, dass wir die vier Funktionen früh und bewusst
>    weggelassen haben, damit der Umfang beherrschbar bleibt, und dass beim Blocker die Zeit
>    der Grund war und nicht das Planungsmodell. Passt die Formulierung für dich?
> 2. **Die Idee einer KI, die selbst Muster erkennt und sich meldet, ist herausgenommen**, auch
>    aus dem Ausblick. Betroffen waren „Eine KI, die sich von selbst meldet" im früheren 10.3
>    sowie je eine Tabellenzeile im früheren 10.1 und 10.4. Das Konzept soll im Bericht nicht
>    vorkommen, auch nicht als verworfen.
> 3. **Querverweise auf Kapitel 7** sind an dessen neue Gliederung angepasst, der Katalog steht
>    jetzt in 7.8 und das Datenmodell in 7.15.

Dieses Kapitel blickt auf vier Monate Projektarbeit zurück. Es beschreibt, welche
Entscheidungen getragen haben, an welchen Stellen wir umgekehrt sind, welche Rolle
KI-Werkzeuge dabei gespielt haben und wo die Grenzen unseres Vorgehens liegen. Wir halten
uns dabei an dieselbe Regel wie im übrigen Bericht. Wir berichten, was entschieden wurde und
warum, nicht, was wir uns im Nachhinein gewünscht hätten. Der zweite Teil des
Kapitels blickt nach vorn, auf das, was wir bewusst weggelassen haben, und darauf, wie sich
Align weiterentwickeln ließe.

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
Funktionsumfangs (Abschnitt 7.8).

**Auch das Datenmodell ist gewachsen, nicht entworfen worden.** Die Reihenfolge unserer
Migrationen zeichnet den Weg der Anwendung nach; bemerkenswert sind dabei jene, die etwas
entfernen. Punktuelle Gewohnheiten, geratene Situationen und eine überflüssige Kursart sind
nach dem tatsächlichen Gebrauch wieder verschwunden (Abschnitt 7.15).

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
Produktentscheidung; die langfristige Produktidee bleibt die mobile Anwendung, und Abschnitt 9.10
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
erste Punkt, den Abschnitt 9.9 aufgreift.

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

## 9.7 Was wir bewusst weggelassen haben, und der Weg zurück

Vier Funktionen haben wir in Phase 4 ausdrücklich nicht gebaut. Wir haben uns früh dafür
entschieden, damit der Umfang beherrschbar bleibt und die übrigen Funktionen zuverlässig
laufen. Drei davon scheiterten an einer Eigenschaft, die unser Planungsmodell voraussetzt,
für die vierte reichte die Zeit der Umsetzung nicht mehr.

| Weggelassen | Warum | Was es bräuchte |
|---|---|---|
| **Punktuelle Gewohnheiten** wie „Treppe statt Aufzug" | haben keine Dauer und belegen kein Zeitfenster | ein zweiter Gewohnheitstyp, der ohne Platz im Tag auskommt und nur gezählt wird |
| **Situative Anker ohne planbare Uhrzeit** wie „nach dem Frühstück" | zu individuell, um daraus eine verlässliche Planung abzuleiten | eine Möglichkeit, die Uhrzeit eines solchen Moments je Wochentag einmal selbst festzulegen |
| **Eigene Gewohnheiten eintragen** | frei formulierte Gewohnheiten tragen keine Dauer (Abschnitt 7.8) | ein eigener Eintrag mit Dauer und Bereich als Pflichtangaben |
| **Blocker** als eigene Kategorie für feste Termine | im Rahmen dieser Umsetzung nicht mehr erreicht | ein dritter Blocktyp neben Kurs und Gewohnheit, der den Tag belegt, ohne abgehakt zu werden |

Der dritte Punkt ist der wichtigste, weil er die spürbarste Einschränkung der fertigen
Anwendung ist. Wer eine Gewohnheit vorhat, die im Katalog fehlt, kann sie derzeit nicht
anlegen. Die Umkehrung wäre dabei kein Rückschritt zum alten Zustand. Was die freie Eingabe
unbrauchbar machte, war nicht die Freiheit, sondern die fehlende Angabe. Ein eigener Eintrag,
der nach Bereich und Dauer fragt, behält den Katalog als Vorschlag und öffnet ihn zugleich.

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

**Erinnerungen, die die Anwendung verlassen.** Unser Konzept sah eine Erinnerung vor dem
Auslöser vor, nicht danach. Umgesetzt ist ein Wecker innerhalb der Anwendung, der nur wirkt,
solange sie geöffnet ist. Das ist eine direkte Folge der Plattformentscheidung aus Abschnitt
7.1 und einer der Punkte, an denen sich ihr Preis zeigt.

**Eine Erprobung mit Nutzern.** Nach Abschnitt 9.5 ist dies die deutlichste Lücke unseres
Vorgehens. Sinnvoll wäre beides. Ein Usability-Test des Einrichtungsflows und der
Tagesansicht mit Studierenden, die das Projekt nicht kennen, würde zeigen, ob die Anwendung
ihren ersten Anspruch einlöst. Eine Folgebefragung mit mindestens 50 Teilnehmenden über die
eigene Fachrichtung hinaus würde die Befunde absichern, die wir bisher als richtungsweisend
bezeichnen müssen, und zugleich die beiden Skalen nachholen, die im ausgelieferten Fragebogen
fehlten. Besonders offen ist dabei die Frage, ob der Koordinationsaufwand einer Verabredung
im Alltag tragbar ist. Das lässt sich nur mit echten Paaren prüfen, nicht mit
Einzelpersonen.

## 9.10 Die langfristige Richtung

Gemessen an den vier Marktlücken, die unsere Analyse im Mai gefunden hat, fällt die Bilanz
gemischt aus, aber nachvollziehbar.

| Marktlücke | Stand |
|---|---|
| **Lebensrealität Studierender als Produktlogik** | halb geschlossen, der Semesterplan kennt Kurse und Kollisionen, aber keine Prüfungsphasen |
| **Planen findet am Laptop statt** | erfüllt, aber als Nebenwirkung der Entwicklungsentscheidung, nicht als Produktentscheidung |
| **KI als Begleiter statt Content-Bibliothek** | geschlossen, die KI kennt Gewohnheiten, Schlafrahmen und Stundenplan und macht Vorschläge für den tatsächlichen Tag |
| **Subtile statt aggressiver Gamifizierung** | geschlossen durch Konsistenzrate, neutrale Fehltage und fehlenden Verlustdruck |

Von den sechs Ansprüchen aus Abschnitt 1.6 haben wir fünf erreicht und können sie belegen:
klare Struktur, Verzicht auf Bestrafung, Konsistenz über Light und Dark Mode, kontextsensitive
Personalisierung über Situations-Anker und eine Gestaltung, deren Entscheidungen auf Literatur
zurückführbar sind. Beim ersten Anspruch, dass die Anwendung nicht selbst zum Hindernis werden
darf, haben wir gute Gründe, aber keinen Beleg. Ihn zu erbringen, ist die eigentliche Aufgabe
der nächsten Phase.

Die langfristige Produktidee bleibt die native mobile Anwendung. Sie löst einen der offenen
Punkte unmittelbar, denn Erinnerungen erreichen den Nutzer dann auch außerhalb der Anwendung. Die Positionierung aus Abschnitt 2.4
bleibt dabei unverändert das Ziel, nämlich eine Anwendung zum Gewohnheitsaufbau, die die
Lebensrealität Studierender versteht und ohne Druck zur Konsistenz führt. Align ist heute ein
MVP aus einem Studienprojekt und kein marktfähiges Produkt. Es zeigt etwas anderes, das für
den Zweck dieser Arbeit genauer passt. Eine solche Anwendung lässt sich aus Interviews, einer
Umfrage und drei wissenschaftlichen Quellen begründen und in lauffähige Form bringen, ohne
dass zwischen der Begründung und dem Gebauten eine Lücke entsteht.
