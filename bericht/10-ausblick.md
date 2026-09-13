# 10. Ausblick

Ein Ausblick lässt sich als Wunschliste schreiben oder aus dem heraus, was bereits belegt
ist. Wir wählen den zweiten Weg. Unsere Migrationen zeigen, dass wir Konzepte nicht nur
ergänzt, sondern nach dem tatsächlichen Gebrauch auch zurückgenommen haben (Abschnitt 7.14).
Jede dieser Rücknahmen trägt einen Grund, und jeder dieser Gründe beschreibt zugleich, was
nötig wäre, um sie aufzuheben. Dieses Kapitel führt die bewussten Weglassungen aus Abschnitt
7.13 weiter, ordnet die verworfenen Konzeptideen ein und benennt, was als Nächstes käme.

---

## 10.1 Die vier Weglassungen und der Weg zurück

Vier Funktionen haben wir in Phase 4 ausdrücklich nicht gebaut. Keine davon ist am Aufwand
gescheitert; jede scheiterte an einer Eigenschaft, die unser Planungsmodell voraussetzt.

| Weggelassen | Warum | Was es bräuchte |
|---|---|---|
| **Punktuelle Gewohnheiten** wie „Treppe statt Aufzug" | haben keine Dauer und belegen kein Zeitfenster | ein zweiter Gewohnheitstyp, der ohne Platz im Tag auskommt und nur gezählt wird |
| **Situative Anker ohne planbare Uhrzeit** wie „nach dem Frühstück" | zu individuell, um daraus eine verlässliche Planung abzuleiten | eine Uhrzeit, die die Anwendung aus dem Abhakverhalten lernt, statt sie zu raten |
| **Eigene Gewohnheiten eintragen** | frei formulierte Gewohnheiten tragen keine Dauer (Abschnitt 7.9) | ein eigener Eintrag mit Dauer und Bereich als Pflichtangaben |
| **Blocker** als eigene Kategorie für feste Termine | im Rahmen dieser Umsetzung nicht mehr erreicht | ein dritter Blocktyp neben Kurs und Gewohnheit, der den Tag belegt, ohne abgehakt zu werden |

Der dritte Punkt ist der wichtigste, weil er die spürbarste Einschränkung der fertigen
Anwendung ist: Wer eine Gewohnheit vorhat, die im Katalog fehlt, kann sie derzeit nicht
anlegen. Die Umkehrung wäre dabei kein Rückschritt zum alten Zustand. Was die freie Eingabe
unbrauchbar machte, war nicht die Freiheit, sondern die fehlende Angabe. Ein eigener Eintrag,
der nach Bereich und Dauer fragt, behält den Katalog als Vorschlag und öffnet ihn zugleich.

## 10.2 Verworfenes, das wiederkommen könnte

**Die Habit Journey.** Wir haben sie nach der Umfrage gestrichen und die Streichung bewusst
in die Zwischenpräsentation aufgenommen, statt sie stillschweigend verschwinden zu lassen
(Abschnitt 5.13). Die methodische Einordnung in Abschnitt 5.11 zwingt uns hier zu einer
Einschränkung: Der ausgelieferte Fragebogen enthielt keine eigene Skala zur Habit Journey.
Wir haben also ein Konzept gestrichen, ohne es je erhoben zu haben. Das Konzept selbst liegt
vollständig ausgearbeitet vor und beruht unmittelbar auf Lally et al. (2010): eine
Automatisierungskurve pro Gewohnheit mit gemessenem und geschätztem Verlauf, eine
Phasenanzeige von Aufbau über Festigung bis Gewohnheit und Erfolgsmarken nach 30, 66 und 100
Tagen. Die Zahl 66 ist dabei kein Spielelement, sondern der in der Studie gemessene
Durchschnitt. Für eine Anwendung, die Gewohnheitsbildung als Prozess über Wochen versteht,
schließt das eine Lücke, die unsere Fortschrittsanzeige heute offen lässt: Sie zeigt die
letzten 30 Tage, aber nicht den Weg.

**Das Freiwerden eines Platzes.** Eng damit verbunden ist ein Mechanismus, den wir konzipiert
und nicht gebaut haben. Läuft eine Gewohnheit über Wochen zuverlässig, könnte die Anwendung
anbieten, sie als gefestigt zu markieren und damit einen der fünf aktiven Plätze freizugeben.
Heute gibt es nur „Beenden", und das liest sich wie ein Abbruch. Die Grenze von fünf
Gewohnheiten ist unsere am besten begründete Produktregel; sie hat aber keinen vorgesehenen
Ausgang nach oben.

**Zurückhaltende Erweiterungen der Community.** Gebaut ist die Verabredung für einen
einzelnen Tag. Ausgearbeitet, aber nicht umgesetzt sind drei kleinere Bausteine: ein Signal,
dass jemand heute aktiv ist, ohne zu zeigen, woran; eine einzelne Reaktion auf eine erledigte
Gewohnheit; und eine gemeinsame Gewohnheit für eine kleine Gruppe, etwa eine WG oder eine
Lerngruppe. Alle drei folgen derselben Regel wie der bestehende Bereich, dass Aussetzer für
andere unsichtbar bleiben.

**Was verworfen bleibt.** Die Rangliste und das Community Dashboard nehmen wir nicht wieder
auf. In den Interviews wurde Vergleich als Kontrolle beschrieben, in der Umfrage war eine
Rangliste ausdrücklich nicht gewünscht, und beide Mechaniken widersprechen der Zusage, mit
der der Community-Bereich beginnt. Ebenso bleiben der gemeinsame Kalender (ø 3,04) und
Live-Bilder während einer Gewohnheit (ø 2,83) gestrichen. Sie waren die beiden schwächsten
Bewertungen der gesamten Umfrage.

