# 3. Vorgehen und Zusammenarbeit

## 3.1 Das Team

Align haben wir zu dritt entwickelt, **Berkay**, **Silas** und **Ngoc Ha**, alle im
Studiengang E-Commerce an der Technischen Hochschule Würzburg-Schweinfurt. Ein vierter
Kommilitone, Prabjot, war zu Projektbeginn eingeplant, konnte wegen eines parallel
beginnenden Praktikums jedoch nicht mitarbeiten. Betreut wurde unser Projekt von
**Anne Heß**.

Recherche, Interviews und alle zentralen Konzeptentscheidungen haben wir gemeinsam
erarbeitet. Daneben haben sich Schwerpunkte entlang von Interessen und Vorkenntnissen
herausgebildet, ohne dass daraus strikte Zuständigkeiten wurden. Jede und jeder von uns hat
konzeptionell, gestalterisch und technisch beigetragen.

| | Schwerpunkt |
|---|---|
| **Berkay** | Competitor-Analyse, Personas, Interview-Leitfaden, technische Umsetzung |
| **Silas** | Literaturrecherche, Endfassung der Online-Umfrage, Feature-Ableitung, technische Umsetzung |
| **Ngoc Ha** | Wireframes und Design, Erstfassung der Umfrage, Zwischenpräsentationen, Dokumentation |

## 3.2 Phasen und Iterationen

Unser Projekt lief vom 17. Mai bis zum 7. September 2026 und durchlief vier
Entwicklungsphasen. Innerhalb dieser Phasen haben **sechs Iterationen** die Arbeit
strukturiert. Jede endete mit einem Betreuungsgespräch, in dem wir unseren Stand präsentiert
und Feedback für das weitere Vorgehen eingeholt haben. Zwischen zwei Gesprächen lagen in der
Regel drei Wochen.

| Phase | Iterationen | Gespräche | Schwerpunkt |
|---|---|---|---|
| **1 · Analyse und Grundlagen** | Iteration 1 | 20.05. | Literatur, Competitor-Analyse, erste Wireframes |
| **2 · Nutzerforschung** | Iterationen 2 und 3 | 08.06. · 29.06. | Interviews, Personas, Online-Umfrage, Feature-Priorisierung |
| **3 · Konzeption und Design** | Iteration 4 | 20.07. | Feature-Ausarbeitung, Designsprache, Prototypen |
| **4 · Technische Umsetzung** | Iterationen 5 und 6 | 10.08. · 07.09. | Aufbau der Anwendung, Ausbau, Härtung |

Wir sind bewusst schrittweise vorgegangen. Statt den gesamten Projektverlauf im Voraus
festzulegen, haben wir jeweils den nächsten sinnvollen Schritt aus dem Stand unserer
Erkenntnis abgeleitet. Ob eine Phase eine oder zwei Iterationen brauchte, ergab sich daraus,
wie tragfähig ihre Ergebnisse waren. Die Nutzerforschung beanspruchte zwei Iterationen, weil
wir sie zweistufig angelegt haben, mit qualitativen Interviews zur Hypothesenbildung und
einer quantitativen Umfrage zur Validierung. Die technische Umsetzung brauchte ebenfalls
zwei, weil eine lauffähige Grundlage und ihr Ausbau zu benutzbarer Software zwei
verschiedene Aufgaben sind.

Das Iterationsformat wirkte über die Terminstruktur hinaus. Weil wir alle drei Wochen ein
vorzeigbares Ergebnis brauchten, blieben Konzeptdiskussionen nie lange abstrakt. Mehrere
Annahmen haben wir dadurch früh korrigiert, etwa die Ausgestaltung der Fortschrittsanzeige
oder den Wechsel der Farbwelt.

## 3.3 Zusammenarbeit und Dokumentation

Für die laufende Abstimmung haben wir einen gemeinsamen Gruppenchat genutzt, ergänzt um
regelmäßige interne Videocalls, die Betreuungsgespräche liefen über Zoom. Ein
niedrigschwelliger Kanal für den Alltag und feste Termine für inhaltliche Abstimmungen haben
ausgereicht, ein eigenes Projektmanagement-Werkzeug brauchten wir nicht.

Als gemeinsame Arbeitsumgebung diente uns **Notion**. Dort haben wir pro Iteration das
Feedback aus dem Betreuungsgespräch, die daraus abgeleiteten Aufgaben und die
Zwischenergebnisse abgelegt. Der Gedanke dahinter war, die Dokumentation projektbegleitend zu
führen statt sie am Ende rekonstruieren zu müssen. Diese Entscheidung hat sich bewährt, ohne
sie wäre dieser Bericht deutlich lückenhafter ausgefallen. Mit dem Beginn der technischen
Umsetzung ist die Dokumentation in Markdown-Dateien in das Projektverzeichnis gewandert, wo
sie neben dem Code versioniert wird.

Zu jedem Betreuungsgespräch haben wir eine kurze Präsentation erstellt. Das gab den
Iterationen eine feste Form und macht unseren Fortschritt im Nachhinein nachvollziehbar.

## 3.4 Eingesetzte Werkzeuge

| Zweck | Werkzeug |
|---|---|
| Abstimmung und Betreuungsgespräche | Gruppenchat, Videocalls, Zoom |
| Projektdokumentation | Notion, später Markdown im Projektverzeichnis |
| Zwischenpräsentationen | Canva (Hochschulvorlage) |
| Design und Prototyping | Figma |
| Online-Umfrage | LimeSurvey |
| Literaturrecherche | THWS-Bibliothek, Springer, Wiley |
| Entwicklung | Laravel Herd, VS Code, GitHub, KI-gestützte Entwicklungswerkzeuge |

Drei Werkzeugentscheidungen sind für unseren Projektverlauf relevant.

**LimeSurvey statt Google Forms.** Anne wies in Iteration 1 auf Datenschutz und Anonymität
bei der Umfrage hin und riet von Google Forms ab. Wir haben die Umfrage deshalb in LimeSurvey
umgesetzt (Kapitel 5).

**Figma als verbindliche Designquelle.** Ab Iteration 2 haben wir in einer gemeinsamen
Figma-Datei gearbeitet, in der wir Farben, Typografie und Komponenten festgelegt haben. Sie
blieb bis zum Projektende unsere maßgebliche Referenz für alle Gestaltungsfragen, auch
gegenüber den später daraus abgeleiteten Prototypen.

**KI-gestützte Entwicklungswerkzeuge.** In Phase 4 haben wir bei der Implementierung mit
einem KI-gestützten Entwicklungswerkzeug gearbeitet. Wir benennen es hier, weil es den in der
verfügbaren Zeit erreichten Funktionsumfang wesentlich ermöglicht hat. Seine Konfiguration
liegt offen im Quellcode-Repository. Abschnitt 7.1 ordnet den Einsatz in die technische
Umsetzung ein, Abschnitt 9.4 reflektiert ihn und zieht die Grenze zu den fachlichen
Entscheidungen.
