#!/usr/bin/env bash
# Baut Align-Projektbericht.pdf aus bericht.md.
# Voraussetzungen: Node und Tectonic (brew install tectonic).
set -euo pipefail
cd "$(dirname "$0")"
node md2tex.mjs
tectonic --keep-logs Align-Projektbericht.tex
grep -E 'Overfull \\hbox|Missing character' Align-Projektbericht.log | sort | uniq -c || true
echo "✓ Align-Projektbericht.pdf"
