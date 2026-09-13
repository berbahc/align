# Projektbericht Align

Der Bericht entsteht zuerst als Markdown, später wird er in LaTeX in unserer Designsprache
gesetzt. Eine Datei pro Kapitel, fortlaufend nummeriert.

**Stand:** 13.09.2026 · Kapitel 1–9 fertig · rund 16.200 Wörter und 41 Abbildungen

## Aufbau

Der Bericht folgt den vier Entwicklungsphasen; die sechs Iterationen sind darin eingeordnet.

| Datei | Kapitel | Status |
|---|---|---|
| `00-gliederung.md` | Gliederung und Arbeitsplan | — |
| `01-einleitung.md` | Einleitung, Problemstellung, Leitfrage | ✅ |
| `02-problemraum-markt.md` | Anforderungs- und Competitor-Analyse | ✅ |
| `03-vorgehen.md` | Vorgehen und Zusammenarbeit | ✅ |
| `04-phase1-analyse.md` | Phase 1 — Analyse und Grundlagen (Iteration 1) | ✅ |
| `05-phase2-nutzerforschung.md` | Phase 2 — Nutzerforschung (Iterationen 2–3) | ✅ |
| `06-phase3-konzeption-design.md` | Phase 3 — Konzeption und Design (Iteration 4) | ✅ |
| `07-phase4-technische-umsetzung.md` | Phase 4 — Technische Umsetzung (Iterationen 5–6) | ✅ |
| `08-die-fertige-app.md` | Die fertige App, mit Screenshots | ✅ |
| `09-reflexion-ausblick.md` | Reflexion und Ausblick, inklusive der bewusst weggelassenen Funktionen | ✅ |
| — | Anhang | offen |

## Screenshots

Alle Aufnahmen der App sind in der Mobilansicht entstanden (390 px, dreifache Pixeldichte).
Das Demokonto trägt den Namen unserer ersten Persona. Abbildungen sind kapitelweise
nummeriert, also „Abb. 8.3" für die dritte Abbildung in Kapitel 8.

| Ordner | Inhalt | verwendet in |
|---|---|---|
| `screenshots/personas/` | die zwei Persona-Sheets | Kapitel 5 |
| `screenshots/figma/` | Entwürfe aus der Figma-Datei | Kapitel 6 |
| `screenshots/verlauf/` | frühere Stände der App, nachträglich aus dem Code vom 10.08., 31.08. und 03.09. aufgenommen | Kapitel 7 |
| `screenshots/kapitel8/` | Übersicht, Anlegen einer Gewohnheit in fünf Schritten, Verschieben mit Kette, Dark Mode | Kapitel 8 |
| `screenshots/app/` | Auftakt sowie Gewohnheiten und Kalender im Dark Mode | Kapitel 8 |
| `screenshots/abb*.png` | Katalog, Kalender, Gewohnheiten, Schlafplan, Community | Kapitel 7 und 8 |

## Konventionen

- **Wir-Perspektive.** „Wir haben uns entschieden", nicht „das Team entschied".
- **Keine personenbezogene Zuschreibung im Fließtext.** Wer welchen Schwerpunkt hatte, steht
  gebündelt in Kapitel 3.1. Im Verlauf wird beschrieben, was nacheinander geschah.
- **Auf Projektebene bleiben.** Einzelne Werkzeugpannen und Terminfragen gehören nicht in den
  Bericht; berichtet wird, was entschieden wurde und warum.
- **Querverweise** als „Kapitel 5" oder „Abschnitt 7.8", nicht als Dateinamen.

## Offene Punkte

- Anhang schreiben
- **Berkay:** Notiz am Anfang von Kapitel 9 lesen und vor der Abgabe entfernen
- Quelle „Birgmeier" prüfen — wird im alten Entwurf zitiert, liegt aber nicht in
  `quellenrecherche/`
- In der App nennt Schritt 3 des Assistenten „eine Viertelstunde" Abstand zur vorherigen
  Gewohnheit, gebaut sind fünf Minuten. Wird der Satz angepasst, Abb. 8.5 neu aufnehmen.
