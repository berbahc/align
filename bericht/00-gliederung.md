# Bericht Align — Gliederung und Arbeitsplan

**Stand:** 13. September 2026 · **Abgabe:** 16.09.2026
**Vorgabe:** ab 30 Seiten mit Screenshots.

---

## Aufbauidee

Der Bericht folgt den **vier Entwicklungsphasen** des Projekts. Die sechs Iterationen sind
darin eingeordnet — jede Phase enthält sowohl ihren Verlauf als auch ihre Ergebnisse. Damit
gibt es eine einzige durchgehende Achse statt zweier paralleler Strukturen.

**Zur Darstellung der Beiträge:** Der Fließtext beschreibt, was nacheinander geschah, nicht
wer es tat. Die Schwerpunkte der drei Teammitglieder stehen gebündelt in Kapitel 3.1;
personenbezogene Zuschreibungen im Verlauf nur dort, wo sie inhaltlich nötig sind.

**Zum Detailgrad:** Der Bericht bleibt auf Projektebene. Einzelne Werkzeugpannen,
Terminfragen und organisatorische Kleinigkeiten sind bewusst nicht enthalten; berichtet wird,
was entschieden wurde und warum. Der Einsatz KI-gestützter Werkzeuge wird dort benannt, wo er
für das Ergebnis relevant ist — bei der Implementierung —, nicht als durchgehendes Thema.

---

## Teil I — Rahmen (≈ 8 Seiten)

| # | Kapitel | Status | Quellen |
|---|---|---|---|
| 1 | **Einleitung** — Motivation, Problemstellung, Leitfrage, Anspruch, Zielgruppe, Aufbau | ✅ | `notion/motivation.md`, `infos-align/align.md` |
| 2 | **Problemraum und Markt** — Anforderungsanalyse, Competitor-Analyse (8 Apps), Marktlücken, Positionierung, Folgerungen fürs MVP | ✅ | `notion/competitoranalyse.md`, `anforderungsanalyse.md` |
| 3 | **Vorgehen und Zusammenarbeit** — Team und Schwerpunkte, Phasen- und Iterationsmodell, Zusammenarbeit und Dokumentation, Werkzeuge | ✅ | `material/chronik.md` |

## Teil II — Die vier Entwicklungsphasen (≈ 30 Seiten)

| # | Kapitel | Enthält | Status |
|---|---|---|---|
| 4 | **Phase 1 — Analyse und Grundlagen** | Iteration 1 · Literaturrecherche mit Wirkungszuordnung · Competitor-Analyse · erste Visualisierungen · Feedback | ✅ |
| 5 | **Phase 2 — Nutzerforschung** | Iterationen 2–3 · Interviewleitfaden, Erkenntnisse, Zitate · Personas · Umfrage n=25 mit allen Ergebnissen · LimeSurvey-Zwischenfall · methodische Einordnung · abgeleitete Produktentscheidungen | ✅ |
| 6 | **Phase 3 — Konzeption und Design** | Iteration 4 · die vier Features mit wissenschaftlicher Herleitung · Designsprache und Farbentscheidung · Werkzeugwege · Prototypen | ✅ |
| 7 | **Phase 4 — Technische Umsetzung** | Iterationen 5–6 · Tech-Stack mit Begründung · Architektur · KI-Anbindung · Qualitätssicherung · schrittweiser Ausbau · Entwicklung des Datenmodells · bewusste Weglassungen | ✅ |

## Teil III — Ergebnis und Rückblick (≈ 10 Seiten)

| # | Kapitel | Inhalt | Status |
|---|---|---|---|
| 8 | **Die fertige App** | Screenshot-Walkthrough durch alle fünf Bereiche, 10 Abbildungen, plus Funktionsumfang im Überblick | ✅ |
| 9 | **Reflexion** | Was gut lief · Schwierigkeiten · KI als Werkzeug und ihre Grenzen · methodische Grenzen · Learnings fachlich und im Team | ✅ |
| 10 | **Ausblick** | Die bewusst weggelassenen Funktionen (Abschnitt 7.13) · verworfene Konzeptideen · was als Nächstes käme · langfristige Vision | ✅ |
| — | **Anhang** | Quellenverzeichnis · Interviewleitfaden · Umfragefragen · Persona-Sheets · Iterationsfeedback im Original | offen |

**Stand:** Kapitel 1–10 fertig — rund 14.400 Wörter plus 10 Abbildungen, etwa 50 Seiten.

---

## Vorgehen

### Phase 0 — Materialbasis ✅
- `material/chronik.md` — belegte Zeitleiste aus Chat, Git und Iterationsnotizen
- `material/pruefung-entwurf-noggi.md` — was aus dem 38-Seiten-Entwurf übernommen wird und
  was falsch ist

### Phase 1 — Schreiben
1. ✅ Kapitel 1 bis 7 — Rahmen und alle vier Entwicklungsphasen
2. ✅ Kapitel 8 — die fertige App, mit Screenshots
3. ✅ Kapitel 9 und 10 — Reflexion und Ausblick
4. Anhang

### Screenshots
Aufgenommen am 12.09.2026 gegen `https://align.test`, Mobilansicht 390 px, dreifache
Pixeldichte, hell und dunkel. Das Demokonto wurde dafür aufbereitet: sprechender Anzeigename,
eine erledigte Gewohnheit am aktuellen Tag, eine Verbindung im Community-Kreis. Die Rohdaten
der Anwendung (Gewohnheiten, Kurse, Schlafplan) stammen unverändert aus dem Seed.

### Phase 2 — LaTeX
Erst wenn der Inhalt steht. Setzung in der eigenen Designsprache (Sora, `#775A19`);
verbindlich ist die Figma-Datei, nicht `designsprache.md`.

---

## Geklärt

- **Abgabe:** 16. September 2026.
- **Prabjot:** war anfangs eingeplant, konnte wegen eines Praktikums nicht mitarbeiten.
  In Kapitel 3.1 als Kontext erwähnt, nicht auf dem Titelblatt.
- **Plattform:** geplant war eine native mobile App; umgesetzt wurde eine responsive,
  mobil-first Web-App, um schnell entwickeln zu können. Der MVP ist bewusst kein
  marktfähiges Produkt.
- **Stundenplan-Integration:** bewusst umgesetzt, weil die App für Studierende gebaut ist.
- **Darstellung der Beiträge:** gleichmäßig, ohne personenbezogene Zuschreibung im Verlauf.

## Noch zu klären

1. **Quelle Birgmeier** — in Noggis Entwurf zitiert, liegt nicht in `quellenrecherche/`.
   Belegen oder streichen.
2. **Design-Screenshots aus Figma** — für Kapitel 6 und 8 angekündigt.