## 10.3 Was als Nächstes käme

**Ein Modus für die Prüfungsphase.** Das ist der stärkste Befund unserer Nutzerforschung, den
die fertige Anwendung nicht bedient. Stress und Prüfungsphase sind mit 17 von 25 Nennungen
der mit Abstand größte Grund, Gewohnheiten aufzugeben. Entscheidend ist die Reaktion darauf:
15 von 25 reduzieren in dieser Zeit, statt ganz aufzuhören (Abschnitt 5.9). Align kennt
heute den Stundenplan, aber keine Prüfungsphase; es kann einen Tag umsortieren, aber nicht
kleiner machen. Ein solcher Modus würde den Tag auf eine Kernroutine zusammenziehen und die
übrigen Gewohnheiten sichtbar beiseitestellen, statt sie zu löschen, mit demselben Weg
zurück. Reduzieren statt pausieren, als Funktion statt als Vorsatz. Von allen offenen Punkten
hat dieser die beste Datengrundlage.

**Eine KI, die sich von selbst meldet.** In Abschnitt 1.3.2 haben wir dem gesamten Markt
vorgehalten, dass seine Anwendungen statisch sind: Wird eine Gewohnheit nicht eingehalten,
hat das keinen Einfluss auf die Planung des nächsten Tages, und je weiter Plan und Realität
auseinanderdriften, desto eher wird die Anwendung beiseitegelegt. Diesen Vorwurf haben wir
nur zur Hälfte eingelöst. Unsere KI ordnet den Tag neu und schlägt neue Zeiten vor, aber erst
auf Zuruf. Der nächste Schritt wäre, dass sie einen mehrfach verpassten Anker selbst bemerkt
und einmal nachfragt, ob ein anderer Platz besser passt. Der Unterschied zwischen einem
Werkzeug und einem Begleiter liegt genau hier, und die technische Grundlage dafür steht:
Kontextwissen über Gewohnheiten, Rahmen und Stundenplan ist vorhanden, die Vorschlagslogik
ebenfalls.

**Erinnerungen, die die Anwendung verlassen.** Unser Konzept sah eine Erinnerung vor dem
Auslöser vor, nicht danach. Umgesetzt ist ein Wecker innerhalb der Anwendung, der nur wirkt,
solange sie geöffnet ist. Das ist eine direkte Folge der Plattformentscheidung aus Abschnitt
7.1 und einer der Punkte, an denen sich ihr Preis zeigt.

**Eine Erprobung mit Nutzern.** Nach Abschnitt 9.5 ist dies die deutlichste Lücke unseres
Vorgehens. Sinnvoll wäre beides: ein Usability-Test des Einrichtungsflows und der
Tagesansicht mit Studierenden, die das Projekt nicht kennen, und eine Folgebefragung mit
mindestens 50 Teilnehmenden über die eigene Fachrichtung hinaus. Diese Befragung würde
zugleich die beiden Skalen nachholen, die im ausgelieferten Fragebogen fehlten, und damit
eine Entscheidung absichern, die wir bisher ohne Daten getroffen haben. Eine Frage ist dabei
besonders offen: Ob sich eine Verabredung zwischen zwei Menschen in der Praxis ohne feste
Uhrzeit koordinieren lässt, ist eine Annahme, die wir nie geprüft haben.

## 10.4 Die langfristige Richtung

Gemessen an den vier Marktlücken, die unsere Analyse im Mai gefunden hat, fällt die Bilanz
gemischt aus, aber nachvollziehbar.

| Marktlücke | Stand |
|---|---|
| **Lebensrealität Studierender als Produktlogik** | halb geschlossen — der Semesterplan kennt Kurse und Kollisionen, aber keine Prüfungsphasen |
| **Planen findet am Laptop statt** | erfüllt, aber als Nebenwirkung der Entwicklungsentscheidung, nicht als Produktentscheidung |
| **KI als Begleiter statt Content-Bibliothek** | geschlossen, solange sie gefragt wird; die adaptive Hälfte fehlt |
| **Subtile statt aggressiver Gamifizierung** | geschlossen — Konsistenzrate, neutrale Fehltage, kein Verlustdruck |

Von den sechs Ansprüchen aus Abschnitt 1.6 haben wir fünf erreicht und können sie belegen:
klare Struktur, Verzicht auf Bestrafung, Konsistenz über Light und Dark Mode, kontextsensitive
Personalisierung über Situations-Anker und eine Gestaltung, deren Entscheidungen auf Literatur
zurückführbar sind. Beim ersten Anspruch, dass die Anwendung nicht selbst zum Hindernis werden
darf, haben wir gute Gründe, aber keinen Beleg. Ihn zu erbringen, ist die eigentliche Aufgabe
der nächsten Phase.

Die langfristige Produktidee bleibt die native mobile Anwendung. Sie löst gleich zwei der
offenen Punkte: Erinnerungen erreichen den Nutzer außerhalb der Anwendung, und eine KI, die
sich von selbst meldet, braucht genau diesen Kanal. Die Positionierung aus Abschnitt 2.4
bleibt dabei unverändert das Ziel — eine Anwendung zum Gewohnheitsaufbau, die die
Lebensrealität Studierender versteht und ohne Druck zur Konsistenz führt. Align ist heute
ein MVP im Rahmen eines Studienprojekts und kein marktfähiges Produkt. Was es zeigt, ist
etwas anderes und für den Zweck dieser Arbeit Genaueres: dass sich eine solche Anwendung aus
Interviews, einer Umfrage und drei wissenschaftlichen Quellen begründen und in lauffähige
Form bringen lässt, ohne dass zwischen der Begründung und dem Gebauten eine Lücke entsteht.
