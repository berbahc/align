# Dokumentation

Verlauf des Projekts, erzeugt aus der Git-Historie.

> Diese Datei wird automatisch geschrieben — Änderungen von Hand gehen beim
> nächsten Commit verloren. Sie entsteht neu mit `php artisan dokumentation:generate`
> und läuft nach jedem Commit sowie nach jedem Pull von selbst.

**Stand:** 14.09.2026 16:02 · **161 Commits** · erster Eintrag 02.08.2026

## Beteiligte

- **berbahc** — 118 Commits, zuletzt am 14.09.2026
- **Silas2505** — 43 Commits, zuletzt am 13.09.2026

---

## 14.09.2026

### docs: die Notiz in Kapitel 9 ist erledigt

`c531bdf` · **berbahc** · 15:12 Uhr

> Alle vier Punkte aus Silas' Notiz sind angenommen, wie sie stehen: 7.13 geht
> in 9.7 auf, die Verweise auf Kapitel 7 folgen dessen neuer Gliederung, die
> Zeitangaben zur Entwicklung bleiben draussen, und die Idee einer KI, die von
> selbst Muster erkennt, kommt im Bericht nicht vor.
>
> Damit ist die Notiz aus dem Dokument raus und der offene Punkt in der README
> auch.
>
> Claude-Session: https://claude.ai/code/session_01YDPKGL7he93DXyGacG7drN

<details><summary>2 Dateien · +0/−19</summary>

- `bericht/README.md` +0/−1
- `bericht/zusammenarbeit/bericht.md` +0/−18

</details>

## 13.09.2026

### docs: Kapitel 7 ohne Entwicklungszeitraum, mit Bildern zu Schlafplan, Verschieben und Stundenplan

`723ec19` · **Silas2505** · 21:22 Uhr

> Änderungen in bericht/zusammenarbeit/bericht.md:
>
> - 7.1 erklärt schlicht, warum Web-App, Mobile First und Laravel.
> - Ab 7.6 stehen keine Zeiträume und Commit-Zahlen mehr. Bei alten
>   Abbildungen steht nur noch der Code-Stand. Dasselbe gilt für 7.16, 8.1
>   und Kapitel 9.
> - Die Einleitung zu Iteration 6 begründet, warum sich die beiden Ziele
>   schwer vereinbaren ließen, statt das Protokoll zu zitieren.
> - Roter Faden zu Weggelassenem: Katalog statt Textfeld (7.8), nur noch
>   berechenbare Situationen (7.10), Ausblick mit eigenen Gewohnheiten, die
>   die KI auswertet (9.7).
> - 7.9: Der Schlafrhythmus ist selbst eine Gewohnheit, unterstützt durch die
>   Erinnerung 20 Minuten vor der Schlafenszeit (so steht es im Code).
> - 7.10 und 7.11: Regeln beim Verschieben („Immer" nur bei freiem Platz an
>   allen Tagen, Vorlesungen haben Vorrang, verdrängte Gewohnheiten werden
>   geparkt), jeweils mit Abbildung.
> - Abbildungen in Kapitel 7 neu nummeriert (7.1 bis 7.15).
>
> Neue Bilder liegen in screenshots/kapitel7. Das Bild für Abb. 7.13
> (verlauf/v07-0309-seitenleiste.png) fehlte bisher im Ordner und ist jetzt
> dabei.

<details><summary>9 Dateien · +235/−146</summary>

- `bericht/zusammenarbeit/bericht.md` +235/−146
- `bericht/zusammenarbeit/screenshots/kapitel7/k7-01-schlafplan.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel7/k7-02-tagesbeginn.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel7/k7-03-tagesende.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel7/k7-04-immer-abgelehnt.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel7/k7-05-kurs-vorrang.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel7/k7-06-ohne-festen-platz.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel7/k7-07-anderer-zeitpunkt.png` +0/−0
- `bericht/zusammenarbeit/screenshots/verlauf/v07-0309-seitenleiste.png` +0/−0

</details>

### docs: die einzelnen Kapiteldateien entfallen, es gibt nur noch bericht.md

`63fd9f9` · **Silas2505** · 17:51 Uhr

> Die Kapitel 1 bis 9 und die Gliederung stehen vollständig in
> bericht/zusammenarbeit/bericht.md. Die Einzeldateien bericht/0*.md sind
> entfernt, damit niemand mehr in eine zweite Fassung schreibt. Die README
> zeigt auf das gemeinsame Dokument und beschreibt Ablauf und Schreibregeln.

<details><summary>11 Dateien · +24/−2145</summary>

- `bericht/00-gliederung.md` +0/−94
- `bericht/01-einleitung.md` +0/−149
- `bericht/02-problemraum-markt.md` +0/−175
- `bericht/03-vorgehen.md` +0/−97
- `bericht/04-phase1-analyse.md` +0/−100
- `bericht/05-phase2-nutzerforschung.md` +0/−338
- `bericht/06-phase3-konzeption-design.md` +0/−224
- `bericht/07-phase4-technische-umsetzung.md` +0/−347
- `bericht/08-die-fertige-app.md` +0/−249
- `bericht/09-reflexion-ausblick.md` +0/−327
- `bericht/README.md` +24/−45

</details>

### docs: bericht/zusammenarbeit/bericht.md ist ab jetzt das gemeinsame Dokument

`33d3869` · **Silas2505** · 17:47 Uhr

> An den Bericht wird ab jetzt nur noch in einer Datei geschrieben:
> bericht/zusammenarbeit/bericht.md. Sie enthält alle Kapitel 1 bis 9 am Stück,
> die Bilder liegen daneben in bericht/zusammenarbeit/screenshots/.
>
> Für Claude-Sitzungen im Team: Änderungen am Berichtstext gehören in diese
> Datei, nicht in die Kapiteldateien bericht/0*.md, nicht in Google Docs und
> nicht in Notion. Vor dem Schreiben git pull, danach ein kurzer Commit, der
> sagt, was geändert wurde, und sofort git push. Wer was geändert hat, zeigt
> die Git-Historie dieser Datei. Die Schreibregeln stehen oben in der Datei.

<details><summary>41 Dateien · +2045/−0</summary>

- `bericht/zusammenarbeit/bericht.md` +2045/−0
- `bericht/zusammenarbeit/screenshots/abb02-gewohnheiten.png` +0/−0
- `bericht/zusammenarbeit/screenshots/abb04-katalog-auswahl.png` +0/−0
- `bericht/zusammenarbeit/screenshots/abb06-kalender-monat.png` +0/−0
- `bericht/zusammenarbeit/screenshots/abb07-tagesansicht.png` +0/−0
- `bericht/zusammenarbeit/screenshots/abb08-schlafplan.png` +0/−0
- `bericht/zusammenarbeit/screenshots/abb09-community.png` +0/−0
- `bericht/zusammenarbeit/screenshots/app/app01-onboarding.png` +0/−0
- `bericht/zusammenarbeit/screenshots/app/app11-habits-dark.png` +0/−0
- `bericht/zusammenarbeit/screenshots/app/app12-calendar-dark.png` +0/−0
- `bericht/zusammenarbeit/screenshots/figma/fig01-startseite-iteration1.png` +0/−0
- `bericht/zusammenarbeit/screenshots/figma/fig02-startseite-iteration2.png` +0/−0
- `bericht/zusammenarbeit/screenshots/figma/fig03-startseite-iteration3.png` +0/−0
- `bericht/zusammenarbeit/screenshots/figma/fig04-anker-dynamisch.png` +0/−0
- `bericht/zusammenarbeit/screenshots/figma/fig05-warum-satz.png` +0/−0
- `bericht/zusammenarbeit/screenshots/figma/fig06-starthilfe-sheet.png` +0/−0
- `bericht/zusammenarbeit/screenshots/figma/fig08-progress-uebersicht.png` +0/−0
- `bericht/zusammenarbeit/screenshots/figma/fig09-progress-insights.png` +0/−0
- `bericht/zusammenarbeit/screenshots/figma/fig10-verabredung-vorschlagen.png` +0/−0
- `bericht/zusammenarbeit/screenshots/figma/fig11-vergleich-community.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel8/k01-uebersicht.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel8/k02-schritt1.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel8/k03-schritt2.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel8/k04-schritt3.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel8/k05-schritt4-ki.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel8/k06-schritt5.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel8/k07-fast-fertig.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel8/k08-tag-mit-kette.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel8/k09-beim-ziehen.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel8/k10-neuer-platz.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel8/k11-nach-dem-ziehen.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel8/k12-uebersicht-mit-kette.png` +0/−0
- `bericht/zusammenarbeit/screenshots/kapitel8/k13-uebersicht-dunkel.png` +0/−0
- `bericht/zusammenarbeit/screenshots/personas/persona-1.png` +0/−0
- `bericht/zusammenarbeit/screenshots/personas/persona-2.png` +0/−0
- `bericht/zusammenarbeit/screenshots/verlauf/v01-1008-uebersicht.png` +0/−0
- `bericht/zusammenarbeit/screenshots/verlauf/v02-1008-kalender.png` +0/−0
- `bericht/zusammenarbeit/screenshots/verlauf/v03-1008-gewohnheiten.png` +0/−0
- `bericht/zusammenarbeit/screenshots/verlauf/v04-3108-anlegen-schritt2.png` +0/−0
- `bericht/zusammenarbeit/screenshots/verlauf/v05-0309-gewohnheiten.png` +0/−0
- `bericht/zusammenarbeit/screenshots/verlauf/v06-0309-semesterplan.png` +0/−0

</details>

### docs: der Bericht zeigt, wie die App wurde, und bekommt einen gemeinsamen Schluss

`bbdcdfe` · **Silas2505** · 14:47 Uhr

> Kapitel 8 führt jetzt in der Reihenfolge des ersten Benutzens durch die App:
> Auftakt, Übersicht, das Anlegen einer Gewohnheit in allen fünf Schritten mit
> einem echten KI-Vorschlag, und das Verschieben einer Gewohnheit, bei dem die
> angehängte mitrutscht.
>
> Iteration 6 in Kapitel 7 ist als Folge einzelner Änderungen erzählt, jeweils
> mit Vorher und Nachher. Für die Vorher-Bilder haben wir die Code-Stände vom
> 10.08., 31.08. und 03.09. noch einmal gebaut und nachträglich aufgenommen.
> Abschnitt 7.13 entfällt, sein Inhalt steht jetzt im Ausblick.
>
> Kapitel 9 und 10 sind zu „Reflexion und Ausblick" zusammengelegt, mit einer
> Notiz für Berkay am Anfang. Aus dem ganzen Bericht entfernt sind die Idee einer
> KI, die aus verpassten Tagen lernt und sich von selbst meldet, und die
> Beratungsstelle der Hochschule, die wir nie einbezogen haben. Neu sind die
> Persona-Sheets in Kapitel 5 und das Ziel einer möglichst hürdenfreien Bedienung
> in Kapitel 1 und 6; die Figma-Entwürfe und App-Aufnahmen kommen mit.

<details><summary>49 Dateien · +575/−395</summary>

- `bericht/00-gliederung.md` +12/−13
- `bericht/01-einleitung.md` +16/−10
- `bericht/02-problemraum-markt.md` +9/−8
- `bericht/04-phase1-analyse.md` +2/−2
- `bericht/05-phase2-nutzerforschung.md` +10/−2
- `bericht/06-phase3-konzeption-design.md` +44/−10
- `bericht/07-phase4-technische-umsetzung.md` +155/−107
- `bericht/08-die-fertige-app.md` +161/−98
- `bericht/{09-reflexion.md => 09-reflexion-ausblick.md}` +148/−6
- `bericht/10-ausblick.md` +0/−131
- `bericht/README.md` +18/−8
- `bericht/screenshots/abb01-uebersicht.png` +0/−0
- `bericht/screenshots/abb05-anker.png` +0/−0
- `bericht/screenshots/abb10-darkmode.png` +0/−0
- `bericht/screenshots/app/app01-onboarding.png` +0/−0
- `bericht/screenshots/app/app11-habits-dark.png` +0/−0
- `bericht/screenshots/app/app12-calendar-dark.png` +0/−0
- `bericht/screenshots/figma/fig01-startseite-iteration1.png` +0/−0
- `bericht/screenshots/figma/fig02-startseite-iteration2.png` +0/−0
- `bericht/screenshots/figma/fig03-startseite-iteration3.png` +0/−0
- `bericht/screenshots/figma/fig04-anker-dynamisch.png` +0/−0
- `bericht/screenshots/figma/fig05-warum-satz.png` +0/−0
- `bericht/screenshots/figma/fig06-starthilfe-sheet.png` +0/−0
- `bericht/screenshots/figma/fig08-progress-uebersicht.png` +0/−0
- `bericht/screenshots/figma/fig09-progress-insights.png` +0/−0
- `bericht/screenshots/figma/fig10-verabredung-vorschlagen.png` +0/−0
- `bericht/screenshots/figma/fig11-vergleich-community.png` +0/−0
- `bericht/screenshots/kapitel8/k01-uebersicht.png` +0/−0
- `bericht/screenshots/{abb03-katalog-bereiche.png => kapitel8/k02-schritt1.png}` +0/−0
- `bericht/screenshots/kapitel8/k03-schritt2.png` +0/−0
- `bericht/screenshots/kapitel8/k04-schritt3.png` +0/−0
- `bericht/screenshots/kapitel8/k05-schritt4-ki.png` +0/−0
- `bericht/screenshots/kapitel8/k06-schritt5.png` +0/−0
- `bericht/screenshots/kapitel8/k07-fast-fertig.png` +0/−0
- `bericht/screenshots/kapitel8/k08-tag-mit-kette.png` +0/−0
- `bericht/screenshots/kapitel8/k09-beim-ziehen.png` +0/−0
- `bericht/screenshots/kapitel8/k10-neuer-platz.png` +0/−0
- `bericht/screenshots/kapitel8/k11-nach-dem-ziehen.png` +0/−0
- `bericht/screenshots/kapitel8/k12-uebersicht-mit-kette.png` +0/−0
- `bericht/screenshots/kapitel8/k13-uebersicht-dunkel.png` +0/−0
- `bericht/screenshots/personas/persona-1.png` +0/−0
- `bericht/screenshots/personas/persona-2.png` +0/−0
- `bericht/screenshots/verlauf/v01-1008-uebersicht.png` +0/−0
- `bericht/screenshots/verlauf/v02-1008-kalender.png` +0/−0
- `bericht/screenshots/verlauf/v03-1008-gewohnheiten.png` +0/−0
- `bericht/screenshots/verlauf/v04-3108-anlegen-schritt2.png` +0/−0
- `bericht/screenshots/verlauf/v05-0309-gewohnheiten.png` +0/−0
- `bericht/screenshots/verlauf/v06-0309-semesterplan.png` +0/−0
- `bericht/screenshots/verlauf/v07-0309-seitenleiste.png` +0/−0

</details>

### fix: die zwei Angebote unter einer Gewohnheit stehen nebeneinander

`0013f23` · **Silas2505** · 14:21 Uhr

> Auf dem Handy standen „Mit jemandem zusammen?" und „Kleinen ersten Schritt"
> untereinander, vier Pixel auseinander. Die Reihe war schon als umbrechende
> Zeile gebaut, bekam aber nie genug Platz: Neben dem Einzug auf Titelhöhe
> bleiben bei 390 Pixeln Breite 264, und die beiden Knöpfe brauchten 336.
>
> „Kleinen ersten Schritt" bleibt, wie es ist — derselbe Wortlaut wie im
> Kalender, damit es ein Angebot bleibt und nicht zwei. Der Weg zur Verabredung
> heißt jetzt „Zu zweit?" und trägt das Zeichen des Community-Tabs. Das ist
> kürzer und genauer, denn mehr als eine zweite Person hat eine Verabredung
> nicht. Beide Knöpfe tragen damit ein Zeichen vorn und stehen in einer Zeile
> (252 von 264 Pixeln). Auf einem 360 Pixel schmalen Gerät bricht die Reihe
> weiterhin um, jetzt aber mit acht statt vier Pixeln zwischen den Zeilen.
>
> Der sichtbare Text ist Teil des zugänglichen Namens („Zu zweit mit
> jemandem?"), damit Sprachsteuerung ihn findet.

<details><summary>1 Datei · +19/−5</summary>

- `resources/js/components/habit-row.tsx` +19/−5

</details>

### docs: Dokumentation.md auf den Stand der Schlusskapitel

`b477f38` · **berbahc** · 13:52 Uhr

> Claude-Session: https://claude.ai/code/session_01YDPKGL7he93DXyGacG7drN

<details><summary>1 Datei · +111/−3</summary>

- `Dokumentation.md` +111/−3

</details>

### docs: Kapitel 9 und 10 sprechen wie der Rest des Berichts

`0ddac38` · **berbahc** · 13:52 Uhr

> Die beiden Schlusskapitel waren vor der sprachlichen Überarbeitung
> geschrieben. Jetzt folgen sie denselben Regeln: keine Gedankenstriche im
> Fliesstext, Doppelpunkte nur vor Aufzaehlungen und Zitaten, zugespitzte
> Merksaetze nuechtern gesagt.
>
> Zwei inhaltliche Angleichungen kommen dazu. Abschnitt 9.3 nennt den
> urspruenglich geplanten Tech-Stack nicht mehr beim Namen, weil er auch aus
> 7.1 verschwunden ist. Und die offene Frage in 10.3, ob sich eine Verabredung
> ohne feste Uhrzeit koordinieren laesst, ist keine mehr: Laut 7.15 hat eine
> Verabredung inzwischen eine Uhrzeit. An ihre Stelle tritt die Frage, die
> offen geblieben ist, naemlich ob der Koordinationsaufwand im Alltag traegt.
>
> Claude-Session: https://claude.ai/code/session_01YDPKGL7he93DXyGacG7drN

<details><summary>2 Dateien · +55/−55</summary>

- `bericht/09-reflexion.md` +22/−23
- `bericht/10-ausblick.md` +33/−32

</details>

### docs: der Bericht bekommt seinen Schluss

`b60d050` · **berbahc** · 13:49 Uhr

> Kapitel 9 und 10 waren die letzten offenen Stellen. Beide standen im Bericht
> schon als Versprechen: Abschnitt 7.13 endet mit "Diese Liste ist die Grundlage
> des Ausblicks in Kapitel 10", und Abschnitt 8.8 wiederholt den Verweis.
>
> Kapitel 9 blickt zurück auf das, was getragen hat, auf die drei Stellen, an
> denen wir umgekehrt sind, auf den Plattformwechsel und auf die Grenzen unserer
> Methode. Dazu gehoert die Luecke, die bisher nur durch ihre Abwesenheit
> sichtbar war: Die fertige Anwendung ist nie mit Nutzern getestet worden.
>
> Kapitel 10 fuehrt die vier bewussten Weglassungen weiter, ordnet die
> verworfenen Konzeptideen ein und benennt, was als Naechstes kaeme. Ganz oben
> steht der Modus fuer die Pruefungsphase: der staerkste Befund der
> Nutzerforschung, den die fertige App nicht bedient.
>
> Abschnitt 3.4 nennt jetzt auch die KI-gestuetzten Entwicklungswerkzeuge. Im
> Repository stehen sie offen, im Bericht fehlten sie in der Werkzeugtabelle;
> Abschnitt 9.4 reflektiert den Einsatz und zieht die Grenze zu den fachlichen
> Entscheidungen.
>
> Claude-Session: https://claude.ai/code/session_01YDPKGL7he93DXyGacG7drN

<details><summary>5 Dateien · +334/−11</summary>

- `bericht/00-gliederung.md` +5/−5
- `bericht/03-vorgehen.md` +9/−2
- `bericht/09-reflexion.md` +186/−0
- `bericht/10-ausblick.md` +130/−0
- `bericht/README.md` +4/−4

</details>

## 12.09.2026

### docs: der Bericht liest sich weniger nach Maschine

`14b904a` · **Silas2505** · 15:31 Uhr

> Alle acht Kapitel sprachlich überarbeitet. Gedankenstriche im Fließtext
> ersetzt, verschachtelte Sätze aufgeteilt, Doppelpunkte auf Aufzählungen
> und Zitate beschränkt, zugespitzte Merksätze durch nüchterne Aussagen
> ersetzt.
>
> Inhaltlich unverändert bis auf zwei Stellen: der ursprünglich geplante
> Tech-Stack ist aus 7.1 raus, und ein grammatisch gebrochener Satz am
> Ende von Kapitel 2 ist repariert.

<details><summary>8 Dateien · +490/−480</summary>

- `bericht/01-einleitung.md` +64/−63
- `bericht/02-problemraum-markt.md` +53/−51
- `bericht/03-vorgehen.md` +24/−23
- `bericht/04-phase1-analyse.md` +31/−30
- `bericht/05-phase2-nutzerforschung.md` +75/−74
- `bericht/06-phase3-konzeption-design.md` +66/−62
- `bericht/07-phase4-technische-umsetzung.md` +137/−138
- `bericht/08-die-fertige-app.md` +40/−39

</details>

### fix: die Beteiligtenliste nennt die Gruppe, nicht jeden Commit-Autor

`acc468d` · **berbahc** · 11:33 Uhr

> Der Generator zählte bisher jeden Autor, der je einen Commit gesetzt hat.
> In einer Abgabe soll daneben stehen, wer das Projekt gemacht hat, deshalb
> zählt die Liste jetzt nur noch die Projektgruppe. Sie steht als Konstante
> im Befehl.
>
> Gemeldet von Silas.
>
> Claude-Session: https://claude.ai/code/session_01MFVXELMFVbmLAtY3FauFgr

<details><summary>2 Dateien · +82/−9</summary>

- `Dokumentation.md` +54/−7
- `app/Console/Commands/GenerateDokumentation.php` +28/−2

</details>

### docs: der Bericht zieht in das Repository

`e885f9f` · **Silas2505** · 10:17 Uhr

> Kapitel 1 bis 8 als Markdown, eine Datei pro Kapitel, dazu zehn Screenshots
> der laufenden App für Kapitel 8. Der Bericht folgt den vier Entwicklungsphasen;
> die sechs Iterationen sind darin eingeordnet.
>
> Offen sind Reflexion, Ausblick und Anhang. Die Konventionen — Wir-Perspektive,
> keine personenbezogene Zuschreibung im Fließtext — stehen in bericht/README.md.

<details><summary>20 Dateien · +1644/−0</summary>

- `bericht/00-gliederung.md` +95/−0
- `bericht/01-einleitung.md` +142/−0
- `bericht/02-problemraum-markt.md` +172/−0
- `bericht/03-vorgehen.md` +89/−0
- `bericht/04-phase1-analyse.md` +99/−0
- `bericht/05-phase2-nutzerforschung.md` +329/−0
- `bericht/06-phase3-konzeption-design.md` +186/−0
- `bericht/07-phase4-technische-umsetzung.md` +300/−0
- `bericht/08-die-fertige-app.md` +185/−0
- `bericht/README.md` +47/−0
- `bericht/screenshots/abb01-uebersicht.png` +0/−0
- `bericht/screenshots/abb02-gewohnheiten.png` +0/−0
- `bericht/screenshots/abb03-katalog-bereiche.png` +0/−0
- `bericht/screenshots/abb04-katalog-auswahl.png` +0/−0
- `bericht/screenshots/abb05-anker.png` +0/−0
- `bericht/screenshots/abb06-kalender-monat.png` +0/−0
- `bericht/screenshots/abb07-tagesansicht.png` +0/−0
- `bericht/screenshots/abb08-schlafplan.png` +0/−0
- `bericht/screenshots/abb09-community.png` +0/−0
- `bericht/screenshots/abb10-darkmode.png` +0/−0

</details>

## 11.09.2026

### docs: Dokumentation.md nachgezogen

`1e924d2` · **berbahc** · 14:07 Uhr

> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>1 Datei · +46/−2</summary>

- `Dokumentation.md` +46/−2

</details>

### fix: die Platzhalter zeigen die Regel, nicht eine Person

`9bccf8a` · **berbahc** · 14:07 Uhr

> Im Registrierungsformular stand „berkay" als Beispiel für den Username —
> derselbe Name auch in den Profileinstellungen. Ein Platzhalter soll zeigen,
> was erlaubt ist: Kleinbuchstaben, Ziffern, Unterstrich. „max_23" tut das,
> ein Vorname tut es nicht.
>
> Dabei sind die letzten englischen Platzhalter mitgegangen. Sie standen in den
> Einstellungen („Full name", „Current password") und im Passkey-Feld, während
> alles daneben deutsch ist. Der Passkey-Baustein war ohnehin noch ganz
> englisch — er kam so aus dem Starter-Kit und ist nie übersetzt worden.
>
> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>5 Dateien · +15/−15</summary>

- `resources/js/components/delete-user.tsx` +1/−1
- `resources/js/components/passkey-register.tsx` +7/−7
- `resources/js/pages/auth/register.tsx` +1/−1
- `resources/js/pages/settings/profile.tsx` +3/−3
- `resources/js/pages/settings/security.tsx` +3/−3

</details>

### docs: Dokumentation.md auf den Stand der Abgabe

`e709513` · **berbahc** · 13:59 Uhr

> Der Hook schreibt die Datei nach jedem Commit neu. Seit sie in der
> Versionsverwaltung liegt, hinterlässt er damit eine Änderung, die mit
> committet werden will — dieser Stand enthält alle 146 Commits.
>
> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>1 Datei · +52/−5</summary>

- `Dokumentation.md` +52/−5

</details>

### docs: das Repository als Abgabe

`d3b4b97` · **berbahc** · 13:56 Uhr

> Die README war für das Team geschrieben — sie erklärt das Einrichten und die
> Stolpersteine, aber nicht, was dieses Projekt ist. Für jemanden, der den Link
> zum ersten Mal öffnet, fehlte genau das. Jetzt steht vorn die Leitfrage, das
> beobachtete Problem mit den Zahlen aus der eigenen Umfrage, und eine Zeile je
> Funktion.
>
> Dazu drei Dinge, die beim Nachbauen von außen aufgefallen sind:
>
> - Die Anleitung setzte Laravel Herd voraus und nannte keinen anderen Weg.
>   Dabei steht `.env.example` längst auf `localhost:8000` — es fehlte nur der
>   Hinweis auf `php artisan serve`.
> - `touch database/database.sqlite` ist überflüssig: `migrate --force` legt die
>   Datei selbst an.
> - `APP_NAME` stand auf `Laravel`. Ein frischer Klon nannte sich im Browsertab
>   also nach dem Framework statt nach der App.
>
> Der Seeder füllte bisher nur die Hälfte der App. `/community` und der
> Semesterteil des Kalenders gingen leer auf, obwohl es für beide Factories und
> gut 150 Tests gibt — wer durchklickte, musste sie für unfertig halten. Jetzt
> kommen ein Semester mit Stundenplan, zwei Bekannte, eine offene Anfrage und
> eine Verabredung dazu. Und ein zweites, leeres Konto: Wer sehen will, wie die
> App jemanden empfängt, kann sich nicht mit einem eingerichteten anmelden.
>
> `Dokumentation.md` liegt nicht mehr außerhalb der Versionsverwaltung. Sie
> erzählt 145 Commits auf Deutsch und war bisher nur auf einem Rechner sichtbar.
> Der Hook, der sie nach jedem Commit neu schreibt, hinterlässt damit eine
> Änderung im Arbeitsbaum — vorher war das egal, weil die Datei ignoriert war.
>
> Die Lizenzangabe stand auf MIT, ohne dass es eine Lizenzdatei gab. Für eine
> Studienarbeit ist `proprietary` die ehrlichere Angabe.
>
> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>7 Dateien · +6304/−74</summary>

- `.env.example` +4/−1
- `.gitignore` +5/−2
- `Dokumentation.md` +6036/−0
- `README.md` +145/−65
- `composer.json` +4/−4
- `database/seeders/DatabaseSeeder.php` +106/−2
- `package.json` +4/−0

</details>

### fix: die statische Analyse hat nichts mehr zu melden

`e0e4b8e` · **berbahc** · 13:46 Uhr

> `composer ci:check` scheiterte an 21 PHPStan-Fehlern, und die CI war deshalb
> rot — auf der Startseite des Repositorys stand neben dem letzten Commit ein
> rotes Kreuz. Die README wusste davon und nannte 17; die Zahl war also
> gestiegen, obwohl dort steht, sie solle es nicht.
>
> Keiner der Fehler war Kosmetik, und keiner brauchte einen Eingriff in Logik:
>
> - Neun Methoden versprachen eine Liste und lieferten ein Array mit
>   Ganzzahlschlüsseln. `Collection::values()` ist im Framework als `static`
>   typisiert und beweist keine Liste; `array_values()` tut genau das.
> - Drei Eigenschaften waren als `Carbon` dokumentiert, obwohl der
>   `AppServiceProvider` die App auf `CarbonImmutable` stellt. Die Doku war
>   schlicht falsch.
> - Zweimal hing `isoFormat()` an `locale('de')` in derselben Kette. `locale()`
>   gibt je nach Aufruf das Objekt oder die aktuelle Sprache zurück — in der
>   Kette weiß das niemand mehr. Jetzt gesetzt und dann formatiert, wie es der
>   `DashboardController` ohnehin vormacht.
> - Die Form eines Commits stand dreimal in `GenerateDokumentation`, zweimal
>   verkürzt. Einmal benannt, dreimal benutzt.
> - `$completed_today` kommt aus einem `withExists` und steht nur da, wenn die
>   Abfrage danach fragt. Das ist jetzt am Modell dokumentiert.
>
> Dazu fällt weg, was aus dem Starter-Kit übrig war und nie jemand aufgerufen
> hat: die Werbeseite `welcome.tsx` (42 KB, `/` leitet auf `/login`), zwei
> Platzhalter-Komponenten, das Vorschau-Gerüst `preview.html` samt
> `__preview.tsx`, und `tests/Unit/ExampleTest.php` mit seinem „true ist wahr".
>
> An seine Stelle treten echte Unit-Tests: Die Umrechnung zwischen Uhrzeit und
> Minute steht unter jedem Block im Kalender und war bisher nur über drei Ecken
> geprüft. Der Umbruch über Mitternacht ist dabei kein Sonderfall, sondern der
> Regelfall am Abend.
>
> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>20 Dateien · +135/−629</summary>

- `app/Actions/Fortify/CreateNewUser.php` +1/−1
- `app/Console/Commands/GenerateDokumentation.php` +8/−6
- `app/Http/Controllers/CalendarController.php` +2/−2
- `app/Http/Controllers/DashboardController.php` +11/−6
- `app/Http/Controllers/HabitController.php` +5/−2
- `app/Http/Middleware/HandleInertiaRequests.php` +2/−2
- `app/Http/Requests/AddFriendRequest.php` +2/−0
- `app/Http/Requests/ProposeAppointmentRequest.php` +2/−0
- `app/Models/Appointment.php` +13/−10
- `app/Models/Friendship.php` +4/−3
- `app/Models/Habit.php` +11/−3
- `preview.html` +0/−6
- `resources/js/__preview.tsx` +0/−111
- `resources/js/app.tsx` +3/−3
- `resources/js/components/feature-placeholder.tsx` +0/−60
- `resources/js/components/ui/placeholder-pattern.tsx` +0/−20
- `resources/js/pages/welcome.tsx` +0/−389
- `tests/Feature/{ExampleTest.php => HomeRedirectTest.php}` +0/−0
- `tests/Unit/DayPlanTimeTest.php` +71/−0
- `tests/Unit/ExampleTest.php` +0/−5

</details>

## 10.09.2026

### feat: Bild 03 sitzt im Hörsaal

`60e2251` · **berbahc** · 12:04 Uhr

> Der alte Clip zeigte einen Schreibtisch am Fenster und dahinter einen
> erleuchteten Raum. Er deutete an, dass jemand nicht dort ist, wo etwas los
> ist — vom Grund dafür zeigte er nichts. Jetzt sitzt man mit im Hörsaal:
> von hinten über die Schulter, Kopf in der Hand, ein paar Studierende weit
> verstreut in den Reihen, der Dozent klein vorn an der Tafel.
>
> Der Satz daneben behauptet nicht mehr zu viel. „Deine Freunde seit zwei
> Wochen nicht" stimmt für die meisten schlicht nicht — man sieht sie schon,
> es wird nur aufwendig, weil jeder einen anderen Stundenplan hat. Also:
> „Deine Dozenten siehst du täglich. Freunde nur, wenn der Tag passt." Damit
> zeigt Bild 03 das Problem, das Bild 06 löst — eine Person, ein Tag, eine
> Zusage.
>
> Die Datei heißt `hoersaal`, nicht mehr `freunde`. Ein neuer Clip unter
> altem Namen kommt bei jedem, der die Seite schon offen hatte, aus dem
> Cache — und auf einem Vorführrechner merkt man das im falschen Moment.
>
> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>5 Dateien · +6/−7</summary>

- `public/onboarding/freunde.jpg` +0/−0
- `public/onboarding/freunde.mp4` +0/−0
- `public/onboarding/hoersaal.jpg` +0/−0
- `public/onboarding/hoersaal.mp4` +0/−0
- `resources/js/components/onboarding-intro-scenes.tsx` +6/−7

</details>

### fix: die drei Bildschirme zeigen die App so, wie sie wirklich aussieht

`bcbbb3e` · **berbahc** · 11:44 Uhr

> Der Kalendertag war ein Kartenausschnitt und keine Kalenderseite. Jetzt
> steht dort, was dort steht: die Datumszeile mit ihren Pfeilen, der Weg
> zurück in den Monat, „Tag neu ordnen" mit der Figur davor, und darunter
> das Raster auf seiner Blattkarte — Stundenspalte links, Aufsteh-Marke an
> ihrer echten Minute, ein erledigtes Frühstück, die Vorlesung mit
> durchgezogener Kante und die Gewohnheit, die daran einrastet, mit
> gestrichelter. Der Unterschied der beiden Kanten ist die halbe Aussage des
> Bildes (§7.3).
>
> Die Übersicht ist ein Stück gescrollt. Das ist kein Kniff, sondern der
> Zustand, in dem man diesen Bildschirm meistens sieht — und er stellt die
> Zeile zu zweit in die Mitte statt ans untere Ende. Vom Fortschritt bleibt
> die Karte ganz, die Begrüßung liegt darüber außerhalb des Bildes. Dazu die
> erledigte Gewohnheit, ohne die „1 von 2" eine Rechnung wäre, die man
> nachprüft und die nicht stimmt, und am Fuß der Rahmen des Tages, den die
> Bildkante anschneidet.
>
> Der Knopf „Neu hinzufügen" ist raus: Er passt neben der Überschrift auf
> 390 Pixeln nicht, und der Film soll den Bildschirm zeigen, nicht die
> Werkzeugleiste.
>
> Beide Breiten sind durchgesehen. Die Bühne der Telefon-Bilder ist auf dem
> Handy höher als die der Filmbilder — ein Telefon ist hoch, ein Filmbild
> breit —, und das Raster ist flacher, damit auch dort der Tag bis zu der
> Stelle reicht, um die es geht.
>
> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>2 Dateien · +327/−102</summary>

- `resources/css/app.css` +2/−2
- `resources/js/components/onboarding-intro-scenes.tsx` +325/−100

</details>

### feat: die Antwort-Bilder des Auftakts zeigen die App im Gerät

`74c5ea3` · **berbahc** · 11:09 Uhr

> Bild 04 bis 06 haben lose Kartenausschnitte auf einer Farbfläche gezeigt.
> Das liest sich wie ein Ausschnitt aus einer Präsentation, nicht wie eine
> App, die es gibt. Jetzt steht dort ein Telefon mit einem echten
> Bildschirm: Statusleiste, die Kopfzeile der App, der Inhalt.
>
> Was darin steht, ist nicht nachgebaut, sondern die Sache selbst — der
> Kalendertag mit der Vorlesung und der Gewohnheit, die daran einrastet; der
> vierte Schritt des Assistenten mit dem echten `AiSuggestion`; die
> Übersicht mit Begrüßung, Fortschrittskarte und der Zeile zu zweit.
>
> Dabei fällt eine Erfindung weg: Die gestrichelte KI-Karte auf `ai-fill`
> gibt es in der App nirgends. Das echte `AiSuggestion` ist `bg-sand/60` mit
> der Figur davor, und genau das steht jetzt im Bild. Ein Film, der eine
> Oberfläche erfindet, verspricht etwas, das die App danach einlösen müsste.
>
> Alles im Gerät ist in echten Gerätepixeln gemaßt und als ein Stück
> skaliert. Die Bausteine der App sind für 390 Pixel gezeichnet; in einen
> schmaleren Kasten gepresst brächen sie um und wären dann keine echte
> Oberfläche mehr. Das Gerät ist höher als die Bühne und läuft unten aus dem
> Bild — ein ganzes Telefon in einer flachen Fläche wäre so klein, dass man
> die Schrift darin sucht.
>
> Hinter dem Gerät liegt ein Foto statt eines Verlaufs: Blattschatten von
> links, eine Fläche unten, sonst nichts. Ein Gerät auf einer glatten
> Farbfläche sieht aus wie ausgeschnitten.
>
> Nebenbei ein Test, der nichts damit zu tun hat: `HabitSlotTest` legt einen
> Kurs auf einen Donnerstag und erwartet den Wochentag im Satz. Fällt heute
> auf denselben Tag, sagt die App richtigerweise „Heute" — der Test fiel
> also jeden Donnerstag um. Die Uhr steht dort jetzt fest.
>
> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>4 Dateien · +468/−83</summary>

- `public/onboarding/ground.jpg` +0/−0
- `resources/css/app.css` +100/−11
- `resources/js/components/onboarding-intro-scenes.tsx` +363/−72
- `tests/Feature/HabitSlotTest.php` +5/−0

</details>

## 09.09.2026

### fix: die Anrede auf der Übersicht liest die Uhr, nicht das Datum

`375df97` · **berbahc** · 18:25 Uhr

> Über der Seite stand rund um die Uhr „Guten Morgen". Die Ursache war ein
> Wort: Die Anrede bekam `Carbon::today()` — Mitternacht, bei jedem Aufruf —
> und Mitternacht ist nun einmal vor elf. Sie bekommt jetzt `Carbon::now()`.
>
> Und weil sie die Uhr ohnehin liest, darf sie den Tag auch benennen: fünf
> Stufen statt drei, vom „Gute Nacht" bis zum „Guten Abend". Die Nacht
> bekommt eine Anrede und keine Ermahnung — wer um drei die Übersicht
> öffnet, hat dafür seinen Grund.
>
> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>2 Dateien · +37/−2</summary>

- `app/Http/Controllers/DashboardController.php` +19/−2
- `tests/Feature/DashboardTest.php` +18/−0

</details>

### fix: jedes Bild trägt seine eigene Überschrift

`8a27aae` · **berbahc** · 18:18 Uhr

> Dreimal „Dein Alltag", dreimal „Mit Align" — beim zweiten Mal sagt so eine
> Zeile nichts mehr, und beim dritten steht sie nur noch da. Jetzt benennt
> sie, wovon das Bild handelt: Sport, Essen, Freunde, dann Dein Tag mit
> Align, Der erste Schritt, Zu zweit.
>
> Der Umschlag zur Antwort geht dabei nicht verloren. Er trägt sich
> ohnehin selbst: Ab Bild 04 ist die Oberfläche der App zu sehen, und das
> Bild heißt dann auch so.
>
> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>1 Datei · +12/−10</summary>

- `resources/js/components/onboarding-intro-scenes.tsx` +12/−10

</details>

### fix: Sport scheitert am ganzen Tag, nicht nur am Abend

`cee06f2` · **berbahc** · 18:16 Uhr

> Bild 01 hat den Abend beschrieben und damit nur die Hälfte gesagt. Sport
> fällt nicht aus, weil es abends spät wird, sondern weil er in einem vollen
> Uni-Tag an keiner Stelle hineinpasst: morgens zu früh, zwischen den
> Vorlesungen zu knapp, abends zu müde.
>
> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>1 Datei · +5/−5</summary>

- `resources/js/components/onboarding-intro-scenes.tsx` +5/−5

</details>

### fix: die Sätze des Films sagen, woran es wirklich liegt

`40a9696` · **berbahc** · 18:14 Uhr

> „Weil vom Tag nichts mehr übrig ist" klang nach einem Bild und erklärte
> nichts. Gemeint war etwas Einfacheres: Es fehlt nicht der Wille, es fehlt
> die Zeit, in Ruhe zu kochen. Alle sechs Sätze sind daraufhin noch einmal
> durchgegangen — jeder benennt jetzt eine Ursache, keine Stimmung.
>
> Zwei Stellen greifen dabei ineinander: Bild 03 sagt „hat nichts davon
> einen festen Platz im Tag", Bild 04 antwortet „Align gibt jedem Vorhaben
> einen festen Platz im Tag". Vorher lag zwischen Frage und Antwort ein
> halber Wortwechsel.
>
> Bild 05 trägt den Leitsatz der KI in einfachen Worten: Sie schlägt den
> kleinsten ersten Schritt vor, entscheiden tut der Nutzer.
>
> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>1 Datei · +14/−12</summary>

- `resources/js/components/onboarding-intro-scenes.tsx` +14/−12

</details>

### fix: der Film wird über zwei Pfeile geblättert, nicht über das Bild

`3f4e72f` · **berbahc** · 18:08 Uhr

> Über dem Bild lagen zwei unsichtbare Flächen: links zurück, rechts weiter.
> Das ist die Geste aus Stories, und genau deshalb war sie hier falsch — wer
> nicht damit rechnet, springt beim Zeigen auf das Bild eine Szene weiter
> und weiß nicht, warum. Das Bild darf jetzt Bild sein.
>
> Stattdessen eine Leiste unter dem Film: zurück, anhalten, weiter. Sie sagt,
> was sie tut, ist 44 Pixel groß und mit Tastatur wie Vorlesehilfe zu
> bedienen. Die Pfeile blättern nur durch den Film — auf dem letzten Bild
> steht der rechte still, und hinaus führen der Knopf darunter, `Esc` und
> „Überspringen".
>
> Die Clips laufen wieder in normaler Geschwindigkeit. Halbes Tempo sah aus
> wie Zeitlupe; dass ein Fünf-Sekunden-Clip in einer Sieben-Sekunden-Szene
> einmal von vorn beginnt, fällt weniger auf als ein schleichendes Bild.
>
> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>2 Dateien · +76/−89</summary>

- `resources/js/components/onboarding-intro-scenes.tsx` +4/−21
- `resources/js/components/onboarding-intro.tsx` +72/−68

</details>

### fix: „Zurück" im Assistenten führt in den Film, nicht in den Rahmen

`6d2f9ec` · **berbahc** · 18:02 Uhr

> Der Rückweg von „Fangen wir klein an" ging bisher eine Stufe zurück auf
> die Frage nach dem Rahmen. Gemeint war der Auftakt: Wer noch einmal sehen
> will, wofür die App gebaut ist, will den Film, nicht seine Aufstehzeit.
>
> Damit fällt auch der Umweg weg, den der Rahmen dafür gebraucht hätte — er
> musste sich wieder öffnen lassen, obwohl er schon stand, und dazu seine
> eigenen Zeiten als Vorbelegung mitbringen. Beides ist zurückgenommen; die
> Zeiten ändert man später unter „Schlaf", so wie die Frage es selbst sagt.
>
> Der Film steigt bei seinem letzten Bild ein und führt über „Fangen wir mit
> deinem Tag an" wieder in den Assistenten. Ein Ausgang je Bild, in beide
> Richtungen.
>
> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>4 Dateien · +11/−88</summary>

- `app/Http/Controllers/OnboardingController.php` +3/−11
- `resources/js/components/habit-wizard.tsx` +2/−2
- `resources/js/pages/onboarding.tsx` +6/−35
- `tests/Feature/OnboardingTest.php` +0/−40

</details>

### fix: der erste Schritt des Assistenten hat einen Rückweg

`a0d327d` · **berbahc** · 17:54 Uhr

> „Fangen wir klein an" war eine Einbahnstraße: Der Rahmen stand gespeichert
> auf dem Server, also zeigte die Seite ab da nur noch den Assistenten. Wer
> die Aufstehzeit vertippt hatte, kam ohne „Später einrichten" nicht mehr
> daran.
>
> Der Assistent bekommt dafür einen Ausgang, den die Seite füllt: Er kennt
> nur seine eigenen Schritte, was davor liegt, weiß die Seite, die ihn
> zeigt. Im Onboarding ist das der Rahmen, sonst nichts — dann bleibt der
> erste Schritt wie bisher ohne Rückweg.
>
> Der wiedergeöffnete Rahmen kommt mit den eigenen Zeiten statt mit der
> Voreinstellung. Sonst stünde beim Zurückgehen wieder 07:00/23:00 im Feld,
> und ein Druck auf „Weiter" ersetzte stillschweigend, was der Nutzer
> gewählt hat.
>
> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>4 Dateien · +101/−10</summary>

- `app/Http/Controllers/OnboardingController.php` +11/−3
- `resources/js/components/habit-wizard.tsx` +15/−2
- `resources/js/pages/onboarding.tsx` +35/−5
- `tests/Feature/OnboardingTest.php` +40/−0

</details>

### feat: ein Auftakt, bevor die erste Frage kommt

`fda73df` · **berbahc** · 16:58 Uhr

> Wer sich registriert, landete bisher ohne ein Wort Erklärung bei „Wann
> beginnt dein Tag?". Davor stehen jetzt sechs Bilder: drei aus dem
> Studienalltag, in denen ein Vorhaben liegen bleibt, und drei aus der App,
> die zeigen, was Align dagegen tut — ein fester Platz im Tag, der kleinste
> erste Schritt von der KI, und auf Wunsch zu zweit.
>
> Zwischen Film und Rahmen gibt es keinen Schnitt: Die Stufe wechselt im
> Browser, der Server erfährt es erst danach. Eine Antwort, die neu rendert,
> wäre genau der Schnitt, den es nicht geben soll.
>
> Der Film läuft von selbst und lässt sich trotzdem in die Hand nehmen:
> tippen, halten, Pfeiltasten, Leertaste, Esc. Auf dem letzten Bild hält er
> an und wartet — niemand wird ungefragt ins Einrichten gekippt. Wer schon
> im Rahmen steht, kommt über „Zurück zum Film" wieder hinein, und zwar bei
> dem Bild, das er zuletzt gesehen hat.
>
> Ohne Bewegung fällt nur die Bewegung weg: kein Zähler, kein Video, alle
> Texte da, geblättert wird von Hand.
>
> Die drei Clips liegen als 16:9 in `public/onboarding/` und laufen mit 0.7
> ab — so ist die Szene vorbei, bevor der Clip von vorn beginnt.
>
> Claude-Session: https://claude.ai/code/session_01TpmLvQkYiNDdkKKpUpHPKi

<details><summary>16 Dateien · +1342/−19</summary>

- `app/Http/Controllers/OnboardingController.php` +30/−2
- `app/Models/User.php` +2/−0
- `database/factories/UserFactory.php` +18/−0
- `database/migrations/2026_09_09_115952_add_intro_seen_at_to_users_table.php` +35/−0
- `public/onboarding/essen.jpg` +0/−0
- `public/onboarding/essen.mp4` +0/−0
- `public/onboarding/freunde.jpg` +0/−0
- `public/onboarding/freunde.mp4` +0/−0
- `public/onboarding/sport.jpg` +0/−0
- `public/onboarding/sport.mp4` +0/−0
- `resources/css/app.css` +230/−0
- `resources/js/components/onboarding-intro-scenes.tsx` +445/−0
- `resources/js/components/onboarding-intro.tsx` +408/−0
- `resources/js/pages/onboarding.tsx` +133/−15
- `routes/web.php` +2/−0
- `tests/Feature/OnboardingTest.php` +39/−2

</details>

## 08.09.2026

### fix: was ausgemacht ist, rückt nicht

`b589c0b` · **berbahc** · 13:53 Uhr

> Der Rest des Lochs, das mit dem Festnageln der Uhrzeit aufging. Die Zeit steht
> fest, sobald gefragt wurde — damit beide Seiten dieselbe lesen. Wer danach
> seine Gewohnheit verschob, brach das auf: Sein Block wanderte, die Verabredung
> blieb, und die andere Person erfuhr nichts davon. Zwei Kalender, zwei
> Uhrzeiten, beide überzeugt. Genau das Bild, das den ganzen Umbau ausgelöst
> hat, nur über einen anderen Weg.
>
> Vier Wege führten dorthin. Drei davon sind Gesten, mit denen jemand eine
> Gewohnheit bewusst verschiebt, und die weist `PromiseLock` jetzt ab — mit
> Tag, Uhrzeit und Namen, weil „geht nicht" ohne den Grund dazu führt, dass man
> die Verabredung sucht, statt sie zu sehen:
>
> - für eine andere Verabredung Platz machen
> - im Raster ziehen, für heute wie für immer
> - die Gewohnheit bearbeiten
> - und die Ausnahme zurücknehmen, denn auch das verschiebt
>
> Dieselbe Antwort wie beim Kurs: Es geht nicht. Ein Kurs kommt von der Uni,
> eine Zusage gehört zu zweit — beide sind keine Sache, die man allein
> verschiebt. Der Ausweg ist nicht ein anderer Platz, sondern die Absage, und
> die trifft man bewusst. Der Riegel gilt für beide Seiten: auf der fragenden
> für die eigene Gewohnheit, auf der gefragten für die, die sie an diesem Tag
> ersetzt.
>
> **Der vierte Weg ist der Schlafplan, und der lässt sich nicht verriegeln** —
> er betrifft den ganzen Tag und nicht diese eine Gewohnheit. Wer seine
> Aufstehzeit verstellt, verschiebt jede Gewohnheit, die an einer Situation
> hängt, und darunter wäre auch das ausgemachte Frühstück. Ihn deswegen
> abzuweisen hieße, eine Verabredung über den eigenen Schlaf zu stellen.
>
> Stattdessen hält eine Tagesausnahme sie fest: Beim Zusagen bekommt auch die
> fragende Seite eine, wenn ihre Gewohnheit keine eigene Uhrzeit hat. Die
> Ausnahme schlägt jede Regel (`Habit::resolveStart()`), also bleibt das
> Frühstück um sieben, während der Wecker auf zehn rückt. Bei einer festen
> Uhrzeit braucht es das nicht — die wandert nicht von selbst, und die Wege, sie
> von Hand zu ändern, sind jetzt verriegelt. Wird abgesagt, fällt beides weg.
>
> Sechs der acht neuen Fälle fallen um, wenn man den Riegel wieder aushängt.
>
> Claude-Session: https://claude.ai/code/session_01BRjkfR9h9k5wrjpY1K3GZg

<details><summary>6 Dateien · +393/−21</summary>

- `app/Http/Controllers/AppointmentController.php` +37/−19
- `app/Http/Controllers/HabitDayShiftController.php` +36/−2
- `app/Http/Requests/Concerns/ChecksDayPlan.php` +18/−0
- `app/Http/Requests/ShiftHabitDayRequest.php` +15/−0
- `app/Support/PromiseLock.php` +92/−0
- `tests/Feature/PromiseLockTest.php` +195/−0

</details>

### fix: eine Zusage belegt den Tag — in beide Richtungen

`5970719` · **berbahc** · 13:33 Uhr

> Die letzten zwei Löcher aus derselben Prüfung. Beide hatten dieselbe Ursache:
> Eine zugesagte Verabredung hängt an einer **fremden** Gewohnheit, steht damit
> in keinem eigenen Plan, und der Stundenplan kennt sie erst recht nicht. Wer
> den Tag aus Gewohnheiten und Kursen baute, sah sie schlicht nicht.
>
> **Zwei Zusagen für eine Minute.** Anna fragt für zwei, Bea auch, beide
> Zusagen gingen durch. Danach lagen zwei gemeinsame Termine übereinander, jeder
> mit jemand anderem — und keiner von beiden wusste davon. Die Karte nennt jetzt
> den Grund mit Namen („Laufen gehen mit Anna") und bietet keine Ausweichzeiten
> an: Eine Zusage rückt nicht, sie ließe sich nur absagen. Vierte Art in
> `AppointmentConflictKind`.
>
> **Und die Gegenrichtung.** `SlotConflict` baute den Tag aus Gewohnheiten und
> Stundenplan; die Zusage fehlte darin. Man konnte also eine neue Gewohnheit auf
> das gemeinsame Frühstück legen, und niemand widersprach — auf allen fünf
> Wegen, die durch diese Klasse laufen. Der Satz dazu steht bei den anderen
> beiden, weil er von der Art des Blocks abhängt und nicht vom Aufrufer.
>
> `Appointment::blocksOnDates()` ist die eine Stelle, an der aus Zusagen die
> Belegung eines Tages wird — das Gegenstück zu `Timetable::blocksOn()` und aus
> demselben Grund an einem Ort: `DayPlan` nimmt Fremdblöcke von mehreren
> Aufrufern entgegen, und gäbe einer eine andere Belegung heraus als die übrigen,
> säße dieselbe Minute auf zwei Wegen an zwei Stellen. Eine Abfrage für alle
> gefragten Tage, die Kollisionsprüfung fragt bis zu vierzehn davon.
>
> Zwei Arten fallen dabei heraus, weil sie schon als Gewohnheit im Tag stehen:
> die eigene Frage und die ersetzte Sache — wer zum Frühstück zusagt und selbst
> Frühstück führt, hat seine Zeile für den Tag mitgezogen.
>
> Zwei Wege mehr in der Überlappungs-Invariante, und sie liest die Uhrzeit der
> Verabredung jetzt aus `startMinute()` statt sie nachzurechnen: gegen eine
> zweite Wahrheit zu prüfen ist keine Prüfung.
>
> Claude-Session: https://claude.ai/code/session_01BRjkfR9h9k5wrjpY1K3GZg

<details><summary>8 Dateien · +308/−19</summary>

- `app/Enums/AppointmentConflictKind.php` +3/−0
- `app/Models/Appointment.php` +91/−0
- `app/Support/AppointmentFit.php` +27/−7
- `app/Support/SlotConflict.php` +30/−6
- `resources/js/components/appointment-request-notice.tsx` +10/−3
- `resources/js/types/friendship.ts` +3/−2
- `tests/Feature/AppointmentConflictTest.php` +87/−0
- `tests/Feature/NoOverlapInvariantTest.php` +57/−1

</details>

### fix: jeder angebotene Tag trägt seine eigene Uhrzeit

`b14c412` · **berbahc** · 13:21 Uhr

> Das Sheet zeigte die Zeile von **heute** — und zwar auch dann, wenn der
> gewählte Tag ein anderer war. Aylin drückte „Nochmal ausmachen?", wählte
> Donnerstag, und darüber stand „07:00 · nur an diesem Tag": die Ausnahme, die
> das heutige gemeinsame Frühstück mit Berkay in ihre Zeile geschrieben hatte.
> Am Donnerstag gilt weder die sieben noch „nur an diesem Tag" — dort frühstückt
> sie um neun.
>
> Der Satz stimmte nie für zwei Tage gleichzeitig, es fiel nur nicht auf,
> solange heute keine Ausnahme trug. Bei einer Situation fällt es immer auf: Wer
> mittwochs später aufsteht als montags, hat je Tag eine andere Uhrzeit, und
> eine Zahl für alle drei Knöpfe ist für zwei davon falsch.
>
> `dayChoicesFor()` gibt deshalb je Tag die Uhrzeit mit, die die Verabredung an
> diesem Tag wirklich tragen wird — dieselbe Rechnung, die beim Fragen in
> `starts_at` landet. Das Sheet nennt vor der Wahl den Plan der Gewohnheit und
> danach den gewählten Tag samt seiner Uhrzeit, und die Zusammenfassung über dem
> Knopf sagt die Zeit, die die gefragte Person gleich lesen wird.
>
> `startTimeFor()` lädt den Nutzer selbst nach: Ohne ihn steigt
> `sleepBoundStartMinute()` aus, und „nach dem Aufstehen" fiele auf die Stunde
> aus der Vorschlagsliste zurück statt auf die wirkliche Aufstehzeit. Das traf
> den Weg über `HabitController::store()`, wo die Beziehung nicht gesetzt war.
>
> Claude-Session: https://claude.ai/code/session_01BRjkfR9h9k5wrjpY1K3GZg

<details><summary>4 Dateien · +116/−6</summary>

- `app/Models/Appointment.php` +22/−4
- `resources/js/components/appointment-sheet.tsx` +15/−2
- `resources/js/types/friendship.ts` +11/−0
- `tests/Feature/AppointmentReplacementTest.php` +68/−0

</details>

### test: „Nochmal ausmachen?" bringt die Zeit dessen mit, der drückt

`dfb3c38` · **berbahc** · 13:11 Uhr

> Die Wiederholung ist keine Kopie der alten Verabredung, sondern eine neue
> Frage: Sie hängt an der Gewohnheit dessen, der fragt
> (`repeatableHabitFor()`), und seit dem Festnageln nimmt sie deren Stelle in
> **seinem** Tag als Uhrzeit mit. Berkay bringt seine Situation mit, Aylin ihre
> Uhr — beim selben gemeinsamen Frühstück.
>
> Das lief schon so, war aber an keiner Stelle festgehalten. Drei Fälle, die
> auseinandergehen könnten, ohne dass es jemand merkt:
>
> - Die Situation wird **am gefragten Tag** aufgelöst, nicht am heutigen. Wer
>   mittwochs später aufsteht als montags, verabredet sich für Mittwoch auf
>   neun und nicht auf sechs.
> - Die Ausnahme eines einzelnen Tages schlägt den Wochenplan: ein Mittwoch, an
>   dem er ausschläft, verschiebt das gemeinsame Frühstück mit.
> - Eine feste Uhrzeit bleibt eine feste Uhrzeit, unberührt vom Anker der
>   anderen Seite.
>
> Claude-Session: https://claude.ai/code/session_01BRjkfR9h9k5wrjpY1K3GZg

<details><summary>1 Datei · +108/−0</summary>

- `tests/Feature/AppointmentReplacementTest.php` +108/−0

</details>

### fix: eine Verabredung, eine Uhrzeit — und der Tag prüft sie ganz

`6f1cf7d` · **berbahc** · 13:02 Uhr

> Vier Löcher an derselben Stelle. `AppointmentFit` war eine zweite Rechnung
> neben `SlotConflict` und sah nur die eigenen Gewohnheiten; alles andere im Tag
> war für sie unsichtbar.
>
> **Der Stundenplan zählt mit.** Er tat es nur bei den Ausweichzeiten:
> `options()` legte kein Fenster in eine Vorlesung, aber die Prüfung darüber
> kannte den Stundenplan nicht. Wer um zehn Mathe hatte und für halb zehn gefragt
> wurde, sagte zu und hatte danach zwei Dinge zur selben Zeit. Gewohnheiten und
> Kurse kommen jetzt in einer Liste, nach Tageszeit sortiert, durch dieselbe
> Rechnung mit derselben Viertelstunde Luft.
>
> **Der Schlafrahmen auch.** Er ist die erste Grenze, vor allem anderen: Wer um
> drei Uhr nachts gefragt wurde, hatte dort nichts stehen — und sagte deshalb zu.
> Der Rahmen gilt überall sonst (`HabitDayShiftController::guard()`); die Zusage
> war der eine Weg, auf dem er nicht galt. `MinutesPerDay` lag dafür privat im
> Controller und wohnt jetzt bei `DayPlan`, wo die übrige Tagesarithmetik steht.
>
> Aus `movable` wird `AppointmentConflictKind`: Ein Wahrheitswert trug zwei Fälle,
> es sind drei, und der dritte hat gar keinen Block, gegen den er kollidiert. Die
> Karte sagt jetzt je nach Art, was zu tun ist — oder dass nichts zu tun ist. Ein
> Ausweg, den es nicht gibt, ist schlimmer als keiner.
>
> **Dieselbe Sache steht einmal im Tag.** Wer zum Frühstück zusagt und selbst
> Frühstück im Plan hat, frühstückt einmal. Das ist kein Konflikt, sondern ein
> Zusammenfallen: Die eigene Zeile bleibt stehen — sie ist täglich, an ihr hängen
> Ketten, sie zählt in die Serie —, bekommt den zweiten Kreis und zieht für
> diesen einen Tag auf die gemeinsame Uhrzeit. Weg fällt nur die zweite Zeile
> darunter, die ohnehin nur wiederholte, was zwei Zentimeter darüber steht.
>
> Erkannt wird das an der Vorlage aus dem Katalog, nicht am Titel: „Frühstücken"
> und „Frühstück" wären zwei Zeichenketten und dasselbe Essen. Verschoben wird
> über `HabitDayShift` — dieselbe Mechanik wie beim Platzmachen, deshalb stimmen
> Kalender, Raster und Kollisionsprüfung von selbst, ohne dass eine davon etwas
> von Verabredungen wissen müsste. Abgehakt wird die eigene Zeile; ein zweiter
> Haken an der Zusage wäre derselbe Morgen zweimal.
>
> **Und die Verabredung bekommt eine eigene Uhrzeit.** Sie lieh sich bisher den
> Anker der fragenden Gewohnheit — aber „nach dem Aufstehen" hat keine Uhrzeit,
> sondern eine Stelle im Tag, und die rechnet jeder aus seinem eigenen
> Schlafplan aus. Berkay steht um sieben auf, Aylin frühstückt um neun: Beide
> Kalender zeichneten korrekt ihren eigenen Tag, und in den beiden Tagen stand
> derselbe Morgen an zwei Stellen.
>
> `starts_at` hält die Stelle der fragenden Person beim Vorschlagen einmal als
> echte Uhrzeit fest. Festgenagelt und nicht jedes Mal neu gerechnet: Sonst
> wanderte eine längst zugesagte Verabredung mit, wenn die fragende Person später
> ihren Wecker verstellt — im Kalender der anderen, ohne dass sie davon erführe.
> Die Karte nennt sie jetzt auch („um 07:00" statt „nach dem Aufstehen"), denn
> den Anker las die gefragte Person als **ihr** Aufstehen.
>
> Daran hing das größte der vier Löcher: Ohne Uhrzeit gab `windowOf()` null
> zurück, und für eine Verabredung an einer Situation lief **gar keine** Prüfung.
> Wer um sieben eine Vorlesung hatte und für „nach dem Aufstehen" gefragt wurde,
> saß gleichzeitig in zwei Räumen. Jetzt hat jede Verabredung ein Fenster, und
> alle drei Prüfungen greifen darauf.
>
> Zwei Fälle mehr in der Überlappungs-Invariante — in die Vorlesung zusagen und
> in die Nacht zusagen —, und `AppointmentReplacementTest` hält das Zusammenfallen
> fest, samt der Gegenproben: Radfahren gegen Frühstück bleibt ein Konflikt, eine
> Mo–Fr-Gewohnheit wird samstags nicht ersetzt, und eine Zeile ohne Vorlage wird
> nicht geraten.
>
> Offen bleibt: zwei Zusagen auf derselben Minute, und `SlotConflict` kennt die
> Zusagen noch nicht.
>
> Claude-Session: https://claude.ai/code/session_01BRjkfR9h9k5wrjpY1K3GZg

<details><summary>20 Dateien · +1344/−83</summary>

- `app/Enums/AppointmentConflictKind.php` +28/−0
- `app/Http/Controllers/AppointmentController.php` +86/−4
- `app/Http/Controllers/CalendarController.php` +47/−12
- `app/Http/Controllers/DashboardController.php` +35/−7
- `app/Http/Controllers/HabitDayShiftController.php` +1/−4
- `app/Models/Appointment.php` +191/−5
- `app/Support/AppointmentFit.php` +200/−31
- `app/Support/DayPlan.php` +9/−0
- `database/migrations/2026_09_08_124708_add_starts_at_to_appointments_table.php` +40/−0
- `resources/js/components/appointment-request-notice.tsx` +42/−7
- `resources/js/components/habit-row.tsx` +4/−0
- `resources/js/types/friendship.ts` +26/−7
- `resources/js/types/habit.ts` +8/−0
- `tests/Feature/AppointmentCompletionTest.php` +4/−0
- `tests/Feature/AppointmentConflictTest.php` +186/−0
- `tests/Feature/AppointmentRepeatTest.php` +6/−1
- `tests/Feature/AppointmentReplacementTest.php` +373/−0
- `tests/Feature/AppointmentTest.php` +8/−4
- `tests/Feature/CalendarAppointmentTest.php` +1/−1
- `tests/Feature/NoOverlapInvariantTest.php` +49/−0

</details>

## 07.09.2026

### test: der Test sagt jetzt, worüber er spricht

`bfc3a6f` · **Silas2505** · 20:28 Uhr

> Die Übersichtskarte ist wieder die alte (643c071) — die Rate steht als Fußnote
> und die Karte nur an einem Tag, an dem etwas ansteht. Mein Test hieß aber
> weiter „survives a day with nothing due" und beschrieb damit einen Bildschirm,
> den es nicht mehr gibt. Grün war er trotzdem, denn er prüft die Props.
>
> Genau das steht jetzt dran: Die Rate misst dreißig Tage und wird auch an einem
> leeren Sonntag gerechnet — zu sehen ist sie dort nicht. Ein Test, der etwas
> Unwahres behauptet, ist schlechter als keiner, auch wenn er durchläuft.

<details><summary>1 Datei · +14/−6</summary>

- `tests/Feature/DashboardTest.php` +14/−6

</details>

### Merge branch 'main' of https://github.com/berbahc/align

`c92b243` · **Silas2505** · 20:23 Uhr

### fix: ein Kurs blockt die Zeit — auch der, der erst im Oktober beginnt

`a3f79f0` · **Silas2505** · 20:23 Uhr

> Drei Dinge an derselben Stelle: Ein Kurs rückt nicht, also muss die App das
> überall gleich behandeln.
>
> **Der dauerhafte Zug prüfte nur eine Woche weit.** `always()` nahm je
> Wochentag das nächste Vorkommen — den nächsten Mittwoch also, nicht den ersten
> im Semester. Ein Kurs, der mit der Vorlesungszeit beginnt, lag außerhalb des
> Blickfelds: Die Gewohnheit rutschte anstandslos auf zehn Uhr und stand ab
> Oktober neben der Vorlesung im Raster, als wäre dort Platz für beides.
> {@see SlotConflict::datesFor()} kannte die Antwort längst und nimmt je
> Wochentag zwei Daten; jetzt fragt der Zug sie auch.
>
> Und weil ein Kurs nicht rückt, trägt die Absage den Konflikttag mit: Im Sheet
> steht „Zum Mittwoch, 14. Oktober springen". Ohne ihn müsste man Wochen weit
> blättern.
>
> **Ein Kurs in einem Semester, das erst beginnt, ließ sich nicht anlegen.** Die
> Meldung nennt seit gestern den Tag, ab dem der Platz weg ist — über eine
> Closure, die `?Carbon` versprach. `DisplaceHabits::effectiveFrom()` gibt aber
> zweierlei zurück: `now()`, solange das Semester läuft, und
> `starts_on->startOfDay()`, wenn es erst beginnt — und das ist eine
> unveränderliche Instanz. Der Kurs war da längst geschrieben, nur die Meldung
> platzte; nach dem Neuladen stand er da, als wäre nichts gewesen. Die Tests
> trafen den Fall nie, weil ihre Semester alle schon liefen.
>
> **Und der Weg zum Konflikt führte zum Semesterbeginn.** `firstConflictDate()`
> suchte ab heute und zwei Wochen weit — ein Kurs im Oktober lag außerhalb, also
> fand sie nichts. Sie sucht jetzt ab dem Tag, an dem der Platz wegfällt, und im
> verdrängten Bereich steht bei jeder Gewohnheit ihr eigener Weg dorthin: „Joggen
> gehen" zum Montag, „Essen vorkochen" zum Donnerstag. Nicht der Semesterbeginn,
> der bei beiden derselbe wäre — liegt „Statistik" mittwochs, zeigte der Montag
> davor einen freien Tag und keine Ursache.
>
> Der Weg steht neben „Neue Zeiten vorschlagen", nicht statt dessen: sich
> vorschlagen lassen oder selbst hinlegen sind zwei gleich gute Wege dahin.

<details><summary>9 Dateien · +251/−27</summary>

- `app/Http/Controllers/CalendarController.php` +34/−14
- `app/Http/Controllers/CourseController.php` +8/−3
- `app/Http/Controllers/HabitDayShiftController.php` +19/−3
- `resources/js/components/shift-sheet.tsx` +44/−4
- `resources/js/pages/calendar-day.tsx` +19/−3
- `resources/js/pages/calendar.tsx` +23/−0
- `resources/js/types/semester.ts` +10/−0
- `tests/Feature/HabitDisplacementTest.php` +39/−0
- `tests/Feature/HabitShiftTest.php` +55/−0

</details>

### style: the sleep plan is called the sleep plan, everywhere

`b73560e` · **berbahc** · 19:26 Uhr

> The page announced itself as "Schlaf & Rhythmus" while the sidebar, the
> browser tab and the breadcrumb said "Schlaf", the save button said
> "Schlafplan speichern", and every reference elsewhere in the app — the
> frame markers in the day grid, the wake sheet, the carry list — called it
> the Schlafplan. One page under five names is four other pages.
>
> "Rhythmus" was also a promise the page does not keep. It measures
> nothing and shows no pattern over time; it sets a plan of seven weekdays
> with a wake time, a bedtime and an alarm. The word belongs to the strip
> on the habits page, where something actually is observed.
>
> The sentence under the title stays and carries what the heading used to
> hint at: "6,5 Stunden bis 9 Stunden Schlaf, je nach Tag".
>
> The card on the overview keeps the name "Dein Rahmen". It is not the
> plan but today's result of it, and that difference is worth a word.
>
> Claude-Session: https://claude.ai/code/session_01AFPTfMMvtARzH64GYD9yoj

<details><summary>2 Dateien · +20/−10</summary>

- `resources/js/components/app-sidebar.tsx` +2/−2
- `resources/js/pages/sleep.tsx` +18/−8

</details>

### fix: the frame is set in one place, and that place is the sleep plan

`12bed07` · **berbahc** · 19:26 Uhr

> The sun marker in the day grid was a button. It opened a sheet that moved
> the frame for that one day, while the moon marker beside it had always
> been a plain link to the sleep plan. Two edges of the same frame, two
> different behaviours, and two places where the frame could be changed.
>
> Both markers are links now, and they carry the day they sit on: tapping
> the edge on a Thursday opens the plan on Thursday instead of on Monday.
> `sleep.show` takes `?weekday=1…7` for that; anything outside the week
> counts as not asked, since a plan opened on a day that does not exist has
> no times to show.
>
> This leaves the single-day exception without an entry point. The sheet in
> wake-sheet.tsx was the only thing in the app that could write or drop a
> sleep_day_overrides row — the routes, the controller and the table stay,
> but nothing calls them, and the component is now unused. Removing that
> feature outright, or moving it into the sleep plan, is a separate
> decision and not made here.
>
> Claude-Session: https://claude.ai/code/session_01AFPTfMMvtARzH64GYD9yoj

<details><summary>5 Dateien · +107/−47</summary>

- `app/Http/Controllers/SleepScheduleController.php` +19/−0
- `resources/js/components/day-grid.tsx` +21/−27
- `resources/js/pages/calendar-day.tsx` +11/−19
- `resources/js/pages/sleep.tsx` +9/−1
- `tests/Feature/SleepScheduleTest.php` +47/−0

</details>

### revert: the overview goes back to the card it had

`a20f1ed` · **berbahc** · 19:01 Uhr

> Reverts the overview half of c29d698 and nothing else. The percentage is
> the head number again, the bar sits under it, and the consistency rate
> returns to its footnote line — "Letzte 30 Tage: 36 von 102 Mal erledigt".
> The card again appears only on a day that has something in it.
>
> The file is byte for byte the one from 98aa0ae. The controller was never
> touched by that commit, so the props are unchanged and nothing else on
> the page moves.
>
> The other four parts of c29d698 stay: one template one running habit, the
> tappable week strip, the way out of the sleep frame, and the line about
> hasTriggerOn().
>
> The test "the consistency rate survives a day with nothing due" stays
> green — it asserts on props, which still arrive. It no longer describes
> what is on screen, since the card is once more tied to the day.
>
> Claude-Session: https://claude.ai/code/session_01AFPTfMMvtARzH64GYD9yoj

<details><summary>1 Datei · +63/−115</summary>

- `resources/js/pages/dashboard.tsx` +63/−115

</details>

### fix: a dot means a habit, and it means that on both pages

`a945da4` · **berbahc** · 19:01 Uhr

> Two legends contradicted each other. In the calendar a dot is a habit —
> pale while open, filled once done. On the habits page a dot was the
> placeholder for "nothing was planned here". The same shape said "one
> habit" on one page and "none at all" on the other.
>
> The habits page gives the dot up, because the calendar cannot: there the
> dot carries the whole count, and a day is read from its row of them. Here
> it was only holding a slot.
>
> "Nicht vorgesehen" is now an empty dashed outline. That puts the three
> states on one ladder of a single form — filled with a check, outlined
> with a check, dashed and empty — and it keeps the tone the dot had, so
> nothing gets louder.
>
> One gap stays, deliberately: an open habit is a pale *filled* dot in the
> calendar and an outline on the habits page. Closing it would mean turning
> the open dot into a ring, and a ring already means "with someone" there.
> That mark would need a new form first.
>
> Claude-Session: https://claude.ai/code/session_01AFPTfMMvtARzH64GYD9yoj

<details><summary>1 Datei · +14/−10</summary>

- `resources/js/components/rhythm-strip.tsx` +14/−10

</details>

### fix: a day can only be reordered while it is today

`c1579cc` · **berbahc** · 19:01 Uhr

> The button hung on `canComplete`, which is the backdating window and
> reaches seven days back. So "Tag neu ordnen" stood above days that were
> already over.
>
> That is not a cosmetic mismatch. Taking over an order writes fixed times
> into the habits themselves, and those hold from then on. Ordered from
> last Monday it would have rearranged the coming week while changing
> nothing about the Monday it was asked for. Ordered from tomorrow it
> would do the same a night early.
>
> The rule now lives at both ends: the button asks `isToday`, and the
> server refuses any other date — 409 with a sentence for the suggestion,
> a validation error for taking it over. What the interface does not offer,
> the server does not accept.
>
> Two older tests in HabitSlotTest ordered the *next* Monday. Their subject
> is the overlap guard, not the date, so they pin today to a Monday instead
> of stepping into the future.
>
> Claude-Session: https://claude.ai/code/session_01AFPTfMMvtARzH64GYD9yoj

<details><summary>4 Dateien · +116/−5</summary>

- `app/Http/Controllers/DayOrderController.php` +25/−0
- `resources/js/pages/calendar-day.tsx` +9/−2
- `tests/Feature/DayOrderTest.php` +59/−0
- `tests/Feature/HabitSlotTest.php` +23/−3

</details>

### fix: give every block in the day its icon, not only the long ones

`bd781d8` · **berbahc** · 19:01 Uhr

> The icon tile hung on the same height threshold as the third text line.
> At 96 pixels per hour that meant 41 minutes: an hour of cycling had its
> mark, half an hour of dinner did not. And because the tile takes width,
> the title jumped 32 pixels left whenever it was missing — the left edge
> of a day read as ragged rather than as a column.
>
> The threshold was right for the third line, which genuinely does not fit
> in 44 pixels. The tile is not a line but a column: 24 pixels tall, and
> two rows of text never need the 36 that are there. It now stands in
> every block; the smallest step still waits for room.
>
> Claude-Session: https://claude.ai/code/session_01AFPTfMMvtARzH64GYD9yoj

<details><summary>1 Datei · +34/−28</summary>

- `resources/js/components/calendar-block.tsx` +34/−28

</details>

### feat: der Wochentag entscheidet mit — bei der Kette und bei der Uhrzeit

`fb6ebe2` · **Silas2505** · 16:45 Uhr

> Zwei Fragen, die dieselbe Stelle betreffen, und deshalb zusammen: Der Plan
> einer Gewohnheit war bisher ein Wert für alle ihre Tage.
>
> **Eine Kette darf seltener laufen als ihr Vorgänger.** „Immer wenn die andere
> läuft" war die einzige Möglichkeit — wer montags, mittwochs und freitags joggt,
> will danach aber vielleicht nur mittwochs dehnen. Die Auswahl zeigt jetzt die
> Tage des Vorgängers; die übrigen sind gesperrt und benannt, denn an einem Tag
> ohne Joggen gäbe es kein „danach".
>
> `activeWeekdays()` verschneidet dafür die eigenen Tage mit denen des
> Vorgängers: Die eigene Wahl schneidet, sie erweitert nie. Bleibt die Spalte
> leer, folgt die Kette ihrem Vorgänger — auch dann noch, wenn der seine Tage
> später ändert. Der Server prüft dieselbe Grenze; ein Formular ist kein Beweis.
>
> **Eine Uhrzeit je Wochentag.** Wer dienstags um acht Vorlesung hat und
> donnerstags um zehn, lernt nicht an beiden Tagen zur selben Zeit nach. Neu ist
> `scheduled_times` als Abbildung `Tag => "HH:MM"`, **leer solange alle Tage
> dieselbe Zeit haben** — der Regelfall bleibt eine Zahl statt sieben, und jede
> Zeile aus der Zeit davor bedeutet unverändert dasselbe. Sieben gleiche Einträge
> räumt das Formular selbst wieder zu einer Zahl zusammen.
>
> Der Schalter heißt „Jeden Tag zur selben Uhrzeit" und steht an; umgelegt bekommt
> jeder gewählte Tag seine eigene Zeile. Dieselbe Sprache wie im Schlafplan, wo
> dieselbe Frage schon beantwortet war.
>
> `resolveStart()` liest die Zeit **dieses** Tages, damit Kalender, Raster,
> Reihenfolge und Erinnerung von selbst stimmen. Kollision und Schlafrahmen
> fragen jetzt je Tag — im Browser wie auf dem Server —, und die belegten Fenster
> liefern eine Gruppe je Uhrzeit. Sind alle Zeiten gleich, ist es überall wieder
> genau eine Prüfung wie vorher.
>
> Wo die App selbst eine Uhrzeit setzt — Tagesordnung, Ziehen mit „immer", neuer
> Platz, verschobener Schlafrahmen —, fallen die Abweichungen zusammen: Diese Wege
> suchen einen Platz, an dem die Gewohnheit an *allen* ihren Tagen liegt, und
> finden deshalb genau einen. Die alten Zeiten stehen zu lassen hieße, den
> gefundenen Platz gleich wieder zu überschreiben.

<details><summary>20 Dateien · +811/−56</summary>

- `app/Actions/CarryHabitsWithFrame.php` +11/−1
- `app/Actions/ReleaseChainedHabits.php` +3/−0
- `app/Http/Controllers/DayOrderController.php` +4/−0
- `app/Http/Controllers/HabitController.php` +55/−8
- `app/Http/Controllers/HabitDayShiftController.php` +2/−0
- `app/Http/Controllers/NewPlaceController.php` +4/−0
- `app/Http/Middleware/HandleInertiaRequests.php` +4/−1
- `app/Http/Requests/AdjustHabitRequest.php` +4/−0
- `app/Http/Requests/Concerns/ChecksSleepWindow.php` +9/−1
- `app/Http/Requests/HabitFormRequest.php` +142/−17
- `app/Models/Habit.php` +103/−9
- `database/migrations/2026_09_07_170000_add_scheduled_times_to_habits_table.php` +35/−0
- `resources/js/components/habit-wizard.tsx` +15/−5
- `resources/js/components/schedule-picker.tsx` +168/−14
- `resources/js/lib/sleep.ts` +21/−0
- `resources/js/lib/slots.ts` +30/−0
- `resources/js/pages/habits/edit.tsx` +10/−0
- `resources/js/types/habit.ts` +10/−0
- `tests/Feature/HabitChainTest.php` +78/−0
- `tests/Feature/HabitScheduleTest.php` +103/−0

</details>

### feat: der Weg zur Tagesordnung steht über dem Raster, nicht darunter

`fd24b7a` · **Silas2505** · 16:44 Uhr

> Er betrifft den ganzen Tag und nicht eine Zeile — als leiser Link am Seitenende
> war er der letzte Satz einer langen Spalte. Wer seinen Tag ordnen will, will
> das, bevor er ihn durchgescrollt hat.
>
> Unterlegt, aber nicht gesättigt: Die eine kräftige Fläche dieser Seite ist das
> Raster selbst (§5.4). Der Knopf trägt denselben Ton wie der Vorschlagskasten
> darunter — dort spricht dieselbe Stimme, und sie soll auch so aussehen.

<details><summary>1 Datei · +28/−19</summary>

- `resources/js/pages/calendar-day.tsx` +28/−19

</details>

### test: die Fabrik würfelt ihre Vorlage nicht mehr

`a0ca23e` · **Silas2505** · 15:53 Uhr

> `fake()->randomElement(HabitTemplate::cases())` — die Vorlage einer Gewohnheit
> war Zufall. Solange zwei Gewohnheiten dieselbe Vorlage haben durften, fiel das
> nicht auf; seit eine Vorlage nur noch eine laufende Gewohnheit trägt, hieß es
> ein Test, der mal grün und mal rot ist: Traf der Würfel die Vorlage, die der
> Test gleich selbst anlegt, wies das Formular sie ab. Bei neunzehn Vorlagen ist
> das jeder zwanzigste Lauf.
>
> Die Vorlage läuft jetzt reihum, mit demselben Zähler wie der Moment und
> derselben Rücksetzung je Test — die ersten neunzehn Gewohnheiten eines Tests
> bekommen verschiedene Vorlagen, und welche es sind, steht fest. Ein Test, der
> von einem Würfel abhängt, prüft nichts.
>
> `resetSituations()` heißt deshalb `resetRotation()`: Sie setzt jetzt beides
> zurück, und ein Name, der nur die Hälfte nennt, führt beim nächsten Mal in
> dieselbe Suche.
>
> Dreimal hintereinander 689 grün.

<details><summary>2 Dateien · +19/−8</summary>

- `database/factories/HabitFactory.php` +18/−7
- `tests/Pest.php` +1/−1

</details>

### test: gib der Fixture eine echte Vorlage, nicht nur einen Titel

`663f8f1` · **Silas2505** · 15:49 Uhr

> Die Gewohnheit hieß „Essen vorkochen" und war innen „Lesen": Der Test
> überschrieb den Titel, ließ aber die Vorlage stehen, die die Factory reihum
> vergibt — und legte danach genau diese Vorlage noch einmal an.
>
> Aufgefallen ist das erst, seit eine Vorlage nur noch eine laufende Gewohnheit
> trägt. Der vierte Fall dieser Art; die Regel macht sichtbar, wo Titel und
> Vorlage in den Testdaten auseinanderliefen.

<details><summary>1 Datei · +6/−2</summary>

- `tests/Feature/HabitSlotTest.php` +6/−2

</details>

### Merge branch 'main' of https://github.com/berbahc/align

`0c8edbc` · **Silas2505** · 15:39 Uhr

> # Conflicts:
> #	resources/js/components/habit-board.tsx
> #	resources/js/pages/habits/index.tsx
> #	tests/Feature/HabitStreakTest.php

### feat: die Konsistenzrate zurück auf die Übersicht, plus A, B und C

`37bc6a2` · **Silas2505** · 15:37 Uhr

> **Übersicht.** Die Konsistenzrate war nicht weg, aber sie stand als
> 11-px-Fußnote neben einer großen „0 %", und die ganze Karte verschwand an
> Tagen ohne Gewohnheit. Jetzt zwei Zahlen mit je eigenem Zeitraum: oben der
> Tag als große Zahl („0 von 2 Gewohnheiten erledigt"), darunter, durch eine
> Haarlinie abgesetzt, „Konsistenz · letzte 30 Tage: 35 % — 36 von 102
> Gelegenheiten genutzt".
>
> Gezählt wird oben, was erledigt ist, nicht ein Prozentsatz davon: „0 %"
> stand jeden Morgen als größte Zahl auf dem Bildschirm und las sich wie ein
> Rückstand, obwohl der Tag erst anfängt. Ein Prozentwert gehört zur
> Konsistenz — dort misst er dreißig Tage und sagt wirklich etwas.
>
> **A — eine Vorlage, eine laufende Gewohnheit.** Zwei „Joggen gehen" sind im
> Kalender nicht auseinanderzuhalten, und sie ließen sich sogar aneinander
> hängen. Der Katalog weist eine laufende Vorlage jetzt als „läuft schon" aus,
> `StoreHabitRequest` weist sie ab — beides aus derselben Quelle
> (`Habit::takenTemplatesFor()`), sonst böte der Assistent etwas an, das beim
> Speichern scheitert. Der Weg „Übernehmen" geht durch dieselbe Prüfung. Eine
> beendete Gewohnheit gibt ihre Vorlage wieder frei.
>
> **B — der Wochenstreifen ist antippbar.** Wer gestern vergessen hat, sieht
> die Lücke dort und musste bisher über Kalender, Tag und Haken gehen. Jeder
> Tag, an dem die Gewohnheit anstand, ist ein Knopf; Tage ohne Vorsehung
> bleiben stumm. Aus `role="img"` wird eine Gruppe: Was sich bedienen lässt,
> muss sich ansagen lassen.
>
> **C — der Schlafrahmen hat einen Ausweg.** Der Hinweis endet mit „Samstag
> und Sonntag abwählen" — ein Tipp, und der Weg ist frei. Nur angeboten, wenn
> danach ein Tag übrig bleibt.
>
> Dazu die eine Zeile aus dem letzten Pull: Ohne geladenen Nutzer antwortet
> `Habit::hasTriggerOn()` mit „ja", und der Streifen zeigte für „nach der
> Vorlesung" sieben offene Tage in einer Woche ganz ohne Vorlesung — während
> dieselbe Gewohnheit auf der Übersicht zu Recht nicht anstand.
>
> In einem Commit, weil `HabitController` an zwei der Teile hängt: getrennt
> wäre der Zwischenstand nicht lauffähig.

<details><summary>19 Dateien · +595/−106</summary>

- `app/Enums/HabitCategory.php` +10/−2
- `app/Http/Controllers/HabitController.php` +9/−1
- `app/Http/Controllers/OnboardingController.php` +1/−1
- `app/Http/Requests/StoreHabitRequest.php` +35/−0
- `app/Models/Habit.php` +26/−0
- `resources/js/components/habit-board.tsx` +93/−26
- `resources/js/components/habit-wizard.tsx` +20/−0
- `resources/js/components/rhythm-strip.tsx` +7/−0
- `resources/js/components/schedule-picker.tsx` +50/−1
- `resources/js/lib/sleep.ts` +19/−0
- `resources/js/pages/dashboard.tsx` +115/−63
- `resources/js/pages/habits/index.tsx` +33/−1
- `resources/js/types/habit.ts` +5/−0
- `tests/Feature/DashboardTest.php` +34/−0
- `tests/Feature/HabitAdoptionTest.php` +7/−3
- `tests/Feature/HabitMeasureTest.php` +66/−0
- `tests/Feature/HabitSituationTest.php` +6/−2
- `tests/Feature/HabitStreakTest.php` +40/−0
- `tests/Feature/OnboardingTest.php` +19/−6

</details>

### feat: when the frame moves, the day moves with it

`0d3db4e` · **berbahc** · 14:23 Uhr

> Situational habits have always followed the sleep frame. Fixed times never
> did: they were checked against it once, when they were created, and never
> again. Pull your wake time from 07:00 to 10:00 and breakfast stayed at 08:00
> — sitting in the night band, refused for a new habit, kept for an old one.
> Two standards for the same frame.
>
> A fixed time that falls out now moves by the same amount the edge did. The
> distance to waking is preserved: breakfast was an hour after the alarm and
> stays an hour after it. What already fits inside stays put — this is a
> nudge, not a replan. Chains ride along with their anchor.
>
> The frame itself now knows single days. „I got up late today" is not a
> change of rhythm but an exception to it, so it hangs on a date
> (sleep_day_overrides) and expires with it. Situational habits follow that
> exception for free — reading the wake time of the date is the whole
> mechanism. Fixed ones get a day shift, marked with its origin so taking the
> day back drops what the frame moved and keeps what you moved by hand.
>
> Nothing moves unannounced. Both entry points — the weekly plan and the sun
> marker in the grid — show what would happen first. The preview saves,
> computes and rolls back, so it cannot show anything other than what follows.
> Habits that fit nowhere are named with the reason instead of being hidden,
> and the plan is saved either way: it describes when someone sleeps, and the
> app does not argue with that.
>
> Claude-Session: https://claude.ai/code/session_01LNfHufoQ9kWD27MhhTfEsJ

<details><summary>24 Dateien · +2334/−69</summary>

- `app/Actions/CarryHabitsWithFrame.php` +438/−0
- `app/Enums/ShiftOrigin.php` +21/−0
- `app/Http/Controllers/CalendarController.php` +6/−1
- `app/Http/Controllers/DashboardController.php` +2/−3
- `app/Http/Controllers/DayOrderController.php` +1/−1
- `app/Http/Controllers/HabitDayShiftController.php` +2/−2
- `app/Http/Controllers/SleepDayOverrideController.php` +226/−0
- `app/Http/Controllers/SleepScheduleController.php` +163/−22
- `app/Http/Middleware/HandleInertiaRequests.php` +7/−7
- `app/Models/HabitDayShift.php` +4/−1
- `app/Models/SleepDayOverride.php` +61/−0
- `app/Models/User.php` +70/−0
- `app/Support/SlotConflict.php` +3/−3
- `database/migrations/2026_09_07_130735_create_sleep_day_overrides_table.php` +53/−0
- `database/migrations/2026_09_07_131441_add_origin_to_habit_day_shifts_table.php` +39/−0
- `resources/js/components/day-grid.tsx` +75/−15
- `resources/js/components/frame-carry-list.tsx` +88/−0
- `resources/js/components/wake-sheet.tsx` +195/−0
- `resources/js/hooks/use-frame-carry.ts` +115/−0
- `resources/js/pages/calendar-day.tsx` +24/−0
- `resources/js/pages/sleep.tsx` +127/−14
- `routes/web.php` +15/−0
- `tests/Feature/SleepDayOverrideTest.php` +357/−0
- `tests/Feature/SleepScheduleTest.php` +242/−0

</details>

### fix: let the day's edges hold their habits, morning and night

`a2e69dd` · **berbahc** · 14:23 Uhr

> „Vor dem Schlafengehen" was placed an hour before bedtime whenever that
> hour happened to be free. The window it may slide inside was searched from
> its start, and for the evening the start is the wrong end: the block should
> end at bedtime and only move earlier when something is in the way.
>
> The docblock had said as much for a while; the placement never did.
>
> A situation now carries the edge it clings to. „Nach dem Aufstehen" begins
> when you get up and gives way backwards; „vor dem Schlafengehen" ends when
> you go to bed and gives way forwards. DayPlan searches from that edge —
> forwards or backwards — and falls back in the same direction.
>
> Claude-Session: https://claude.ai/code/session_01LNfHufoQ9kWD27MhhTfEsJ

<details><summary>3 Dateien · +191/−21</summary>

- `app/Models/Habit.php` +21/−11
- `app/Support/DayPlan.php` +72/−10
- `tests/Feature/SleepScheduleTest.php` +98/−0

</details>

### test: nagle den Tag fest, an dem der Konflikt gemeldet wird

`76b0206` · **berbahc** · 03:23 Uhr

> Zwei Tests fielen jeden Montag um und liefen an den anderen sechs Tagen
> durch — nicht wegen der Logik, die sie prüfen, sondern wegen des echten
> Kalenders.
>
> Die Meldung nennt den Tag, an dem etwas im Weg liegt:
> `SlotConflict::weekdayLabel()` schreibt „heute", wenn der Konflikt auf den
> heutigen Tag fällt, und sonst den Wochentag („montags"). Beide Tests legen
> ihre Gewohnheit auf Montag und erwarten wörtlich „montags" — an einem Montag
> schreibt die App aber korrekt „heute", und der Vergleich scheitert. Die App
> war nie falsch; der Test prüfte unbeabsichtigt mit, welcher Wochentag gerade
> ist.
>
> Beide bekommen jetzt einen festen Mittwoch, wie die Tests ringsum. Bei
> `HabitShiftTest` gehört der Tag sogar zur Aussage selbst: „Heute ist frei,
> aber montags liegt dort etwas" stimmt nur, solange heute kein Montag ist.
>
> Damit ist die Suite wieder vollständig grün — 661 Tests.
>
> Claude-Session: https://claude.ai/code/session_01RMjt7cpbChU6ndsMvLr7vC

<details><summary>2 Dateien · +13/−0</summary>

- `tests/Feature/HabitShiftTest.php` +6/−0
- `tests/Feature/HabitSlotTest.php` +7/−0

</details>

### feat: give the explanation a material, and the streaks a place of their own

`bed7373` · **berbahc** · 03:17 Uhr

> **Die Bilanzspalte sagt jetzt einen Satz, der hineinpasst.** Dort stand
> „1 von 2 Tagen seit dem Start" — vier Wörter, die nicht umbrechen durften, in
> einer 112 Pixel schmalen Spalte; sie liefen nach links über die Tagesspalten
> und legten sich über den heutigen Tag. Der Zusatz war dabei überflüssig: Der
> Nenner **ist** die Zahl der Gelegenheiten, und der andere Fall nannte sein
> Fenster ohnehin nie. „Angefangen am 06.09.2026" ist zu „Seit 06.09.2026"
> geworden — das Datum sagt selbst, dass etwas angefangen hat.
>
> Der Kasten dahinter redete falsches Deutsch, sobald es genau eine Gelegenheit
> gab: „stand sie an **1** Tagen an. An **1** davon hast du sie erledigt", und
> bei null „An **0** davon". Jetzt wird gebeugt, und bei einem einzigen Tag
> erklärt der Kasten stattdessen, warum draußen ein Datum steht. Wo es nichts zu
> erklären gibt, bleibt der Platz des ⓘ trotzdem stehen: Sonst endete der Text
> solcher Zeilen 24 Pixel weiter rechts als der darüber.
>
> **Zwei Materialstufen statt gestrichelter Konturen.**
>
> `glass` ist die Ebene über dem Blatt und trägt die zwei Erklärkästen. Sandig
> und nicht weiß, weil ein fast weißes Glas auf der weißen Blattkarte keine
> Ebene ist (Apple §12: „never stack a light translucent surface on another"),
> und nicht dunkel, weil das auf dieser Seite ein Loch wäre. Fließtext 8,1:1,
> Überschrift 14,6:1; im Dunkeln 7,6:1 und 9,9:1. Das Band des heutigen Tages
> scheint durch, deshalb bleibt es Material und wird keine Box.
>
> Verworfen wurde davor beides: helles Glas ohne getönten Grund (Weiß auf Weiß)
> und dunkles Glas mit cremefarbener Schrift (auf dieser Seite zu schwer). Auch
> die getönte Zeile unter dem offenen Kasten ist wieder weg — mit ihr lagen vier
> Beigetöne übereinander.
>
> `hollow` ist ihr Gegenstück: eine Vertiefung mit Innenschatten, Lichtkante und
> gestricheltem Strich. Sie liegt auf allem, was auf eine Antwort wartet — dem
> offenen Haken in Zeile, Kalender- und Terminblock, dem Platzhalterkreis, den
> zwei Anfragen, dem noch nicht zugesagten Termin, der gefestigten Gewohnheit im
> Archiv, „Eigener Schritt" und dem KI-Vorschlag. Gestrichelt heißt weiterhin
> „noch nicht entschieden"; neu ist nur, dass es Material hat statt einer
> gezeichneten Linie.
>
> **Bis zu drei laufende Serien statt einer.** Die Karte war vollflächig
> `primary`, und Designsprache §5.4 lässt genau eine solche Fläche zu: „ihre
> Wirkung hängt davon ab, dass sie allein bleibt." Genau daran wäre die zweite
> gescheitert. Als Glas liegen sie als eine Ebene über der Übersicht, und die
> eine gesättigte Fläche des Systems ist wieder frei. Sortiert wird nach
> `position` und nicht nach Länge: Sonst stünde dieselbe Gewohnheit heute vorn
> und morgen hinten, nur weil eine andere einen Tag aufgeholt hat — und eine
> Rangliste wäre der Vergleich, den `progress-tracking.md` ausschließt.
>
> Auf der Gewohnheiten-Seite ist die Serienzeile dafür weggefallen. Sie stand
> klein unter der Bilanz und noch einmal groß auf der Übersicht.
>
> **Und jede Karte lässt sich wegnehmen.** Ein blasses × oben rechts, 44 Pixel
> Trefferfläche, 14 Pixel Zeichen. Weggenommen wird die Anzeige, nicht der
> Fortschritt: `streak_hidden_at` je Gewohnheit, ein Zeitstempel wie
> `committed_at` und `displaced_at`, die Serie läuft und zählt weiter.
> `progress-tracking.md` verlangt „einen ehrlichen, nicht strafenden Kontext",
> und dazu gehört, ihn wegklicken zu dürfen. Der Weg zurück steht im ⋯-Menü der
> Gewohnheit und erscheint nur dort, wo etwas ausgeblendet ist — auf der Karte
> kann er nicht stehen, weil es die Karte dann nicht mehr gibt.
>
> Vier neue Tests: die Reihenfolge der Serien, die Kappung bei drei, das
> Ausblenden mit dem Zurückholen und der Zugriffsschutz für fremde Gewohnheiten.
>
> Claude-Session: https://claude.ai/code/session_01RMjt7cpbChU6ndsMvLr7vC

<details><summary>23 Dateien · +744/−141</summary>

- `app/Http/Controllers/DashboardController.php` +37/−16
- `app/Http/Controllers/HabitController.php` +3/−0
- `app/Http/Controllers/HabitStreakCardController.php` +49/−0
- `app/Models/Habit.php` +2/−0
- `database/migrations/2026_09_07_031104_add_streak_hidden_at_to_habits_table.php` +39/−0
- `resources/css/app.css` +196/−0
- `resources/js/components/appointment-block.tsx` +1/−1
- `resources/js/components/appointment-request-notice.tsx` +3/−1
- `resources/js/components/calendar-block.tsx` +4/−2
- `resources/js/components/friend-request-notice.tsx` +6/−4
- `resources/js/components/graduated-habit-row.tsx` +3/−1
- `resources/js/components/habit-board.tsx` +180/−67
- `resources/js/components/habit-limit-note.tsx` +7/−3
- `resources/js/components/habit-row.tsx` +1/−1
- `resources/js/components/habit-wizard.tsx` +1/−1
- `resources/js/components/person-circle.tsx` +1/−1
- `resources/js/components/streak-card.tsx` +70/−27
- `resources/js/components/upcoming-appointments.tsx` +5/−2
- `resources/js/pages/dashboard.tsx` +8/−6
- `resources/js/pages/habits/index.tsx` +12/−0
- `resources/js/types/habit.ts` +12/−0
- `routes/web.php` +8/−0
- `tests/Feature/HabitStreakTest.php` +96/−8

</details>

## 06.09.2026

### style: make the day a check, filled when done and hollow when open

`03b4363` · **berbahc** · 22:20 Uhr

> Zwei verschieden helle Quadrate waren eine Legende weit von ihrer Bedeutung
> entfernt: Man musste lernen, dass dunkel „erledigt" heißt. Der Haken sagt es
> von selbst.
>
> Und die zwei Zustände unterscheiden sich jetzt in der **Form**, nicht nur im
> Ton — gefüllt gegen Kontur bleibt auch ohne Farbwahrnehmung lesbar. Der
> offene Tag trägt denselben Haken, nur ungefüllt: Er markiert die Stelle, an
> die er gehört, und macht keinen Vorwurf daraus (§1.4 — kein Alarm, kein
> Kreuz, kein Rot). Der nicht vorgesehene Tag bleibt der Punkt.
>
> **Der offene Haken ist nicht `sand`.** Das war der erste Griff, weil der Ton
> vorher die Füllung trug — als Strich reicht er nicht. Eine Fläche liest sich
> über ihre Helligkeit, eine Linie über ihren Kontrast, und 1,55:1 ist für eine
> Linie zu wenig. Oliv bei 75 % erreicht 3,8:1 auf der Karte und 3,1:1 im Band
> des heutigen Tages — die 3:1, die WCAG 1.4.11 für ein bedeutungstragendes
> Zeichen verlangt. Die Kante bleibt bei 35 %: Der Haken trägt die Aussage, der
> Kasten nur seinen Platz.
>
> Derselbe Farbton wie „erledigt" und trotzdem keine Verwechslung, weil der
> Unterschied in der Form liegt. §1.2 bleibt gewahrt: gesättigt **und flächig**
> ist weiterhin nur das Erledigte.
>
> Verworfen wurden dabei drei Stufen, nebeneinander gerendert und verglichen:
> Oliv bei 55 % (im Band wieder zu blass), Gold (kollidiert mit dem
> Vergleichswert der Diagramme, §10) und eine 1,5px-Kante mit `olive-mid` (die
> dicke Kante macht den Kasten wichtiger als den Haken).
>
> **Die Marke wächst auf dem Telefon von 14 auf 16 Pixel**, die Tagesspalte von
> 18 auf 20. Für eine Fläche reichten 14, für eine Kontur nicht — sie verliert
> beim Verkleinern mehr. Die vierzehn Pixel gehen von der Namensspalte ab; kein
> Überlauf auf 320, 360, 375 und 414.
>
> Die Legende zieht von selbst mit: Sie zeichnet dieselben Marken aus derselben
> Quelle, damit sie nie etwas anderes erklärt als das, was dasteht.
>
> Claude-Session: https://claude.ai/code/session_01QgraEkFyA7sDVbaXMPtG6E

<details><summary>2 Dateien · +42/−8</summary>

- `resources/js/components/habit-board.tsx` +4/−2
- `resources/js/components/rhythm-strip.tsx` +38/−6

</details>

### fix: count only the days that were opportunities, on both sides

`84cc43e` · **berbahc** · 19:56 Uhr

> Die Konsistenz rechnete ihre zwei Zahlen unterschiedlich. Der Nenner zählte
> die Gelegenheiten nach dem **heutigen** Plan, der Zähler jeden Haken im
> Fenster — egal, an welchem Wochentag er gesetzt wurde.
>
> Solange niemand seinen Plan änderte, fiel das nicht auf. Danach:
>
>     täglich, 20 Tage am Stück abgehakt   →  20 von 30
>     danach auf Sa+So umgestellt          →  20 von  9
>
> Eine Zahl, die größer ist als ihre eigene Bezugsgröße, und eine Konsistenz
> von 222 %.
>
> **Die Regel ist nicht neu, nur ihre zweite Hälfte.**
> `HabitCompletionController::completionDate()` weist ein Nachtragen an einem
> nicht vorgesehenen Tag längst ab, wörtlich weil „eine Erfüllung dort sie
> über 100 % treiben" würde. Sie galt beim Schreiben und nicht beim Lesen, und
> eine Planänderung reichte, um sie auszuhebeln.
>
> `Habit::consistencyDone()` legt jetzt dieselbe Bedingung an wie der Nenner:
> gezählt wird nur, was auf einen vorgesehenen Wochentag fällt.
> `isScheduledOn()` und nicht `isDueOn()` — der Nenner fragt allein nach dem
> Wochentag, prüfte der Zähler zusätzlich Auslöser und Vermerk, entstünde
> dieselbe Schieflage nur andersherum.
>
> **Beide Seiten waren betroffen, auf verschiedenen Wegen.** Die Gewohnheiten-
> Seite über `Habit::consistency()` mit einer eigenen `count()`-Abfrage, die
> Übersicht über ein `withCount` in der Hauptabfrage — dort stand es als
> „20 von 9 Mal erledigt". Beide holen den Zähler jetzt aus derselben Methode.
>
> Eine Abfrage weniger je Gewohnheit fällt dabei ab: `consistencyDone()` nimmt
> die geladene `completionDates`-Beziehung, wenn sie da ist, und beide Seiten
> laden sie ohnehin für die Serie. Der Test, der die Abfragezahl der Übersicht
> deckelt, bleibt grün.
>
> Was aus dem Zähler fällt, ist kein Verlust: Ein Montag zählt nicht mehr mit,
> weil er nach dem heutigen Plan keine Gelegenheit mehr ist — genau wie er im
> Nenner keine mehr ist. Gelöscht wird nichts, die alten Erfüllungen bleiben
> stehen und zählen nur in dieser einen Rechnung nicht mehr.
>
> Zwei Tests halten den Fall fest, je einer pro Seite. Die Zahlen stehen
> ausgeschrieben statt nachgerechnet — sonst machte der Test denselben Fehler
> wie der Code, falls er einen hat.
>
> Claude-Session: https://claude.ai/code/session_01QgraEkFyA7sDVbaXMPtG6E

<details><summary>4 Dateien · +155/−16</summary>

- `app/Http/Controllers/DashboardController.php` +12/−11
- `app/Models/Habit.php` +48/−5
- `tests/Feature/DashboardTest.php` +42/−0
- `tests/Feature/HabitStreakTest.php` +53/−0

</details>

### feat: lay the habits on one sheet instead of stacking five cards

`e80afde` · **berbahc** · 19:56 Uhr

> Der Tab war ein Stapel. Jede Gewohnheit hatte ihre eigene weiße Karte, und
> in jeder Karte stand derselbe Streifen mit eigenen Tagesbuchstaben darunter.
> Vier Karten hießen vier Achsen, viermal „Mo Di Mi Do Fr Sa So" und viermal
> „Angefangen am 06.09.2026" — viermal dasselbe Gerüst mit anderem Inhalt.
> Was die Seite zeigte, war ihr eigener Aufbau.
>
> **Ein Verlauf ist Gewohnheit mal Tag, also zweidimensional.** Dieselbe
> Beobachtung, aus der `SleepWeek` entstanden ist. Die sieben Tage stehen
> jetzt einmal oben als Achse, und alle Gewohnheiten hängen darunter an
> denselben Spalten. Getrennt wird durch Haarlinien statt durch Abstände.
> Nichts kommt hinzu und nichts fällt weg; es steht nur einmal statt fünfmal
> da, und das Blatt ist dabei deutlich niedriger als der Stapel.
>
> **Der heutige Tag ist eine Spalte, keine Beschriftung.** Er läuft als Band
> von der Achse bis zur letzten Zeile durch — auch durch die Überschrift eines
> Abschnitts, die dafür dieselben leeren Kästen mitführt. Ohne das zerfiele
> die Spalte in Kapseln, und eine Kapsel neben einer Kapsel sieht aus wie zwei
> Sachen und nicht wie ein Tag. `track` und nicht `accent`: Auf `accent` läge
> die offene Marke nur noch ΔL* 4 über ihrem Grund und verschwände genau in
> der Spalte, auf die man schaut.
>
> **Die Seite grenzt sich gegen die Übersicht ab.** `HabitController::index()`
> sagt, worum es hier geht: „um die Gewohnheit an sich, nicht um den heutigen
> Tag". Die Seite sagte „heute" trotzdem dreimal — in der Zählung, in der
> Gliederung, im Band. Zwei davon sind weg:
>
> - „Steht heute an" / „Steht später an" gliedern nicht mehr. Die Sortierung
>   bleibt unverändert, sie wird nur nicht mehr angesagt. Die Auskunft geht
>   nicht verloren, sie steht genauer in der Spalte: gefülltes Kästchen heißt,
>   sie steht heute an, ein Punkt heißt, heute nicht.
> - Der nächste Termin führt die Zeile nicht mehr an. „morgen · 19:00" ist der
>   Blick auf die nächsten Stunden und gehört der Übersicht; hier zählt der
>   Plan, und den nennt dieselbe Zeile ohnehin.
> - Der Kopf sagt „4 aktive Gewohnheiten" statt „4 aktiv · 3 stehen heute an".
>   An der Grenze weiter „5 von 5 aktiv" — das gibt es sonst nirgends.
>
> „Braucht einen neuen Platz" behält seinen Titel: Der Abschnitt sagt nichts
> über heute, sondern dass eine Entscheidung offen ist. Sein Hinweis läuft
> über die ganze Breite, weil er in der Namensspalte auf dem Telefon als
> fünfzeiliger Turm neben leeren Kästen stünde.
>
> **Die Konsistenz wird eine Spalte** statt einer vierten Zeile in jeder
> Karte. Ohne Kopf darüber: Dort stand „30 Tage", und das stimmte nur für
> einen ihrer drei Zustände — „12 von 14 Tagen" passt, ein Anfangsdatum ist
> kein Fenster, und eine Serie hört nicht nach dreißig Tagen auf. Beschriftet
> bleibt, was ohne Wort nicht lesbar wäre: sieben Kästchen.
>
> **Auf dem Telefon** bleiben der Namensspalte rund 140 Pixel. Der Titel
> bricht dort um, statt abgeschnitten zu werden, die Bilanz rutscht unter den
> Namen — und zwar innerhalb der Zeile, damit die Spalte nicht zwischen zwei
> Zeilen abreißt. Symbol und Dauer fallen unter `sm` weg; sie sind der Preis
> dafür, dass sieben Tage und ein Titel beide lesbar bleiben. Geprüft auf 375,
> 768 und 1280, hell und dunkel.
>
> „Beendet" und „Erinnerungen" stehen unten nebeneinander statt untereinander:
> Als zwei weitere Blöcke im Stapel bekämen sie dasselbe Gewicht wie das Blatt
> darüber, obwohl man sie selten braucht. Die Seite ist von `max-w-3xl` auf
> `max-w-4xl` gegangen — auf 1280 lag sonst eine Handbreit Leere zwischen dem
> Namen und seinen Marken, und die Zeile fiel auseinander.
>
> `RhythmStrip` fällt weg, `managed-habit-row.tsx` auch — beides ging im Blatt
> auf. Die Marken bleiben in `rhythm-strip.tsx`, weil die Legende aus derselben
> Quelle kommen muss wie das Erklärte. Sie steht jetzt unter dem Blatt statt
> darüber: Über der Liste war sie das Erste, was man las — drei Wörter über
> Farbtönen, bevor überhaupt etwas zu sehen war, das sie erklären.
>
> Ein neuer Test nagelt fest, worauf die gemeinsame Achse baut: Jede
> Gewohnheit liefert dieselben sieben Tage in derselben Reihenfolge, auch die
> heute angelegte, deren Tage davor `scheduled: false` sind statt abwesend.
> Genau daran ist ein Testdatensatz beim Bauen aufgelaufen — eine Zeile mit
> sechs Einträgen schob alle Marken um eine Spalte, und das Blatt behauptete
> Tage, die es nie gab.
>
> Claude-Session: https://claude.ai/code/session_01QgraEkFyA7sDVbaXMPtG6E

<details><summary>6 Dateien · +955/−675</summary>

- `resources/js/components/habit-board.tsx` +672/−0
- `resources/js/components/managed-habit-row.tsx` +0/−360
- `resources/js/components/rhythm-strip.tsx` +38/−120
- `resources/js/pages/habits/index.tsx` +180/−194
- `resources/js/types/habit.ts` +15/−1
- `tests/Feature/HabitStreakTest.php` +50/−0

</details>

### fix: neun Dinge, die beim wirklichen Benutzen im Weg standen

`e8eff6f` · **Silas2505** · 18:59 Uhr

> Aus zwanzig durchgespielten Szenarien entlang eines Studienalltags —
> registrieren, Stundenplan eintragen, abhaken, nachtragen, verschieben,
> anpassen, dranbleiben.
>
> - Die KI war im Onboarding tot: `habits/smallest-step/suggestions` lag
>   hinter `EnsureOnboarded` und antwortete bei der allerersten Gewohnheit
>   mit 302 auf `/onboarding`. Der Wizard meldete „Die Vorschläge lassen
>   sich gerade nicht laden" — ausgerechnet im ersten Moment mit der App.
> - Begründete Absagen kamen nie an: Inertia behandelt **jede** 422 als
>   Validierungsantwort und ruft `onHttpException` nicht auf. Der Satz des
>   Servers verschwand, das Sheet blieb im Ladezustand stehen. Die
>   Absagen sind jetzt 409 — kein Formularfehler, sondern der Zustand des
>   Tages —, und `readMessage` verkraftet Text wie geparstes Objekt.
> - „Tag neu ordnen" plante Gewohnheiten ein, die an dem Tag keinen
>   Auslöser haben. Der Kalender filtert `hasTriggerOn`, die Neuordnung
>   nicht: „nach der Vorlesung" landete auf einem Samstag, und mit
>   „Übernehmen" wäre daraus eine feste Uhrzeit geworden.
> - „Übersicht" stand als „Ubersicht" in der Navigationsleiste —
>   `leading-none` und das `overflow-hidden` von `truncate` schnitten die
>   Umlautpunkte ab.
> - Der Auth-Bereich war englisch, als erster Bildschirm einer sonst
>   durchgehend deutschen App.
> - „Soll das nur für heute gelten?" stand auch, wenn der verschobene
>   Block an einem anderen Tag lag.
> - „Nachtragen geht für die letzten sieben Tage" stand unter künftigen
>   Tagen, als wäre eine Frist verstrichen, die noch nicht läuft.
> - Die Verdrängungs-Meldung sagte „jetzt", obwohl der Platz erst zum
>   Semesterbeginn wegfällt, und verwies auf eine Liste, in der bis dahin
>   nichts steht. Sie nennt jetzt das Datum.
> - Der Wochentag beim Kurs sieht aus wie die mehrfach wählbare Reihe bei
>   Gewohnheiten, ist aber Einfachauswahl. Ein Satz sagt es.

<details><summary>21 Dateien · +198/−95</summary>

- `app/Http/Controllers/CalendarController.php` +6/−1
- `app/Http/Controllers/CourseController.php` +14/−0
- `app/Http/Controllers/DayOrderController.php` +21/−8
- `app/Http/Controllers/NewPlaceController.php` +8/−2
- `resources/js/components/course-sheet.tsx` +8/−0
- `resources/js/components/flash-notice.tsx` +17/−4
- `resources/js/components/mobile-nav.tsx` +7/−1
- `resources/js/components/shift-sheet.tsx` +12/−2
- `resources/js/hooks/use-day-order.ts` +19/−10
- `resources/js/hooks/use-new-places.ts` +1/−1
- `resources/js/pages/auth/confirm-password.tsx` +7/−7
- `resources/js/pages/auth/forgot-password.tsx` +7/−7
- `resources/js/pages/auth/login.tsx` +13/−11
- `resources/js/pages/auth/register.tsx` +12/−12
- `resources/js/pages/auth/reset-password.tsx` +7/−7
- `resources/js/pages/auth/two-factor-challenge.tsx` +9/−10
- `resources/js/pages/calendar-day.tsx` +7/−2
- `resources/js/types/global.d.ts` +2/−0
- `routes/web.php` +17/−6
- `tests/Feature/DayOrderTest.php` +3/−3
- `tests/Feature/NewPlaceTest.php` +1/−1

</details>

### feat: give situations weekdays, and one weekday row for both anchors

`55c83a1` · **Silas2505** · 18:59 Uhr

> Eine Situation lief zwangsläufig täglich: `isScheduledOn()` gab für sie
> `true` zurück, und `scheduled_days` wurde nur bei einer festen Uhrzeit
> abgefragt. „Nach dem Aufstehen lesen" hieß damit auch sonntags — nicht,
> weil es jemand so gewählt hätte, sondern weil die Spalte für sie nie
> gefüllt wurde.
>
> Neu ist `Habit::activeWeekdays()` als die eine Quelle dafür. Kalender,
> Übersicht, Tagesplan, Abhaken, Serie und KI-Kontext hängen alle an
> `isScheduledOn()` und ziehen ohne eigene Zeile mit; die sieben Stellen,
> die `?? [1,2,3,4,5,6,7]` ausgeschrieben hatten, lesen jetzt von dort.
> `null` heißt weiterhin täglich — bestehende Gewohnheiten laufen
> unverändert weiter, ohne dass eine Migration ihnen Tage zuweist.
>
> Eine Situation ist ab jetzt **pro Tag** vergeben statt als Ganzes: Mo/Mi/Fr
> „Lesen" und Di/Do „Dehnen" nach dem Aufstehen überschneiden sich nirgends,
> und bei drei angebotenen Situationen wäre die Sperre über die ganze Woche
> eine Grenze von drei situativen Gewohnheiten gewesen.
>
> Die Wochentagsreihe ist eine Komponente für beide Anker, mit einem
> „täglich"-Schalter und täglich als Vorgabe. Belegte Tage sind gesperrt,
> benannt, und fallen beim Wechsel aus der Auswahl — sonst schickte das
> Formular mehr ab, als zu sehen war.

<details><summary>17 Dateien · +674/−180</summary>

- `app/Actions/RestoreDisplacedHabits.php` +1/−1
- `app/Http/Controllers/HabitAdjustmentController.php` +8/−2
- `app/Http/Controllers/HabitController.php` +1/−1
- `app/Http/Controllers/HabitDayShiftController.php` +1/−1
- `app/Http/Controllers/HabitGraduationController.php` +1/−1
- `app/Http/Requests/AdjustHabitRequest.php` +25/−2
- `app/Http/Requests/Concerns/ChecksSituation.php` +35/−18
- `app/Http/Requests/HabitFormRequest.php` +42/−11
- `app/Models/Habit.php` +113/−55
- `database/factories/HabitFactory.php` +3/−1
- `resources/js/components/habit-wizard.tsx` +16/−3
- `resources/js/components/schedule-picker.tsx` +201/−75
- `resources/js/pages/habits/edit.tsx` +9/−1
- `resources/js/types/habit.ts` +5/−3
- `tests/Feature/HabitAdjustmentTest.php` +31/−1
- `tests/Feature/HabitSituationTest.php` +179/−3
- `tests/Feature/HabitUpdateTest.php` +3/−1

</details>

### Füge Schlaf-Wochen-Komponente und Preview hinzu

`612ffc5` · **berbahc** · 14:12 Uhr

<details><summary>6 Dateien · +736/−269</summary>

- `preview.html` +6/−0
- `resources/js/__preview.tsx` +111/−0
- `resources/js/components/managed-habit-row.tsx` +85/−59
- `resources/js/components/sleep-week.tsx` +243/−0
- `resources/js/lib/sleep.ts` +47/−0
- `resources/js/pages/sleep.tsx` +244/−210

</details>

### fix: put the way into today above the month, not under it

`b7fdcff` · **berbahc** · 13:24 Uhr

> „Heutigen Tag öffnen" und der Stundenplan standen unter dem Raster. Wer
> den Kalender öffnet, will meistens in den heutigen Tag, und dieser Weg lag
> damit hinter fünf Wochen Raster.
>
> Beide stehen jetzt zwischen dem Hinweis auf Verdrängtes und dem Monat. Die
> Reihenfolge ist auf jeder Breite dieselbe: Monat mit seiner Zahl, dann was
> eine Entscheidung braucht, dann die Wege hinaus, dann das Raster. Die
> Legende bleibt unten, weil sie erklärt, was direkt über ihr steht.
>
> Claude-Session: https://claude.ai/code/session_01YaKG16k1w5433JTEusqCwP

<details><summary>1 Datei · +16/−13</summary>

- `resources/js/pages/calendar.tsx` +16/−13

</details>

### feat: let the month carry the page, and mark the week you are in

`b9a142b` · **berbahc** · 13:15 Uhr

> Der Kalender war die Handy-Ansicht auf einem großen Bildschirm: eine
> schmale Karte in der oberen Bildschirmhälfte, darunter nichts. Fünfunddreißig
> gleich aussehende Zellen, vier verschiedene Marken darin und keine
> Erklärung, und ein Hut oben rechts, den man erraten musste.
>
> **Das Raster trägt jetzt die Seite.** §12 und §16 lassen genau eine Fläche
> tragen; auf dieser Seite ist das der Monat. Er lag vorher so flach da wie
> jede Notiz daneben. Dieselbe Erhebung wie die „Heute"-Karte auf der
> Übersicht. Die Zellen wachsen in Stufen mit, von 56 Pixeln auf dem Handy
> bis 96 auf dem Desktop. Vorher blieben sie überall bei 56, während sie
> auf dem Desktop 130 breit waren, und das Raster sah gedrückt aus.
>
> **Die laufende Woche liegt auf einem Band.** Der eine Griff, den die
> Seite nicht hatte: Man suchte den heutigen Kreis und rechnete von dort.
> Das Band beantwortet „wo stehe ich" vor dem ersten Blick auf die Zahlen.
> Es ist `track`, die leiseste Stufe der Flächenleiter, und nimmt der
> Ziffer nichts weg.
>
> **Der Monat sagt, wie er lief.** „19 von 22 erledigt" unter dem Titel, in
> derselben Sprache wie Übersicht und Gewohnheiten-Seite. Die Punkte zeigen
> es je Tag, aber niemand zählt fünfunddreißig Zellen zusammen. Gezählt wird
> nur bis heute: Was noch kommt, ist nicht offen, sondern nicht dran, sonst
> wäre jeder Monatsanfang ein Rückstand.
>
> **Eine Legende für die vier Marken.** Punkte, Strich für Vorlesungstage,
> Ring für Verabredungen: keine erklärte sich. Sie teilt sich die Bausteine
> mit dem Raster und kann deshalb nicht von ihm abweichen.
>
> **Die Kopfzeile ist symmetrisch geworden.** Der Stundenplan saß dort als
> drittes Zeichen rechts und zog die Zeile aus der Mitte. Er steht jetzt
> unter dem Raster als „Stundenplan, 5 Kurse", neben „Heutigen Tag öffnen"
> als mittiges Paar, und trägt damit ein Wort statt geraten zu werden.
>
> Beim Monatswechsel ziehen die Wochen nacheinander ein, von oben nach
> unten, in Leserichtung. Der Wechsel ist ein Seitenwechsel und das Raster
> wird neu gebaut; ohne Bewegung springt der Monat hart um. Alles unter
> `motion-safe`.
>
> Eine Zwischenfassung stellte das Raster links neben eine Seitenspalte.
> Ein Kalender ist ein symmetrischer Gegenstand, und eine Spalte daneben
> kämpft dagegen; die Fassung ist wieder weg.
>
> Nicht geändert: Ein offener Punkt sieht morgen weiter genauso aus wie
> gestern. Künftige Punkte zu dämpfen ließe die vergangenen offenen
> schwerer wiegen, und ein Rückblick darf kein Vorwurf sein.
>
> Claude-Session: https://claude.ai/code/session_01YaKG16k1w5433JTEusqCwP

<details><summary>2 Dateien · +241/−86</summary>

- `resources/js/components/month-grid.tsx` +170/−54
- `resources/js/pages/calendar.tsx` +71/−32

</details>

### feat: count days, not percent, and say which days count

`5723bce` · **berbahc** · 12:21 Uhr

> Die Konsistenz stand an zwei Stellen und rechnete an beiden anders. Auf
> der Gewohnheiten-Seite je Gewohnheit, auf der Übersicht als Prozentzahl
> über alle. Wer gestern seine erste Gewohnheit anlegte und erledigte, las
> dort 3 % und hier 100 %.
>
> **Der Nenner zählt nur echte Gelegenheiten.** Lally et al. 2010, zitiert
> in `progress-tracking.md`: „Konsistenz ist der wichtigste Prädiktor für
> Habitstatus, **nicht die absolute Anzahl** der Ausführungen." Konsistenz
> ist ein Anteil, und ein Tag vor dem Anlegen war keine Gelegenheit.
> Zählte man ihn mit, wäre die Zahl eine verkappte Altersangabe der
> Gewohnheit. Die Übersicht folgt jetzt derselben Regel wie die Serie und
> der KI-Rückblick, die sie längst anwenden.
>
> **Beide Seiten nennen Zahlen statt Prozent.** „62 %" über alle
> Gewohnheiten lädt zu einer Fehllesung ein: Wer täglich meditiert und das
> Wochenend-Radfahren auslässt, liest 79 %, obwohl eine seiner beiden
> Gewohnheiten bei null steht. Die Zahl ist nach Häufigkeit gewichtet, und
> niemand liest sie so. „30 von 39 Mal erledigt" behauptet gar nicht, ein
> Durchschnitt zu sein. Damit fällt die Frage weg, statt beantwortet zu
> werden, und die Karte „Heute" trägt nur noch eine Prozentzahl, ihre
> eigene.
>
> „Mal" und nicht „Tage": Die Summe über alle Gewohnheiten kann größer sein
> als 30, und „60 Tage in den letzten 30 Tagen" wäre ein Widerspruch.
> Dieselbe Unterscheidung trifft `streakUnit()` schon für die Serie.
>
> **Die Erklärung steht dort, wo die Frage entsteht.** Ein Zeichen neben
> der Zahl klappt einen Kasten auf, wie bei „5 von 5 aktiv". Er nennt die
> echten Zahlen dieser Gewohnheit statt einer allgemeinen Regel: „In den
> letzten 30 Tagen stand Frühstücken an 22 Tagen an. An 18 davon hast du
> sie erledigt." Und das Startdatum, das bisher nirgends stand: Das Archiv
> nennt „Beendet am", eine laufende Gewohnheit sagte nicht, seit wann es
> sie gibt.
>
> **Der Streifen erfindet keine Tage mehr.** `weekOverview()` kannte das
> Anlegedatum nicht und zeigte für eine heute angelegte Mo–Fr-Gewohnheit
> fünf offene Marken für die Woche davor, als hätte jemand fünfmal
> ausgelassen. `progress-tracking.md` schließt das aus: „fehlende Tage
> dürfen nie bestraft oder prominent angezeigt werden."
>
> Der Zähler bleibt in der Hauptabfrage, wo er hingehört. Er braucht die
> Grenze am Anlegedatum nicht, denn vor dem Anlegen kann es keine
> Erfüllung geben; nur der Nenner braucht sie, und der rechnet in
> `consistencyWindow()` ohne Datenbank. Ein Test hält fest, dass die
> Übersicht mit fünf Gewohnheiten nicht mehr Abfragen braucht als mit
> einer.
>
> Claude-Session: https://claude.ai/code/session_01YaKG16k1w5433JTEusqCwP

<details><summary>9 Dateien · +588/−71</summary>

- `app/Http/Controllers/DashboardController.php` +38/−17
- `app/Http/Controllers/HabitController.php` +21/−10
- `app/Models/Habit.php` +89/−7
- `resources/js/components/managed-habit-row.tsx` +128/−22
- `resources/js/components/rhythm-strip.tsx` +9/−4
- `resources/js/pages/dashboard.tsx` +25/−5
- `resources/js/types/habit.ts` +26/−4
- `tests/Feature/DashboardTest.php` +133/−2
- `tests/Feature/HabitStreakTest.php` +119/−0

</details>

### style: say it in short sentences, and drop the dashes

`8011d93` · **berbahc** · 12:20 Uhr

> Berkay: „bitte schau dass du nicht so komisches deutsch verwendest. es soll
> einfach sein", und kurz darauf „und auch bitte ohne gedankenstriche".
>
> Betroffen war jeder Text, den jemand in der App liest. Wo ein
> Gedankenstrich zwei eigenständige Aussagen zusammenhielt, stehen jetzt
> zwei Sätze; wo er nur einen Einschub markierte, ein Komma. 25 Stellen in
> 20 Dateien, die Texte für die Vorlesehilfe eingeschlossen.
>
> Dazu die Sätze, die man zweimal lesen musste: „siehst du, was dagegen
> steht" wird zu „siehst du den Kurs", „Die einzelne Erinnerung steht im
> ⋯-Menü" zu „Ein- und ausschalten kannst du sie im ⋯-Menü", „der Tag steht
> dabei" zu „mit Tag und Uhrzeit".
>
> Halb wäre schlechter gewesen als gar nicht: Ein Strich in jedem zweiten
> Text sieht nach Absicht aus, in jedem zehnten nach Versehen.
>
> Zwei Sätze sind beim Ersetzen zuerst kaputtgegangen und stehen jetzt
> richtig. „Das lässt sich nicht rückgängig machen — solange die Gewohnheit
> im Archiv liegt, bleibt der Verlauf erhalten" wurde mit einem Komma zum
> Satzbruch und braucht einen Punkt. Und „Selbst umlegen — zum Montag" ist
> als „Selbst umlegen, zum Montag" keine Knopfbeschriftung mehr; dort trägt
> jetzt die Präposition den Satz.
>
> Code-Kommentare und Commit-Nachrichten bleiben unangetastet. Dort ist der
> ausführliche Ton die Hausschrift des Projekts.
>
> Claude-Session: https://claude.ai/code/session_01YaKG16k1w5433JTEusqCwP

<details><summary>22 Dateien · +52/−52</summary>

- `resources/js/components/appointment-sheet.tsx` +1/−1
- `resources/js/components/block-sheet.tsx` +2/−2
- `resources/js/components/companion-step.tsx` +1/−1
- `resources/js/components/course-cancellation-sheet.tsx` +1/−1
- `resources/js/components/courses-sheet.tsx` +2/−2
- `resources/js/components/day-grid.tsx` +1/−1
- `resources/js/components/flash-notice.tsx` +4/−4
- `resources/js/components/graduated-habit-row.tsx` +2/−2
- `resources/js/components/habit-limit-note.tsx` +1/−1
- `resources/js/components/month-grid.tsx` +3/−3
- `resources/js/components/new-places-sheet.tsx` +2/−2
- `resources/js/components/schedule-picker.tsx` +6/−6
- `resources/js/components/semester-sheet.tsx` +3/−3
- `resources/js/components/shift-sheet.tsx` +4/−4
- `resources/js/components/upcoming-appointments.tsx` +1/−1
- `resources/js/components/wake-alarm.tsx` +1/−1
- `resources/js/pages/calendar-day.tsx` +3/−3
- `resources/js/pages/calendar.tsx` +2/−2
- `resources/js/pages/community.tsx` +2/−2
- `resources/js/pages/habits/create.tsx` +3/−3
- `resources/js/pages/habits/index.tsx` +6/−6
- `resources/js/pages/onboarding.tsx` +1/−1

</details>

### feat: let the habits tab show a week instead of five switches

`4c0043d` · **berbahc** · 10:34 Uhr

> Der Tab war eine Einstellungsliste. Pro Karte nahm der Erinnerungs-
> Schalter die halbe Höhe ein, darüber stand noch ein zweiter — das
> Auffälligste auf dem Gewohnheiten-Tab waren zwei Benachrichtigungs-
> Voreinstellungen. Und fünf Gewohnheiten sahen gleich aus, weil
> Einstellungen immer gleich aussehen.
>
> **Der Rhythmusstreifen** macht die Zeilen unterscheidbar: sieben Marken,
> drei Zustände. Erfüllt in Oliv, vorgesehen-und-offen in Sand, gar nicht
> vorgesehen als Punkt. Der dritte Zustand ist der entscheidende — ein
> Samstag ohne Mo–Fr-Gewohnheit ist keine Lücke, und ohne ihn wäre der
> Streifen eine Anklage gegen Tage, an denen nie etwas geplant war.
> Erfunden ist daran nichts: `weekOverview()` gab es längst, der Kommentar
> sagt wörtlich „sieben Tage für den Streifen", und die Methode erreichte
> die Oberfläche nie. Bewusst kein Streak — nichts zählt hoch, nichts
> bricht.
>
> Daneben die **Konsistenzrate** über dreißig Tage, in derselben Zeile wie
> die Serie. Genau die Paarung, die `consistencyRate()` selbst beschreibt:
> die Serie als Antrieb, die Rate als der ehrlichere Blick, der bei einem
> Fehltag nicht springt. Die Zahl **am** Streifen ist dafür weggefallen —
> sieben Marken lassen sich abzählen, und „5 von 6" sagte dasselbe noch
> einmal. Jetzt trägt der Streifen die Woche und die Zahl den Monat.
>
> Eine **Legende** über der Liste erklärt die drei Töne, einmal statt
> fünfmal. Ihre Marken kommen aus derselben Quelle wie die im Streifen; der
> Punkt für „nicht vorgesehen" ist von `track` auf `border` gewechselt,
> weil er auf dem Seitengrund, wo die Legende steht, sonst verschwand.
>
> Der **Schalter** wandert ins ⋯-Menü, wo Bearbeiten und Beenden schon
> lagen; eine stille Glocke in der Kopfzeile sagt weiter, dass etwas
> eingestellt ist. Der Sammelschalter steht jetzt unten bei dem Satz, der
> zur selben Sache gehört. Weggefallen ist nichts, und die Karte ist
> **niedriger als vorher**, obwohl sie mehr zeigt.
>
> Dazu drei Dinge, die beim Bauen auffielen: „Braucht einen neuen Platz"
> sah aus wie jede andere Gruppe, obwohl der Kalender denselben Fall längst
> markiert und einen Ausweg bietet — jetzt auch hier. „Beenden" war
> wortlos; ein Ring holt die Zeile in beide Richtungen ein, mit dem Muster,
> das die Übersicht dafür schon hat. Und der leere Zustand bekommt den
> Knopf hinein statt nur einen Satz.
>
> **Das Zeichen folgt jetzt der Vorlage, nicht der Verhaltensrichtung.**
> Der `behavior_type` ist eine fachliche Einordnung — er steuert die
> Plateau-Schätzung und benennt der KI den Bereich. Als Bildsprache taugt
> er nicht: „Tagebuch schreiben", „Aufräumen", „Meditieren" und „Woche
> planen" sind alle `other` und trugen deshalb alle denselben Halbmond, der
> obendrein schon für den Schlaf vergeben ist. Tagebuch auf „Lernen"
> umzuschreiben hätte still seine Plateau-Schätzung geändert; stattdessen
> entscheidet die Vorlage, die genau weiß, worum es geht. Alle achtzehn
> haben ein eigenes bekommen, die Richtung bleibt der Rückfall für
> Gewohnheiten aus der Zeit der freien Eingabe. Umgestellt überall, wo ein
> Zeichen gezeichnet wird — §5.10 verlangt ein durchgehendes Set, und
> dieselbe Gewohnheit darf je nach Bildschirm nicht anders aussehen.
>
> `categoryLabel` fällt aus der Liste: Es wurde geschickt und von nichts
> gerendert. Die „10 Min" der Erinnerung standen zweimal da, als Konstante
> im Hook und als Text daneben — jetzt einmal.
>
> Ein neuer Test nagelt fest, was den Streifen von einem Streak trennt: Ein
> nicht vorgesehener Tag trägt `scheduled: false` und ist keine Lücke.
>
> Claude-Session: https://claude.ai/code/session_01YaKG16k1w5433JTEusqCwP

<details><summary>17 Dateien · +815/−131</summary>

- `app/Http/Controllers/CalendarController.php` +7/−1
- `app/Http/Controllers/DashboardController.php` +3/−0
- `app/Http/Controllers/HabitController.php` +38/−3
- `resources/js/components/block-sheet.tsx` +4/−8
- `resources/js/components/calendar-block.tsx` +2/−11
- `resources/js/components/graduated-habit-row.tsx` +23/−5
- `resources/js/components/habit-glyph.tsx` +50/−0
- `resources/js/components/habit-row.tsx` +2/−7
- `resources/js/components/managed-habit-row.tsx` +116/−49
- `resources/js/components/rhythm-strip.tsx` +200/−0
- `resources/js/components/schedule-picker.tsx` +0/−1
- `resources/js/hooks/use-habit-reminders.ts` +8/−2
- `resources/js/lib/behavior-icons.ts` +67/−2
- `resources/js/pages/habits/edit.tsx` +4/−7
- `resources/js/pages/habits/index.tsx` +179/−31
- `resources/js/types/habit.ts` +58/−4
- `tests/Feature/HabitStreakTest.php` +54/−0

</details>

### fix: name the action, and let the day stand up straight

`ed5f5a1` · **berbahc** · 10:33 Uhr

> Zwei Stellen, an denen die Oberfläche ihre eigenen Worte verschluckt hat.
>
> **„Annehmen" und „Ablehnen" statt „Ja" und „Nein".** Der Knopf benennt
> jetzt die Handlung statt der Zustimmung zu einem Satz. „Ja" trägt nur,
> solange man die Frage darüber im Kopf hat — wer nach dem Scrollen
> zurückkommt oder mit einer Vorlesehilfe durch die Knöpfe geht, liest zwei
> Wörter, die für sich nichts bedeuten.
>
> **Der Tag gehört groß, wo er für sich steht.** `Appointment::dayLabel()`
> liefert „heute", „morgen", „nächsten Samstag" bewusst klein, weil dieselbe
> Zeile auch mitten in einem Satz läuft („mit Test2 · morgen · 15:00"). Auf
> einem Knopf ist das eine Satzmitte ohne Satz.
>
> Die Großschreibung stand schon dreimal da: einmal als Funktion in der
> Anfrage-Karte, zweimal als abgetippte Zeile. Sie steht jetzt einmal als
> `capitaliseDay()` und deckt fünf Stellen ab — die Tagesknöpfe im
> Verabredungs-Sheet und im Assistenten hatten den Fehler beide, samt ihrer
> Zusammenfassungszeilen. Die Zeitknöpfe der Anfrage-Karte bleiben außen
> vor: Das sind Uhrzeiten, keine Tage.
>
> Claude-Session: https://claude.ai/code/session_01YaKG16k1w5433JTEusqCwP

<details><summary>6 Dateien · +46/−25</summary>

- `resources/js/components/appointment-request-notice.tsx` +2/−10
- `resources/js/components/appointment-sheet.tsx` +10/−3
- `resources/js/components/companion-step.tsx` +5/−3
- `resources/js/components/friend-request-notice.tsx` +14/−6
- `resources/js/components/upcoming-appointments.tsx` +2/−3
- `resources/js/lib/utils.ts` +13/−0

</details>

### fix: pin the clock where the expected day depends on today's weekday

`cb6d20b` · **berbahc** · 10:33 Uhr

> Der Test rechnete den ersten **Montag** des Semesters aus. `previewDate()`
> nimmt aber den frühesten Wochentag der Gewohnheit — sie läuft montags und
> mittwochs, gezeigt wird der Tag, an dem der Vorschlag zuerst greift.
>
> Beides fällt nur zusammen, solange „heute + 1 Monat" auf einen Montag
> fällt. Am 05.09. tat es das, am 06.09. nicht mehr: Das Semester begann
> dann an einem Dienstag, der erste Mittwoch lag vor dem ersten Montag, und
> der Test fiel über Nacht um, ohne dass jemand etwas angefasst hatte.
>
> Die Uhr steht jetzt fest, und der erwartete Tag steht als Datum da statt
> als Rechnung, die zufällig stimmt.
>
> Claude-Session: https://claude.ai/code/session_01YaKG16k1w5433JTEusqCwP

<details><summary>1 Datei · +16/−8</summary>

- `tests/Feature/NewPlaceTest.php` +16/−8

</details>

## 05.09.2026

### merge: let the appointment ride along with the timetable

`2390e47` · **berbahc** · 17:44 Uhr

> Silas' vier Commits gegen unsere Verabredungen. Drei Textkonflikte, einer
> davon inhaltlich, und ein Test, der still falsch wurde.
>
> **Der Kalenderblock trägt jetzt beides.** Silas gibt `block()` den
> Stundenplan mit, damit ein verdrängter Block den ersten Tag kennt, an dem
> ihm ein Kurs den Platz wirklich nimmt; wir geben ihm den Mitmacher mit,
> damit der Kalender denselben Doppel-Kreis zeigt wie die Übersicht. Der
> Rumpf benutzte längst beide Angaben — nur die Signatur und die
> Aufrufstelle waren zweimal geschrieben. `conflictDate` stand in keiner
> der beiden Rückgabeformen; es steht jetzt drin.
>
> **Das `✦` bleibt weg.** Silas hat im Block-Sheet ein eigenes `AI_LINK`
> mit dem alten Zeichen angelegt, wir haben dasselbe `AI_LINK` nach
> `lib/interaction.ts` gezogen und das Zeichen durch das Maskottchen
> ersetzt. Zwei Deklarationen desselben Namens in einer Datei wären ohnehin
> ein Fehler; die gemeinsame gewinnt, weil sie die Lücke für die Figur
> trägt. Sein `dayLabel` und der Weg „Selbst umlegen" bleiben unberührt.
> Dasselbe im Kalendertag: Wo er `Sparkles` in die Vorschlagskarte setzt,
> steht bei uns die antwortende Figur — das Icon ist damit unbenutzt und
> fällt raus.
>
> **Und der Test, der niemandem aufgefallen wäre.** `CalendarAppointmentTest`
> prüft, dass eine Verabredung eine eigene Situation aus ihrem Platz
> schiebt, und benutzte dafür „wenn ich nach Hause komme" auf 17 Uhr. Genau
> diese Situation hat Silas abgeschafft, weil sie auf einer geratenen
> Uhrzeit ruhte. Der Test lief danach gegen den Mittagsrückfall um 12 Uhr
> und behauptete eine Verdrängung, die nicht stattfand. Er hängt jetzt an
> „nach dem Aufstehen", das am Schlafplan hängt statt an einer Annahme —
> die Prüfung selbst ist dieselbe geblieben.
>
> 639 Tests grün. Larastan meldet dieselben 20 Befunde wie vor dem Merge,
> keinen neuen.
>
> Claude-Session: https://claude.ai/code/session_01YaKG16k1w5433JTEusqCwP

### feat: a promise you can keep, a helper you can see, a day you can read

`aebc6be` · **berbahc** · 17:36 Uhr

> Drei Stränge, die im Arbeitsverzeichnis übereinander gewachsen sind und
> sich nicht mehr sauber trennen ließen — sie fassen dieselben Dateien an.
>
> **Die Verabredung ist zu Ende gebaut.** Wer zusagte, konnte bis hierher
> nichts abhaken: Die Gewohnheit gehört der fragenden Seite, und ein Haken
> an ihr hätte deren Erfüllung gemeldet. Der Haken hängt jetzt an der
> Verabredung, zählt nicht in die fremde Konsistenzrate und bleibt für die
> fragende Seite unsichtbar (§6). Dazu der Weg nach vorn — „Nochmal
> ausmachen?", jedes Mal eine neue Einzelentscheidung statt eines Abos
> (§7) — und der Block im Stundenraster: Eine zugesagte Verabredung stand
> in keinem Kalender, weil der Tag nur eigene Gewohnheiten lädt. Sie war
> zugesagt und dann verschwunden.
>
> **Die KI bekommt ein Gesicht.** Vier Funktionen sprachen durch dasselbe
> stumme `✦` in vierzehn Pixeln; die stärkste Funktion der Umfrage
> (Starthilfe ø 4,16) war damit der leiseste Teil der Oberfläche. Das
> Maskottchen kennt genau die vier Zustände, die die Oberfläche hat — mehr
> liefert kein KI-Hook.
>
> **Die Übersicht sagt, was was ist.** „mit Test2 · 09:00 · nur an diesem
> Tag · 90 Min" waren vier Auskünfte in einem Gewicht, und zwei
> gleichnamige Gewohnheiten am selben Tag ließen sich darin nicht
> auseinanderhalten. Die Uhrzeit steht jetzt links in einer eigenen Spalte
> — dieselbe Stundenspalte wie im Kalender —, und die Liste liest sich als
> Plan von früh nach spät, was sie ohnehin schon war. `schedulePieces()`
> liefert Uhr und Wiederholung getrennt; `scheduleLabel()` setzt sie nur
> noch zusammen und kann deshalb nicht abweichen. Leer bleibt die Spalte,
> wo es keine Uhr gibt: Eine abgeleitete Stunde dort wäre eine Festlegung,
> die niemand getroffen hat.
>
> Dazu die Abschnitte: „Heutige Gewohnheiten" trug eine H2, „ZUSAMMEN"
> eine Kleinversalien-Zeile — zwei gleichrangige Bereiche in zwei Graden,
> und keiner erklärte, warum dieselbe Person zweimal auf der Seite steht.
> Ein Grad für beide, ein Satz darunter, und aus „Zusammen" wird
> „Verabredungen", dessen Karten mit dem Tag anführen. Der Tag ist der
> Unterschied zur Zeile oben.
>
> Zwei Korrekturen am Rand: Das Doppel-Zeichen war 64 px breit neben einer
> 44-px-Kachel, wodurch der Titel einer Verabredungs-Zeile 20 px weiter
> rechts begann als jeder andere — beide sind jetzt gleich breit. Und der
> Titel bricht um statt abzuschneiden; auf 375 px hätte die neue Spalte
> sonst „Tagebuch schrei…" übrig gelassen.
>
> `AppointmentTest` prüfte noch die alte Regel, dass nur die drei
> angebotenen Tage durchgehen. Der Server prüft seit `possibleDaysFor()`
> bewusst gegen die ganze Woche — sonst läge der dritte Vorschlag von
> „Nochmal ausmachen?", das einen Tag später anfängt, jenseits der
> Prüfung. Der Test nennt jetzt die Grenze, die der Code hat.
>
> Claude-Session: https://claude.ai/code/session_01YaKG16k1w5433JTEusqCwP

<details><summary>45 Dateien · +3667/−392</summary>

- `app/Actions/RestoreDisplacedHabits.php` +4/−1
- `app/Http/Controllers/AppointmentCompletionController.php` +58/−0
- `app/Http/Controllers/CalendarController.php` +139/−7
- `app/Http/Controllers/DashboardController.php` +110/−11
- `app/Http/Controllers/HabitDayShiftController.php` +12/−2
- `app/Http/Requests/ProposeAppointmentRequest.php` +7/−5
- `app/Models/Appointment.php` +230/−23
- `app/Models/Habit.php` +75/−7
- `app/Policies/AppointmentPolicy.php` +12/−0
- `app/Support/AppointmentFit.php` +9/−2
- `database/migrations/2026_09_05_133452_add_completed_at_to_appointments_table.php` +42/−0
- `resources/css/app.css` +196/−0
- `resources/js/components/adjustment-sheet.tsx` +37/−18
- `resources/js/components/ai-mascot.tsx` +156/−0
- `resources/js/components/ai-suggestion.tsx` +33/−19
- `resources/js/components/appointment-block.tsx` +175/−0
- `resources/js/components/appointment-request-notice.tsx` +149/−93
- `resources/js/components/appointment-sheet.tsx` +22/−2
- `resources/js/components/block-sheet.tsx` +14/−7
- `resources/js/components/calendar-block.tsx` +46/−16
- `resources/js/components/day-grid.tsx` +61/−18
- `resources/js/components/day-order-sheet.tsx` +38/−18
- `resources/js/components/habit-row.tsx` +168/−24
- `resources/js/components/habit-wizard.tsx` +2/−2
- `resources/js/components/month-grid.tsx` +23/−8
- `resources/js/components/new-places-sheet.tsx` +42/−22
- `resources/js/components/section-heading.tsx` +50/−0
- `resources/js/components/starting-help-sheet.tsx` +34/−14
- `resources/js/components/upcoming-appointments.tsx` +136/−19
- `resources/js/lib/day-grid.ts` +14/−6
- `resources/js/lib/interaction.ts` +10/−0
- `resources/js/pages/calendar-day.tsx` +58/−10
- `resources/js/pages/calendar.tsx` +7/−8
- `resources/js/pages/dashboard.tsx` +141/−25
- `resources/js/types/friendship.ts` +73/−1
- `resources/js/types/habit.ts` +43/−1
- `routes/web.php` +9/−0
- `tests/Feature/AppointmentCompletionTest.php` +266/−0
- `tests/Feature/AppointmentRepeatTest.php` +236/−0
- `tests/Feature/AppointmentTest.php` +257/−2
- `tests/Feature/CalendarAppointmentTest.php` +240/−0
- `tests/Feature/DashboardTest.php` +24/−0
- `tests/Feature/HabitAdoptionTest.php` +53/−0
- `tests/Feature/HabitShiftTest.php` +60/−0
- `tests/Feature/NoOverlapInvariantTest.php` +96/−1

</details>

### feat: only offer anchors the app can work out, and let habits hang on habits

`54a4332` · **Silas2505** · 15:13 Uhr

> Two changes with one question behind them: what may the app claim to know?
>
> A situation is now offered only when it follows from something the user
> actually told us. The sleep plan says when they get up and go to bed, so
> "nach dem Aufstehen" and "vor dem Schlafengehen" stay. The timetable says
> when lectures end, so "nach der Vorlesung" stays — and is offered only
> while a timetable exists.
>
> The other three rested on guesses: breakfast 45 minutes after waking,
> lunch at one, home at five. That is right for nobody, and a calendar that
> puts a habit at an invented time is unreliable exactly where it has to be
> reliable. They are gone, and so is the free-text field, which was worse:
> whatever was typed there could not be placed at all and landed at noon.
>
> Nothing is lost, because the capability moves to a mechanism that knows
> the time instead of guessing it. Whoever plans "after breakfast" now hangs
> the habit on the habit "Frühstücken" — which is the second change: the AI
> offers every habit of that day as something to hang on, checks that form
> first, and may still propose moments and free windows beside it. It may
> only name habits it was given, so it cannot invent one or close a circle.
>
> Existing habits keep their place: a migration turns the retired situations
> into the clock time they were being sorted to. Validation runs against the
> whole vocabulary rather than the current offer, so deleting a semester
> does not lock the habits that hang on it.

<details><summary>27 Dateien · +600/−296</summary>

- `app/Actions/RememberSuggestions.php` +11/−6
- `app/Ai/Agents/SuggestBetterAnchor.php` +69/−5
- `app/Http/Controllers/HabitAdjustmentController.php` +77/−4
- `app/Http/Requests/AdjustHabitRequest.php` +6/−1
- `app/Http/Requests/HabitFormRequest.php` +7/−0
- `app/Models/Habit.php` +43/−69
- `database/factories/AiSuggestionFactory.php` +1/−1
- `database/factories/HabitFactory.php` +12/−1
- `database/migrations/2026_09_05_120000_retire_the_guessed_situations.php` +64/−0
- `database/seeders/DatabaseSeeder.php` +4/−4
- `resources/js/components/adjustment-sheet.tsx` +12/−6
- `resources/js/components/schedule-picker.tsx` +11/−52
- `resources/js/types/habit.ts` +10/−0
- `tests/Feature/AiMemoryTest.php` +24/−20
- `tests/Feature/CalendarTest.php` +2/−2
- `tests/Feature/DashboardTest.php` +1/−1
- `tests/Feature/HabitAdjustmentTest.php` +121/−26
- `tests/Feature/HabitAdoptionTest.php` +2/−2
- `tests/Feature/HabitMeasureTest.php` +4/−4
- `tests/Feature/HabitShiftTest.php` +3/−3
- `tests/Feature/HabitSituationTest.php` +72/−62
- `tests/Feature/HabitSlotTest.php` +14/−11
- `tests/Feature/HabitUpdateTest.php` +5/−5
- `tests/Feature/NoOverlapInvariantTest.php` +1/−1
- `tests/Feature/OnboardingTest.php` +19/−5
- `tests/Feature/SleepScheduleTest.php` +1/−1
- `tests/Feature/SmallestStepTest.php` +4/−4

</details>

### feat: give the displaced habits a place of their own, and a way out of it

`5b146db` · **Silas2505** · 12:58 Uhr

> They sat at the foot of the grid under a small heading, which read like a
> remainder that had not fitted in. They now stand below the calendar in a
> card of their own, in the accent tone with an olive edge — visible as
> something still open, without red and without an exclamation mark, since
> nobody missed anything here.
>
> From there two ways lead out. The known one asks the AI. The new one does
> not ask at all: "Selbst umlegen" goes to the first day on which a course
> really takes the old slot, because that is the only place where a new
> slot can be chosen against something visible.
>
> That day is searched for, not guessed. Whoever runs Mon/Wed/Fri and whose
> lecture is on Wednesday has no conflict on Monday, and sending them there
> would show a free day and no cause. The old time is the memory of where
> the habit ran; the search walks a fortnight over the days it ran on and
> returns the first real overlap.
>
> Arriving there, the card can be picked up and carried into the grid. It
> moves absolutely rather than relatively: a block in the grid already has
> a place and the finger pushes it on from there, but the list sits a
> screen and a half below the hour you are aiming at, so the same distance
> would land anywhere. Released, the familiar question follows — only today
> or always — and "always" gives the habit its place back.

<details><summary>6 Dateien · +264/−44</summary>

- `app/Http/Controllers/CalendarController.php` +55/−2
- `resources/js/components/block-sheet.tsx` +33/−1
- `resources/js/components/day-grid.tsx` +0/−33
- `resources/js/hooks/use-block-drag.ts` +30/−4
- `resources/js/pages/calendar-day.tsx` +140/−4
- `resources/js/types/habit.ts` +6/−0

</details>

### fix: one heading per page on the phone, and room for the umlaut

`2876f5d` · **Silas2505** · 12:33 Uhr

> The header title and the page heading below it said the same word twice.
> The lower one goes on the phone — but only where it really was an echo:
> Gewohnheiten, Schlaf & Rhythmus, Community. The greeting on the overview
> and the month in the calendar stay, because neither repeats a tab name;
> "Übersicht" cannot replace "Guten Morgen, Silas." and "Kalender" cannot
> replace "September 2026", which is also what the arrows beside it move.
>
> Since the header title is now the only heading a phone shows, it takes
> the colour a page heading has in this app and the size a navigation bar
> has.
>
> It also had `leading-none`, and a line height of 1 leaves no room above
> the letter: the dots on the Ü of "Übersicht" were cut off.

<details><summary>4 Dateien · +29/−8</summary>

- `resources/js/components/app-sidebar-header.tsx` +13/−4
- `resources/js/pages/community.tsx` +6/−2
- `resources/js/pages/habits/index.tsx` +5/−1
- `resources/js/pages/sleep.tsx` +5/−1

</details>

### feat: give the phone a header of its own

`6d003c0` · **Silas2505** · 12:25 Uhr

> The breadcrumbs answered a question the phone does not ask. There is no
> sidebar to go back to and the path is never more than two links long, so
> "Übersicht > Gewohnheiten" filled half the header without orienting
> anyone.
>
> In its place: the name of the tab that is lit in the bottom navigation,
> read from the same `mainNavItems` the navigation itself uses, so the two
> cannot drift apart. `isCurrentOrParentUrl` keeps a single day
> (/calendar/2026-09-05) under "Kalender" and the wizard under
> "Gewohnheiten"; anything outside the five tabs falls back to the last
> breadcrumb.
>
> Left of it the brand, which already picks the tile that stands out
> against the ground rather than the one that matches it. Deliberately not
> a link — the bottom bar already leads to the overview, and a second way
> to the same place is one too many.
>
> Three grid columns rather than `justify-between`: the account side is
> wider than the brand, so equal outer columns are what actually puts the
> title in the middle. Measured at 390px: 0px off centre in both themes.
> From the tablet up, nothing changes.

<details><summary>1 Datei · +90/−40</summary>

- `resources/js/components/app-sidebar-header.tsx` +90/−40

</details>

### feat: a chain only needs a breath, not the full quarter hour

`750687d` · **berbahc** · 10:18 Uhr

> Die Viertelstunde zwischen zwei Blöcken ist Weg und Wechsel: hinkommen,
> umschalten, ankommen. Innerhalb einer Kette gibt es beides nicht — wer
> „danach" plant, ist schon dabei und läuft weiter. Fünfzehn Minuten
> Leerlauf nach jedem Glied summierten sich über eine dreigliedrige Kette
> auf eine halbe Stunde, die niemand meint.
>
> Fünf Minuten reichen dort. Zwischen unabhängigen Blöcken bleibt es bei
> der Viertelstunde, und die Invariante unterscheidet jetzt beides: Glieder
> derselben Kette dürfen enger liegen, alles andere nicht.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>9 Dateien · +54/−25</summary>

- `app/Models/Habit.php` +3/−3
- `app/Support/DayPlan.php` +9/−0
- `resources/js/lib/day-grid.ts` +10/−2
- `tests/Feature/CalendarTest.php` +2/−2
- `tests/Feature/HabitChainTest.php` +12/−11
- `tests/Feature/HabitShiftTest.php` +1/−1
- `tests/Feature/HabitSituationTest.php` +2/−2
- `tests/Feature/HabitSlotTest.php` +1/−1
- `tests/Feature/NoOverlapInvariantTest.php` +14/−3

</details>

### feat: every situation hangs on its own occasion

`f512eb9` · **berbahc** · 10:11 Uhr

> Die sechs Situationen lagen auf festen Uhrzeiten, und die stimmten für
> niemanden, dessen Tag anders läuft. „Nach dem Frühstück" war 08:00 —
> wer um zehn aufsteht, bekam es damit vor dem Aufstehen.
>
> Jetzt hängt jede an dem, was sie benennt: Aufstehen und Schlafenszeit am
> Schlafplan, die Vorlesung am Stundenplan, das Nachhausekommen am
> letzten Kurs plus Heimweg. Frühstück heißt eine Dreiviertelstunde nach
> dem Aufstehen — und wer sein Frühstücken als Gewohnheit führt, dessen
> Block liegt ohnehin im Weg, sodass die Situation ihm ausweicht. Die App
> muss also nicht wissen, wann jemand isst.
>
> Die Spanne ist zwei Stunden statt drei. Weiter weg wäre keine Situation
> mehr, sondern eine andere Tageszeit.
>
> Dazu ein echter Überlapp, den es vorher gab: Eine Kette an einer
> Situation folgte ihr nicht. Wich der Anker aus, blieb die Nachfolgerin
> liegen — mitten in dem, dem er gerade ausgewichen war. Der Tagesplan
> legt die Kette jetzt als Ganzes, und die Spanne fasst sie. Ist sie zu,
> gilt der Rest des Tages: später als der Anlass ist besser als
> übereinander. Die Invariante fährt den Weg jetzt mit ab.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>4 Dateien · +268/−63</summary>

- `app/Models/Habit.php` +93/−59
- `app/Support/DayPlan.php` +51/−4
- `tests/Feature/HabitSituationTest.php` +102/−0
- `tests/Feature/NoOverlapInvariantTest.php` +22/−0

</details>

### feat: after the lecture means after the lecture

`8d2eaf5` · **berbahc** · 09:51 Uhr

> „Nach der Vorlesung" lag auf einem festen Fenster zwischen 11:00 und
> 15:00 — und damit irgendwo, nur nicht dort, wo die Vorlesung tatsächlich
> endet. Wer seinen Stundenplan gepflegt hat, meint den Moment, an dem der
> Uni-Tag vorbei ist.
>
> Die Situation hängt jetzt am letzten Kurs des Tages, plus der
> Viertelstunde Luft, und reicht drei Stunden weit. An einem Tag ohne
> Vorlesung gibt es den Auslöser nicht, also auch die Gewohnheit nicht:
> Sie steht dort gar nicht erst an, statt an einer erfundenen Uhrzeit zu
> liegen — „ohne Trigger keine Gewohnheit". Wer kein Semester eingetragen
> hat, behält das feste Fenster; Schweigen ist kein Nein.
>
> Und „vor dem Schlafengehen" reicht nur noch eine Stunde zurück statt
> drei. Drei Stunden vorher ist früher Abend und nichts, was jemand so
> plant.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>5 Dateien · +227/−12</summary>

- `app/Http/Controllers/CalendarController.php` +6/−1
- `app/Models/Habit.php` +103/−7
- `app/Support/DayPlan.php` +9/−0
- `tests/Feature/HabitSituationTest.php` +103/−0
- `tests/Feature/HabitSlotTest.php` +6/−4

</details>

### fix: say what a friend request asks, and answer it with yes

`6a5ba32` · **berbahc** · 09:38 Uhr

> Die Karte sagte nur „Silas fragt dich" — worum, stand nirgends. Und die
> Antwort war „Passt mir" oder „Lieber nicht": die Wörter der
> Gewohnheitsanfrage, wo der Kalender darüber entscheidet, ob etwas passt.
> Ob man befreundet sein will, entscheidet niemand anders.
>
> Jetzt steht „Silas will mit dir befreundet sein", darunter Ja und Nein.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>1 Datei · +14/−8</summary>

- `resources/js/components/friend-request-notice.tsx` +14/−8

</details>

### feat: a situation gives way instead of colliding

`43fe33e` · **berbahc** · 09:36 Uhr

> Eine Situation ist keine Uhrzeit. „Nach dem Frühstück" heißt irgendwann
> am Vormittag — der Kalender legte sie trotzdem stur auf eine Stunde und
> ließ sie dort mit dem kollidieren, was schon da lag.
>
> Jede Situation hat jetzt eine Spanne, in der sie ausweichen darf: drei
> Stunden ab dem Aufstehen, drei vor der Schlafenszeit, und für die vier
> übrigen ein Fenster um ihre Stunde. Der Nutzer sieht davon nichts — er
> hat eine Situation gewählt, und die bedeutet ohnehin einen Zeitraum.
>
> Der Tagesplan legt sie deshalb in zwei Durchgängen: erst alles mit
> fester Stelle, dann die Situationen an den ersten freien Fleck in ihrer
> Spanne, mit derselben Viertelstunde Luft wie überall. Das Raster fragt
> jetzt den Plan statt die Gewohnheit — sonst zeigte es eine andere Stelle
> als die Rechnung.
>
> Ein Umzug für einen einzelnen Tag schlägt die Spanne: Er ist eine
> Ansage, keine Näherung. Und das Fenster fasst mindestens, was
> hineinsoll, damit es keine Dauer gibt, zu der keine Situation passt.
>
> Abgewiesen wird nur noch, was gar nicht mehr hineinpasst — dann gäbe es
> keine Stelle zum Ausweichen, und sie läge übereinander. Die Invariante
> fährt den Weg jetzt mit ab.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>6 Dateien · +376/−84</summary>

- `app/Http/Controllers/CalendarController.php` +16/−11
- `app/Http/Requests/HabitFormRequest.php` +69/−40
- `app/Models/Habit.php` +77/−0
- `app/Support/DayPlan.php` +134/−4
- `tests/Feature/HabitSlotTest.php` +67/−29
- `tests/Feature/NoOverlapInvariantTest.php` +13/−0

</details>

### fix: check the situation's slot, because the day already reserves it

`9abc0f0` · **berbahc** · 09:23 Uhr

> Eine Gewohnheit an einer Situation hat keine Uhrzeit — der Kalender legt
> sie trotzdem auf eine geschätzte Stelle und rechnet sie dort als belegt.
> Geprüft wurde nur, ob die Situation selbst noch frei ist. Wer also
> zuerst Joggen auf 17:00 legte und danach „Mittagessen, wenn ich nach
> Hause komme" eintrug, bekam zwei Blöcke auf derselben Minute: im Raster
> nebeneinander, in jeder Rechnung darüber gegeneinander.
>
> Die Behauptung, die Stelle sei genau, stand längst im Raster — nur
> ungeprüft. Jetzt wird sie geprüft, an genau der Stelle, an die das
> Raster sie legt: über eine Probe-Gewohnheit, damit es nicht zwei
> Rechnungen für dieselbe Frage gibt. Die sechs Situationen haben
> paarweise verschiedene Stunden und blockieren sich nie gegenseitig; es
> trifft nur eine feste Uhrzeit oder einen Kurs in derselben Stunde.
>
> Der Test, der das Gegenteil festhielt, ist umgedreht. Zwei Tests zum
> Übernehmen wählen jetzt eine freie Situation: Sie fragen nach der
> Zusage und nach der Berechtigung, nicht nach dem Platz.
>
> Und zwei neue Tests würfelten ihre Vorlage — mit ihr das Tagesfenster,
> und damit zu einem Fünftel ein falsches Rot.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>5 Dateien · +149/−11</summary>

- `app/Http/Requests/HabitFormRequest.php` +90/−5
- `tests/Feature/HabitAdoptionTest.php` +6/−2
- `tests/Feature/HabitDisplacementTest.php` +4/−0
- `tests/Feature/HabitSlotTest.php` +45/−4
- `tests/Feature/NewPlaceTest.php` +4/−0

</details>

### merge: bring the semester plan into the calendar (#1)

`43f113b` · **berbahc** · 08:48 Uhr

> Kurse aus dem Stundenplan liegen im Kalender, Gewohnheiten planen sich
> darum herum. Eine Regel fuer den ganzen Tag, mit einer Viertelstunde Luft
> zwischen zwei Bloecken. Der Semesterwechsel parkt, was ein Kurs verdraengt,
> statt es zu verlieren, und die KI schlaegt neue Zeiten vor.

### merge: fold the day's minute-accurate edges into the semester plan

`7ab2415` · **berbahc** · 08:37 Uhr

> Silas hat die Tagesränder minutengenau gemacht, das Raster wachsen
> lassen, wo ein Block außerhalb des Rahmens liegt, und `Carbon::today()`
> gegen den gezeigten Tag getauscht. Wir haben in denselben drei Dateien
> die Atempause, den getragenen Block und die Kurse im Raster gebaut.
>
> Aufgelöst wurde so:
>
> `placeBlocks()` bleibt generisch (Kurse und Gewohnheiten liegen in einem
> Durchgang) und nimmt seine Grenze für die Schlafenszeit an. Sein
> `boundsFor()` ersetzt unsere Handrechnung für den Ausschnitt und nimmt
> dafür auch die Kurse entgegen — es braucht nur `Placeable`, nicht den
> ganzen Block. Seine Marken für Aufstehen und Schlafenszeit stehen jetzt
> im Raster, die alte Zeile darunter fällt weg. Das Zeitschild beim Ziehen
> behält unsere Warnfarbe, verliert aber die halbe Zeilenverschiebung, die
> er überall korrigiert hat.
>
> Eine stille Regression kam durch den Merge und ist mit ihm geschlossen:
> `dayStartMinute()` fragte seine minutengenaue Randstelle, bevor
> irgendjemand geprüft hatte, ob die Gewohnheit an diesem Tag überhaupt
> eine Stelle hat. Eine geparkte Gewohnheit am Tagesrand wäre damit
> zurück auf den Platz gerutscht, den jetzt ein Kurs hat. Die Frage nach
> der Stelle kommt jetzt zuerst, die Minute verfeinert sie nur.
>
> Und eine Verbesserung mitgenommen: Wo der Rückweg einer Gewohnheit ohne
> Uhrzeit ihre alte Stelle schätzte, nimmt er jetzt seine genaue Minute.
> Wer um 07:40 aufsteht, bekommt 07:40 statt 07:00.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

### feat: a cancelled course gives the day back

`09b46b3` · **berbahc** · 08:17 Uhr

> Fällt eine Vorlesung an einem Datum aus, ist ihre Zeit an diesem Tag
> frei — die verdrängte Gewohnheit stand trotzdem weiter unter „braucht
> einen neuen Platz", und der Vormittag blieb ungenutzt.
>
> Sie bekommt den Tag jetzt zurück, und nur ihn: über einen Umzug für
> genau dieses Datum, den Baustein, der in der App ohnehin „nur heute
> hier" bedeutet. Der Vermerk bleibt, nächste Woche läuft der Kurs
> wieder. Wird der Ausfall zurückgenommen, ist der geliehene Tag weg.
>
> Dafür kippt eine Reihenfolge: Der Umzug für einen Tag ist die
> speziellere Aussage und geht dem Parkvermerk vor.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>4 Dateien · +162/−17</summary>

- `app/Actions/RestoreDisplacedHabits.php` +67/−0
- `app/Http/Controllers/CourseExceptionController.php` +31/−5
- `app/Models/Habit.php` +16/−12
- `tests/Feature/CalendarSemesterTest.php` +48/−0

</details>

### fix: a habit without a clock time finds its way back

`9bb2ae4` · **berbahc** · 08:13 Uhr

> Eine Gewohnheit an einer Situation belegt im Tag die Stunde ihres
> Ankers und wird darüber verdrängt wie jede andere. Beide Rückwege
> filterten sie aber weg, weil sie keine Uhrzeit hat: Sie bekam nie einen
> Vorschlag der KI und kam nie von selbst zurück — sie hing, bis jemand
> sie von Hand bearbeitete. Ein Drittel aller Gewohnheiten ist von dieser
> Art, und das Konzept nennt die Situation den besseren Auslöser.
>
> Beide Wege fragen jetzt nach der Stelle, an der sie lag: die Uhrzeit,
> oder ohne eine die Stunde ihres Ankers. Dafür beantwortet das Modell
> diese Frage neu, ohne die Prüfung, ob gerade Platz ist.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>5 Dateien · +157/−5</summary>

- `app/Actions/RestoreDisplacedHabits.php` +31/−2
- `app/Http/Controllers/NewPlaceController.php` +31/−3
- `app/Models/Habit.php` +12/−0
- `tests/Feature/HabitDisplacementTest.php` +49/−0
- `tests/Feature/NewPlaceTest.php` +34/−0

</details>

### fix: a parked habit is not a missed one

`3e1dc1f` · **berbahc** · 08:08 Uhr

> Der Stundenplan nimmt den Platz — und die App rechnete den Verlust dem
> Studenten an: Die Serie brach ab, der Wochenstreifen zeigte offene
> Lücken, die Übersicht führte die Gewohnheit unter „steht heute an", und
> die KI bekam „mehrfach verpasst" als Beleg für ihren Vorschlag. Genau
> die Bestrafung, die time-blocking.md ausschließt.
>
> `isScheduledOn()` beantwortet weiter „steht im Plan" — dafür brauchen
> die Kollisionsprüfung und der Tagesplan alle Tage. Wer daraus eine
> Bilanz zieht, fragt jetzt `isDueOn()`: im Plan und mit Platz.
>
> Dazu dieselbe Verwechslung beim Wiederaufnehmen: Dort galt ein Vermerk
> schon als wirksam, der erst zum Semesterbeginn gilt.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>4 Dateien · +76/−5</summary>

- `app/Http/Controllers/DashboardController.php` +4/−1
- `app/Http/Controllers/HabitGraduationController.php` +4/−2
- `app/Models/Habit.php` +19/−2
- `tests/Feature/HabitStreakTest.php` +49/−0

</details>

### fix: close the three ways past the one rule

`815cc2e` · **berbahc** · 08:06 Uhr

> Drei Wege setzten eine Uhrzeit, ohne die Regel zu halten, die überall
> sonst gilt.
>
> Die Verschiebung für eine Verabredung rechnete die Überschneidung selbst
> nach — ohne die Viertelstunde Luft und ohne die Kette. Über sie ließ
> sich ein Block Rücken an Rücken an eine Vorlesung legen, und ein
> Kettenglied so schieben, dass sein Nachfolger mitten in ihr landete.
> Sie fragt jetzt dieselbe Stelle wie alle anderen.
>
> Ein an einem Datum verlegter Kurs prüfte gegen gar nichts und verdrängte
> niemanden; der Tag zeichnete beides übereinander. Die Kollisionsprüfung
> kann jetzt auch ein einzelnes Datum beantworten, und die Verlegung
> verdrängt wie jeder Kurs — samt Rückmeldung.
>
> Ein zweites Semester ließ sich anlegen und war danach weder zu ändern
> noch zu löschen, samt seiner Kurse. Es gibt genau eines; ein zweiter
> Versuch ändert das bestehende.
>
> Die Invariante kannte die Atempause nicht — deshalb fiel der erste
> Punkt nicht auf. Sie prüft sie jetzt und fährt zwei Wege mehr ab.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>8 Dateien · +275/−41</summary>

- `app/Actions/DisplaceHabits.php` +34/−1
- `app/Http/Controllers/CourseExceptionController.php` +32/−1
- `app/Http/Controllers/SemesterController.php` +15/−1
- `app/Http/Requests/ShiftHabitDayRequest.php` +20/−35
- `app/Support/SlotConflict.php` +51/−1
- `tests/Feature/HabitShiftTest.php` +65/−0
- `tests/Feature/NoOverlapInvariantTest.php` +41/−2
- `tests/Feature/SemesterTest.php` +17/−0

</details>

### feat: the end of a course follows its start

`41b0d44` · **berbahc** · 01:45 Uhr

> Wer einen Kurs von 8 auf 11 Uhr schiebt, hat ihn verschoben und nicht
> verkürzt — das Ende sprang aber stehen und musste hinterhergetippt
> werden. Jetzt wandert es mit und behält die Länge. Der zweite Zeiger
> bleibt hinter dem ersten und innerhalb dessen, was ein Kurs sein darf,
> damit dort nichts steht, was der Server gleich wieder abweist.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>1 Datei · +68/−4</summary>

- `resources/js/components/course-sheet.tsx` +68/−4

</details>

### refactor: one shell for every sheet, and drop what nothing calls

`e418550` · **berbahc** · 01:33 Uhr

> Zehn Sheets trugen dieselbe Klassenzeile — Höhe, Breite, Rundung, Rand.
> Einmal geschrieben heißt: Ein Sheet sitzt nie anders als das davor.
> Dazu raus, was niemand ruft: zwei Freundschafts-Relationen, die keine
> Abfrage benutzt, und ein Typ, den kein Modul liest.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>13 Dateien · +45/−73</summary>

- `app/Models/User.php` +0/−20
- `resources/js/components/adjustment-sheet.tsx` +2/−4
- `resources/js/components/block-sheet.tsx` +7/−5
- `resources/js/components/course-cancellation-sheet.tsx` +2/−5
- `resources/js/components/course-detail-sheet.tsx` +2/−5
- `resources/js/components/course-sheet.tsx` +2/−4
- `resources/js/components/courses-sheet.tsx` +2/−4
- `resources/js/components/day-order-sheet.tsx` +2/−4
- `resources/js/components/new-places-sheet.tsx` +7/−5
- `resources/js/components/semester-sheet.tsx` +2/−4
- `resources/js/components/shift-sheet.tsx` +6/−5
- `resources/js/lib/interaction.ts` +11/−0
- `resources/js/types/habit.ts` +0/−8

</details>

## 04.09.2026

### feat: say up front when all five places are taken

`d94ca0b` · **berbahc** · 20:11 Uhr

> Die Grenze von fünf stand nur in der Absage des Servers — auf einem
> Feld des zweiten Schritts, während der Wizard auf dem fünften wartete.
> Jetzt sagt die Seite „Neue Gewohnheit" es vorneweg, mit dem Ausweg:
> eine gefestigte beenden, dann ist Platz.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>3 Dateien · +50/−2</summary>

- `app/Http/Controllers/HabitController.php` +4/−0
- `resources/js/pages/habits/create.tsx` +33/−2
- `tests/Feature/HabitGraduationTest.php` +13/−0

</details>

### fix: the wizard shows a refusal where the field is, not behind it

`dfe6fb2` · **berbahc** · 20:08 Uhr

> Der Wizard steht beim Abschicken auf dem letzten Schritt, die Uhrzeit
> aber auf dem dritten. Kam die Absage des Servers dort an, sah es aus,
> als ginge der Knopf nicht. Jetzt springt der Wizard zum Schritt des
> Feldes — und hält Schritt 3 schon an, wenn die Uhrzeit nach derselben
> Regel wie auf dem Server zu nah an etwas liegt.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>1 Datei · +50/−2</summary>

- `resources/js/components/habit-wizard.tsx` +50/−2

</details>

### feat: look at a suggested place in the day before taking it

`ea361b2` · **berbahc** · 20:01 Uhr

> Ein Vorschlag im Sheet ist eine Zeile — „11:45 · Mo, Mi" sagt nicht,
> was daneben liegt. Jetzt führt „Im Tag ansehen" auf den ersten
> Vorlesungstag, an dem er gälte: Dort liegt er gestrichelt im Raster,
> neben den Kursen, um die es geht, und über dem Raster steht, warum.
> Übernehmen mit einem Druck, oder selbst einordnen — oder ausblenden
> und weiterschauen. Derselbe Ghost wie bei der Einzelanpassung, derselbe
> Weg beim Übernehmen wie im Sheet.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>6 Dateien · +389/−67</summary>

- `app/Http/Controllers/CalendarController.php` +56/−0
- `app/Http/Controllers/NewPlaceController.php` +26/−1
- `resources/js/components/new-places-sheet.tsx` +63/−44
- `resources/js/pages/calendar-day.tsx` +137/−21
- `resources/js/types/semester.ts` +21/−1
- `tests/Feature/NewPlaceTest.php` +86/−0

</details>

### feat: see every course at once behind the timetable button

`970d495` · **berbahc** · 19:50 Uhr

> Der Tag zeigt, was an ihm liegt; wer sechs Kurse hat, will sie
> trotzdem einmal alle sehen und geradewegs ändern oder löschen, ohne
> sechs Tage zu öffnen. Hinter „Alle Kurse ansehen" liegt die Liste,
> Wochentag für Wochentag — und antippen führt in dieselben Sheets wie
> im Tag. Der Kurs hat dafür eine Zeile (`CourseRow`), der Block im Tag
> ist dieselbe Zeile plus Lage.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>11 Dateien · +263/−32</summary>

- `app/Http/Controllers/CalendarController.php` +3/−0
- `app/Support/Timetable.php` +24/−0
- `resources/js/components/course-cancellation-sheet.tsx` +2/−2
- `resources/js/components/course-detail-sheet.tsx` +5/−5
- `resources/js/components/course-sheet.tsx` +2/−7
- `resources/js/components/courses-sheet.tsx` +116/−0
- `resources/js/components/semester-sheet.tsx` +23/−3
- `resources/js/pages/calendar-day.tsx` +5/−6
- `resources/js/pages/calendar.tsx` +45/−1
- `resources/js/types/semester.ts` +19/−8
- `tests/Feature/CalendarSemesterTest.php` +19/−0

</details>

### feat: explain the air, and give it to chains too

`98fba6b` · **berbahc** · 19:47 Uhr

> Die Viertelstunde stand in der Meldung, aber nicht ihr Grund und nicht
> ihre Kante. Jetzt sagt jeder Satz — auf dem Server, im Formular, im
> Pop-up nach dem Ziehen —, wofür die Luft da ist (zum Hinkommen und
> Umschalten) und bis wann beziehungsweise ab wann Platz ist. Das
> Formular rechnet vorher mit derselben Regel und bietet die nächste
> freie Zeit an.
>
> Und die Kette bekommt dieselbe Luft: „Danach" heißt nach dem Ende plus
> einer Viertelstunde, nicht in derselben Minute. Die Zeile bei der Wahl
> der Vorgängerin sagt es dazu, und „danach ab 17:35" rechnet schon so.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>13 Dateien · +153/−87</summary>

- `app/Http/Controllers/HabitController.php` +4/−3
- `app/Models/Habit.php` +19/−6
- `app/Support/SlotConflict.php` +11/−2
- `resources/js/components/habit-wizard.tsx` +1/−0
- `resources/js/components/schedule-picker.tsx` +57/−39
- `resources/js/components/shift-sheet.tsx` +13/−6
- `resources/js/lib/day-grid.ts` +14/−2
- `resources/js/lib/slots.ts` +16/−15
- `resources/js/pages/habits/edit.tsx` +1/−0
- `tests/Feature/CalendarTest.php` +2/−2
- `tests/Feature/HabitChainTest.php` +13/−10
- `tests/Feature/HabitShiftTest.php` +1/−1
- `tests/Feature/HabitSlotTest.php` +1/−1

</details>

### fix: two new places may share a weekday

`acd13ff` · **berbahc** · 19:39 Uhr

> `distinct` auf `places.*.days.*` verglich über alle Plätze hinweg — zwei
> Gewohnheiten am Mittwoch galten als doppelter Wert, und die Übernahme
> scheiterte mit einem englischen Satz. Die Regel ist weg; doppelte Tage
> innerhalb eines Platzes fallen ohnehin zusammen.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>2 Dateien · +26/−1</summary>

- `app/Http/Controllers/NewPlaceController.php` +4/−1
- `tests/Feature/NewPlaceTest.php` +22/−0

</details>

### fix: a course parks every habit beneath it, not the first one twice

`9d7b150` · **berbahc** · 19:13 Uhr

> Ein Vermerk gilt erst ab Semesterbeginn — bis dahin belegt die
> Gewohnheit ihren alten Platz weiter. Die Verdrängung fand sie darum
> jede Runde aufs Neue und kam nie zur zweiten Gewohnheit unter demselben
> Kurs. Was geparkt ist, zählt in der nächsten Runde nicht mehr mit, samt
> seiner Kette.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>2 Dateien · +32/−1</summary>

- `app/Actions/DisplaceHabits.php` +9/−1
- `tests/Feature/HabitDisplacementTest.php` +23/−0

</details>

### feat: air for every hand, and the semester announces itself

`e7e5942` · **berbahc** · 19:12 Uhr

> Vier Entscheidungen aus der Rückschau:
>
> Die Viertelstunde Luft gilt jetzt für jede Hand, nicht nur für die KI.
> Zwei Blöcke direkt hintereinander sind zu eng — von Hand wie im
> Vorschlag, damit der Tag einen Maßstab hat. Nur Kurse untereinander
> dürfen Rücken an Rücken liegen, so legt die Uni sie. Der Satz dazu
> kommt überall aus derselben Stelle; das Tagesverschieben hatte noch
> einen eigenen.
>
> Ist das Semester vorbei, kommt zurück, was seine Kurse verdrängt
> hatten — täglich per Befehl, und beim Öffnen des Kalenders, damit es
> auch ohne Scheduler geschieht. Geholt wird nur, was wirklich frei ist.
>
> Ein Kurs im Oktober wird im September angekündigt: Das Band sagt, ab
> wann der Platz weg ist, und ein neuer lässt sich schon jetzt finden.
> So kommt die Änderung nicht über Nacht.
>
> Der Kurs im Tag hat eine Form statt zweier: Der Block trägt, was die
> Sheets zum Ändern brauchen. Was man antippt, ist das, was man ändert.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>23 Dateien · +297/−159</summary>

- `app/Actions/DisplaceHabits.php` +1/−1
- `app/Console/Commands/RestoreParkedHabits.php` +37/−0
- `app/Http/Controllers/CalendarController.php` +18/−25
- `app/Http/Controllers/HabitDayShiftController.php` +10/−32
- `app/Http/Controllers/NewPlaceController.php` +9/−3
- `app/Models/Course.php` +8/−4
- `app/Models/Habit.php` +5/−5
- `app/Support/DayPlan.php` +12/−6
- `app/Support/SlotConflict.php` +6/−3
- `app/Support/Timetable.php` +10/−6
- `resources/js/components/course-cancellation-sheet.tsx` +3/−3
- `resources/js/components/course-detail-sheet.tsx` +9/−7
- `resources/js/components/course-sheet.tsx` +9/−4
- `resources/js/components/shift-sheet.tsx` +4/−2
- `resources/js/lib/day-grid.ts` +16/−5
- `resources/js/pages/calendar-day.tsx` +7/−14
- `resources/js/pages/calendar.tsx` +14/−5
- `resources/js/types/semester.ts` +12/−18
- `routes/console.php` +4/−0
- `tests/Feature/HabitDisplacementTest.php` +46/−7
- `tests/Feature/HabitShiftTest.php` +6/−1
- `tests/Feature/HabitSlotTest.php` +44/−2
- `tests/Feature/SemesterTest.php` +7/−6

</details>

### refactor: drop what nothing calls

`1cfd1f1` · **berbahc** · 18:58 Uhr

> Eine Zeile im Stundenplan, die niemand fragte (`isEmpty`), und eine, die
> nur er selbst fragte (`hasLecturesOn`, jetzt an Ort und Stelle); ein
> zweites `nextWeekday` im Tagesverschieben, das dasselbe tat wie das in
> `SlotConflict`; die Fenster-Beschriftung einmal statt zweimal; der
> Vermerk-Prüfer privat, weil ihn nur die Gewohnheit selbst fragt; vier
> Exporte, die kein anderes Modul liest.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>9 Dateien · +22/−42</summary>

- `app/Http/Controllers/HabitAdjustmentController.php` +1/−2
- `app/Http/Controllers/HabitDayShiftController.php` +2/−22
- `app/Models/Habit.php` +1/−1
- `app/Support/DayPlan.php` +12/−1
- `app/Support/Timetable.php` +1/−11
- `resources/js/components/shift-sheet.tsx` +1/−1
- `resources/js/hooks/use-day-order.ts` +1/−1
- `resources/js/lib/day-grid.ts` +2/−2
- `tests/Feature/TimetableTest.php` +1/−1

</details>

### feat: a course takes the place only once the semester starts

`8151b37` · **berbahc** · 18:47 Uhr

> Ein Kurs im Oktober nahm im September schon den Platz weg: Die
> Gewohnheit war sofort geparkt, das Band stand im Monat, die KI wollte
> neue Zeiten vorschlagen — für eine Frage, die noch keine Frist hatte.
> Jetzt trägt der Vermerk den Tag, ab dem er gilt: sofort, wenn das
> Semester schon läuft, sonst sein erster Tag. Bis dahin läuft die
> Gewohnheit weiter, wo sie lief, samt Erinnerung; das Band und die
> neuen Plätze kommen mit dem Semester.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>7 Dateien · +147/−11</summary>

- `app/Actions/DisplaceHabits.php` +23/−1
- `app/Http/Controllers/CalendarController.php` +1/−1
- `app/Http/Controllers/DayOrderController.php` +1/−1
- `app/Http/Controllers/NewPlaceController.php` +1/−1
- `app/Models/Habit.php` +41/−7
- `tests/Feature/HabitDisplacementTest.php` +74/−0
- `tests/Feature/NewPlaceTest.php` +6/−0

</details>

### feat: the navigation moves to the bottom on the phone

`f373c00` · **berbahc** · 18:44 Uhr

> Unter dem Tablet-Breakpoint steckte die Seitenleiste hinter einem Knopf,
> und jede Ecke der App war zwei Tipps entfernt. Jetzt liegt sie dort, wo
> der Daumen ist: eine Leiste am unteren Rand mit denselben fünf
> Einträgen, immer sichtbar, ein Tipp pro Ecke. Der Seitenleisten-Knopf
> verschwindet auf dem Telefon; Einstellungen und Abmelden hängen am
> Avatar oben rechts.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>4 Dateien · +124/−4</summary>

- `resources/js/components/app-sidebar-header.tsx` +47/−1
- `resources/js/components/app-sidebar.tsx` +1/−1
- `resources/js/components/mobile-nav.tsx` +67/−0
- `resources/js/layouts/app/app-sidebar-layout.tsx` +9/−2

</details>

### fix: a course in a future semester already claims its slot

`3486e53` · **berbahc** · 18:44 Uhr

> Die Kollisionsprüfung rechnete nur gegen den nächsten Termin eines
> Wochentags. Beginnt das Semester erst nächsten Monat, gab es dort noch
> keine Kurse — die Gewohnheit kam durch, der Kurs verdrängte nichts, und
> am ersten Vorlesungsmontag lagen drei Sachen übereinander. Jetzt zählt
> zusätzlich der erste Termin des Wochentags im Semester; frei ist, was
> an beiden Daten frei ist. Dieselbe Datumswahl für die KI: Die neuen
> Plätze und die Einzelanpassung bekommen nur Fenster, die auch am ersten
> Vorlesungstag offen sind — sonst fiel der Vorschlag beim Übernehmen an
> genau dem Kurs durch, den er nicht sah.
>
> Im Raster teilen sich nur noch Blöcke die Breite, die sich wirklich
> überschneiden; was direkt hintereinander liegt, liegt untereinander.
> Ein Block ohne eigene Dauer sagt jetzt, womit gerechnet wird
> („10:45 – ca. 11:00"), und das Sheet nennt die Annahme.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>13 Dateien · +335/−41</summary>

- `app/Http/Controllers/HabitAdjustmentController.php` +19/−10
- `app/Http/Controllers/NewPlaceController.php` +16/−10
- `app/Support/DayPlan.php` +44/−0
- `app/Support/SlotConflict.php` +35/−5
- `app/Support/Timetable.php` +33/−0
- `resources/js/components/block-sheet.tsx` +13/−1
- `resources/js/components/calendar-block.tsx` +2/−2
- `resources/js/components/day-order-sheet.tsx` +2/−1
- `resources/js/lib/day-grid.ts` +30/−12
- `tests/Feature/HabitAdjustmentTest.php` +33/−0
- `tests/Feature/HabitDisplacementTest.php` +43/−0
- `tests/Feature/HabitSlotTest.php` +23/−0
- `tests/Feature/NewPlaceTest.php` +42/−0

</details>

### fix: a carried block passes a course at full width

`cc63e65` · **berbahc** · 18:25 Uhr

> Sobald der gezogene Block einen Kurs streifte, teilte ihn die
> Spaltenrechnung mit dem Kurs auf — er sprang auf halbe Breite nach
> rechts und wieder zurück. Jetzt wird das Getragene getrennt vom Rest
> gelegt und liegt in voller Breite obenauf; die Kette rutscht mit hoch.
> Alles bleibt eine keyed Liste, damit der Browser den Griff nicht
> verliert.
>
> Das Zeitschild sagt schon beim Ziehen, wenn die Stelle in einem Kurs
> liegt, und das Pop-up nach dem Loslassen sagt es klar: Während des
> Kurses geht das nicht, er rückt nicht.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>3 Dateien · +66/−8</summary>

- `resources/js/components/calendar-block.tsx` +4/−0
- `resources/js/components/day-grid.tsx` +59/−5
- `resources/js/components/shift-sheet.tsx` +3/−3

</details>

### feat: retire the internship as a course kind

`7603028` · **berbahc** · 18:19 Uhr

> Ein Praktikum ist kein Kurs neben anderen: Es nimmt ein halbes Jahr am
> Stück, und dann fällt der Stundenplan als Ganzes weg, nicht eine Zeile
> darin. Vier Arten bleiben, „Sonstiges" bleibt der Ausweg. Was noch als
> Praktikum eingetragen war, wird zu „Sonstiges", damit der Enum-Cast beim
> Laden nicht stolpert.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>4 Dateien · +43/−15</summary>

- `app/Enums/CourseKind.php` +5/−3
- `database/migrations/2026_09_04_181732_retire_praktikum_course_kind.php` +24/−0
- `resources/js/types/semester.ts` +1/−12
- `tests/Feature/SemesterTest.php` +13/−0

</details>

### refactor: month and day only — courses live where they lie

`be650d7` · **berbahc** · 18:18 Uhr

> Zwei Kalender in einem Tab zeigten dasselbe zweimal. Jetzt gibt es nur
> noch den Monat als Ankunft und den Tag als Ebene darunter. Der Stundenplan
> hat keine eigene Ansicht mehr: Kurse werden über den Knopf oben rechts im
> Monat eingetragen und im Tag angefasst — antippen öffnet, ändern, ausfallen
> lassen. Die Wochenansicht und die Semesterseite sind weg, samt ihren
> Strecken; die Datenstrecken bleiben.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>16 Dateien · +453/−778</summary>

- `app/Http/Controllers/CalendarController.php` +82/−4
- `app/Http/Controllers/SemesterController.php` +0/−132
- `app/Models/Course.php` +33/−0
- `resources/js/components/calendar-views.tsx` +0/−58
- `resources/js/components/course-block.tsx` +20/−7
- `resources/js/components/day-grid.tsx` +4/−0
- `resources/js/components/semester-sheet.tsx` +103/−13
- `resources/js/components/week-grid.tsx` +0/−238
- `resources/js/pages/calendar-day.tsx` +57/−1
- `resources/js/pages/calendar-week.tsx` +0/−254
- `resources/js/pages/calendar.tsx` +129/−30
- `routes/web.php` +3/−7
- `tests/Feature/CalendarSemesterTest.php` +4/−26
- `tests/Feature/FeaturePagesTest.php` +0/−1
- `tests/Feature/HabitDisplacementTest.php` +1/−1
- `tests/Feature/SemesterTest.php` +17/−6

</details>

### refactor: one calendar — month, week, day — instead of two side by side

`9a1fba3` · **berbahc** · 18:00 Uhr

> Unter „Kalender" standen zwei Kalender: der Tag mit Gewohnheiten und
> Kursen, daneben eine Semesterseite mit den Kursen allein. Dieselben Dinge,
> zweimal gezeichnet, einmal weniger. Wer einen Kurs eintragen wollte, musste
> dorthin wechseln, wo die Gewohnheiten fehlten — also genau die Stelle
> verlassen, an der sich zeigt, ob der Kurs überhaupt Platz hat.
>
> Jetzt hat der Kalender drei Ebenen, wie jeder Kalender: Monat, Woche, Tag.
> Die Woche ist die mittlere — Kurse und Gewohnheiten auf einem Raster — und
> der Ort, an dem Kurse eingetragen werden: dort, wo man sie sieht. Ein Kurs
> öffnet beim Antippen seine Handlungen, eine Gewohnheit führt in den Tag,
> denn dort wird gehandelt; in der Woche wird geschaut und der Rahmen
> gesetzt. Dieselbe Grammatik wie im Tag: sandfarben mit Oliv für den Kurs,
> heller Grund mit Primärfarbe für die Gewohnheit, durchgezogen bei einer
> Uhrzeit, gestrichelt bei einer Situation.
>
> Das Semester selbst ist keine Ansicht mehr, sondern eine Zeile im Kopf der
> Woche — was gilt, ab wann, und ein Weg, es zu ändern — mit einem Sheet
> dahinter. Ein Zeitraum ist eine Angabe, die man einmal im Semester macht,
> keine Seite, auf der man sich aufhält. Wer noch keins hat, sieht die
> Einladung samt Grund an derselben Stelle.
>
> Die Adresse heißt `calendar/week`; die Daten des Semesters bleiben unter
> `calendar/semester`, denn das sind sie. Der Umschalter sagt „Monat | Woche".
> Der Server gibt der Woche je Wochentag die Gewohnheiten mit ihrer Stelle —
> den nächsten Tag je Wochentag, wo Schlafrahmen und Ausnahmen greifen und
> wohin das Antippen führt.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>13 Dateien · +671/−504</summary>

- `app/Http/Controllers/SemesterController.php` +42/−2
- `resources/js/components/calendar-views.tsx` +8/−9
- `resources/js/components/semester-sheet.tsx` +209/−0
- `resources/js/components/week-grid.tsx` +110/−60
- `resources/js/pages/calendar-semester.tsx` +0/−419
- `resources/js/pages/calendar-week.tsx` +254/−0
- `resources/js/pages/calendar.tsx` +2/−2
- `resources/js/types/semester.ts` +10/−0
- `routes/web.php` +7/−6
- `tests/Feature/CalendarSemesterTest.php` +23/−0
- `tests/Feature/FeaturePagesTest.php` +1/−1
- `tests/Feature/HabitDisplacementTest.php` +1/−1
- `tests/Feature/SemesterTest.php` +4/−4

</details>

### test: walk the invariant over the new paths too

`bee16a6` · **berbahc** · 17:54 Uhr

> Drei Fälle mehr für „auf einer Minute liegt höchstens eine Sache": das
> Übernehmen neuer Plätze auf einen inzwischen vergebenen, das Auflösen einer
> Kette, deren Anker geparkt ist, und der Kurs über einer Gewohnheit — der
> jetzt wirklich landen muss, damit der Fall nicht leer durchgeht.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>1 Datei · +37/−2</summary>

- `tests/Feature/NoOverlapInvariantTest.php` +37/−2

</details>

### feat: let the AI bring the routine back after a semester change

`a8ec9f2` · **berbahc** · 17:52 Uhr

> Was ein Kurs verdrängt hat, bekommt einen neuen Platz vorgeschlagen — nah
> an der alten Zeit, im Rahmen dessen, was die Gewohnheit überhaupt zulässt,
> und alle auf einmal, damit sich die Vorschläge nicht gegenseitig im Weg
> stehen. Was das Modell nicht unterbringt, legt die Person selbst hin; das
> Sheet sagt, was und warum.
>
> `SuggestNewPlaces` ist das Gegenteil von `SuggestBetterAnchor`. Dort ist
> die bisherige Zeit ausdrücklich egal, weil sie nicht funktioniert hat. Hier
> ist sie das Wertvollste, was es gibt — sie hat funktioniert, nur liegt jetzt
> ein Kurs darauf. Der Prompt sagt das als Erstes, und der Server sortiert die
> freien Fenster nach ihrem Abstand zur alten Zeit.
>
> Der Server rechnet, das Modell wählt, der Server prüft nach — wie überall:
>
> - Die freien Fenster kommen je Wochentag, nicht als Schnitt über alle. Eine
>   Mo–Fr-Gewohnheit, die nur montags klemmt, darf als „Di–Fr, gleiche Zeit"
>   zurückkommen; Tage lassen sich weglassen, nie hinzufügen. Eine Routine an
>   vier von fünf Tagen zu halten ist die Routine zu halten.
> - Die Tageszeit aus dem Katalog ist eine Grenze und keine Bitte: Das Modell
>   bekommt für ein Frühstück nur den Morgen zur Wahl, und was es trotzdem
>   mittags hinlegt, fällt durch. Wer um elf aufsteht, frühstückt trotzdem —
>   liegt das ganze Fenster vor dem Aufstehen, rutscht es an den Anfang des
>   Tages und wird zum Hinweis.
> - Was in keinem Fenster Platz hat, geht gar nicht erst zum Modell. Es nach
>   etwas zu fragen, das es nicht geben kann, erzeugt nur einen Vorschlag, den
>   die Nachprüfung wegwirft.
> - Die Plätze werden gegeneinander geprüft, je Wochentag, mit der üblichen
>   Atempause. Und anders als beim Ordnen des Tages wird nicht verworfen, was
>   unvollständig ist — ein halber Erfolg ist hier die Regel, nicht der Fehler.
>
> Beim Übernehmen wird jeder Platz noch einmal geprüft, gegen den Tag, wie er
> jetzt liegt, und die Plätze gegeneinander. Zwischen Vorschlag und Annahme
> liegt eine Entscheidung. Was durchkommt, bekommt seine Zeit, verliert den
> Vermerk und markiert den Vorschlag als übernommen — im selben Gedächtnis wie
> die Einzelanpassung, weil eine abgelehnte Zeit eine abgelehnte Zeit ist.
>
> Das Sheet ist die Schale von „Tag ordnen" mit der Einzelwahl der Anpassung:
> Vorher durchgestrichen, Pfeil, Nachher fett — und ein Haken je Platz, alle
> vorab auf Ja. Abgewählt wird die Vorschlagskennung, nicht die Gewohnheit;
> jeder Aufruf legt neue an, alte Abwahlen treffen nichts mehr, und kein
> Effekt muss etwas zurücksetzen.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>12 Dateien · +1527/−3</summary>

- `app/Ai/Agents/SuggestNewPlaces.php` +372/−0
- `app/Http/Controllers/NewPlaceController.php` +421/−0
- `resources/js/components/flash-notice.tsx` +30/−1
- `resources/js/components/new-places-sheet.tsx` +274/−0
- `resources/js/hooks/use-day-order.ts` +1/−1
- `resources/js/hooks/use-new-places.ts` +75/−0
- `resources/js/pages/calendar-semester.tsx` +17/−1
- `resources/js/types/global.d.ts` +9/−0
- `resources/js/types/semester.ts` +27/−0
- `routes/web.php` +10/−0
- `tests/Feature/NewPlaceTest.php` +289/−0
- `tests/Pest.php` +2/−0

</details>

### refactor: show the semester as a week, not as a list of cards

`ccd8b57` · **berbahc** · 17:44 Uhr

> Zwölf Kurse waren zwölf Karten untereinander, jede mit drei Knöpfen und
> einer Liste von Ausnahmen — die Seite wurde lang, und die Woche war darauf
> nirgends zu sehen. Ein Stundenplan ist aber eine Woche. Jedes Uni-Portal
> zeigt ihn so, und so kennt ihn jeder Studierende: Wochentage als Spalten,
> Stunden als Zeilen, ein Block je Kurs.
>
> `WeekGrid` zeichnet genau das. Die Blöcke tragen dieselbe Grammatik wie im
> Tagesraster — sandfarben, links eine Kante in Oliv —, weil es dieselben
> Dinge sind. Die Stunde ist halb so hoch wie dort: Ein Tag braucht Platz für
> Bedienung, eine Übersicht braucht Platz für den Überblick. Das Wochenende
> erscheint nur, wenn dort etwas liegt; fünf Spalten sind auf einem Telefon
> schon eng.
>
> Was man mit einem Kurs tun kann, steht nicht mehr am Kurs, sondern im
> Sheet, das sich beim Antippen öffnet — das Gegenstück zum Block-Sheet der
> Gewohnheiten: Ändern, ein Ausfall, die Ausnahmen mit ihrem Rückweg, und
> ganz unten leise das Löschen. Im Block selbst steht nur, was er ist.
>
> Kein neuer Tab, keine neue Adresse. Dieselbe Seite, dieselben Props — nur
> die Form.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>4 Dateien · +386/−170</summary>

- `resources/js/components/course-detail-sheet.tsx` +175/−0
- `resources/js/components/course-row.tsx` +0/−128
- `resources/js/components/week-grid.tsx` +188/−0
- `resources/js/pages/calendar-semester.tsx` +23/−42

</details>

### feat: let a course park the habit beneath it instead of being refused

`1204b30` · **berbahc** · 17:37 Uhr

> Alle sechs Monate kommt ein neuer Stundenplan, und seine Kurse landen dort,
> wo längst Gewohnheiten laufen. Bis hierher wehrte sich die App dagegen:
> `StoreCourseRequest` wies den Kurs ab, solange dort eine Gewohnheit lag. Für
> den Einzelfall war das richtig. Zweimal im Jahr ist es falsch herum — wer
> sein Semester einträgt, müsste erst von Hand jede kollidierende Gewohnheit
> wegräumen, also genau die Arbeit leisten, die die App ihm abnehmen soll. Und
> wer dabei aufgibt, verliert die Routine.
>
> Jetzt gewinnt der Kurs, und die Gewohnheit wird geparkt. `displaced_at` ist
> ein Zeitstempel wie `committed_at` und `graduated_at`: Die Gewohnheit bleibt
> aktiv, zählt gegen die Fünfergrenze, steht in der Liste — und behält ihre
> Uhrzeit als Erinnerung daran, wann sie lief. Nur im Tag liegt sie nirgends.
> So liegt weiterhin nichts übereinander, und trotzdem geht nichts verloren.
>
> Platzlos wird sie an genau zwei Stellen, `resolveStart()` und
> `dayAnchorHour()`. Alles Weitere folgt daraus: `DayPlan::occupied()` lässt
> sie aus, das Raster zeichnet sie in die Zone unter dem Tag, und was an ihr
> hängt, fragt seinen Vorgänger und bekommt nichts. Verdrängt wird deshalb
> nur der Anker; die Kette leitet sich ab und kommt beim Zurücklegen in einem
> einzigen Schreibvorgang mit.
>
> Drei Stellen wussten es trotzdem nicht von selbst, und eine davon war
> gefährlich: Die Erinnerungen lesen `scheduled_time` roh aus der Datenbank,
> nicht über `startsAt()` — eine geparkte Gewohnheit hätte weiter zur alten
> Zeit geklingelt, während dort jetzt eine Vorlesung läuft. Dafür gibt es den
> Scope `placed()`, an beiden rohen Abfragen. Die Liste bekommt eine dritte
> Gruppe, „Braucht einen neuen Platz", vor „Steht heute an"; und die Zeile
> sagt „braucht einen neuen Platz · lief bisher 10:15".
>
> Vier Wege setzen eine Uhrzeit und nehmen damit den Vermerk weg:
> bearbeiten, anpassen, im Raster ziehen, den Tag ordnen — wobei der letzte
> geparkte Gewohnheiten gar nicht erst anfasst. Und `ReleaseChainedHabits`
> vererbt den Vermerk mit der Uhrzeit: Sonst erbte ein Nachfolger eine
> geparkte Zeit als lebendige und läge in genau der Vorlesung, aus der sein
> Vorgänger gerade gewichen ist.
>
> Verschwindet der Kurs wieder, kommt die Gewohnheit zurück — aber nur
> dorthin, wo inzwischen nichts anderes liegt. Geprüft wird vor dem
> Zurückholen. Ein Ausfall an einem Datum holt sie nicht zurück: Die Woche
> gilt weiter, ein Ausfall ist kein Umzug.
>
> Der Katalog weiß jetzt, wozu eine Gewohnheit gehört: `dayBand()` gibt fünf
> Vorlagen eine Tageszeit — Frühstück, Mittag, Abend, der bildschirmfreie
> Abend, das Planen des Uni-Tags. Nur dort, wo das Wort selbst eine Tageszeit
> trägt; Joggen oder Lernen eine Grenze zu geben hieße, etwas zu behaupten,
> das nicht stimmt. Gelesen wird das Fenster noch von niemandem — es ist die
> Grenze, in der die KI in der nächsten Runde neue Plätze suchen wird, damit
> das Frühstück nicht mittags landet, nur weil dort Platz ist.
>
> Zwei Tests drehen sich um, alle anderen bleiben — und die Invariante hält:
> Eine geparkte Gewohnheit belegt nichts, also liegt weiterhin auf keiner
> Minute mehr als eine Sache.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>25 Dateien · +922/−119</summary>

- `app/Actions/DisplaceHabits.php` +76/−0
- `app/Actions/ReleaseChainedHabits.php` +8/−0
- `app/Actions/RestoreDisplacedHabits.php` +55/−0
- `app/Enums/HabitTemplate.php` +31/−0
- `app/Http/Controllers/CourseController.php` +95/−6
- `app/Http/Controllers/DayOrderController.php` +8/−1
- `app/Http/Controllers/HabitAdjustmentController.php` +2/−0
- `app/Http/Controllers/HabitController.php` +11/−2
- `app/Http/Controllers/HabitDayShiftController.php` +1/−0
- `app/Http/Controllers/HabitGraduationController.php` +6/−0
- `app/Http/Controllers/HabitReminderController.php` +3/−0
- `app/Http/Controllers/SemesterController.php` +52/−35
- `app/Http/Middleware/HandleInertiaRequests.php` +4/−0
- `app/Http/Requests/StoreCourseRequest.php` +6/−51
- `app/Models/Habit.php` +90/−1
- `database/migrations/2026_09_04_172013_add_displaced_at_to_habits_table.php` +47/−0
- `resources/js/components/day-grid.tsx` +9/−1
- `resources/js/components/flash-notice.tsx` +61/−2
- `resources/js/pages/calendar-semester.tsx` +38/−0
- `resources/js/pages/habits/index.tsx` +5/−0
- `resources/js/types/global.d.ts` +17/−0
- `resources/js/types/habit.ts` +5/−1
- `resources/js/types/semester.ts` +15/−0
- `tests/Feature/HabitDisplacementTest.php` +256/−0
- `tests/Feature/HabitSlotTest.php` +21/−19

</details>

### fix: show the logo that stands out, not the one that matches

`9c96d84` · **berbahc** · 17:01 Uhr

> Im hellen Modus lief die helle Kachel, im dunklen die dunkle — die Marke hatte
> also überall dieselbe Farbe wie ihr Untergrund und verschwand darin. Dasselbe
> in der Tab-Leiste und auf der dunklen Hälfte der Anmeldeseite.
>
> Die Ursache steckte im Dateinamen. `logo-light.webp` konnte zweierlei heißen:
> die Kachel ist hell, oder sie gehört in den hellen Modus. Der Code las das
> zweite, die Datei meinte das erste, und beim Lesen fiel es niemandem auf, weil
> beide Lesarten plausibel sind.
>
> Deshalb nicht nur die Zuordnung getauscht, sondern die Namen nach ihrem Zweck
> gesetzt:
>
>   logo-on-light.webp   die dunkle Kachel, für hellen Grund
>   logo-on-dark.webp    die helle Kachel, für dunklen Grund
>   icon-on-light.png    dasselbe für die Tab-Leiste
>   icon-on-dark.png
>
> Damit steht am Aufrufort dasselbe Wort wie in der Bedingung — `light` neben
> `prefers-color-scheme: light`, `on-light` neben `block dark:hidden`. Wer es
> wieder verdreht, sieht es beim Hinschreiben.
>
> Die feste Kachel auf der dunklen Hälfte der Anmeldeseite ist mitgewandert: Die
> Fläche ist dort unabhängig vom Theme dunkel, also gehört die helle Kachel
> darauf — vorher stand dort schwarz auf schwarz.
>
> Und ein eigener Fehler von gestern mit: Der Vorab-Hintergrund im Blade, der
> das Weißblitzen vor dem Stylesheet verhindert, stand noch auf den alten
> Canvas-Farben (#FDF9F8 / #17130F). Er trägt jetzt die neuen (#F3EDE4 /
> #100E0B) und einen Satz dazu, dass er mit `--canvas` mitwandern muss.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>7 Dateien · +24/−12</summary>

- `public/{icon-light.png => icon-on-dark.png}` +0/−0
- `public/{icon-dark.png => icon-on-light.png}` +0/−0
- `public/{logo-light.webp => logo-on-dark.webp}` +0/−0
- `public/{logo-dark.webp => logo-on-light.webp}` +0/−0
- `resources/js/components/app-logo-icon.tsx` +10/−3
- `resources/js/layouts/auth/auth-split-layout.tsx` +3/−3
- `resources/views/app.blade.php` +11/−6

</details>

### fix: close the six ways two things could still land on one minute

`515a913` · **berbahc** · 16:57 Uhr

> Die Frage war „passt das soweit?" — und die Antwort auf den letzten Satz war
> nein. Die Prüfung hing an den Formularen, nicht am Tag. Wer sie umging, kam
> durch; und es gab mehr Wege daran vorbei, als auf meiner Liste standen.
>
> Sechs, von denen vier erst beim Nachsehen auffielen:
>
> 1. **Tagesordnung übernehmen.** Der Vorschlag wurde geprüft, das Übernehmen
>    nicht — `store()` validierte nur das Zeitformat und schrieb dann, was
>    ankam. Jetzt wird geprüft, was ankommt, und zusätzlich, ob sich die
>    geordneten Gewohnheiten untereinander überschneiden. Das kann
>    `SlotConflict` nicht sehen: Dort zählen sie bewusst nicht mit, weil sie
>    sich ja gerade bewegen.
>
> 2. **Kurs über eine bestehende Gewohnheit.** Der Kurs verglich sich nur mit
>    anderen Kursen. Die Richtung ist hier eine andere als sonst — der Kurs ist
>    die Tatsache, die Gewohnheit das Bewegliche. Sie ungefragt zu verschieben
>    wäre ein Eingriff in einen Plan, den sich jemand vorgenommen hat; sie
>    überdecken zu lassen hieße, zwei Dinge auf eine Minute zu legen. Also die
>    dritte Möglichkeit: Der Kurs wartet, und der Satz sagt, was zuerst zu tun
>    ist.
>
> 3. **Beendete Gewohnheit wieder aufnehmen.** Geprüft wurde nur die
>    Fünfergrenze. Ihr alter Platz kann längst vergeben sein.
>
> 4. **Semesterzeitraum verschieben.** Die Hintertür: Nicht der Kurs zieht um,
>    sondern der Zeitraum um ihn herum. Kurse, die gestern nicht galten, gelten
>    heute — und liegen auf Gewohnheiten, die es damals noch nicht gab.
>
> 5. **Zwei Gewohnheiten an derselben.** Ein Altfehler, kein neuer: Beide
>    beginnen, wenn die vorige endet, also zur selben Minute — und das schon
>    ohne jedes Auflösen. `Habit::spansFrom()` folgte ohnehin nur der ersten;
>    die Regel schreibt jetzt fest, wovon die Rechnung längst ausging. Eine
>    Kette ist eine Reihe, kein Fächer.
>
> 6. **Kette auflösen.** Alle Nachfolger erbten den Anker und lagen danach
>    aufeinander. Jetzt erbt der erste, die übrigen hängen sich an ihn. Neu
>    entstehen kann so etwas nach (5) nicht mehr — Zeilen aus der Zeit davor
>    gibt es aber, und die sollen in eine Reihe fallen statt aufeinander.
>
> `App\Support\SlotConflict` trägt die Rechnung und den Satz. Sie steht jetzt
> an sieben Stellen und muss überall dieselbe Antwort geben; sieben Rechnungen
> wären sieben Wahrheiten über denselben Tag. Der Unterschied zwischen Kurs und
> Gewohnheit steckt nicht in der Rechnung, sondern im Ausweg danach — deshalb
> steht der Text dort und nicht bei den Aufrufern.
>
> `NoOverlapInvariantTest` prüft nicht mehr die Abweisungen, sondern den
> Zustand: Jeder Weg wird abgefahren, danach wird im Tag nachgesehen. Das ist
> der Unterschied zwischen „diese Abweisung funktioniert" und „es gibt keinen
> Weg vorbei".
>
> Der Test war beim ersten Anlauf selbst eine Falle. Die Fälle standen als
> `fn () => function (...)` im Datensatz; der Aufruf gab die innere Funktion
> zurück, statt sie auszuführen. Zehn grüne Fälle, die nichts prüften — und sie
> blieben grün, als ich zur Gegenprobe eine Wache entfernte. Jetzt sind es
> Namen und ein `match`, und die Gegenprobe schlägt fehl, wie sie soll.
>
> Nicht gesperrt bleibt die Situation („nach der Vorlesung"). Sie hat keinen
> Zeitpunkt, mit dem sich kollidieren ließe, und ihre Ankerstunde ist eine
> Sortierhilfe, keine Zusage. Sie zu sperren hieße unter anderem, „nach der
> Vorlesung" zu verbieten, weil um elf eine Vorlesung läuft. Im Raster liegen
> solche Blöcke nebeneinander statt übereinander — `placeBlocks` teilt die
> Breite —, es wird also nichts verdeckt und nichts behauptet.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>11 Dateien · +897/−116</summary>

- `app/Actions/ReleaseChainedHabits.php` +19/−2
- `app/Http/Controllers/DayOrderController.php` +89/−0
- `app/Http/Controllers/HabitGraduationController.php` +44/−0
- `app/Http/Controllers/SemesterController.php` +49/−0
- `app/Http/Requests/AdjustHabitRequest.php` +15/−0
- `app/Http/Requests/Concerns/ChecksDayPlan.php` +13/−114
- `app/Http/Requests/HabitFormRequest.php` +24/−0
- `app/Http/Requests/StoreCourseRequest.php` +50/−0
- `app/Support/SlotConflict.php` +157/−0
- `tests/Feature/HabitSlotTest.php` +216/−0
- `tests/Feature/NoOverlapInvariantTest.php` +221/−0

</details>

### fix: stop telling people to move a lecture they cannot move

`0c36e48` · **berbahc** · 16:27 Uhr

> Beim Ziehen einer Gewohnheit auf einen Kurs stand „Verschiebe die zuerst,
> dann ist hier Platz." Das ist ein Ausweg, den es nicht gibt: Ein Kurs kommt
> von der Uni und rückt nicht. Wer dem Satz folgt, sucht einen Knopf, den die
> App nie hatte.
>
> Der Server unterschied die beiden Fälle schon; das Sheet im Browser nicht.
> Es zeigt den Konflikt, bevor der Server überhaupt gefragt wird — und
> `collisionOf()` gab nur den Titel zurück, nicht die Art. Jetzt gibt es
> beides zurück, und das Sheet wählt den Satz danach:
>
>   Kurs        „Dort liegt heute ‚Mathe 1' aus deinem Semesterplan. Such der
>                Gewohnheit eine andere Zeit — der Kurs rückt nicht."
>   Gewohnheit  „‚Joggen gehen' liegt heute schon dort. Verschiebe die zuerst,
>                dann ist hier Platz."
>
> Derselbe Weg über die Verabredung sagte bisher nur neutral, was im Weg
> liegt. Er unterscheidet jetzt ebenfalls — nicht weil die alte Meldung falsch
> war, sondern weil bei einer eigenen Gewohnheit der Hinweis fehlte, den es
> dort zu geben gibt.
>
> Und ein Wort ist überall dasselbe geworden: „Kurs", nicht „Vorlesung". Eine
> Übung, ein Seminar und ein Praktikum weichen genauso wenig, und der Katalog
> kennt alle vier.
>
> Damit steht „Verschiebe die zuerst" nur noch im Zweig für Gewohnheiten —
> serverseitig an drei Stellen, im Browser an einer.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>7 Dateien · +103/−21</summary>

- `app/Http/Controllers/HabitDayShiftController.php` +5/−4
- `app/Http/Requests/Concerns/ChecksDayPlan.php` +6/−5
- `app/Http/Requests/ShiftHabitDayRequest.php` +12/−4
- `resources/js/components/shift-sheet.tsx` +19/−4
- `resources/js/lib/day-grid.ts` +11/−2
- `tests/Feature/HabitShiftTest.php` +1/−0
- `tests/Feature/HabitSlotTest.php` +49/−2

</details>

### feat: refuse a habit that would land in a lecture, on every path that sets a time

`590575a` · **berbahc** · 16:18 Uhr

> Time-Blocking heißt, dass jede Sache eine Spanne hat und zwei Spannen sich
> nicht überschneiden. Beim Ziehen im Raster galt das schon: Wer einen Block
> auf eine Vorlesung zog, bekam eine Absage. Wer dieselbe Uhrzeit ins
> Formular tippte, kam durch — dort prüfte der Server gar nichts, weder
> gegen Kurse noch gegen andere Gewohnheiten.
>
> Der Plan widersprach sich damit an genau der Stelle, an der die App ihr
> Versprechen einlöst. Jetzt prüfen alle drei Wege, auf denen eine Uhrzeit
> gesetzt wird, dasselbe: anlegen, bearbeiten, einen KI-Vorschlag übernehmen.
>
> `ChecksDayPlan` ist die eine Prüfung dahinter. Sie geht jeden gewählten
> Wochentag einzeln durch, weil der Schlafrahmen am Wochentag hängt und die
> Ausnahmen des Stundenplans am Datum — „montags" allein hat keinen Rahmen.
> Geladen wird einmal für alle sieben möglichen Tage, Tagesausnahmen
> eingeschlossen; je Tag nachzuladen wären sieben Abfragen für eine Antwort.
>
> Zwei Sätze statt einem, weil die beiden Fälle verschieden sind: Eine
> Vorlesung rückt nicht, und der Satz darf deshalb keinen Ausweg anbieten,
> den es nicht gibt — er nennt die Spanne und sagt, dass die Gewohnheit eine
> andere Zeit braucht. Eine eigene Gewohnheit lässt sich dagegen verschieben,
> und der Satz sagt genau das.
>
> `Habit::spansFrom()` ist aus `HabitDayShiftController` ins Modell gezogen.
> Eine Kette rückt mit, also belegt eine Uhrzeit nicht eine Spanne, sondern
> alle — und ein Nachfolger, der dabei in einer Vorlesung landet, ist derselbe
> Widerspruch. Zwei Rechnungen dafür wären zwei Wahrheiten über denselben Tag;
> jetzt teilen Ziehen und Formular eine.
>
> Der Hinweis im Formular kannte bisher nur andere Gewohnheiten. Er kennt
> jetzt auch die Kurse — sonst sagte er „frei" und das Speichern wiese ab, und
> das wäre schlimmer als gar kein Hinweis.
>
> Zwei Grenzen bleiben bewusst offen:
>
> - Eine Situation („nach der Vorlesung") wird nicht gesperrt. Sie hat keinen
>   Zeitpunkt, mit dem sich kollidieren ließe; sie zu blockieren hieße, eine
>   Genauigkeit zu behaupten, die sie nicht hat.
> - Außerhalb der Vorlesungszeit belegt ein Kurs nichts, auch nicht hier.
>
> Damit ist auch die Uneinigkeit zwischen Formular und Raster aufgelöst: Zwei
> Gewohnheiten dürfen sich nirgends mehr überschneiden. Der Kommentar in
> `lib/slots.ts` („ein Hinweis, keine Sperre") beschreibt ab jetzt nur noch,
> was der Client tut, bevor der Server dasselbe entscheidet.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>7 Dateien · +537/−48</summary>

- `app/Http/Controllers/HabitController.php` +37/−1
- `app/Http/Controllers/HabitDayShiftController.php` +2/−44
- `app/Http/Requests/AdjustHabitRequest.php` +32/−2
- `app/Http/Requests/Concerns/ChecksDayPlan.php` +162/−0
- `app/Http/Requests/HabitFormRequest.php` +31/−1
- `app/Models/Habit.php` +47/−0
- `tests/Feature/HabitSlotTest.php` +226/−0

</details>

### style: give the day a ladder of surfaces, in both modes

`c2f2245` · **berbahc** · 16:04 Uhr

> Alle Flächen lagen fast aufeinander. Eine Karte hob sich vom Seitengrund um
> ΔL* 1.8 ab, ein Gewohnheitsblock von der Karte um 4.0 — unter der Schwelle,
> ab der das Auge zwei Flächen als zwei liest. Der Tag sah aus wie ein Blatt
> Papier mit Text darauf, nicht wie ein Raster mit Blöcken darin.
>
> Jetzt gibt es vier Stufen, und jede sagt etwas:
>
>   Seitengrund → Karte    ΔL* 6   „hier beginnt ein Bereich"
>   Karte       → track    ΔL* 8   „das ist eine Gewohnheit"
>   track       → accent   ΔL* 4   „die ist erledigt"
>   accent      → sand     ΔL* 4   „das ist kein Vorsatz, das ist Uni"
>
> Gemessen in ΔL* und nicht im WCAG-Verhältnis: Für große Flächen sagt das
> Verhältnis wenig — 1.2:1 kann deutlich sichtbar sein —, die wahrgenommene
> Helligkeit dagegen genau das, was hier gemeint ist. Für Text gilt weiter
> WCAG AA.
>
> Im Dunkeln waren die Blöcke dunkler als sinnvoll: Grund #17130f, Blöcke
> #262019 und #332c24, also ΔL* 2.5 und 8.3 zur Karte. Gerade im Dunkeln
> sollen sie aber tragen. Der Grund liegt jetzt tiefer, die Blöcke deutlich
> höher, und die Leiter steigt durch: L* 4.1 → 12.6 → 19.9 → 24.6 → 29.2.
>
> Beide Modi folgen damit derselben Regel — was vorn liegt, ist heller. Der
> Umschalter fühlt sich dadurch wie eine Beleuchtung an und nicht wie eine
> zweite App.
>
> Zwei Textfarben waren schlicht nicht lesbar und sind es jetzt: `faintest`
> (die Stundenzahlen im Raster, die Ziffern künftiger Tage) von 2.29:1 auf
> 4.52:1 im Hellen und von 2.40:1 auf 4.73:1 im Dunkeln; `olive-mid` (die
> Zeile über dem Kursblock) von 2.85:1 auf 4.54:1. Der Feldrahmen erfüllt
> jetzt in beiden Modi die 3:1 aus WCAG 1.4.11, im Dunkeln vorher 1.6:1.
>
> `accent` ist nicht mehr derselbe Wert wie `sand`. Es trägt den
> Überfahr-Zustand und die erledigte Gewohnheit und musste zwischen die
> beiden anderen Stufen passen — als ein Wert für beides ließ sich die Leiter
> nicht bauen.
>
> Die Icon-Kachel im Kursblock entfällt. Eine Fläche unter dem Symbol müsste
> heller sein als der Block, und was im Hellen heller ist, ist im Dunkeln
> dunkler — sie hätte sich beim Umschalten umgedreht. Der gefüllte Block
> trägt die Zugehörigkeit ohnehin.
>
> Damit sind zwei Punkte geschlossen, die designsprache.md §11.2 und §11.4
> ausdrücklich offen gelassen hatten: die Kontrastfrage und die „eigene
> Runde" für den dunklen Modus. Beides auf ausdrückliche Ansage.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>2 Dateien · +98/−52</summary>

- `resources/css/app.css` +84/−41
- `resources/js/components/course-block.tsx` +14/−11

</details>

### fix: give the course block its kind, so the day view stops rendering nothing

`5ff2bba` · **berbahc** · 15:54 Uhr

> Die Tagesansicht blieb weiß, sobald an dem Tag ein Kurs lag. Im Browser
> stand React-Fehler #130: eine Komponente war beim Rendern `undefined`.
>
> Die Ursache liegt eine Ebene tiefer. Das Raster unterscheidet Kurs und
> Gewohnheit über ein Feld `kind`, und das wird an zwei Stellen vergeben —
> in `CalendarController::block()` für die Gewohnheit und in
> `Timetable::coursesOn()` für den Kurs. Die zweite fehlte. Damit fiel jeder
> Kursblock in den Zweig für Gewohnheiten, dort wurde `BEHAVIOR_ICONS[undefined]`
> nachgeschlagen, und ein `undefined` als Komponente bringt React dazu, den
> gesamten Baum abzubrechen — nicht nur den einen Block. Deshalb war der
> ganze Tag leer und nicht bloß der Kurs.
>
> Warum das durch alle Prüfungen kam: TypeScript beschreibt die Props, aber es
> prüft sie nicht. Sie kommen als JSON vom Server; die Schnittstelle ist ein
> Versprechen, keine Zusicherung. Und die bestehenden Tests fragten den
> Kursblock nach Titel, Spanne, Art und Ort — nach allem also außer nach dem
> einen Feld, das die Zeichnung überhaupt erst in den richtigen Zweig führt.
>
> Beide Lücken sind jetzt zu: `coursesOn()` gibt `kind` mit, und zwei Tests
> nageln es fest — einer am Stundenplan, einer an den Props der Tagesansicht,
> und dieser prüft beide Arten nebeneinander. Ein Feld, das an zwei Stellen
> vergeben wird, muss auch an zwei Stellen geprüft werden.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>3 Dateien · +36/−1</summary>

- `app/Support/Timetable.php` +6/−1
- `tests/Feature/CalendarSemesterTest.php` +11/−0
- `tests/Feature/TimetableTest.php` +19/−0

</details>

### refactor: fold the semester into the calendar instead of giving it a tab

`30d4654` · **berbahc** · 15:45 Uhr

> Der Semesterplan hatte einen eigenen Eintrag in der Navigation, als wäre er
> eine sechste Ecke der App. Er ist aber keine: Er sagt, wann im Tag nichts
> geht — und das gehört dorthin, wo der Tag steht.
>
> Der Kalender hat jetzt zwei Ansichten, „Monat" und „Semester", über einen
> Umschalter im Kopf. Zwei Adressen und kein Client-Zustand, wie beim
> einzelnen Tag auch: `calendar/semester` übersteht ein Neuladen und lässt
> sich teilen. Alle Strecken darunter ziehen mit (`calendar/semester/courses`
> und so fort) und stehen vor `calendar/{date}` — das Datumsmuster ließe
> „semester" zwar ohnehin nicht durch, aber die Reihenfolge soll nicht von
> einer Regel abhängen, die jemand später lockert.
>
> Die leise Zeile unter dem Monat bleibt nur noch als Einladung, solange kein
> Plan steht. Steht einer, führt der Umschalter ohnehin hin; zwei Wege zum
> selben Ziel auf einem Bildschirm wären Lärm. Was der Umschalter allein nicht
> sagt, ist das Warum — deshalb bleibt der Satz, bis er beantwortet ist. Der
> Monat bekommt dafür nur noch ein `hasSemester` statt Titel und Kurszahl:
> Alles Weitere steht eine Ansicht daneben.
>
> Der Kursblock im Stundenraster ist jetzt sandfarben gefüllt statt hohl. Die
> hohle Form unterschied ihn zwar von der Gewohnheit, aber erst beim
> Hinsehen; gefüllt fällt er im Raster sofort als andere Art auf. Sand und
> keine zweite Farbfamilie — die hätte den Tag in zwei Kalender zerlegt. Alles
> Übrige bleibt: keine Hakenspalte, ein `div` statt eines Knopfes, nicht
> ziehbar.
>
> Und der Mangel, der das alles ausgelöst hat: Ein Semester, das noch nicht
> angefangen hat, war stumm. Der Plan stand voller Kurse, im Kalender
> erschien nichts, und die App sagte nicht, warum — man sucht den Fehler dann
> in der Software, obwohl die Vorlesungszeit schlicht erst im Oktober
> beginnt. Die Semesteransicht sagt es jetzt, in beide Richtungen: „beginnt
> am 1. Oktober 2026" oder „ist vorbei". Kein Warnton — es ist nichts falsch,
> es ist nur noch nicht so weit.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>15 Dateien · +209/−136</summary>

- `app/Http/Controllers/CalendarController.php` +4/−9
- `app/Http/Controllers/SemesterController.php` +17/−10
- `resources/js/components/app-sidebar.tsx` +4/−17
- `resources/js/components/calendar-views.tsx` +59/−0
- `resources/js/components/course-block.tsx` +12/−9
- `resources/js/components/course-cancellation-sheet.tsx` +1/−1
- `resources/js/components/course-row.tsx` +2/−2
- `resources/js/components/course-sheet.tsx` +1/−1
- `resources/js/pages/{semester.tsx => calendar-semester.tsx}` +26/−9
- `resources/js/pages/calendar.tsx` +19/−25
- `resources/js/types/semester.ts` +5/−1
- `routes/web.php` +32/−24
- `tests/Feature/CalendarSemesterTest.php` +3/−4
- `tests/Feature/FeaturePagesTest.php` +1/−1
- `tests/Feature/SemesterTest.php` +23/−23

</details>

## 03.09.2026

### feat: let the timetable block the day, so the AI stops planning into lectures

`5aba05e` · **berbahc** · 20:22 Uhr

> Etappe 1 legte den Semesterplan an, las ihn aber nirgends. Hier wirkt er.
>
> `App\Support\Timetable` ist der einzige Ort, an dem aus wöchentlichen
> Kursen und datierten Ausnahmen die Belegung eines konkreten Tages wird.
> Einmal je Anfrage gebaut, beliebig oft je Datum befragt — der Monat fragt
> zweiundvierzigmal und kostet trotzdem keine zusätzliche Abfrage.
>
> Alle sechs `DayPlan`-Stellen bekommen die Kursblöcke, und zwar in einem
> Commit. Gäbe ein Pfad sie mit und ein anderer nicht, säße dieselbe
> Gewohnheit auf zwei Wegen an zwei Minuten — genau der Widerspruch,
> gegen den `DayPlan` geschrieben wurde.
>
> Damit wird die KI besser, ohne dass ein Agent etwas davon erfährt:
> `SuggestBetterAnchor` bekommt seine freien Fenster aus `DayPlan`, und wo
> eine Vorlesung liegt, ist kein Fenster mehr. Was das Modell trotzdem
> dorthin legt, fällt in `fitsInFreeWindow()` durch — nicht weil der Agent
> es wüsste, sondern weil die Zeit in keinem Fenster steht, das er bekam.
>
> Eine Ausnahme brauchte doch eine Zeile im Agenten: `SuggestDayOrder`
> bekommt keine freien Fenster, sondern nur den Rahmen und die
> Gewohnheiten. Ohne eine ausdrückliche Liste des Belegten hätte er
> weiterhin in Vorlesungen geordnet. Er bekommt sie jetzt als `busy`, und
> was trotzdem hineinragt, fällt bei der Prüfung durch — dann fehlt eine
> Gewohnheit, und ein unvollständiger Tag ist kein Vorschlag. Die
> Vorab-Rechnung „passt der Tag überhaupt" zieht die Vorlesungsminuten ab,
> damit ein von Kursen ausgefüllter Tag gar nicht erst gefragt wird.
>
> Im Tag liegen Kurse auf derselben Achse wie Gewohnheiten, tragen aber ein
> eigenes Prop statt zehn nullbarer Felder an `blocks` — jede Stelle, die
> einen Block anfasst, hätte sich sonst gegen eine Art verteidigen müssen,
> die sie nicht behandeln kann, und eine vergessene Wache ist ein Absturz.
> `placeBlocks` wurde dafür generisch: Beide Arten müssen durch denselben
> Durchgang, sonst zeichnete die Spaltenverteilung eine Gewohnheit auf eine
> Vorlesung.
>
> Der Kursblock sagt durch Weglassen, was er ist: keine Hakenspalte, hohl
> statt gefüllt, ein `div` statt eines Knopfes. Die linke Kante ist
> durchgezogen, weil eine Vorlesung eine echte Uhrzeit hat, und
> `olive-mid` statt `primary`, weil `primary` dort „das hast du dir
> vorgenommen" bedeutet — und das hat sich niemand vorgenommen. Ein
> ausgefallener Kurs fehlt einfach; die Absage schenkt einen freien
> Vormittag, keinen durchgestrichenen.
>
> Im Monat bekommt ein Vorlesungstag eine Haarlinie unter der Punktreihe —
> eine Linie und kein Punkt, damit sie nicht mitgezählt wird, und leiser
> als der offene Punkt, damit sie den Inhalt nie überstimmt. Für die
> Zukunft wird sie nicht gedämpft: Ein künftiger Tag hat noch kein
> Ergebnis, eine Vorlesung in drei Wochen ist aber genauso Tatsache wie
> eine von gestern. So zeigt der Monat, was er sonst nicht kann — wo die
> Vorlesungszeit aufhört. Darunter eine leise Zeile statt eines Knopfes:
> ohne Plan die Einladung samt Grund, mit Plan sein Name. Ein dauerhafter
> Aufruf zum Eintragen wäre für alle da, die gar nicht studieren.
>
> Zwei Ränder, die dabei auffielen:
>
> - Das Raster dehnt sich jetzt über die Kurse. Eine Abendvorlesung unter
>   einer frühen Schlafenszeit belegt auf dem Server Zeit; zeichnete das
>   Raster sie nicht, wäre das genau der Widerspruch zwischen Rechnung und
>   Bild, den dieser Kalender vermeiden soll.
> - Der Konfliktsatz beim Ziehen unterscheidet die beiden Fälle. „Verschiebe
>   die zuerst" ist bei einer Vorlesung kein Ausweg — dort heißt der Satz,
>   dass die Gewohnheit eine andere Zeit braucht.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>22 Dateien · +1268/−76</summary>

- `app/Ai/Agents/SuggestDayOrder.php` +44/−2
- `app/Http/Controllers/CalendarController.php` +35/−5
- `app/Http/Controllers/DayOrderController.php` +41/−8
- `app/Http/Controllers/HabitAdjustmentController.php` +6/−0
- `app/Http/Controllers/HabitDayShiftController.php` +32/−7
- `app/Http/Requests/ShiftHabitDayRequest.php` +5/−0
- `app/Support/AppointmentFit.php` +11/−6
- `app/Support/Timetable.php` +272/−0
- `resources/js/components/calendar-block.tsx` +1/−1
- `resources/js/components/course-block.tsx` +81/−0
- `resources/js/components/day-grid.tsx` +62/−30
- `resources/js/components/month-grid.tsx` +26/−4
- `resources/js/lib/day-grid.ts` +41/−11
- `resources/js/pages/calendar-day.tsx` +15/−2
- `resources/js/pages/calendar.tsx` +27/−0
- `resources/js/types/habit.ts` +4/−0
- `resources/js/types/semester.ts` +23/−0
- `tests/Feature/CalendarSemesterTest.php` +120/−0
- `tests/Feature/DayOrderTest.php` +102/−0
- `tests/Feature/HabitAdjustmentTest.php` +65/−0
- `tests/Feature/HabitShiftTest.php` +83/−0
- `tests/Feature/TimetableTest.php` +172/−0

</details>

### feat: give the semester its own plan, so the day knows when nothing goes

`c23756a` · **berbahc** · 20:11 Uhr

> Der Schlafplan sagt, wann der Tag anfängt und aufhört. Für wen studiert,
> fehlte darin ein zweiter Rahmen: Zwischen 10:00 und 11:30 liegt dienstags
> eine Vorlesung und donnerstags nicht. Die App wusste davon nichts und
> schlug Zeitpunkte vor, an denen jemand im Hörsaal saß.
>
> Diese Etappe legt den Plan an — sie liest ihn noch nirgends. Der Kalender
> und `DayPlan` folgen als Nächstes; erst dann wirkt er.
>
> Drei Tabellen statt einer:
>
> - `semesters` trägt den Zeitraum, weil er allen Kursen gemeinsam ist.
>   Auf dem Kurs hätte man 13.10.–07.02. sechsmal eingetippt und sechsfach
>   gepflegt, und die vorlesungsfreie Zeit wäre sechs unabhängige Abläufe
>   statt eines Zustands.
> - `courses` ist eine wöchentliche Wiederholung, kein Termin: sechs Kurse
>   statt neunzig Zeilen, und ein Raumwechsel ist eine Änderung statt
>   neunzig. Eine Veranstaltung an zwei Tagen sind zwei Zeilen mit demselben
>   Titel — der Kalender zeichnet Blöcke, keine Module.
> - `course_exceptions` ist dasselbe Muster wie `habit_day_shifts`, mit
>   einem Unterschied: Dort reicht eine Uhrzeit, hier nicht. Ein
>   Nachholtermin liegt an einem anderen Tag, nicht bloß zu einer anderen
>   Stunde. Deshalb zwei Zeiten, und beide dürfen fehlen — dann fällt der
>   Kurs aus. Die vierte Kombination (Ausfall an einem Tag, an dem der Kurs
>   ohnehin nicht läuft) weist der Validator ab; sie wäre eine Zeile ohne
>   Wirkung.
>
> Es gelten dieselben Grenzen wie beim Planen überhaupt: Nichts reicht über
> Mitternacht, und zwei Kurse am selben Wochentag liegen nicht
> übereinander. Geprüft wird mit demselben halboffenen Vergleich wie in
> `DayPlan::collisionWith()` — ein Kurs, der endet, wenn der nächste
> anfängt, ist keine Überschneidung. Ein Stundenplan, der sich selbst
> widerspricht, wäre als Rahmen wertlos, weil die Rechnung, wo im Tag noch
> Platz ist, ihn ungeprüft weiterträgt.
>
> Was die Tabellen bewusst nicht haben: keinen Dozenten, keine Modulnummer,
> keine CP, keinen 14-tägigen Rhythmus. Align plant Zeit, es verwaltet kein
> Studium. Wer eine Übung in geraden Wochen hat, trägt die ungeraden als
> Ausfall ein — zwei Arten von Wiederholung wären eine zu viel.
>
> Ein Kurs wird nicht abgehakt und hat keine Serie. Er ist keine
> Gewohnheit, sondern eine Tatsache, um die herum geplant wird; die Zeile
> im Plan trägt deshalb nur, was man mit ihr tun kann.
>
> Der Einstieg bleibt, wie er war. Der Schlafrahmen kostet zehn Sekunden
> und hat brauchbare Voreinstellungen; ein Stundenplan ist eine Liste
> beliebiger Länge und betrifft nicht jeden. Er bekommt eine eigene Seite
> zwischen Kalender und Schlaf — derselben Art wie beide: ein Rahmen, der
> sagt, wann nichts geht.
>
> Zwei Fallen, die dabei zuschnappten und hier festgehalten sind, damit sie
> es nicht wieder tun:
>
> - `Semester::covers()` nahm `Illuminate\Support\Carbon`. Die App stellt
>   über `Date::use()` auf `CarbonImmutable` um, `now()` liefert also etwas
>   anderes als `Carbon::today()`. Der Parameter steht jetzt auf
>   `CarbonInterface`, wie `Habit` es für seine Rückgaben tut.
> - `on_date` liegt als „2026-09-07 00:00:00" in der Spalte. Ein Vergleich
>   gegen „2026-09-07" fand die Zeile nicht, `updateOrCreate` legte eine
>   zweite an und lief in den eindeutigen Schlüssel. Verglichen wird
>   deshalb über Carbon, gelöscht über `whereDate`.
>
> Claude-Session: https://claude.ai/code/session_01FMZEWq2Pf3GjoLPoohM4XD

<details><summary>30 Dateien · +3070/−1</summary>

- `.claude/skills/apple-design/SKILL.md` +282/−0
- `app/Enums/CourseKind.php` +48/−0
- `app/Http/Controllers/CourseController.php` +50/−0
- `app/Http/Controllers/CourseExceptionController.php` +59/−0
- `app/Http/Controllers/SemesterController.php` +120/−0
- `app/Http/Requests/StoreCourseExceptionRequest.php` +155/−0
- `app/Http/Requests/StoreCourseRequest.php` +218/−0
- `app/Http/Requests/StoreSemesterRequest.php` +75/−0
- `app/Http/Requests/UpdateCourseRequest.php` +37/−0
- `app/Models/Course.php` +101/−0
- `app/Models/CourseException.php` +76/−0
- `app/Models/Semester.php` +88/−0
- `app/Models/User.php` +26/−0
- `app/Policies/CoursePolicy.php` +26/−0
- `database/factories/CourseExceptionFactory.php` +56/−0
- `database/factories/CourseFactory.php` +55/−0
- `database/factories/SemesterFactory.php` +56/−0
- `database/migrations/2026_09_03_200000_create_semesters_table.php` +53/−0
- `database/migrations/2026_09_03_200001_create_courses_table.php` +66/−0
- `database/migrations/2026_09_03_200002_create_course_exceptions_table.php` +61/−0
- `resources/js/components/app-sidebar.tsx` +18/−1
- `resources/js/components/course-cancellation-sheet.tsx` +133/−0
- `resources/js/components/course-row.tsx` +128/−0
- `resources/js/components/course-sheet.tsx` +299/−0
- `resources/js/pages/semester.tsx` +367/−0
- `resources/js/types/index.ts` +1/−0
- `resources/js/types/semester.ts` +55/−0
- `routes/web.php` +27/−0
- `tests/Feature/FeaturePagesTest.php` +1/−0
- `tests/Feature/SemesterTest.php` +333/−0

</details>

### docs: write down what a fresh checkout still needs

`814d39a` · **Silas2505** · 14:44 Uhr

> The setup steps lived in CLAUDE.md, which sits in the concept folder
> outside this repository — so nobody who cloned it ever saw them, and
> there was no README at all.
>
> Two things bite in practice, and both are invisible in a diff. The
> database is gitignored, so skipping `php artisan migrate` after a pull
> does not produce a hint but a 500 ("no such table: habit_day_shifts").
> And `resources/js/routes` is gitignored too: without one run of
> `composer dev`, Wayfinder has not written the route files and the
> frontend cannot resolve `@/routes/calendar`.
>
> Also written down, because it looks like a broken checkout otherwise:
> PHPStan needs `--memory-limit=1G`, reports 17 findings that predate any
> current work, and therefore makes `composer ci:check` fail while the
> tests are green. Pint and Prettier pass.

<details><summary>1 Datei · +157/−0</summary>

- `README.md` +157/−0

</details>

### fix: anchor the day's edges to the day being shown, and to the minute

`878883a` · **Silas2505** · 14:34 Uhr

> Three things the calendar got wrong, all visible in one screenshot: a
> habit at 23:30 drawn over the footer, on a day whose bedtime is 23:00.
>
> `sleepBoundAnchorHour()` asked `Carbon::today()` for the sleep window, so
> "nach dem Aufstehen" and "vor dem Schlafengehen" used today's hours on
> every day. The sleep plan is settable per weekday and that is exactly
> where it has to pay off, so the shown date is threaded through.
>
> It also rounded down to the hour. Getting up at 07:40 put the morning
> habit at 07:00 — before waking, and above the top of the grid. Both
> edges now work in minutes: after waking starts at the wake time, and
> before sleeping *ends* at bedtime, so a ten-minute meditation sits ten
> minutes before it rather than a fixed hour.
>
> And the grid grows to hold what falls outside the frame. A sleep plan
> can be changed after a habit was created; the habit then lies past
> bedtime and used to be drawn outside the container, on top of whatever
> followed. It now stays inside, in a shaded night zone below the bedtime
> line.
>
> Two smaller repairs that came with it: the hour lines were drawn five
> pixels low, because the row was as tall as its label and centred the
> line inside it — blocks were right, the lines were not, and the gap
> between a block and the line below it was that error plus a 2px gap now
> down to 1. And a block short enough to hit the 44px minimum no longer
> overshoots the bedtime it was anchored to end at.

<details><summary>4 Dateien · +303/−62</summary>

- `app/Models/Habit.php` +55/−7
- `resources/js/components/day-grid.tsx` +80/−36
- `resources/js/lib/day-grid.ts` +73/−19
- `tests/Feature/SleepScheduleTest.php` +95/−0

</details>

### merge: fold the drag onto the day shift the appointment feature brought

`563063e` · **Silas2505** · 14:07 Uhr

> Both sides invented the same thing at the same time: a per-day exception
> to a habit's time, called `HabitDayShift`, on a table called
> `habit_day_shifts`, keyed by `[habit_id, shifted_on]`. Theirs came from
> appointments ("make room for one day"), mine from dragging a block in the
> hour grid. Theirs landed on main first, so theirs is the one that stays.
>
> What that settles:
>
> - One table, one migration, one model — theirs. Mine is gone.
> - The exception is a `time`, not a minute count. Dragging therefore stops
>   at midnight: a shift past it would be stored as 00:10 and read back the
>   next morning as an early forenoon. Whoever goes to bed after midnight
>   can drag up to it and no further.
> - One controller for one table. `HabitDayShiftController` now answers
>   three verbs on `habits/{habit}/shifts`: POST makes room for an
>   appointment (unchanged), PUT is the drag and may also set the time for
>   good, DELETE takes the exception back.
> - Their `startsAt($on)` / `dayAnchorHour($on)` / `scheduleLabel($on)` are
>   the date-aware primitives; my `placementOn()` is gone and everything
>   reads off theirs. `dayStartMinute($on)` stays as the one place that
>   answers "which minute does the grid draw this at", and `DayPlan::startOf`
>   delegates to it.
> - `DayPlan` keeps their foreign blocks and my `collisionWith()`.
>
> Two of their tests moved with the calendar rework: a day now has its own
> address, so `route('calendar', ['date' => …])` became
> `route('calendar.day', …)`. One of them caught a real mistake in the
> merge — the calendar was asking for `scheduleLabel()` without the date and
> would have hidden the exception it had just been told about.

### feat: give the calendar two levels and let blocks be dragged

`42140d7` · **Silas2505** · 13:59 Uhr

> Three pieces that build on each other.
>
> The small step is kept instead of ticking the habit off. The button
> posted to habits/{habit}/completions — there was no route to store
> `smallest_step` at all. It has one now, and it is called "Kleinen ersten
> Schritt" in the calendar and the overview alike. The partial step no
> longer counts as a finished day; only the circle does.
>
> Moving a habit knows three schedule types instead of two. `Chained` fell
> into the situational branch, got a `trigger_situation` nobody reads and
> kept its old anchor visibly — while blocking that moment for every other
> habit. A migration clears what that left behind. The AI now offers free
> moments and free time windows side by side, whatever the habit is.
>
> The calendar opens on a month; a day has its own address and an hour grid
> behind it. A fixed time gets a solid edge and its span, a situation a
> dashed one and its anchor — nothing is given a clock time it does not
> have. Blocks can be picked up and moved: 400 ms long press, quarter-hour
> steps, then a sheet asking "only today" or "always". Only-today lives in
> `habit_day_shifts` and reaches the calendar, the overview, the reminder
> and the AI's free windows, because a reminder firing at the old time
> would be the contradiction this app exists against. Chained followers
> move along — the model already computes them that way.

<details><summary>38 Dateien · +4223/−628</summary>

- `app/Ai/Agents/SuggestBetterAnchor.php` +94/−71
- `app/Enums/ScheduleType.php` +17/−0
- `app/Http/Controllers/CalendarController.php` +197/−31
- `app/Http/Controllers/DashboardController.php` +13/−2
- `app/Http/Controllers/HabitAdjustmentController.php` +14/−9
- `app/Http/Controllers/HabitShiftController.php` +289/−0
- `app/Http/Controllers/SmallestStepController.php` +59/−0
- `app/Http/Middleware/HandleInertiaRequests.php` +17/−7
- `app/Http/Requests/AdjustHabitRequest.php` +146/−30
- `app/Http/Requests/Concerns/ChecksSituation.php` +7/−2
- `app/Models/Habit.php` +143/−9
- `app/Models/HabitDayShift.php` +47/−0
- `app/Support/DayPlan.php` +34/−15
- `database/migrations/2026_09_02_090000_clear_situations_on_chained_habits.php` +36/−0
- `database/migrations/2026_09_03_090000_create_habit_day_shifts_table.php` +47/−0
- `resources/js/components/adjustment-sheet.tsx` +43/−4
- `resources/js/components/block-sheet.tsx` +191/−0
- `resources/js/components/calendar-block.tsx` +195/−140
- `resources/js/components/day-grid.tsx` +297/−0
- `resources/js/components/flash-notice.tsx` +19/−14
- `resources/js/components/habit-row.tsx` +4/−1
- `resources/js/components/month-grid.tsx` +129/−0
- `resources/js/components/shift-sheet.tsx` +162/−0
- `resources/js/components/starting-help-sheet.tsx` +18/−11
- `resources/js/hooks/use-block-drag.ts` +175/−0
- `resources/js/lib/day-grid.ts` +341/−0
- `resources/js/pages/calendar-day.tsx` +390/−0
- `resources/js/pages/calendar.tsx` +59/−253
- `resources/js/types/global.d.ts` +5/−1
- `resources/js/types/habit.ts` +51/−0
- `routes/web.php` +28/−4
- `tests/Feature/CalendarDurationTest.php` +13/−2
- `tests/Feature/CalendarTest.php` +191/−21
- `tests/Feature/HabitAdjustmentTest.php` +215/−0
- `tests/Feature/HabitShiftTest.php` +401/−0
- `tests/Feature/HabitSituationTest.php` +33/−0
- `tests/Feature/SleepScheduleTest.php` +1/−1
- `tests/Feature/SmallestStepTest.php` +102/−0

</details>

## 02.09.2026

### feat: make room for one day instead of promising twice

`0f59784` · **berbahc** · 21:07 Uhr

> Adopting a habit now runs through the same wizard as creating one, and
> answers the request it came from as a yes. Accepting checks whether the
> day is free — if not, the own habit moves for that one day, not for good.
>
> Claude-Session: https://claude.ai/code/session_01AvJgd6ZPWaxcra5FFqyQCi

<details><summary>29 Dateien · +1515/−435</summary>

- `app/Http/Controllers/AppointmentController.php` +22/−2
- `app/Http/Controllers/CalendarController.php` +8/−6
- `app/Http/Controllers/DashboardController.php` +6/−11
- `app/Http/Controllers/FriendshipController.php` +0/−7
- `app/Http/Controllers/HabitAdoptionController.php` +59/−6
- `app/Http/Controllers/HabitController.php` +81/−0
- `app/Http/Controllers/HabitDayShiftController.php` +43/−0
- `app/Http/Middleware/HandleInertiaRequests.php` +9/−1
- `app/Http/Requests/AdoptHabitRequest.php` +4/−0
- `app/Http/Requests/ShiftHabitDayRequest.php` +149/−0
- `app/Models/Appointment.php` +8/−1
- `app/Models/Habit.php` +68/−12
- `app/Models/HabitDayShift.php` +47/−0
- `app/Support/AppointmentFit.php` +190/−0
- `app/Support/DayPlan.php` +28/−4
- `database/factories/HabitDayShiftFactory.php` +26/−0
- `database/migrations/2026_09_02_100000_create_habit_day_shifts_table.php` +53/−0
- `resources/js/components/appointment-notice.tsx` +11/−9
- `resources/js/components/appointment-request-notice.tsx` +96/−15
- `resources/js/components/habit-adoption-sheet.tsx` +0/−235
- `resources/js/components/habit-wizard.tsx` +95/−43
- `resources/js/pages/community.tsx` +1/−38
- `resources/js/pages/dashboard.tsx` +1/−42
- `resources/js/pages/habits/create.tsx` +16/−3
- `resources/js/types/friendship.ts` +27/−0
- `resources/js/types/habit.ts` +14/−0
- `routes/web.php` +6/−0
- `tests/Feature/AppointmentConflictTest.php` +305/−0
- `tests/Feature/HabitAdoptionTest.php` +142/−0

</details>

## 01.09.2026

### feat: order the whole day, and split the two things the AI can do

`3ff2fd5` · **Silas2505** · 22:59 Uhr

> **Ein Knopf, zwei Fragen.** „Passt der Zeitpunkt?" stand allein an jedem
> Block und war damit die Antwort auf beides — wohin die Gewohnheit gehört
> und ob sie zu groß ist. Jetzt stehen zwei da: „Anderer Zeitpunkt?"
> verschiebt sie im Tag, „Zu groß?" zerlegt sie. Die Starthilfe gab es
> bisher nur auf der Übersicht; sie gehört dorthin, wo die Gewohnheit steht.
>
> **Die KI erfindet keine Momente mehr.** Sie schlug „nachdem ich die
> Laufschuhe ausgezogen habe" vor — das klingt plausibel, steht aber in
> keinem Tag und in keiner Auswahl. Der Tag besteht aus den Gewohnheiten
> und dem Schlafrhythmus; was es dort nicht gibt, lässt sich nicht
> einplanen. Situative Vorschläge kommen deshalb nur noch aus den freien
> Momenten, feste Uhrzeiten nur noch aus Fenstern, in die die ganze Dauer
> passt. Beides prüft der Server nach, statt es dem Modell zu glauben.
>
> **`DayPlan` rechnet den Tag aus.** Rahmen, belegte Spannen, freie Fenster
> mit 15 Minuten Luft dazwischen — die harte Rechnung getrennt von der
> weichen Einschätzung. Die KI wählt daraus und begründet; wo kein Platz
> ist, gibt es auch keinen Vorschlag.
>
> **Der ganze Tag lässt sich neu ordnen.** Ein zweiter Agent legt alle
> Gewohnheiten eines Tages neu, unter Rahmen und Dauern. Passt die Summe
> nicht in den wachen Tag, sagt das der Server mit einer Rechnung, bevor
> die KI überhaupt gefragt wird — eine Absage aus einem Modell wäre eine
> Meinung, diese ist eine Tatsache. Vorher und Nachher stehen nebeneinander,
> und dass dabei aus Situationen feste Uhrzeiten werden, steht vor der
> Entscheidung statt danach.
>
> Dazu drei kleinere Dinge: „In Ruhe frühstücken" heißt „Frühstücken", und
> Mittag- und Abendessen kommen dazu. Im Katalog steht der Bereich als
> Überschrift statt „Womit fängst du an?". Und das ✦ verschwindet von der
> Situations-Kachel — es ist den Momenten vorbehalten, in denen die App
> mitdenkt, und eine Situation zu wählen ist eine eigene Entscheidung.
>
> Der Skeleton kann jetzt ein `<span>` sein: Als `<div>` in der
> Sheet-Beschreibung war er ungültiges HTML, das React zur Laufzeit anmahnte.

<details><summary>22 Dateien · +1517/−77</summary>

- `app/Ai/Agents/SuggestBetterAnchor.php` +73/−25
- `app/Ai/Agents/SuggestDayOrder.php` +237/−0
- `app/Enums/HabitTemplate.php` +12/−2
- `app/Http/Controllers/CalendarController.php` +3/−1
- `app/Http/Controllers/DayOrderController.php` +182/−0
- `app/Http/Controllers/HabitAdjustmentController.php` +34/−5
- `app/Support/DayPlan.php` +215/−0
- `resources/js/components/adjustment-sheet.tsx` +4/−1
- `resources/js/components/calendar-block.tsx` +33/−10
- `resources/js/components/day-order-sheet.tsx` +187/−0
- `resources/js/components/habit-wizard.tsx` +5/−2
- `resources/js/components/schedule-picker.tsx` +6/−10
- `resources/js/components/starting-help-sheet.tsx` +15/−2
- `resources/js/components/ui/skeleton.tsx` +21/−10
- `resources/js/hooks/use-day-order.ts` +104/−0
- `resources/js/pages/calendar.tsx` +36/−0
- `resources/js/types/habit.ts` +2/−0
- `routes/web.php` +9/−0
- `tests/Feature/AiMemoryTest.php` +14/−6
- `tests/Feature/DayOrderTest.php` +223/−0
- `tests/Feature/HabitAdjustmentTest.php` +100/−3
- `tests/Pest.php` +2/−0

</details>

### fix: give each moment one habit, and say less about it

`66a29a0` · **Silas2505** · 22:22 Uhr

> Vier Dinge, die beim Durchsehen aufgefallen sind.
>
> **Ein Moment, eine Gewohnheit.** „Joggen gehen" und „Aufräumen" standen
> beide auf „nach dem Aufstehen" — der Kalender zeigte sie untereinander,
> als gäbe es eine Reihenfolge, die niemand festgelegt hat. Das unterläuft
> das Time-Blocking, dem die Planung dient. Eine Situation trägt jetzt
> genau eine aktive Gewohnheit: `Habit::situationChoicesFor()` ist die eine
> Quelle, aus der die Oberfläche die vergebenen Momente sperrt und
> `ChecksSituation` sie abweist. Die Regel gilt auch für den Freitext,
> sonst wäre sie über „Eigene Situation" umgehbar — und auch für die
> KI-Anpassung, die vergebene Momente weder vorschlägt noch durchlässt.
>
> **Weniger Erklärung.** Rund vierzig Fließtexte standen in der Oberfläche;
> vier davon trugen eine echte technische Grenze, der Rest begründete,
> wiederholte das Element zwei Zeilen darunter oder stand doppelt. Die vier
> bleiben — gekürzt, und die Wecker-Grenze wandert von der Fußzeile zu den
> Schaltern, wo die Frage auftritt. Alles andere ist weg, samt
> `HabitCategory::description()`: „Sport & Bewegung" erklärt sich selbst.
>
> **„nach der Morgenvorlesung" heißt „nach der Vorlesung"**, mit Migration
> für bestehende Zeilen — sonst gälte der alte Wortlaut als Freitext und
> sortierte sich mittags ein statt um elf.
>
> **Die KI läuft wieder.** Kein Code-Fehler: OpenRouter antwortete mit 402,
> weil ohne `max_tokens` die Kosten für das Modell-Maximum (65536) im
> Voraus reserviert werden. Beide Agenten bekommen einen Deckel von 1024 —
> drei Sätze à 160 Zeichen brauchen nicht mehr.
>
> Die Factory vergibt Situationen jetzt reihum statt gewürfelt und setzt
> je Test zurück: Bei fünf Gewohnheiten aus fünf Werten war eine Dublette
> fast sicher, und der Test wäre an seinen eigenen Daten gescheitert.

<details><summary>36 Dateien · +503/−161</summary>

- `app/Ai/Agents/SuggestBetterAnchor.php` +24/−0
- `app/Ai/Agents/SuggestSmallestStep.php` +9/−0
- `app/Enums/HabitCategory.php` +1/−15
- `app/Http/Controllers/DashboardController.php` +1/−1
- `app/Http/Controllers/FriendshipController.php` +1/−1
- `app/Http/Controllers/HabitAdjustmentController.php` +7/−0
- `app/Http/Controllers/HabitController.php` +4/−2
- `app/Http/Controllers/OnboardingController.php` +1/−1
- `app/Http/Requests/AdjustHabitRequest.php` +8/−1
- `app/Http/Requests/Concerns/ChecksSituation.php` +55/−0
- `app/Http/Requests/HabitFormRequest.php` +18/−1
- `app/Models/Habit.php` +35/−1
- `database/factories/HabitFactory.php` +28/−7
- `database/migrations/2026_09_01_090000_rename_morning_lecture_situation.php` +34/−0
- `database/seeders/DatabaseSeeder.php` +1/−1
- `resources/js/components/appointment-sheet.tsx` +1/−2
- `resources/js/components/companion-step.tsx` +1/−2
- `resources/js/components/habit-adoption-sheet.tsx` +4/−4
- `resources/js/components/habit-limit-note.tsx` +2/−4
- `resources/js/components/habit-wizard.tsx` +2/−23
- `resources/js/components/schedule-picker.tsx` +27/−3
- `resources/js/components/sleep-notice.tsx` +0/−3
- `resources/js/components/starting-help-sheet.tsx` +0/−4
- `resources/js/pages/calendar.tsx` +0/−1
- `resources/js/pages/community.tsx` +4/−11
- `resources/js/pages/dashboard.tsx` +3/−4
- `resources/js/pages/habits/create.tsx` +2/−1
- `resources/js/pages/habits/edit.tsx` +3/−3
- `resources/js/pages/habits/index.tsx` +8/−22
- `resources/js/pages/onboarding.tsx` +9/−11
- `resources/js/pages/sleep.tsx` +15/−24
- `resources/js/types/habit.ts` +12/−0
- `tests/Feature/HabitSituationTest.php` +161/−0
- `tests/Feature/HabitUpdateTest.php` +3/−0
- `tests/Feature/OnboardingTest.php` +13/−8
- `tests/Pest.php` +6/−0

</details>

### style: give every screen the same motion and type vocabulary

`e9c7332` · **Silas2505** · 00:12 Uhr

> Der Apple-Pass hatte bisher drei Bildschirme erreicht — Übersicht,
> Habit-Zeile, Streak-Karte. Alles andere lief auf einem generischen
> `duration-200`, und die Kleinversalien-Zeile stand als kopierte
> Konstante in elf Dateien.
>
> Beides zieht in je eine Quelle: `lib/interaction.ts` hält die Gesten
> (Druckpunkt auf Pointer-Down, Fokus, Auswahlkachel, Stepper), die
> Typo-Sets in `app.css` halten Größe, Zeilenabstand und Laufweite
> zusammen. Laufweite ist größenabhängig — große Schrift bekommt sie
> negativ, die Versalien-Zeile positiv; ein fester Wert wäre irgendwo
> falsch.
>
> Dazu drei Dinge, die die Screenshots gezeigt haben:
>
> - In der Gewohnheitsliste stritten Icon, Titel, Schalter und Menü um
>   dieselbe Breite, und der Titel verlor („Vorlesung na…"). Die
>   Erinnerung steht jetzt in einer eigenen Zeile darunter.
> - Drei Schalter waren dauerhaft aus und nicht bedienbar — sie sahen wie
>   ein Angebot aus und waren keins. Sie erscheinen nur noch, wo es etwas
>   zu erinnern gibt.
> - Der Sammelschalter über genau einem Schalter war derselbe Schalter
>   zweimal; er erscheint erst, wenn er etwas zusammenfasst.

<details><summary>19 Dateien · +191/−62</summary>

- `resources/css/app.css` +75/−0
- `resources/js/components/adjustment-sheet.tsx` +3/−3
- `resources/js/components/ai-suggestion.tsx` +2/−2
- `resources/js/components/appearance-toggle.tsx` +4/−4
- `resources/js/components/appointment-notice.tsx` +2/−2
- `resources/js/components/appointment-request-notice.tsx` +2/−2
- `resources/js/components/appointment-sheet.tsx` +6/−6
- `resources/js/components/companion-step.tsx` +8/−11
- `resources/js/components/feature-placeholder.tsx` +3/−7
- `resources/js/components/flash-notice.tsx` +2/−2
- `resources/js/components/friend-request-notice.tsx` +1/−1
- `resources/js/components/habit-limit-note.tsx` +1/−1
- `resources/js/components/habit-reminder-notice.tsx` +2/−2
- `resources/js/components/habit-row.tsx` +1/−1
- `resources/js/components/starting-help-sheet.tsx` +2/−2
- `resources/js/components/streak-card.tsx` +1/−1
- `resources/js/components/upcoming-appointments.tsx` +2/−4
- `resources/js/lib/interaction.ts` +67/−0
- `resources/js/pages/community.tsx` +7/−11

</details>

### feat: plan habits from a fixed catalog inside a real day

`b18efd8` · **Silas2505** · 00:12 Uhr

> Die freie Eingabe von Gewohnheiten fällt weg. Sie hat die App unscharf
> gemacht: „Treppe statt Aufzug" hat keine Uhrzeit, „Wasser trinken" keine
> Dauer — beides ließ sich nicht in ein Time-Blocking einbetten, das mit
> Zeitfenstern plant, und beides zwang die übrigen Features zu Sonderfällen
> (kein Anker, keine Quote, keine Erinnerung, ein eigener Block unter der
> Kalenderachse).
>
> An ihre Stelle tritt ein Katalog aus vier Bereichen (Sport, Uni, Alltag,
> Erholung) mit siebzehn Vorlagen. Aufgenommen ist nur, was planbar ist,
> eine Dauer hat und am Stück passiert. Damit hat jede Gewohnheit wieder
> eine Stelle im Tag — `ScheduleType::Opportunistic` verschwindet, und mit
> ihm jede Ausnahme, die daran hing.
>
> Dazu der Rahmen, in dem geplant wird: Aufsteh- und Schlafenszeit je
> Wochentag. Er ist keine Gewohnheit — er wird nicht abgehakt und hat keine
> Serie —, sondern begrenzt, wann sich Uhrzeiten überhaupt legen lassen.
> Eine Erinnerung 20 Minuten vor der Schlafenszeit beendet den Tag, ein
> Wecker je Wochentag beginnt ihn.
>
> Der Rahmen wirkt überall dort, wo er etwas heißt: Der Kalender zeigt ihn
> als Ränder seiner Achse, „nach dem Aufstehen" sortiert sich zur eigenen
> Aufstehzeit statt zu einem gemittelten Wert, und die KI-Anpassung kennt
> ihn — sie schlägt nichts außerhalb vor, und was doch käme, wird verworfen,
> bevor es die Oberfläche erreicht.
>
> Beim Bearbeiten steht die Identität nicht mehr zur Wahl: Eine Gewohnheit
> wechselt ihren Zeitpunkt, nicht ihren Namen.

<details><summary>68 Dateien · +3922/−2010</summary>

- `app/Ai/Agents/SuggestBetterAnchor.php` +58/−0
- `app/Enums/BehaviorType.php` +8/−86
- `app/Enums/HabitCategory.php` +83/−0
- `app/Enums/HabitTemplate.php` +173/−0
- `app/Enums/MeasureUnit.php` +19/−0
- `app/Enums/ScheduleType.php` +8/−33
- `app/Http/Controllers/CalendarController.php` +14/−18
- `app/Http/Controllers/DashboardController.php` +41/−4
- `app/Http/Controllers/HabitAdjustmentController.php` +4/−7
- `app/Http/Controllers/HabitCompletionController.php` +3/−5
- `app/Http/Controllers/HabitController.php` +21/−39
- `app/Http/Controllers/OnboardingController.php` +52/−4
- `app/Http/Controllers/SleepScheduleController.php` +73/−0
- `app/Http/Controllers/SmallestStepController.php` +20/−10
- `app/Http/Middleware/HandleInertiaRequests.php` +35/−0
- `app/Http/Requests/AdjustHabitRequest.php` +24/−0
- `app/Http/Requests/Concerns/ChecksSleepWindow.php` +54/−0
- `app/Http/Requests/HabitFormRequest.php` +72/−70
- `app/Http/Requests/StoreHabitRequest.php` +51/−6
- `app/Models/Appointment.php` +1/−1
- `app/Models/Habit.php` +79/−78
- `app/Models/SleepSchedule.php` +86/−0
- `app/Models/User.php` +61/−0
- `database/factories/HabitFactory.php` +37/−15
- `database/migrations/2026_08_31_100000_add_template_key_to_habits_table.php` +35/−0
- `database/migrations/2026_08_31_100001_remove_opportunistic_habits.php` +32/−0
- `database/migrations/2026_08_31_100002_create_sleep_schedules_table.php` +64/−0
- `database/seeders/DatabaseSeeder.php` +68/−34
- `resources/js/components/app-sidebar.tsx` +17/−6
- `resources/js/components/calendar-block.tsx` +24/−21
- `resources/js/components/duration-picker.tsx` +59/−0
- `resources/js/components/habit-adoption-sheet.tsx` +91/−76
- `resources/js/components/habit-wizard.tsx` +138/−245
- `resources/js/components/managed-habit-row.tsx` +110/−95
- `resources/js/components/measure-picker.tsx` +0/−103
- `resources/js/components/schedule-picker.tsx` +62/−125
- `resources/js/components/sleep-card.tsx` +74/−0
- `resources/js/components/sleep-notice.tsx` +78/−0
- `resources/js/components/time-stepper.tsx` +113/−0
- `resources/js/components/wake-alarm.tsx` +141/−0
- `resources/js/hooks/use-sleep.ts` +268/−0
- `resources/js/hooks/use-smallest-step.ts` +5/−5
- `resources/js/layouts/app-layout.tsx` +4/−0
- `resources/js/lib/behavior-icons.ts` +27/−5
- `resources/js/lib/measure.ts` +0/−58
- `resources/js/lib/sleep.ts` +46/−0
- `resources/js/pages/calendar.tsx` +92/−91
- `resources/js/pages/dashboard.tsx` +22/−11
- `resources/js/pages/habits/create.tsx` +13/−9
- `resources/js/pages/habits/edit.tsx` +63/−119
- `resources/js/pages/habits/index.tsx` +20/−24
- `resources/js/pages/onboarding.tsx` +146/−29
- `resources/js/pages/sleep.tsx` +341/−0
- `resources/js/types/global.d.ts` +3/−1
- `resources/js/types/habit.ts` +97/−53
- `routes/web.php` +9/−0
- `tests/Feature/AiMemoryTest.php` +7/−9
- `tests/Feature/AppointmentTest.php` +14/−13
- `tests/Feature/HabitAdjustmentTest.php` +59/−0
- `tests/Feature/HabitAdoptionTest.php` +29/−25
- `tests/Feature/HabitChainTest.php` +4/−26
- `tests/Feature/HabitMeasureTest.php` +64/−108
- `tests/Feature/HabitOpportunisticTest.php` +0/−194
- `tests/Feature/HabitScheduleTest.php` +22/−52
- `tests/Feature/HabitUpdateTest.php` +46/−38
- `tests/Feature/OnboardingTest.php` +62/−35
- `tests/Feature/SleepScheduleTest.php` +259/−0
- `tests/Feature/SmallestStepTest.php` +17/−24

</details>

## 31.08.2026

### feat: let a habit be swiped done and give the day its own weight

`2787268` · **berbahc** · 11:03 Uhr

> Swiping a row right completes it, left takes it back: 1:1 tracking,
> rubber-banding, momentum projection and velocity handoff into a spring
> that can be redirected mid-flight. Trackpad swiping goes through wheel
> events, gated tightly so ordinary scrolling never triggers it.
>
> On the overview, the day's card now carries: a warm-tinted lift against
> the flat habit list, a larger counting percentage, tighter tracking on
> large text. Card radius moves to the 16px the design language asks for.
>
> The gesture has not been exercised in a browser yet — tests and build
> pass, feel does not follow from that.

<details><summary>7 Dateien · +918/−117</summary>

- `resources/css/app.css` +64/−0
- `resources/js/components/habit-row.tsx` +199/−99
- `resources/js/components/streak-card.tsx` +2/−2
- `resources/js/components/ui/card.tsx` +3/−1
- `resources/js/hooks/use-counted-number.ts` +70/−0
- `resources/js/hooks/use-swipe-toggle.ts` +543/−0
- `resources/js/pages/dashboard.tsx` +37/−15

</details>

## 18.08.2026

### feat: give the habits without a day their own block

`cc5a432` · **berbahc** · 22:54 Uhr



<details><summary>5 Dateien · +126/−25</summary>

- `app/Http/Controllers/HabitController.php` +23/−3
- `resources/js/components/managed-habit-row.tsx` +5/−2
- `resources/js/pages/habits/index.tsx` +28/−16
- `resources/js/types/habit.ts` +11/−2
- `tests/Feature/HabitScheduleTest.php` +59/−2

</details>

### feat: offer the days the habit actually runs on

`06acf4e` · **berbahc** · 00:07 Uhr

> Der letzte Schritt beim Anlegen fragt "wen" und "wann" - und bot beim
> "wann" immer dieselben drei Tage an: heute, morgen, uebermorgen, stur
> vom Kalender abgezaehlt. Wer samstags eine Mo-Fr-Gewohnheit anlegte,
> konnte sich damit nur fuer Tage verabreden, an denen sie gar nicht
> stattfindet. Dasselbe im Verabredungs-Sheet auf der Uebersicht.
>
> community_feature3.md haelt fuer die Uhrzeit fest, dass die Verabredung
> keine eigene Zeitlogik erfindet, sondern die vorhandene nutzt - genau
> darum ist sie kein gemeinsamer Kalender. Fuer die Tage galt das bisher
> nicht. Jetzt schon: Angeboten werden die naechsten drei Termine der
> Gewohnheit. Mo-Fr am Samstag heisst Montag, Dienstag, Mittwoch.
>
> Die uebrigen Planungsarten fallen dabei von selbst richtig: Eine
> gekoppelte Gewohnheit erbt die Tage ihres Ankers, weil isScheduledOn()
> ohnehin dorthin durchreicht. Was sich ergibt, hat keinen Termin, aber
> jeden Tag eine Gelegenheit - fuer sie bleibt es beim Blick in den
> Kalender. Und ein 17:00-Block, der um 18:30 laengst vorbei ist, faellt
> fuer heute raus: Eine Verabredung fuer einen Moment, der vorueber ist,
> waere keine.
>
> Der Horizont liegt bei einer Woche. Drei Termine bleiben drei Termine,
> aber eine woechentliche Gewohnheit haette ihren dritten sonst erst in
> drei Wochen - und das waere die Terminfindung, die laut §9 nicht in die
> App gehoert. Wer seltener uebt, bekommt entsprechend weniger zur Wahl
> und einen Satz dazu, warum.
>
> Damit Auswahl und Pruefung nicht auseinanderlaufen, liest
> ProposeAppointmentRequest jetzt dieselbe Liste, statt die Spanne ein
> zweites Mal nachzurechnen. Das Fenster von upcomingFor() waechst mit:
> Sonst stuende eine zugesagte Verabredung an einer Wochen-Gewohnheit
> nirgends. Und die Tage haengen ab jetzt an der Gewohnheit statt an der
> Seite - beim Anlegen im habitCreated-Flash, weil es die Gewohnheit beim
> Aufruf des Formulars noch gar nicht gab.

<details><summary>14 Dateien · +328/−51</summary>

- `app/Http/Controllers/DashboardController.php` +5/−1
- `app/Http/Controllers/HabitController.php` +8/−2
- `app/Http/Requests/ProposeAppointmentRequest.php` +7/−3
- `app/Models/Appointment.php` +76/−20
- `app/Models/Habit.php` +29/−3
- `resources/js/components/appointment-sheet.tsx` +13/−0
- `resources/js/components/companion-step.tsx` +13/−0
- `resources/js/pages/community.tsx` +1/−1
- `resources/js/pages/dashboard.tsx` +1/−5
- `resources/js/pages/habits/create.tsx` +1/−4
- `resources/js/types/friendship.ts` +8/−2
- `resources/js/types/global.d.ts` +3/−0
- `resources/js/types/habit.ts` +10/−5
- `tests/Feature/AppointmentTest.php` +153/−5

</details>

## 17.08.2026

### feat: hang one habit on another and say when the day is busy

`427cfd4` · **Silas2505** · 10:40 Uhr

> Das Domino-Prinzip steht seit der Konzeptphase in time-blocking.md und
> war als dritte Ankerart gezeichnet - gebaut war davon nichts. Die KI
> durfte "nach dem Zaehneputzen" als Freitext vorschlagen, aber die beiden
> Gewohnheiten wussten nichts voneinander, und die gekoppelte sortierte
> sich mangels bekannter Situation auf Stunde 12.
>
> Jetzt haengt sie wirklich: Sie faengt an, wo die vorige aufhoert, laeuft
> an deren Tagen und heisst nach ihr. Damit beantwortet die Dauer aus der
> letzten Iteration die Frage nach dem "fuer wann" - ein 20-Minuten-Block
> um 17:00 setzt den Anschluss auf 17:20, ueber mehrere Glieder durch.
>
> Faellt der Vorgaenger weg, erben die Nachfolger seinen Anker statt mit
> ihm zu verschwinden - dasselbe Muster wie bei der abgesagten
> Verabredung. Zyklen weist die Validierung ab, sonst haette die Kette
> keinen Anfang mehr.
>
> Dazu der Hinweis auf belegte Fenster: Wer 17:10 waehlt, waehrend bis
> 17:20 etwas laeuft, erfaehrt es und bekommt die naechste freie Zeit
> angeboten. Ein Hinweis, keine Sperre.
>
> Beleg: Alissa im Interview, ohne den Begriff zu kennen - "wenn ich dann
> im Bett bin, kann ich es direkt machen".

<details><summary>18 Dateien · +1027/−45</summary>

- `app/Actions/ReleaseChainedHabits.php` +55/−0
- `app/Enums/ScheduleType.php` +17/−0
- `app/Http/Controllers/CalendarController.php` +11/−2
- `app/Http/Controllers/HabitController.php` +70/−2
- `app/Http/Controllers/HabitGraduationController.php` +7/−1
- `app/Http/Requests/HabitFormRequest.php` +102/−1
- `app/Models/Habit.php` +177/−10
- `database/migrations/2026_08_17_102548_add_chain_to_habits_table.php` +43/−0
- `resources/js/components/calendar-block.tsx` +8/−0
- `resources/js/components/habit-wizard.tsx` +23/−6
- `resources/js/components/schedule-picker.tsx` +99/−2
- `resources/js/lib/slots.ts` +84/−0
- `resources/js/pages/calendar.tsx` +29/−15
- `resources/js/pages/habits/create.tsx` +8/−0
- `resources/js/pages/habits/edit.tsx` +15/−0
- `resources/js/types/habit.ts` +42/−1
- `tests/Feature/HabitChainTest.php` +232/−0
- `tests/Feature/HabitScheduleTest.php` +5/−5

</details>

### feat: give a home to the habits that have no place in the day

`64bd8d1` · **Silas2505** · 10:24 Uhr

> "Treppe statt Aufzug" und "eine Station frueher aussteigen" haengen an
> einer Gelegenheit, die auftaucht, wann sie will. Bis hierher bekamen sie
> ueber UnknownAnchorHour die Stunde 12 und standen mittags im Kalender,
> als waeren sie geplant - und wurden gemessen wie eine taegliche Pflicht:
> sechsmal in dreissig Tagen las sich als 20 Prozent, zwei freie Tage
> rissen die Serie.
>
> Beides war erfunden. ScheduleType bekommt eine dritte Form ohne Platz im
> Tag. Sie steht im Kalender unter der Achse, hat keine Quote und kein
> Versaeumnis, und der "Passt der Zeitpunkt?"-Chip fehlt ihr - es gibt
> keinen Zeitpunkt zu verbessern.
>
> Der Code fragte bisher ueberall "=== Fixed" gegen alles andere; eine
> dritte Form waere darin stillschweigend als dynamisch durchgelaufen.
> Die Verzweigungen fragen jetzt isPlanned() und hasClockTime().
>
> Beleg: Felix in der Interviewauswertung - an der Uni bewegt er sich
> automatisch mehr, weil der Kontext das ausloest.

<details><summary>23 Dateien · +687/−136</summary>

- `app/Ai/Agents/SuggestBetterAnchor.php` +3/−4
- `app/Enums/BehaviorType.php` +27/−18
- `app/Enums/ScheduleType.php` +55/−2
- `app/Http/Controllers/CalendarController.php` +46/−20
- `app/Http/Controllers/DashboardController.php` +8/−2
- `app/Http/Controllers/HabitAdjustmentController.php` +7/−0
- `app/Http/Controllers/HabitCompletionController.php` +5/−1
- `app/Http/Controllers/HabitController.php` +21/−2
- `app/Http/Controllers/HabitReminderController.php` +2/−1
- `app/Http/Requests/AdjustHabitRequest.php` +2/−3
- `app/Http/Requests/HabitFormRequest.php` +27/−14
- `app/Models/Habit.php` +88/−8
- `resources/js/components/calendar-block.tsx` +4/−2
- `resources/js/components/habit-adoption-sheet.tsx` +15/−7
- `resources/js/components/habit-wizard.tsx` +37/−8
- `resources/js/components/managed-habit-row.tsx` +5/−3
- `resources/js/components/schedule-picker.tsx` +44/−18
- `resources/js/pages/calendar.tsx` +62/−18
- `resources/js/pages/habits/edit.tsx` +4/−2
- `resources/js/types/habit.ts` +20/−1
- `tests/Feature/HabitMeasureTest.php` +3/−0
- `tests/Feature/HabitOpportunisticTest.php` +194/−0
- `tests/Feature/HabitScheduleTest.php` +8/−2

</details>

## 16.08.2026

### feat: show in the calendar how long a block occupies the day

`80b381d` · **Silas2505** · 21:21 Uhr

> Der Umfang steht seit der letzten Iteration als Zahl da, der Kalender hat
> ihn nicht benutzt: Ein 20-Minuten-Spaziergang und ein Glas Wasser sahen
> im Tag gleich aus, und nichts sagte, wann der Platz wieder frei ist.
>
> Nur Minuten sind eine Dauer. "10 Seiten" und "2 Liter" sagen, wie viel,
> nicht wie lange — sie belegen keine Spanne und bleiben als Umfang am
> Titel stehen. Wo eine feste Uhrzeit auf Minuten trifft, tritt
> "17:00 - 17:20" an die Stelle des Ankers.
>
> Der Block bleibt gleich hoch. Eine nach Dauer skalierte Achse wäre das
> Stundenraster, das CalendarController mit der Umfrage im Rücken
> ausschließt.

<details><summary>6 Dateien · +190/−1</summary>

- `app/Http/Controllers/CalendarController.php` +5/−0
- `app/Models/Habit.php` +61/−0
- `resources/js/components/calendar-block.tsx` +16/−1
- `resources/js/pages/calendar.tsx` +5/−0
- `resources/js/types/habit.ts` +9/−0
- `tests/Feature/CalendarDurationTest.php` +94/−0

</details>

### feat: let a habit be changed and give its measure a field of its own

`1e43526` · **Silas2505** · 20:46 Uhr

> Der Wizard versprach auf dem letzten Schritt "Du kannst das jederzeit
> ändern", und es stimmte nicht: Es gab keinen Weg zurück ins Formular.
> Wer sich vertippt hatte, musste beenden und neu anlegen — und verlor
> dabei jeden abgehakten Tag.
>
> Die Menge steckte im Titel ("20 Minuten spazieren") und war damit eine
> fremde Zahl. Genau sie ist der individuellste Teil. Sie bekommt zwei
> eigene Spalten und einen Stepper; der Titel benennt nur noch die
> Handlung. focus_minutes geht darin auf — die Spalte war der Vorläufer
> desselben Gedankens, wurde aber von keinem Formular je befüllt.

<details><summary>29 Dateien · +1673/−172</summary>

- `app/Actions/CreateHabit.php` +1/−1
- `app/Ai/UserContext.php` +1/−1
- `app/Enums/BehaviorType.php` +24/−18
- `app/Enums/MeasureUnit.php` +126/−0
- `app/Http/Controllers/DashboardController.php` +1/−1
- `app/Http/Controllers/HabitController.php` +68/−0
- `app/Http/Controllers/OnboardingController.php` +2/−0
- `app/Http/Requests/HabitFormRequest.php` +172/−0
- `app/Http/Requests/StoreHabitRequest.php` +10/−116
- `app/Http/Requests/UpdateHabitRequest.php` +15/−0
- `app/Models/Appointment.php` +1/−1
- `app/Models/Habit.php` +46/−3
- `database/factories/HabitFactory.php` +30/−2
- `database/migrations/2026_08_16_202746_add_target_measure_to_habits_table.php` +60/−0
- `database/seeders/DatabaseSeeder.php` +18/−9
- `resources/js/components/habit-adoption-sheet.tsx` +14/−0
- `resources/js/components/habit-row.tsx` +1/−1
- `resources/js/components/habit-wizard.tsx` +115/−14
- `resources/js/components/managed-habit-row.tsx` +20/−1
- `resources/js/components/measure-picker.tsx` +103/−0
- `resources/js/components/schedule-picker.tsx` +3/−2
- `resources/js/lib/measure.ts` +58/−0
- `resources/js/pages/habits/create.tsx` +8/−1
- `resources/js/pages/habits/edit.tsx` +313/−0
- `resources/js/pages/onboarding.tsx` +4/−0
- `resources/js/types/habit.ts` +32/−1
- `routes/web.php` +14/−0
- `tests/Feature/HabitMeasureTest.php` +193/−0
- `tests/Feature/HabitUpdateTest.php` +220/−0

</details>

## 15.08.2026

### feat: let a habit outlive the appointment that was cancelled

`4a15a36` · **berbahc** · 21:34 Uhr

<details><summary>20 Dateien · +1039/−110</summary>

- `app/Http/Controllers/DashboardController.php` +6/−0
- `app/Http/Controllers/FriendshipController.php` +7/−0
- `app/Http/Controllers/HabitAdoptionController.php` +67/−0
- `app/Http/Requests/AdoptHabitRequest.php` +29/−0
- `app/Models/Appointment.php` +6/−1
- `app/Models/AppointmentNotice.php` +25/−4
- `app/Models/Habit.php` +24/−0
- `database/migrations/2026_08_15_205527_add_carry_on_to_appointment_notices_table.php` +50/−0
- `resources/js/components/appointment-notice.tsx` +56/−16
- `resources/js/components/appointment-request-notice.tsx` +16/−0
- `resources/js/components/habit-adoption-sheet.tsx` +198/−0
- `resources/js/components/habit-row.tsx` +16/−1
- `resources/js/components/habit-wizard.tsx` +13/−83
- `resources/js/components/schedule-picker.tsx` +99/−0
- `resources/js/pages/community.tsx` +55/−2
- `resources/js/pages/dashboard.tsx` +104/−3
- `resources/js/types/friendship.ts` +20/−0
- `resources/js/types/habit.ts` +17/−0
- `routes/web.php` +7/−0
- `tests/Feature/HabitAdoptionTest.php` +224/−0

</details>

### KI-Gedächtnis: die Agenten wissen, mit wem sie sprechen

`12e2c35` · **Silas2505** · 20:51 Uhr

> Beide Agenten starteten bei jedem Aufruf kalt. Kein Vorschlag wurde
> gespeichert, „Lass so" verpuffte, und der Warum-Satz stand in keinem
> Prompt. align.md Z. 31 verspricht eine KI, „die daraus lernt" —
> umgesetzt war ein zustandsloser Vorschlagsgenerator.
>
> - ai_suggestions: eine Zeile je Vorschlag, accepted_at null heißt
>   angeboten und nicht genommen. Entscheidungen, keine Verläufe —
>   die Conversation-Modelle aus laravel/ai sind für Chats gebaut.
> - App\Ai\UserContext: Warum-Satz, übrige Anker, Tageszeit- und
>   Wochentag-Rhythmus, frühere Vorschläge. Alles abgeleitet, nichts
>   zusätzlich erfragt. Reicht die Datenlage nicht, entfällt die Zeile.
> - SpeaksForAlign: die Haltung aus ki-assistent-design.md §2 steht
>   jetzt an einer Stelle statt in zwei Kopien.
> - Habit::anchorLabel(): ein Anker als Zeile, auch bevor es ihn gibt.
>
> Nachgetragene Haken bleiben aus dem Rhythmus draußen — sie tragen
> endOfDay() und würden jede Person zum Abendmenschen machen.

<details><summary>18 Dateien · +1384/−78</summary>

- `app/Actions/CreateHabit.php` +37/−1
- `app/Actions/RememberSuggestions.php` +66/−0
- `app/Ai/Agents/Concerns/SpeaksForAlign.php` +57/−0
- `app/Ai/Agents/SuggestBetterAnchor.php` +10/−11
- `app/Ai/Agents/SuggestSmallestStep.php` +18/−5
- `app/Ai/UserContext.php` +404/−0
- `app/Enums/SuggestionKind.php` +37/−0
- `app/Http/Controllers/HabitAdjustmentController.php` +27/−31
- `app/Http/Controllers/SmallestStepController.php` +55/−18
- `app/Http/Requests/AdjustHabitRequest.php` +31/−0
- `app/Models/AiSuggestion.php` +107/−0
- `app/Models/Habit.php` +33/−5
- `app/Models/User.php` +14/−0
- `database/factories/AiSuggestionFactory.php` +50/−0
- `database/migrations/2026_08_15_203618_create_ai_suggestions_table.php` +67/−0
- `resources/js/components/adjustment-sheet.tsx` +13/−7
- `resources/js/types/habit.ts` +7/−0
- `tests/Feature/AiMemoryTest.php` +351/−0

</details>

### feat: sort the habit lists by when each habit is next due

`52627a9` · **berbahc** · 20:41 Uhr

<details><summary>10 Dateien · +438/−109</summary>

- `app/Http/Controllers/DashboardController.php` +8/−3
- `app/Http/Controllers/HabitController.php` +23/−1
- `app/Models/Habit.php` +44/−0
- `resources/js/components/managed-habit-row.tsx` +122/−0
- `resources/js/pages/habits/index.tsx` +53/−100
- `resources/js/types/habit.ts` +8/−0
- `tests/Feature/DashboardTest.php` +56/−2
- `tests/Feature/HabitReminderTest.php` +6/−1
- `tests/Feature/HabitScheduleTest.php` +113/−0
- `tests/Feature/HabitStreakTest.php` +5/−2

</details>

## 10.08.2026

### feat: let a cancellation arrive instead of leaving a gap

`1368a29` · **berbahc** · 00:18 Uhr

> §5 asks for "Passt Silas diesmal nicht" to appear at the person who asked,
> and §9 forbids storing cancellations. Both hold only for a line that
> deletes itself: no read flag, no counter, and no id of the person who
> cancelled — just their name as text, so no rate can be derived later.
>
> Two of the three ways an appointment disappears leave a notice. Declining
> an open request tells the asker, dissolving an accepted one tells the other
> side which day is free again. Withdrawing your own unanswered request stays
> silent: nothing was promised, nobody planned around it.

<details><summary>12 Dateien · +572/−3</summary>

- `app/Enums/AppointmentNoticeKind.php` +19/−0
- `app/Http/Controllers/AppointmentController.php` +13/−3
- `app/Http/Controllers/AppointmentNoticeController.php` +25/−0
- `app/Models/AppointmentNotice.php` +145/−0
- `app/Policies/AppointmentNoticePolicy.php` +17/−0
- `database/factories/AppointmentNoticeFactory.php` +33/−0
- `database/migrations/2026_08_10_000248_create_appointment_notices_table.php` +58/−0
- `resources/js/components/appointment-notice.tsx` +67/−0
- `resources/js/pages/dashboard.tsx` +10/−0
- `resources/js/types/friendship.ts` +15/−0
- `routes/web.php` +6/−0
- `tests/Feature/AppointmentNoticeTest.php` +164/−0

</details>

### feat: show in the community tab what is arranged with whom

`4f04b97` · **berbahc** · 00:17 Uhr

> The tab promised to be about the people you meet up with, but appointments
> lived only on the overview — it was an address book with a switch. It now
> carries the open requests and everything arranged in the three-day window.
>
> Unlike the overview it keeps an accepted appointment for today that you
> asked for yourself: there is no habit row here to carry it, so leaving it
> out would hide exactly the appointment that is due.
>
> The queries move from DashboardController into Appointment, the way
> Friendship::pendingFor() already does it for both surfaces.

<details><summary>5 Dateien · +246/−51</summary>

- `app/Http/Controllers/DashboardController.php` +9/−45
- `app/Http/Controllers/FriendshipController.php` +22/−2
- `app/Models/Appointment.php` +83/−0
- `resources/js/pages/community.tsx` +41/−4
- `tests/Feature/AppointmentTest.php` +91/−0

</details>

## 09.08.2026

### feat: count a streak that survives the weekend and one missed day

`8062f65` · **Silas2505** · 23:19 Uhr

> Three docblocks argued against a streak, citing progress-tracking.md. That
> position comes from the interviews and the survey disproved it: 9 of 25
> lean streak, 5 lean consistency rate, 11 are open. umfrage-auswertung.md §6
> flags the decision as still open and proposes the resolution implemented
> here — show the streak, make the break forgiving. Those docblocks now say
> so; the feature file itself is untouched.
>
> A streak counts scheduled occurrences, not calendar days. A Mon-Fri habit
> does not break over the weekend: Saturday is neither a link nor a break, it
> simply is not part of the chain. Days before the habit existed end the walk
> for the same reason recentMisses() stops there.
>
> One grace day per streak, per Lally et al.: single lapses have no
> measurable long-term cost, so one must not zero the count — the second one
> may, or the number stops measuring anything. The missed day is never a link
> itself. No notice when the grace is spent; that would be a reprimand with a
> friendly name, and guilt is the highest problem value in the survey (3.92).
>
> Today counts once ticked and breaks nothing while open — the day is not
> over yet.
>
> No stored counter: completions can be backdated seven days, so a stored
> number would be wrong immediately. A second narrow relation carries the
> full history, because completions is eager-loaded scoped to today and
> Eloquent cannot load the same relation twice.
>
> The overview shows one card (§5.4: the only fully coloured surface, its
> effect depends on being alone) with the strongest run across all active
> habits — including ones not scheduled today, or a Mon-Fri streak would
> vanish every Saturday. Two deliberate deviations from §5.4: no sparkle
> watermark, since §7 has since given it a fixed meaning as the AI marker,
> and Repeat instead of a flame.

<details><summary>9 Dateien · +478/−15</summary>

- `app/Http/Controllers/DashboardController.php` +41/−4
- `app/Http/Controllers/HabitController.php` +17/−0
- `app/Http/Controllers/HabitGraduationController.php` +7/−7
- `app/Models/Habit.php` +141/−4
- `resources/js/components/streak-card.tsx` +56/−0
- `resources/js/pages/dashboard.tsx` +10/−0
- `resources/js/pages/habits/index.tsx` +8/−0
- `resources/js/types/habit.ts` +7/−0
- `tests/Feature/HabitStreakTest.php` +191/−0

</details>

### feat: put the real Align mark in place, light and dark

`a45a10e` · **Silas2505** · 23:19 Uhr

> The app icon comes from the design sheet in the concept repo, which turned
> out to be a raster PNG wrapped in an SVG rather than a vector. Both tiles
> are cropped out of it: cream for light, gold-on-black for dark.
>
> Switched via the `.dark` class, not `prefers-color-scheme` — the appearance
> setting has three states, and a media query would override an explicit
> choice of "light" on a dark system. The favicon is the other way round: the
> tab bar belongs to the OS, so `media` is right there, with favicon.ico as
> the fallback for browsers that ignore it.
>
> Favicon and apple-touch-icon use the dark tile. Both usually land on a light
> surface, where the cream tile would disappear, and it reads better small.
>
> The olive container tile in the sidebar is gone — the mark brings its own.
> Smallest placements moved up to 28-32px, because below that the orbit ring
> and the three dots turn to mush. Vectorising the tile would fix that
> properly, but that is design work of its own.

<details><summary>14 Dateien · +49/−78</summary>

- `public/apple-touch-icon.png` +0/−0
- `public/favicon.ico` +0/−0
- `public/icon-dark.png` +0/−0
- `public/icon-light.png` +0/−0
- `public/logo-dark.webp` +0/−0
- `public/logo-light.webp` +0/−0
- `resources/js/components/app-header.tsx` +1/−1
- `resources/js/components/app-logo-icon.tsx` +28/−68
- `resources/js/components/app-logo.tsx` +3/−3
- `resources/js/layouts/auth/auth-card-layout.tsx` +1/−1
- `resources/js/layouts/auth/auth-simple-layout.tsx` +1/−1
- `resources/js/layouts/auth/auth-split-layout.tsx` +9/−2
- `resources/js/pages/onboarding.tsx` +1/−1
- `resources/views/app.blade.php` +5/−1

</details>

### feat: let two people take a habit on together for one day

`707136d` · **berbahc** · 20:28 Uhr



<details><summary>21 Dateien · +1886/−25</summary>

- `app/Http/Controllers/AppointmentController.php` +63/−0
- `app/Http/Controllers/DashboardController.php` +92/−0
- `app/Http/Controllers/HabitController.php` +25/−1
- `app/Http/Requests/ProposeAppointmentRequest.php` +104/−0
- `app/Models/Appointment.php` +184/−0
- `app/Models/Habit.php` +10/−0
- `app/Policies/AppointmentPolicy.php` +32/−0
- `database/factories/AppointmentFactory.php` +34/−0
- `database/migrations/2026_08_09_194043_create_appointments_table.php` +56/−0
- `resources/js/components/appointment-request-notice.tsx` +92/−0
- `resources/js/components/appointment-sheet.tsx` +188/−0
- `resources/js/components/companion-step.tsx` +177/−0
- `resources/js/components/habit-row.tsx` +54/−14
- `resources/js/components/upcoming-appointments.tsx` +103/−0
- `resources/js/pages/dashboard.tsx` +51/−1
- `resources/js/pages/habits/create.tsx` +38/−9
- `resources/js/types/friendship.ts` +42/−0
- `resources/js/types/global.d.ts` +4/−0
- `resources/js/types/habit.ts` +9/−0
- `routes/web.php` +10/−0
- `tests/Feature/AppointmentTest.php` +518/−0

</details>

### feat: build the friendship layer behind the appointment

`434c1fb` · **berbahc** · 18:51 Uhr



<details><summary>27 Dateien · +1706/−26</summary>

- `app/Actions/Fortify/CreateNewUser.php` +9/−0
- `app/Concerns/ProfileValidationRules.php` +25/−0
- `app/Http/Controllers/AppointmentAvailabilityController.php` +34/−0
- `app/Http/Controllers/DashboardController.php` +5/−0
- `app/Http/Controllers/FriendshipController.php` +108/−0
- `app/Http/Requests/AddFriendRequest.php` +130/−0
- `app/Http/Requests/Settings/ProfileUpdateRequest.php` +15/−0
- `app/Models/Friendship.php` +136/−0
- `app/Models/User.php` +109/−1
- `app/Policies/FriendshipPolicy.php` +35/−0
- `database/factories/FriendshipFactory.php` +33/−0
- `database/factories/UserFactory.php` +3/−0
- `database/migrations/2026_08_09_171712_create_friendships_table.php` +70/−0
- `database/migrations/2026_08_09_180653_add_username_to_users_table.php` +83/−0
- `resources/js/components/friend-request-notice.tsx` +98/−0
- `resources/js/components/person-circle.tsx` +41/−0
- `resources/js/pages/auth/register.tsx` +25/−5
- `resources/js/pages/community.tsx` +245/−14
- `resources/js/pages/dashboard.tsx` +10/−1
- `resources/js/pages/settings/profile.tsx` +27/−1
- `resources/js/types/auth.ts` +2/−0
- `resources/js/types/friendship.ts` +12/−0
- `resources/js/types/index.ts` +1/−0
- `routes/web.php` +19/−4
- `tests/Feature/Auth/RegistrationTest.php` +46/−0
- `tests/Feature/FriendshipTest.php` +383/−0
- `tests/Feature/Settings/ProfileUpdateTest.php` +2/−0

</details>

### fix: pin the anchor so the adjustment test stops flaking

`32de437` · **berbahc** · 18:50 Uhr



<details><summary>1 Datei · +5/−0</summary>

- `tests/Feature/HabitAdjustmentTest.php` +5/−0

</details>

### feat: let the user pick light or dark from the header

`0b6be77` · **berbahc** · 16:34 Uhr

> The dark mode existed but was two navigations deep, in the appearance
> settings. Everyone who never went looking saw the light theme and assumed
> that was all there is.
>
> The button is deliberately two-valued. "System" stays in the settings,
> where three tabs can carry three states honestly; a single icon cannot.
> When the appearance is set to "system", the click follows what is on
> screen — a dark system resolves to a click towards light, not back to
> system.
>
> Sun and moon are one shape, not two icons trading places: a second circle
> slides in from the top right and bites a crescent out of the disc while
> the rays scale away. Only transform and opacity move, 200ms without
> springs, per Designsprache §6.
>
> The dark palette this now exposes is still the placeholder from §11.4 —
> reaching it in one click makes that design round more urgent, not less.

<details><summary>2 Dateien · +122/−0</summary>

- `resources/js/components/app-sidebar-header.tsx` +3/−0
- `resources/js/components/appearance-toggle.tsx` +119/−0

</details>

### style: give eight test files their missing trailing newline

`d1353bd` · **Silas2505** · 15:21 Uhr

> Vorbestehende Pint-Verstöße, die `composer lint:check` und damit
> `composer test` scheitern ließen. Nur der Zeilenumbruch am Dateiende,
> kein inhaltlicher Eingriff.

<details><summary>8 Dateien · +8/−8</summary>

- `tests/Feature/Auth/AuthenticationTest.php` +1/−1
- `tests/Feature/Auth/PasswordConfirmationTest.php` +1/−1
- `tests/Feature/Auth/PasswordResetTest.php` +1/−1
- `tests/Feature/Auth/RegistrationTest.php` +1/−1
- `tests/Feature/Auth/TwoFactorChallengeTest.php` +1/−1
- `tests/Feature/Settings/ProfileUpdateTest.php` +1/−1
- `tests/Feature/Settings/SecurityTest.php` +1/−1
- `tests/Unit/ExampleTest.php` +1/−1

</details>

### feat: add the day calendar and let Claude move a block

`459d64b` · **Silas2505** · 15:20 Uhr

> Die dynamische Anpassung ist mit ø 4,04 die zweitbestbewertete Funktion
> der Umfrage und bei der Einzelwahl auf Platz 2 — und sie braucht eine
> Fläche, auf der ein Block sichtbar an einer Stelle des Tages liegt,
> sonst ist „verschieben" nur ein Formularfeld.
>
> Der Kalender zeigt einen Tag als Achse von Ankern, mit Pfeilen zu den
> Tagen davor. Vergangene Tage sind neutral: kein Rot, keine Kreuze, keine
> markierte Lücke. Im Sieben-Tage-Fenster lässt sich nachtragen — die Regel
> gab es schon im Server, jetzt hat sie eine Oberfläche.
>
> Von jedem Block aus fragt man „Passt der Zeitpunkt?". Claude nennt erst,
> was ihm aufgefallen ist, dann zwei bis drei Alternativen in der Form, die
> der Nutzer selbst gewählt hat — Situationen für situative Gewohnheiten,
> Uhrzeiten für feste. Die gewählte erscheint als Ghost-Block an ihrer
> neuen Stelle, bevor irgendetwas entschieden ist. „Lass so" und
> „Übernehmen" sind gleich breit; die Bestätigung trägt den Weg zurück.
>
> Der Kalender ersetzt „Verlauf" in der Navigation: ein Platzhalter für das
> einzige Feature ganz ohne Umfragedaten weicht der belegten Langzeitsicht.

<details><summary>26 Dateien · +2155/−121</summary>

- `app/Ai/Agents/SuggestBetterAnchor.php` +285/−0
- `app/Http/Controllers/CalendarController.php` +150/−0
- `app/Http/Controllers/HabitAdjustmentController.php` +176/−0
- `app/Http/Controllers/HabitController.php` +1/−1
- `app/Http/Controllers/OnboardingController.php` +1/−1
- `app/Http/Requests/AdjustHabitRequest.php` +101/−0
- `app/Models/Habit.php` +95/−10
- `resources/js/components/adjustment-sheet.tsx` +202/−0
- `resources/js/components/app-sidebar.tsx` +10/−8
- `resources/js/components/calendar-block.tsx` +148/−0
- `resources/js/components/flash-notice.tsx` +47/−12
- `resources/js/components/graduated-habit-row.tsx` +2/−17
- `resources/js/components/habit-row.tsx` +2/−10
- `resources/js/components/habit-wizard.tsx` +3/−9
- `resources/js/hooks/use-anchor-suggestions.ts` +56/−0
- `resources/js/lib/behavior-icons.ts` +16/−0
- `resources/js/pages/calendar.tsx` +206/−0
- `resources/js/pages/habits/index.tsx` +2/−18
- `resources/js/pages/journey.tsx` +0/−30
- `resources/js/types/global.d.ts` +20/−0
- `resources/js/types/habit.ts` +38/−0
- `routes/web.php` +15/−4
- `tests/Feature/CalendarTest.php` +240/−0
- `tests/Feature/FeaturePagesTest.php` +1/−1
- `tests/Feature/HabitAdjustmentTest.php` +336/−0
- `tests/Pest.php` +2/−0

</details>

### fix: drop unsupported minItems/maxItems from the step schema

`f8ebceb` · **Silas2505** · 14:34 Uhr

> Claude's native structured-output format rejects minItems/maxItems on
> array types ("property 'maxItems' is not supported"), so every real
> call failed with a 400 — only the faked tests ever exercised the schema.
> Verified live against OpenRouter now that a key is configured.
>
> The count is still bounded, just later: the prompt asks for up to
> SuggestionCount steps, and suggest() already caps and validates the
> response.

<details><summary>1 Datei · +5/−2</summary>

- `app/Ai/Agents/SuggestSmallestStep.php` +5/−2

</details>

### feat: route the Claude calls through OpenRouter

`9638430` · **Silas2505** · 14:27 Uhr

> Anbieter und Modell standen als Attribute im Agenten und legten Anthropic
> direkt fest. Beides steht jetzt in config/ai.php und kommt aus der
> Umgebung: OpenRouter ist der Standardweg, Anthropic direkt bleibt eine
> Frage von zwei Zeilen in der .env.
>
> Der Agent kennt den Unterschied nicht — er bekommt in beiden Fällen
> dasselbe Modell, nur über einen anderen Vermittler.

<details><summary>3 Dateien · +24/−9</summary>

- `.env.example` +8/−2
- `app/Ai/Agents/SuggestSmallestStep.php` +4/−5
- `config/ai.php` +12/−2

</details>

### feat: let Claude propose the smallest next step

`9f0a024` · **Silas2505** · 14:19 Uhr

> Die Starthilfe ist mit ø 4,16 die bestbewertete Funktion der Umfrage und
> war bislang gar nicht gebaut — die App hatte überhaupt keine KI.
>
> Beim Anlegen schlägt Claude drei erste Handgriffe vor (Schritt 4 von 5,
> überspringbar). Der gewählte steht danach unter der offenen Gewohnheit,
> und „Ich komm nicht rein" öffnet ein Sheet, das ihn weiter zerlegt.
> „Passt" hakt den Tag ab — der Teilschritt zählt als Erfolg.
>
> Bewusst ohne Regel-Fallback: was wie ein KI-Vorschlag aussieht, muss
> einer sein. Fällt der Aufruf aus, sagt die Oberfläche das und bietet
> einen neuen Versuch; der Weg weiter bleibt daneben offen.

<details><summary>24 Dateien · +2208/−56</summary>

- `.claude/skills/ai-sdk-development/SKILL.md` +483/−0
- `.env.example` +4/−0
- `CLAUDE.md` +8/−0
- `app/Actions/CreateHabit.php` +1/−1
- `app/Ai/Agents/SuggestSmallestStep.php` +197/−0
- `app/Http/Controllers/DashboardController.php` +6/−0
- `app/Http/Controllers/SmallestStepController.php` +95/−0
- `app/Http/Requests/StoreHabitRequest.php` +6/−1
- `app/Models/Habit.php` +2/−1
- `boost.json` +1/−0
- `composer.json` +3/−2
- `composer.lock` +365/−1
- `config/ai.php` +159/−0
- `database/migrations/2026_08_09_140136_add_smallest_step_to_habits_table.php` +34/−0
- `resources/js/components/ai-suggestion.tsx` +62/−0
- `resources/js/components/habit-row.tsx` +79/−47
- `resources/js/components/habit-wizard.tsx` +173/−3
- `resources/js/components/starting-help-sheet.tsx` +172/−0
- `resources/js/hooks/use-smallest-step.ts` +68/−0
- `resources/js/pages/dashboard.tsx` +11/−0
- `resources/js/types/habit.ts` +7/−0
- `routes/web.php` +14/−0
- `tests/Feature/SmallestStepTest.php` +251/−0
- `tests/Pest.php` +7/−0

</details>

### feat: show the reminder in the app and roll the day over locally

`1948a27` · **berbahc** · 02:45 Uhr

> Der Hinweis ist jetzt ein Zustand statt eines Ereignisses: er gilt von zehn
> Minuten vorher bis eine Stunde danach und lässt sich nicht mehr verpassen.
> Die Systemmeldung bleibt für den Fall, dass der Tab nicht sichtbar ist.
>
> Der Tageswechsel folgt der Ortszeit statt UTC — sonst bucht ein Abhaken um
> Mitternacht auf den Vortag.
>
> Enthält außerdem die Vorarbeit fürs Nachtragen (Habit::weekOverview,
> completionDate im Controller), die noch an keiner Oberfläche hängt.

<details><summary>8 Dateien · +318/−17</summary>

- `app/Http/Controllers/HabitCompletionController.php` +48/−7
- `app/Models/Habit.php` +41/−0
- `config/app.php` +7/−3
- `resources/js/app.tsx` +15/−1
- `resources/js/components/habit-reminder-notice.tsx` +105/−0
- `resources/js/hooks/use-habit-reminders.ts` +98/−5
- `resources/js/layouts/app-layout.tsx` +2/−0
- `resources/js/pages/habits/index.tsx` +2/−1

</details>

### chore: reword the habit limit note

`b186044` · **berbahc** · 01:25 Uhr



<details><summary>1 Datei · +4/−5</summary>

- `resources/js/components/habit-limit-note.tsx` +4/−5

</details>

### feat: explain the limit of five where it starts to bite

`61ae7d4` · **berbahc** · 01:15 Uhr

> Reaching five habits made the "Neu hinzufügen" button disappear without a
> word. Same silence as the habit that vanished after being created: the
> option is gone, the reason is not given anywhere.
>
> A small info symbol now sits next to the count, but only once the limit is
> reached — below five it would explain a restriction nobody has run into.
> A click opens three sentences: why the ceiling exists, that a short list
> is the higher hit rate rather than the smaller ambition, and that ending a
> habit frees the slot. The last one matters most; it turns a dead end into
> an action.
>
> Collapsible instead of a tooltip: there is no hover on a phone, so a
> tooltip would be unreachable exactly where the app is used.
>
> The five itself was typed into the frontend in three places. Both
> controllers now pass maxActive from Habit::MaxActivePerUser, so changing
> the constant no longer leaves the interface quietly claiming something
> else. A test holds that.
>
> Note on the wording: progress-tracking.md is cited in six places in the
> code but is not in the repository, so the text states the mechanism
> without claiming a source. Lally et al. (2010) would be a misattribution
> here — it measures how long a single habit takes to become automatic, not
> how many can run at once. Worth sharpening once the document is at hand.

<details><summary>6 Dateien · +101/−10</summary>

- `app/Http/Controllers/DashboardController.php` +1/−0
- `app/Http/Controllers/HabitController.php` +1/−0
- `resources/js/components/habit-limit-note.tsx` +57/−0
- `resources/js/pages/dashboard.tsx` +4/−1
- `resources/js/pages/habits/index.tsx` +27/−9
- `tests/Feature/HabitScheduleTest.php` +11/−0

</details>

### fix: say where a new habit went instead of hiding it

`bbdcc82` · **berbahc** · 01:13 Uhr

> Creating a habit with a fixed time looked like it had not been saved. The
> wizard defaults to Mo–Fr, store() redirects to the dashboard, and the
> dashboard only lists what is scheduled today — so a habit created on a
> Saturday landed on the one screen that cannot show it, without a word.
>
> Three things were wrong at once:
>
> Creating gave no feedback. It now flashes a confirmation naming the next
> occurrence: "steht am Montag um 17:00 in deiner Tagesliste", plus an
> explicit note when that is not today. Habit::nextOccurrence() finds the
> day, Inertia::flash carries it, FlashNotice renders it from the layout.
>
> An empty day was indistinguishable from an empty list. The dashboard got
> `habits` filtered to today but had no idea whether any habit existed at
> all, so a Saturday with five Mo–Fr habits claimed "Du verfolgst noch
> keine Gewohnheiten" and pushed to create another. It now receives
> activeCount and tells the two apart.
>
> Two calls to action pointed at the same place. In the empty state the
> outline button in the section header stood next to the filled "Erste
> Gewohnheit anlegen". The header button now yields to it, and disappears
> at five habits as well — it used to lead into a wizard that would be
> rejected at the end, which the habits page already handled correctly.

<details><summary>9 Dateien · +293/−11</summary>

- `app/Http/Controllers/DashboardController.php` +6/−0
- `app/Http/Controllers/HabitController.php` +45/−1
- `app/Models/Habit.php` +22/−0
- `resources/js/components/flash-notice.tsx` +64/−0
- `resources/js/layouts/app-layout.tsx` +2/−0
- `resources/js/pages/dashboard.tsx` +39/−10
- `resources/js/types/global.d.ts` +13/−0
- `tests/Feature/DashboardTest.php` +27/−0
- `tests/Feature/HabitScheduleTest.php` +75/−0

</details>

### feat: generate Dokumentation.md from the git history

`0851db7` · **berbahc** · 01:12 Uhr

> Berkay wanted a document that makes the project history readable — his own
> commits and the ones others push — without having to read git log.
>
> It is generated, never edited: `php artisan dokumentation:generate` writes
> the file from scratch on every run, so it cannot drift from the truth, and
> commits pulled from others appear by themselves. Per commit it carries the
> subject, hash, author, time, the full message as a quote and the changed
> files with their line counts, grouped by day. Commits that exist only
> locally are marked, so it is visible what others can already see.
>
> The file is gitignored on purpose. It is derived from history, so tracking
> it would produce a conflict on nearly every merge — both sides rewrite the
> same file — over content that regenerates in a second. The command is
> tracked instead, so anyone in the repo can produce the same document.
>
> Locally it runs from post-commit, post-merge and post-rewrite hooks. Those
> live in .git/hooks and are not shared; a new machine needs them set up
> once.

<details><summary>3 Dateien · +351/−0</summary>

- `.gitignore` +3/−0
- `app/Console/Commands/GenerateDokumentation.php` +304/−0
- `tests/Feature/DokumentationTest.php` +44/−0

</details>

### feat: end habits instead of being stuck at five

`eab18c5` · **berbahc** · 01:12 Uhr

> The limit of five active habits had no exit. Once five existed there was
> no way to free a slot — StoreHabitRequest rejected the sixth and nothing
> in the interface could remove one. A dead end.
>
> graduated_at already existed: column, cast, active() scope, factory state
> and a test named "a graduated habit frees a slot". Nothing ever set it.
> This wires it up rather than adding a second mechanism.
>
> Ending is the visible action, in a row menu on the habits page, without a
> confirmation — the habit moves into an archive right below where picking
> it up again is one click away. reminder_enabled and every completion stay
> untouched, so the way back restores the habit exactly as it was.
>
> Deleting for good sits in that archive only, behind a dialog that names
> how many completed days would go with it. progress-tracking.md drops the
> streak because one missed day must not undo everything; losing four weeks
> of history to a bad moment would be the same punishment, larger. It stays
> possible — own data has to be removable — but it is not the default path.
>
> Picking a habit up again checks the limit server-side, otherwise the
> archive would be a way around the five that StoreHabitRequest enforces.
>
> graduated_at is deliberately not in the Fillable list, so the controller
> assigns it directly instead of through update().

<details><summary>9 Dateien · +561/−7</summary>

- `app/Http/Controllers/HabitController.php` +35/−0
- `app/Http/Controllers/HabitGraduationController.php` +64/−0
- `app/Models/Habit.php` +11/−0
- `app/Policies/HabitPolicy.php` +20/−0
- `resources/js/components/graduated-habit-row.tsx` +156/−0
- `resources/js/pages/habits/index.tsx` +102/−7
- `resources/js/types/habit.ts` +17/−0
- `routes/web.php` +10/−0
- `tests/Feature/HabitGraduationTest.php` +146/−0

</details>

## 08.08.2026

### feat: add fixed-time scheduling and 10-minute reminders

`0be79b4` · **Silas2505** · 22:52 Uhr

> Habits could only be anchored to a situation ("nach dem Aufstehen").
> That stays the default — time-blocking.md argues a situation triggers
> behaviour by itself while a time has to be actively remembered — but
> habits that really do sit at a fixed point had to be paraphrased into
> a situation. They now get a second option: a time plus weekdays.
>
> Reminders build on that. A "10 minutes before" only exists where there
> is a before, so the switch is limited to fixed-time habits; the bulk
> switch passes over situational ones instead of rejecting them.
> Delivery uses the Notification API while the app is open — no service
> worker, no push. That is the deliberate boundary of this step.
>
> Consistency now counts only the days a habit is actually scheduled for.
> Without that a flawless Mo–Fr habit would be capped at 71 percent, which
> would be exactly the punishment the project rules out.
>
> The habits page turns from a placeholder into a real list carrying both
> switches.

<details><summary>29 Dateien · +1664/−110</summary>

- `app/Actions/CreateHabit.php` +1/−1
- `app/Enums/ScheduleType.php` +39/−0
- `app/Http/Controllers/DashboardController.php` +27/−6
- `app/Http/Controllers/HabitController.php` +29/−0
- `app/Http/Controllers/HabitReminderController.php` +57/−0
- `app/Http/Controllers/OnboardingController.php` +2/−0
- `app/Http/Middleware/HandleInertiaRequests.php` +43/−0
- `app/Http/Requests/StoreHabitRequest.php` +56/−3
- `app/Models/Habit.php` +154/−3
- `app/Policies/HabitPolicy.php` +8/−0
- `database/factories/HabitFactory.php` +27/−0
- `database/migrations/2026_08_08_171129_add_fixed_schedule_to_habits_table.php` +46/−0
- `database/migrations/2026_08_08_171130_add_reminder_to_habits_table.php` +33/−0
- `resources/js/components/habit-reminders.tsx` +17/−0
- `resources/js/components/habit-row.tsx` +1/−1
- `resources/js/components/habit-wizard.tsx` +110/−74
- `resources/js/components/schedule-picker.tsx` +265/−0
- `resources/js/components/ui/toggle-switch.tsx` +51/−0
- `resources/js/hooks/use-habit-reminders.ts` +157/−0
- `resources/js/layouts/app-layout.tsx` +5/−0
- `resources/js/pages/habits/create.tsx` +4/−0
- `resources/js/pages/habits/index.tsx` +180/−16
- `resources/js/pages/onboarding.tsx` +4/−0
- `resources/js/types/global.d.ts` +3/−0
- `resources/js/types/habit.ts` +39/−2
- `routes/web.php` +12/−4
- `tests/Feature/DashboardTest.php` +61/−0
- `tests/Feature/HabitReminderTest.php` +110/−0
- `tests/Feature/HabitScheduleTest.php` +123/−0

</details>

### test: expect homepage redirect to login

`d6953c0` · **berbahc** · 18:16 Uhr

> Die Startseite rendert seit 5a9ac1e nicht mehr die welcome-Seite,
> sondern leitet auf /login um. Der Test prüft jetzt den Redirect
> statt Status 200 und der Inertia-Komponente.

<details><summary>1 Datei · +2/−5</summary>

- `tests/Feature/ExampleTest.php` +2/−5

</details>

## 04.08.2026

### feat: adjust app logo

`f63598b` · **berbahc** · 23:36 Uhr

<details><summary>2 Dateien · +70/−7</summary>

- `resources/js/components/app-logo-icon.tsx` +68/−5
- `resources/js/layouts/auth/auth-simple-layout.tsx` +2/−2

</details>

### chore: redirect homepage to login

`8924ca5` · **berbahc** · 23:27 Uhr

<details><summary>1 Datei · +1/−1</summary>

- `routes/web.php` +1/−1

</details>

## 03.08.2026

### Add onboarding, habit check-off and the three feature sections

`8516ec1` · **berbahc** · 21:56 Uhr

> Registration used to land on an empty dashboard. An EnsureOnboarded middleware
> now sends new users through a four-step flow once: direction, habit, trigger,
> commitment. Skipping counts the same as finishing, so nobody is pushed into it
> twice — the app does not demand anything.
>
> The flow proposes rather than interrogates. Picking a direction (Bewegung &
> Sport, Lernen & Fokus, Essen & Trinken, Schlaf & Erholung) yields concrete
> student habits drawn from the interviews: Aylin's meal prep as a daily anchor,
> Felix moving more on campus than at home, Danial treating sleep as the
> precondition for everything else. All suggestions are deliberately small —
> Ngocanh fails at "what exactly", not at willpower. The trigger is a situation,
> never a time of day, and the closing button sets committed_at.
>
> Habits can now be ticked off from the dashboard and un-ticked again; an undo
> has to exist, or a mistap creates a state you cannot leave. The toggle is
> optimistic so it responds instantly, with Inertia rolling back on failure. A
> HabitPolicy guards ownership and firstOrCreate absorbs double clicks.
>
> The daily goal is simply the number of active habits — there is no separate
> target to miss. The card shows today's percentage with the 30-day consistency
> rate beneath it: immediate feedback above, long-term picture below. Everything
> is phrased as what is done, never as what is missing.
>
> Three sections are scaffolded behind the sidebar: Gewohnheiten, Verlauf and
> Community. They are placeholders, but each states what it will do, taken from
> the feature docs. The Community page names the boundary explicitly — no
> ranking, no points, no badges — so the decision of 18.07.2026 is visible where
> it matters. Routes stay Route::inertia until they actually carry data.
>
> The Mockup item "Analytics" is called "Verlauf" here because habit-journey.md
> specifies a per-habit, strictly individual curve.
>
> Also: InputError used a hardcoded red, which contradicts §1.4 of the design
> language; it now uses the warm destructive token.

<details><summary>31 Dateien · +1686/−59</summary>

- `app/Actions/CreateHabit.php` +31/−0
- `app/Enums/BehaviorType.php` +76/−10
- `app/Http/Controllers/DashboardController.php` +25/−0
- `app/Http/Controllers/HabitCompletionController.php` +47/−0
- `app/Http/Controllers/HabitController.php` +29/−0
- `app/Http/Controllers/OnboardingController.php` +45/−0
- `app/Http/Middleware/EnsureOnboarded.php` +29/−0
- `app/Http/Requests/StoreHabitRequest.php` +89/−0
- `app/Models/Habit.php` +20/−1
- `app/Models/User.php` +2/−0
- `app/Policies/HabitPolicy.php` +20/−0
- `database/factories/UserFactory.php` +14/−0
- `database/migrations/2026_08_03_191150_add_onboarded_at_to_users_table.php` +29/−0
- `database/migrations/2026_08_03_191151_add_motivation_to_habits_table.php` +30/−0
- `database/seeders/DatabaseSeeder.php` +1/−0
- `resources/js/app.tsx` +3/−1
- `resources/js/components/app-sidebar.tsx` +21/−5
- `resources/js/components/feature-placeholder.tsx` +64/−0
- `resources/js/components/habit-row.tsx` +40/−20
- `resources/js/components/habit-wizard.tsx` +426/−0
- `resources/js/components/input-error.tsx` +3/−1
- `resources/js/pages/community.tsx` +30/−0
- `resources/js/pages/dashboard.tsx` +109/−20
- `resources/js/pages/habits/create.tsx` +40/−0
- `resources/js/pages/habits/index.tsx` +32/−0
- `resources/js/pages/journey.tsx` +30/−0
- `resources/js/pages/onboarding.tsx` +61/−0
- `routes/web.php` +27/−1
- `tests/Feature/FeaturePagesTest.php` +27/−0
- `tests/Feature/HabitCompletionTest.php` +120/−0
- `tests/Feature/OnboardingTest.php` +166/−0

</details>

### Apply Align design language and replace ranking with real habit data

`4f4da15` · **berbahc** · 20:58 Uhr

> Implement the binding design language from designsprache.md: the measured
> palette (canvas #FDF9F8, single accent olive #775A19), Sora as the only
> typeface, and the 16/14/12 radii. Values are mapped onto the existing shadcn
> variables so current UI components follow automatically. Measured contrasts
> reproduce §2.3 exactly.
>
> Remove the network ranking widget. Ranking, impact score and badges were
> explicitly ruled out by the team decision of 18.07.2026 on the grounds of the
> user survey (Hannah rejects comparison apps, Ngocanh shows ranking motivation
> collapsing long-term) and by community.md.
>
> Replace it with a real domain: habits and habit_completions with models,
> factories and a seeder. The dashboard route now goes through a controller
> delivering actual props instead of Route::inertia with hardcoded frontend
> constants. Two schema decisions follow the feature docs: only fulfilled days
> are stored (a missed day is an absent row, never a marked state), and habits
> carry a trigger situation rather than a time of day.
>
> Progress is shown as a 30-day consistency rate rather than a streak, per
> progress-tracking.md. Both mockups show a streak instead, so this remains an
> open team decision.
>
> Two fixes found along the way:
> - The date cast writes "Y-m-d H:i:s", so whereBetween against a "Y-m-d" string
>   dropped the final day of the window (97% instead of 100%). Visible on SQLite,
>   hidden on MySQL/Postgres. Now compared with Carbon instances and covered by a
>   test on that boundary.
> - Form field borders reached only 1.29:1 where WCAG 1.4.11 requires 3:1.
>   --input is set to #8E8A82; this is not backed by designsprache.md and needs
>   team review.
>
> The check circle shows state but is not a button — checking off needs its own
> route, and progress-tracking.md describes it as an evening check-in flow.
> Dark mode is a placeholder derivation; §11.4 says it needs its own round.

<details><summary>23 Dateien · +972/−240</summary>

- `CLAUDE.md` +8/−0
- `app/Enums/BehaviorType.php` +45/−0
- `app/Http/Controllers/DashboardController.php` +81/−0
- `app/Models/Habit.php` +103/−0
- `app/Models/HabitCompletion.php` +41/−0
- `app/Models/User.php` +9/−0
- `database/factories/HabitCompletionFactory.php` +36/−0
- `database/factories/HabitFactory.php` +52/−0
- `database/migrations/2026_08_03_180547_create_habits_table.php` +46/−0
- `database/migrations/2026_08_03_180548_create_habit_completions_table.php` +35/−0
- `database/seeders/DatabaseSeeder.php` +90/−4
- `resources/css/app.css` +126/−68
- `resources/js/components/app-sidebar.tsx` +6/−14
- `resources/js/components/habit-row.tsx` +82/−0
- `resources/js/components/network-ranking.tsx` +0/−129
- `resources/js/pages/dashboard.tsx` +105/−18
- `resources/js/types/habit.ts` +12/−0
- `resources/js/types/index.ts` +1/−0
- `resources/views/app.blade.php` +2/−2
- `routes/web.php` +2/−1
- `tests/Feature/DashboardTest.php` +84/−1
- `tests/Feature/ExampleTest.php` +4/−1
- `vite.config.ts` +2/−2

</details>

### Merge branch 'feature/network-ranking-widget'

`ff41388` · **berbahc** · 00:26 Uhr

> Add network ranking widget to dashboard.

### Add network ranking widget to dashboard

`58489de` · **berbahc** · 00:25 Uhr

> Show the current user alongside network peers ranked by impact score,
> with the current user's row highlighted. Uses hardcoded test data.

<details><summary>2 Dateien · +131/−3</summary>

- `resources/js/components/network-ranking.tsx` +129/−0
- `resources/js/pages/dashboard.tsx` +2/−3

</details>

## 02.08.2026

### first commit

`3224d39` · **berbahc** · 23:45 Uhr

<details><summary>211 Dateien · +33604/−0</summary>

- `.claude/skills/fortify-development/SKILL.md` +151/−0
- `.claude/skills/inertia-react-development/SKILL.md` +524/−0
- `.claude/skills/laravel-best-practices/SKILL.md` +59/−0
- `.claude/skills/laravel-best-practices/rules/advanced-queries.md` +106/−0
- `.claude/skills/laravel-best-practices/rules/architecture.md` +202/−0
- `.claude/skills/laravel-best-practices/rules/blade-views.md` +36/−0
- `.claude/skills/laravel-best-practices/rules/caching.md` +70/−0
- `.claude/skills/laravel-best-practices/rules/collections.md` +44/−0
- `.claude/skills/laravel-best-practices/rules/config.md` +73/−0
- `.claude/skills/laravel-best-practices/rules/db-performance.md` +192/−0
- `.claude/skills/laravel-best-practices/rules/eloquent.md` +148/−0
- `.claude/skills/laravel-best-practices/rules/error-handling.md` +72/−0
- `.claude/skills/laravel-best-practices/rules/events-notifications.md` +52/−0
- `.claude/skills/laravel-best-practices/rules/http-client.md` +160/−0
- `.claude/skills/laravel-best-practices/rules/mail.md` +27/−0
- `.claude/skills/laravel-best-practices/rules/migrations.md` +121/−0
- `.claude/skills/laravel-best-practices/rules/queue-jobs.md` +144/−0
- `.claude/skills/laravel-best-practices/rules/routing.md` +99/−0
- `.claude/skills/laravel-best-practices/rules/scheduling.md` +39/−0
- `.claude/skills/laravel-best-practices/rules/security.md` +198/−0
- `.claude/skills/laravel-best-practices/rules/style.md` +125/−0
- `.claude/skills/laravel-best-practices/rules/testing.md` +43/−0
- `.claude/skills/laravel-best-practices/rules/validation.md` +75/−0
- `.claude/skills/pest-testing/SKILL.md` +203/−0
- `.claude/skills/tailwindcss-development/SKILL.md` +119/−0
- `.claude/skills/wayfinder-development/SKILL.md` +80/−0
- `.editorconfig` +18/−0
- `.env.example` +65/−0
- `.gitattributes` +11/−0
- `.github/dependabot.yml` +12/−0
- `.github/workflows/tests.yml` +38/−0
- `.gitignore` +29/−0
- `.mcp.json` +11/−0
- `.npmrc` +1/−0
- `.prettierignore` +2/−0
- `.prettierrc` +25/−0
- `CLAUDE.md` +207/−0
- `app/Actions/Fortify/CreateNewUser.php` +33/−0
- `app/Actions/Fortify/ResetUserPassword.php` +29/−0
- `app/Concerns/PasswordValidationRules.php` +29/−0
- `app/Concerns/ProfileValidationRules.php` +51/−0
- `app/Http/Controllers/Controller.php` +8/−0
- `app/Http/Controllers/Settings/ProfileController.php` +62/−0
- `app/Http/Controllers/Settings/SecurityController.php` +66/−0
- `app/Http/Middleware/HandleAppearance.php` +23/−0
- `app/Http/Middleware/HandleInertiaRequests.php` +47/−0
- `app/Http/Requests/Settings/PasswordUpdateRequest.php` +25/−0
- `app/Http/Requests/Settings/ProfileDeleteRequest.php` +24/−0
- `app/Http/Requests/Settings/ProfileUpdateRequest.php` +22/−0
- `app/Http/Requests/Settings/TwoFactorAuthenticationRequest.php` +22/−0
- `app/Models/User.php` +50/−0
- `app/Providers/AppServiceProvider.php` +50/−0
- `app/Providers/FortifyServiceProvider.php` +96/−0
- `artisan` +18/−0
- `boost.json` +18/−0
- `bootstrap/app.php` +30/−0
- `bootstrap/cache/.gitignore` +2/−0
- `bootstrap/providers.php` +9/−0
- `components.json` +21/−0
- `composer.json` +119/−0
- `composer.lock` +11391/−0
- `config/app.php` +126/−0
- `config/auth.php` +117/−0
- `config/cache.php` +136/−0
- `config/database.php` +184/−0
- `config/filesystems.php` +80/−0
- `config/fortify.php` +176/−0
- `config/inertia.php` +70/−0
- `config/logging.php` +140/−0
- `config/mail.php` +118/−0
- `config/queue.php` +129/−0
- `config/services.php` +38/−0
- `config/session.php` +233/−0
- `database/.gitignore` +1/−0
- `database/factories/UserFactory.php` +60/−0
- `database/migrations/0001_01_01_000000_create_users_table.php` +49/−0
- `database/migrations/0001_01_01_000001_create_cache_table.php` +35/−0
- `database/migrations/0001_01_01_000002_create_jobs_table.php` +59/−0
- `database/migrations/2024_01_01_000000_create_passkeys_table.php` +34/−0
- `database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php` +34/−0
- `database/seeders/DatabaseSeeder.php` +25/−0
- `eslint.config.js` +129/−0
- `package-lock.json` +8072/−0
- `package.json` +76/−0
- `phpstan.neon` +13/−0
- `phpunit.xml` +36/−0
- `pint.json` +3/−0
- `pnpm-workspace.yaml` +5/−0
- `public/.htaccess` +25/−0
- `public/apple-touch-icon.png` +0/−0
- `public/favicon.ico` +0/−0
- `public/favicon.svg` +3/−0
- `public/index.php` +20/−0
- `public/robots.txt` +2/−0
- `resources/css/app.css` +143/−0
- `resources/js/app.tsx` +40/−0
- `resources/js/components/alert-error.tsx` +24/−0
- `resources/js/components/app-content.tsx` +22/−0
- `resources/js/components/app-header.tsx` +248/−0
- `resources/js/components/app-logo-icon.tsx` +13/−0
- `resources/js/components/app-logo.tsx` +20/−0
- `resources/js/components/app-shell.tsx` +21/−0
- `resources/js/components/app-sidebar-header.tsx` +18/−0
- `resources/js/components/app-sidebar.tsx` +65/−0
- `resources/js/components/appearance-tabs.tsx` +45/−0
- `resources/js/components/breadcrumbs.tsx` +50/−0
- `resources/js/components/delete-user.tsx` +120/−0
- `resources/js/components/heading.tsx` +26/−0
- `resources/js/components/input-error.tsx` +17/−0
- `resources/js/components/manage-passkeys.tsx` +71/−0
- `resources/js/components/manage-two-factor.tsx` +126/−0
- `resources/js/components/nav-footer.tsx` +49/−0
- `resources/js/components/nav-main.tsx` +36/−0
- `resources/js/components/nav-user.tsx` +58/−0
- `resources/js/components/passkey-item.tsx` +93/−0
- `resources/js/components/passkey-register.tsx` +108/−0
- `resources/js/components/passkey-verify.tsx` +74/−0
- `resources/js/components/password-input.tsx` +37/−0
- `resources/js/components/text-link.tsx` +23/−0
- `resources/js/components/two-factor-recovery-codes.tsx` +164/−0
- `resources/js/components/two-factor-setup-modal.tsx` +355/−0
- `resources/js/components/ui/alert.tsx` +66/−0
- `resources/js/components/ui/avatar.tsx` +51/−0
- `resources/js/components/ui/badge.tsx` +46/−0
- `resources/js/components/ui/breadcrumb.tsx` +109/−0
- `resources/js/components/ui/button.tsx` +58/−0
- `resources/js/components/ui/card.tsx` +68/−0
- `resources/js/components/ui/checkbox.tsx` +30/−0
- `resources/js/components/ui/collapsible.tsx` +31/−0
- `resources/js/components/ui/dialog.tsx` +133/−0
- `resources/js/components/ui/dropdown-menu.tsx` +255/−0
- `resources/js/components/ui/icon.tsx` +14/−0
- `resources/js/components/ui/input-otp.tsx` +69/−0
- `resources/js/components/ui/input.tsx` +21/−0
- `resources/js/components/ui/label.tsx` +22/−0
- `resources/js/components/ui/navigation-menu.tsx` +168/−0
- `resources/js/components/ui/placeholder-pattern.tsx` +20/−0
- `resources/js/components/ui/select.tsx` +193/−0
- `resources/js/components/ui/separator.tsx` +26/−0
- `resources/js/components/ui/sheet.tsx` +137/−0
- `resources/js/components/ui/sidebar.tsx` +719/−0
- `resources/js/components/ui/skeleton.tsx` +13/−0
- `resources/js/components/ui/sonner.tsx` +27/−0
- `resources/js/components/ui/spinner.tsx` +16/−0
- `resources/js/components/ui/toggle-group.tsx` +71/−0
- `resources/js/components/ui/toggle.tsx` +45/−0
- `resources/js/components/ui/tooltip.tsx` +55/−0
- `resources/js/components/user-info.tsx` +32/−0
- `resources/js/components/user-menu-content.tsx` +63/−0
- `resources/js/hooks/use-appearance.tsx` +115/−0
- `resources/js/hooks/use-clipboard.ts` +32/−0
- `resources/js/hooks/use-current-url.ts` +83/−0
- `resources/js/hooks/use-flash-toast.ts` +19/−0
- `resources/js/hooks/use-initials.tsx` +26/−0
- `resources/js/hooks/use-mobile-navigation.ts` +10/−0
- `resources/js/hooks/use-mobile.tsx` +36/−0
- `resources/js/hooks/use-two-factor-auth.ts` +111/−0
- `resources/js/layouts/app-layout.tsx` +16/−0
- `resources/js/layouts/app/app-header-layout.tsx` +16/−0
- `resources/js/layouts/app/app-sidebar-layout.tsx` +20/−0
- `resources/js/layouts/auth-layout.tsx` +17/−0
- `resources/js/layouts/auth/auth-card-layout.tsx` +48/−0
- `resources/js/layouts/auth/auth-simple-layout.tsx` +38/−0
- `resources/js/layouts/auth/auth-split-layout.tsx` +44/−0
- `resources/js/layouts/settings/layout.tsx` +78/−0
- `resources/js/lib/utils.ts` +12/−0
- `resources/js/pages/auth/confirm-password.tsx` +66/−0
- `resources/js/pages/auth/forgot-password.tsx` +69/−0
- `resources/js/pages/auth/login.tsx` +117/−0
- `resources/js/pages/auth/register.tsx` +120/−0
- `resources/js/pages/auth/reset-password.tsx` +96/−0
- `resources/js/pages/auth/two-factor-challenge.tsx` +133/−0
- `resources/js/pages/dashboard.tsx` +36/−0
- `resources/js/pages/settings/appearance.tsx` +32/−0
- `resources/js/pages/settings/profile.tsx` +105/−0
- `resources/js/pages/settings/security.tsx` +147/−0
- `resources/js/pages/welcome.tsx` +389/−0
- `resources/js/types/auth.ts` +34/−0
- `resources/js/types/global.d.ts` +19/−0
- `resources/js/types/index.ts` +3/−0
- `resources/js/types/navigation.ts` +14/−0
- `resources/js/types/ui.ts` +21/−0
- `resources/js/types/vite-env.d.ts` +1/−0
- `resources/views/app.blade.php` +48/−0
- `routes/console.php` +8/−0
- `routes/settings.php` +34/−0
- `routes/web.php` +11/−0
- `storage/app/.gitignore` +4/−0
- `storage/app/private/.gitignore` +2/−0
- `storage/app/public/.gitignore` +2/−0
- `storage/framework/.gitignore` +9/−0
- `storage/framework/cache/.gitignore` +3/−0
- `storage/framework/cache/data/.gitignore` +2/−0
- `storage/framework/sessions/.gitignore` +2/−0
- `storage/framework/testing/.gitignore` +2/−0
- `storage/framework/views/.gitignore` +2/−0
- `storage/logs/.gitignore` +2/−0
- `tests/Feature/Auth/AuthenticationTest.php` +77/−0
- `tests/Feature/Auth/PasswordConfirmationTest.php` +22/−0
- `tests/Feature/Auth/PasswordResetTest.php` +78/−0
- `tests/Feature/Auth/RegistrationTest.php` +25/−0
- `tests/Feature/Auth/TwoFactorChallengeTest.php` +35/−0
- `tests/Feature/DashboardTest.php` +16/−0
- `tests/Feature/ExampleTest.php` +7/−0
- `tests/Feature/Settings/ProfileUpdateTest.php` +85/−0
- `tests/Feature/Settings/SecurityTest.php` +104/−0
- `tests/Pest.php` +50/−0
- `tests/TestCase.php` +16/−0
- `tests/Unit/ExampleTest.php` +5/−0
- `tsconfig.json` +121/−0
- `vite.config.ts` +31/−0

</details>

