# Projektbericht Align

**An den Bericht wird nur in einer Datei geschrieben: [`bericht.md`](bericht.md).**
Sie enthält alle Kapitel 1 bis 9 am Stück, die Bilder liegen daneben in `screenshots/`.
Das PDF im Layout unserer Designsprache entsteht daraus im Ordner [`latex/`](latex/)
(`./build.sh`, Anleitung in `latex/README.md`).

**Abgabe:** 16.09.2026

## Was wo liegt

| Pfad | Inhalt |
|---|---|
| `bericht.md` | der Berichtstext, einzige Quelle |
| `screenshots/` | alle Abbildungen, nach Kapiteln sortiert |
| `latex/` | Layout, Konverter und das fertige `Align-Projektbericht.pdf` |
| `00-gliederung.md` | ursprüngliche Gliederung |
| `Align-Projektbericht-Entwurf.md` | überarbeitete Fassung von Ngoc Ha, in Kapitel 1 bis 6 eingearbeitet |

## Ablauf

1. Die Änderung in `bericht.md` machen.
2. Im Ordner `latex/` `./build.sh` ausführen, damit das PDF den neuen Stand zeigt.

## Schreibregeln

- **Wir-Perspektive.** „Wir haben uns entschieden", nicht „das Team entschied".
- **Keine Namen im Fließtext.** Die Schwerpunkte der drei Teammitglieder stehen gebündelt in 3.1.
- **Betreuerin** im Text als „Frau Heß".
- **Auf Projektebene bleiben.** Keine Werkzeugpannen oder Terminfragen; berichtet wird, was
  entschieden wurde und warum.
- **KI** nur bei der Implementierung und als Funktion der App erwähnen.
- **Abbildungen** kapitelweise nummeriert („Abb. 8.3"), mit kursiver Bildunterschrift.
- **Querverweise** als „Abschnitt 7.8", nicht als Dateinamen.

## Offene Punkte

- Anhang schreiben
- Quelle „Birgmeier" prüfen, wird im alten Entwurf zitiert, liegt aber nicht in
  `quellenrecherche/`
- In der App nennt Schritt 3 des Assistenten „eine Viertelstunde" Abstand zur vorherigen
  Gewohnheit, gebaut sind fünf Minuten. Wird der Satz angepasst, Abb. 8.5 neu aufnehmen.
