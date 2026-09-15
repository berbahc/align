// Übersetzt zusammenarbeit/bericht.md in inhalt.tex für Align-Projektbericht.tex.
//
// Die Markdown-Datei bleibt die einzige Quelle. Dieses Skript kennt genau die
// Formen, die im Bericht vorkommen: nummerierte Kapitel und Abschnitte,
// Iterations-Zwischentitel, Absätze, Listen, Zitate, Tabellen, ein Codeblock
// und Bildgruppen mit kursiver „Abb."-Unterschrift. Bildgrößen werden aus den
// echten Pixelmaßen berechnet, damit nichts über den Satzspiegel hinausragt.

import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const quelle = join(here, '..', 'bericht.md');
const ziel = join(here, 'inhalt.tex');

// Satzspiegel in cm, muss zu geometry in Align-Projektbericht.tex passen.
const TEXTBREITE = 15.8;
const LUECKE = 0.6; // Abstand zwischen Bildern einer Gruppe

// ---------------------------------------------------------------------------
// Inline-Text
// ---------------------------------------------------------------------------

const escape = (s) =>
    s
        .replace(/\\/g, '\\textbackslash{}')
        .replace(/([#$%&_{}])/g, '\\$1')
        .replace(/\^/g, '\\textasciicircum{}')
        .replace(/~/g, '\\textasciitilde{}');

function inline(text) {
    const codes = [];
    let s = text.replace(/`([^`]+)`/g, (_, c) => {
        codes.push(c);
        return `\u0000${codes.length - 1}\u0000`;
    });
    s = escape(s);
    s = s.replace(/\*\*(.+?)\*\*/g, '\\textbf{$1}');
    s = s.replace(/\*(.+?)\*/g, '\\emph{$1}');
    // Deutsche Anführung: „ … " → „ … “ (ein gerades " wäre unter babel aktiv)
    s = s.replace(/"/g, '“');
    s = s.replace(/<br>/g, "\\newline ");
    // Keine Zeilenumbrüche zwischen Verweis und Nummer
    s = s.replace(/\b(Abb\.|Abschnitt|Abschnitte|Abschnitten|Kapitel|Iteration|Iterationen|Phase) (\d)/g, '$1~$2');
    s = s.replace(/ø (\d)/g, 'ø~$1');
    s = s.replace(/(\d) (von|bis) (\d)/g, '$1~$2~$3');
    s = s.replace(/\u0000(\d+)\u0000/g, (_, i) => `\\code{${escape(codes[Number(i)]).replace(/\//g, '/\\allowbreak{}')}}`);
    return s;
}

// ---------------------------------------------------------------------------
// Bilder
// ---------------------------------------------------------------------------

function pngGroesse(pfad) {
    const b = readFileSync(join(here, '..', pfad));
    return { w: b.readUInt32BE(16), h: b.readUInt32BE(20) };
}

// Höhe einer Bildgruppe in cm, aus den echten Pixelmaßen berechnet.
// Eine Bildzeile darf mit {height=9.5cm} enden, dann gilt diese Höhe als Höchstmaß.
function bildzeile(z) {
    const pfade = [...z.matchAll(/!\[[^\]]*\]\(([^)]+)\)/g)].map((m) => m[1]);
    const m = z.match(/\{height=([\d.]+)cm\}\s*$/);
    return { pfade, maxHoehe: m ? parseFloat(m[1]) : null };
}

function gruppenHoehe(pfade, maxHoehe = null) {
    const seiten = pfade.map((p) => {
        const { w, h } = pngGroesse(p);
        return { p, a: w / h };
    });
    const summe = seiten.reduce((n, x) => n + x.a, 0);
    const minA = Math.min(...seiten.map((x) => x.a));
    const verfuegbar = TEXTBREITE - LUECKE * (seiten.length - 1) - 0.1 * seiten.length;
    // Höchstmaß je Bildart: Handy-Screens 9,5 cm, sehr lange Figma-Screens 14,5 cm,
    // damit sie noch lesbar breit bleiben, Querformate unter 11 cm.
    const maxH = maxHoehe ?? (minA < 0.3 ? 14.5 : minA >= 1 ? 11 : 9.5);
    return { seiten, hoehe: Math.min(maxH, verfuegbar / summe) };
}

function bildgruppe(pfade, unterschrift, maxHoehe = null) {
    const { seiten, hoehe: h } = gruppenHoehe(pfade, maxHoehe);
    const hoehe = h.toFixed(2);

    const bilder = seiten
        .map((x) => `\\rahmen{\\includegraphics[height=${hoehe}cm,keepaspectratio]{${x.p}}}`)
        .join(`\\hspace{${LUECKE}cm}`);

    let cap = '';
    if (unterschrift) {
        const m = unterschrift.match(/^(Abb\.[^:]*):\s*(.*)$/s);
        cap = m ? `\\abb{${inline(m[1])}}{${inline(m[2])}}` : `\\abb{}{${inline(unterschrift)}}`;
    }
    // Bilder stehen genau dort, wo sie im Markdown stehen, also immer nach einem
    // abgeschlossenen Textblock und nie mitten in einem Absatz auf der Folgeseite.
    return `\\begin{figure}[H]\n\\centering\n${bilder}\n${cap}\n\\end{figure}\n`;
}

// ---------------------------------------------------------------------------
// Tabellen
// ---------------------------------------------------------------------------

const zellen = (zeile) =>
    zeile
        .trim()
        .replace(/^\||\|$/g, '')
        .split('|')
        .map((c) => c.trim());

// festhalten: Die Tabelle folgt auf einen Satz mit Doppelpunkt und bleibt deshalb
// direkt dahinter; alle anderen dürfen wie Bilder weiterrutschen.
function tabelle(zeilen, festhalten) {
    const kopf = zellen(zeilen[0]);
    const koerper = zeilen.slice(2).map(zellen);
    const spalten = kopf.length;
    const ohneKopf = kopf.every((c) => c === '');
    const alle = ohneKopf ? koerper : [kopf, ...koerper];
    const laenge = (c) => c.replace(/\*|`/g, '').length;

    const typen = [];
    for (let i = 0; i < spalten; i++) {
        const max = Math.max(...alle.map((r) => laenge(r[i] ?? '')));
        typen.push(max <= 18 ? 'l' : 'L');
    }
    if (!typen.includes('L')) typen[typen.length - 1] = 'L';
    const lang = alle.some((r) => r.some((c) => laenge(c) > 70));
    const groesse = spalten >= 6 ? '\\footnotesize\\setlength{\\tabcolsep}{4pt}' : '\\small';

    const zeile = (r) => r.map((c) => inline(c)).join(' & ') + ' \\\\';
    let out = `\\begin{table}[H]\n\\centering${groesse}\n\\begin{tabularx}{\\textwidth}{@{}${typen.join('')}@{}}\n\\toprule\n`;
    if (!ohneKopf) out += `${kopf.map((c) => `\\kopf{${inline(c)}}`).join(' & ')} \\\\\n\\midrule\n`;
    out += koerper.map(zeile).join(lang ? '\n\\addlinespace[3pt]\n' : '\n') + '\n';
    out += '\\bottomrule\n\\end{tabularx}\n\\end{table}\n';
    return out;
}

// ---------------------------------------------------------------------------
// Blöcke
// ---------------------------------------------------------------------------

const md = readFileSync(quelle, 'utf8').split('\n');
const start = md.findIndex((l) => /^# 1\. /.test(l));
const L = md.slice(start);
const out = [];
let absatz = [];

const absatzEnde = () => {
    if (absatz.length) {
        const text = absatz.join(' ');
        // Eine fett gesetzte Zeile allein ist ein Zwischentitel: Sie darf nicht
        // unten auf einer Seite stehen bleiben, während ihr Inhalt umbricht.
        if (/^\*\*[^*]+\*\*$/.test(text)) out.push(`\\Needspace{7\\baselineskip}\n${inline(text)}\\par\\nobreak\n`);
        else out.push(inline(text) + '\n');
    }
    absatz = [];
};

// Wie viel Platz eine Überschrift mit dem, was direkt zu ihr gehört, braucht (in cm).
// Folgt nach höchstens einem kurzen Einleitungsabsatz und einer Unterüberschrift
// ein Bild, muss das Bild mit auf die Seite, sonst bleibt die Überschrift allein stehen.
const ZEILE = 0.55;
function platzBedarf(i) {
    let bedarf = 1.6, zeilen = 0, absaetze = 0;
    for (let j = i + 1; j < L.length; j++) {
        const z = L[j];
        if (z.trim() === '') continue;
        // Bilder stehen erst hinter dem Textblock und dürfen auf die nächste Seite,
        // die Überschrift braucht also nur ihre ersten Zeilen mit.
        if (z.startsWith('![')) break;
        if (/^###? /.test(z)) { bedarf += 1.2; continue; }
        if (z.startsWith('|') || z.startsWith('#') || z.startsWith('>') || z.startsWith('```')) break;
        let text = '';
        for (; j < L.length && L[j].trim() !== ''; j++) text += L[j] + ' ';
        zeilen += Math.ceil(text.length / 95);
        if (++absaetze >= 2 || zeilen > 6) break;
    }
    return bedarf + Math.min(Math.max(zeilen, 4), 8) * ZEILE;
}

for (let i = 0; i < L.length; i++) {
    const z = L[i];

    if (z.trim() === '') { absatzEnde(); continue; }
    if (/^---\s*$/.test(z)) { absatzEnde(); continue; }

    // Überschriften
    const h = z.match(/^(#{1,3}) (.*)$/);
    if (h) {
        absatzEnde();
        const [, ebene, titel] = h;
        if (ebene === '#') {
            const k = titel.match(/^(\d+)\. (.*)$/);
            if (k) out.push(`\\setcounter{chapter}{${Number(k[1]) - 1}}\n\\chapter{${inline(k[2])}}\n`);
            else out.push(`\\iteration{${inline(titel)}}\n`);
        } else if (ebene === '##') {
            const platz = `\\Needspace{${platzBedarf(i).toFixed(1)}cm}\n`;
            const s = titel.match(/^(\d+)\.(\d+) (.*)$/);
            if (s) out.push(`${platz}\\setcounter{section}{${Number(s[2]) - 1}}\n\\section{${inline(s[3])}}\n`);
            else out.push(`${platz}\\section*{${inline(titel)}}\n`);
        } else {
            const platz = `\\Needspace{${platzBedarf(i).toFixed(1)}cm}\n`;
            const s = titel.match(/^(\d+)\.(\d+)\.(\d+) (.*)$/);
            if (s) out.push(`${platz}\\setcounter{subsection}{${Number(s[3]) - 1}}\n\\subsection{${inline(s[4])}}\n`);
            else out.push(`${platz}\\subsection*{${inline(titel)}}\n`);
        }
        continue;
    }

    // Codeblock
    if (z.startsWith('```')) {
        absatzEnde();
        const code = [];
        for (i++; i < L.length && !L[i].startsWith('```'); i++) code.push(L[i]);
        out.push(`\\begin{codezeile}\n${code.map(escape).join('\\\\\n')}\n\\end{codezeile}\n`);
        continue;
    }

    // Bildgruppe mit Unterschrift
    if (z.startsWith('![')) {
        absatzEnde();
        const { pfade, maxHoehe } = bildzeile(z);
        let j = i + 1;
        while (j < L.length && L[j].trim() === '') j++;
        let unterschrift = '';
        if (j < L.length && L[j].startsWith('*Abb')) {
            const teile = [];
            for (; j < L.length && L[j].trim() !== ''; j++) teile.push(L[j]);
            unterschrift = teile.join(' ').replace(/^\*|\*$/g, '');
            i = j - 1;
        }
        // Direkt unter einer Überschrift bleibt das Bild an seiner Stelle,
        // sonst rutscht es über die Überschrift auf die Vorseite.
        const nachUeberschrift = /\\(section|subsection|chapter|iteration)\*?\{/.test(out[out.length - 1] ?? '');
        const gruppe = bildgruppe(pfade, unterschrift, maxHoehe);
        out.push(nachUeberschrift ? gruppe.replace('[!htbp]', '[H]') : gruppe);
        continue;
    }

    // Tabelle
    if (z.startsWith('|')) {
        absatzEnde();
        const t = [];
        for (; i < L.length && L[i].startsWith('|'); i++) t.push(L[i]);
        i--;
        out.push(tabelle(t, /:\s*$/.test(out[out.length - 1] ?? '')));
        continue;
    }

    // Zitat
    if (z.startsWith('>')) {
        absatzEnde();
        const q = [];
        for (; i < L.length && L[i].startsWith('>'); i++) q.push(L[i].replace(/^>\s?/, ''));
        i--;
        out.push(`\\begin{zitat}\n${inline(q.join(' '))}\n\\end{zitat}\n`);
        continue;
    }

    // Listen: ungeordnet immer, nummeriert nur am Blockanfang
    const ungeordnet = /^- /.test(z);
    const nummeriert = /^\d+\. /.test(z) && absatz.length === 0;
    if (ungeordnet || nummeriert) {
        absatzEnde();
        const umgebung = ungeordnet ? 'itemize' : 'enumerate';
        const muster = ungeordnet ? /^- / : /^\d+\. /;
        const punkte = [];
        for (; i < L.length; i++) {
            if (muster.test(L[i])) punkte.push(L[i].replace(muster, ''));
            else if (/^\s{2,}\S/.test(L[i]) && punkte.length) punkte[punkte.length - 1] += ' ' + L[i].trim();
            else break;
        }
        i--;
        out.push(`\\begin{${umgebung}}\n${punkte.map((p) => `  \\item ${inline(p)}`).join('\n')}\n\\end{${umgebung}}\n`);
        continue;
    }

    absatz.push(z.trim());
}
absatzEnde();

writeFileSync(ziel, `% Automatisch erzeugt aus ../bericht.md – nicht von Hand bearbeiten.\n\n${out.join('\n')}`);
console.log(`✓ inhalt.tex geschrieben (${out.length} Blöcke)`);
