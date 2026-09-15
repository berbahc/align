# Bericht als PDF (LaTeX)

Der Inhalt kommt ausschließlich aus `../zusammenarbeit/bericht.md`. Änderungen am Text
immer dort machen, danach das PDF neu bauen. Nichts in `inhalt.tex` von Hand ändern,
die Datei wird bei jedem Bau überschrieben.

```bash
brew install tectonic   # einmalig
./build.sh              # erzeugt Align-Projektbericht.pdf
```

## Was wo liegt

| Datei | Aufgabe |
|---|---|
| `Align-Projektbericht.tex` | Layout: Titelseite, Farben, Schriften, Überschriften, Bildrahmen |
| `md2tex.mjs` | übersetzt `bericht.md` nach `inhalt.tex` |
| `fonts/` | Sora (SIL Open Font License 1.1), aus den App-Assets umgewandelt |
| `bilder/` | Logo für die Titelseite |

## Regeln im Markdown, die der Konverter erwartet

- Kapitel als `# 7. Titel`, Abschnitte als `## 7.1 Titel`, Unterabschnitte als `### 1.3.1 Titel`
  oder ohne Nummer. `# Iteration …` und `# Ausblick` werden zu Zwischentiteln ohne Nummer.
- Bilder stehen zu eins bis drei in einer Zeile, direkt darunter (nach einer Leerzeile) die
  kursive Unterschrift `*Abb. 7.3 und 7.4: …*`. Die Größe wird aus den Pixelmaßen berechnet.
- Tabellen, die auf einen Satz mit Doppelpunkt folgen, bleiben an ihrer Stelle. Bilder und
  andere Tabellen dürfen innerhalb ihres Abschnitts auf die nächste Seite rutschen.
- Nummerierte Listen beginnen nach einer Leerzeile.

## Farben (aus der Figma-Designsprache)

`olive #775A19` Nummern und Bildunterschriften · `sand #DCD0B8` Linien und Rahmen ·
`canvas #F3EDE4` Zitat- und Codeflächen · `ink #1D1B1B` Text · `gold #C5A058` nur Titelseite
