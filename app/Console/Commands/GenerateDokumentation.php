<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Schreibt Dokumentation.md aus der Git-Historie neu.
 *
 * Die Datei wird nie fortgeschrieben, sondern jedes Mal vollständig erzeugt.
 * Damit kann sie nicht von der Wahrheit abweichen — und sie enthält
 * automatisch auch die Commits, die andere gepusht haben, sobald sie durch
 * einen Pull im lokalen Verlauf liegen.
 */
class GenerateDokumentation extends Command
{
    protected $signature = 'dokumentation:generate {--path=Dokumentation.md : Zieldatei, relativ zum Projektstamm}';

    protected $description = 'Erzeugt Dokumentation.md aus der Git-Historie';

    /**
     * Trennzeichen zwischen Commits und zwischen Feldern.
     *
     * Steuerzeichen statt Text, weil eine Commit-Nachricht jeden denkbaren
     * Textmarker selbst enthalten könnte.
     */
    private const string CommitSeparator = "\x1e";

    private const string FieldSeparator = "\x1f";

    public function handle(): int
    {
        if (! $this->isGitRepository()) {
            $this->components->error('Kein Git-Repository — hier gibt es keine Historie zu dokumentieren.');

            return self::FAILURE;
        }

        $commits = $this->commits();

        if ($commits->isEmpty()) {
            $this->components->warn('Noch keine Commits vorhanden.');

            return self::SUCCESS;
        }

        $path = base_path((string) $this->option('path'));

        file_put_contents($path, $this->render($commits));

        $this->components->info(sprintf(
            '%s geschrieben — %d Commits von %d Beteiligten.',
            basename($path),
            $commits->count(),
            $commits->pluck('author')->unique()->count(),
        ));

        return self::SUCCESS;
    }

    private function isGitRepository(): bool
    {
        return Process::path(base_path())
            ->run('git rev-parse --git-dir')
            ->successful();
    }

    /**
     * Die vollständige Historie als Datensätze.
     *
     * Ein einziger `git log`-Aufruf statt eines pro Commit — bei einigen
     * hundert Commits ist der Unterschied zwischen Sekundenbruchteil und
     * spürbarer Wartezeit, und der Hook läuft nach jedem Commit.
     *
     * @return Collection<int, array{hash: string, short: string, author: string, date: Carbon, subject: string, body: string, files: list<array{path: string, added: int, removed: int}>, pushed: bool}>
     */
    private function commits(): Collection
    {
        $format = implode(self::FieldSeparator, ['%H', '%h', '%an', '%aI', '%s', '%B']);

        $log = Process::path(base_path())->run(sprintf(
            'git log --numstat --date-order --pretty=format:%s%s%s',
            escapeshellarg(self::CommitSeparator.$format.self::FieldSeparator),
            '',
            '',
        ));

        $pushed = $this->pushedHashes();

        return collect(explode(self::CommitSeparator, $log->output()))
            ->skip(1)
            ->map(function (string $chunk) use ($pushed): array {
                $parts = explode(self::FieldSeparator, $chunk);

                return [
                    'hash' => $parts[0],
                    'short' => $parts[1],
                    'author' => $parts[2],
                    'date' => Carbon::parse($parts[3]),
                    'subject' => $parts[4],
                    'body' => trim(Str::after(trim($parts[5]), $parts[4])),
                    'files' => $this->files($parts[6] ?? ''),
                    'pushed' => in_array($parts[0], $pushed, strict: true),
                ];
            })
            ->values();
    }

    /**
     * Alle Commits, die auf einem Remote liegen.
     *
     * Was hier fehlt, existiert nur lokal — die Dokumentation sagt das dazu,
     * damit aus der Datei hervorgeht, was andere sehen können und was nicht.
     *
     * @return list<string>
     */
    private function pushedHashes(): array
    {
        $result = Process::path(base_path())->run('git rev-list --remotes');

        if (! $result->successful()) {
            return [];
        }

        return array_values(array_filter(explode("\n", trim($result->output()))));
    }

    /**
     * @return list<array{path: string, added: int, removed: int}>
     */
    private function files(string $numstat): array
    {
        return collect(explode("\n", trim($numstat)))
            ->filter()
            ->map(function (string $line): ?array {
                $columns = preg_split('/\t/', trim($line));

                if ($columns === false || count($columns) < 3) {
                    return null;
                }

                return [
                    'path' => $columns[2],
                    // "-" steht bei Binärdateien, dort zählt Git keine Zeilen.
                    'added' => (int) $columns[0],
                    'removed' => (int) $columns[1],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array{hash: string, short: string, author: string, date: Carbon, subject: string, body: string, files: list<array{path: string, added: int, removed: int}>, pushed: bool}>  $commits
     */
    private function render(Collection $commits): string
    {
        $lines = [
            '# Dokumentation',
            '',
            'Verlauf des Projekts, erzeugt aus der Git-Historie.',
            '',
            '> Diese Datei wird automatisch geschrieben — Änderungen von Hand gehen beim',
            '> nächsten Commit verloren. Sie entsteht neu mit `php artisan dokumentation:generate`',
            '> und läuft nach jedem Commit sowie nach jedem Pull von selbst.',
            '',
            $this->summary($commits),
            '',
            $this->contributors($commits),
            '',
            '---',
            '',
        ];

        foreach ($commits->groupBy(fn (array $commit): string => $commit['date']->format('Y-m-d')) as $day => $ofDay) {
            $lines[] = '## '.Carbon::parse((string) $day)->format('d.m.Y');
            $lines[] = '';

            foreach ($ofDay as $commit) {
                $lines[] = $this->commit($commit);
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  Collection<int, array{date: Carbon, pushed: bool, ...}>  $commits
     */
    private function summary(Collection $commits): string
    {
        $unpushed = $commits->where('pushed', false)->count();

        $summary = sprintf(
            '**Stand:** %s · **%d Commits** · erster Eintrag %s',
            now()->format('d.m.Y H:i'),
            $commits->count(),
            $commits->last()['date']->format('d.m.Y'),
        );

        if ($unpushed > 0) {
            $summary .= sprintf(
                "\n\n> **%d %s noch nicht gepusht** — nur auf diesem Rechner sichtbar.",
                $unpushed,
                $unpushed === 1 ? 'Commit ist' : 'Commits sind',
            );
        }

        return $summary;
    }

    /**
     * @param  Collection<int, array{author: string, date: Carbon, ...}>  $commits
     */
    private function contributors(Collection $commits): string
    {
        $lines = ['## Beteiligte', ''];

        foreach ($commits->groupBy('author')->sortByDesc(fn (Collection $own): int => $own->count()) as $author => $own) {
            $lines[] = sprintf(
                '- **%s** — %d %s, zuletzt am %s',
                $author,
                $own->count(),
                $own->count() === 1 ? 'Commit' : 'Commits',
                Carbon::parse($own->max(fn (array $commit): string => $commit['date']->format('Y-m-d')))->format('d.m.Y'),
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array{hash: string, short: string, author: string, date: Carbon, subject: string, body: string, files: list<array{path: string, added: int, removed: int}>, pushed: bool}  $commit
     */
    private function commit(array $commit): string
    {
        $added = array_sum(array_column($commit['files'], 'added'));
        $removed = array_sum(array_column($commit['files'], 'removed'));
        $count = count($commit['files']);

        $lines = [
            sprintf('### %s', $commit['subject']),
            '',
            sprintf(
                '`%s` · **%s** · %s Uhr%s',
                $commit['short'],
                $commit['author'],
                $commit['date']->format('H:i'),
                $commit['pushed'] ? '' : ' · ⚠️ nur lokal',
            ),
            '',
        ];

        if ($commit['body'] !== '') {
            $lines[] = $this->quote($commit['body']);
            $lines[] = '';
        }

        if ($count > 0) {
            $lines[] = sprintf(
                '<details><summary>%d %s · +%d/−%d</summary>',
                $count,
                $count === 1 ? 'Datei' : 'Dateien',
                $added,
                $removed,
            );
            $lines[] = '';

            foreach ($commit['files'] as $file) {
                $lines[] = sprintf('- `%s` +%d/−%d', $file['path'], $file['added'], $file['removed']);
            }

            $lines[] = '';
            $lines[] = '</details>';
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Die Commit-Beschreibung als Zitat.
     *
     * Eingerückt statt roh, damit Überschriften oder Listen aus einer
     * Commit-Nachricht die Gliederung der Datei nicht durcheinanderbringen.
     * Co-authored-by-Zeilen fallen weg — sie stehen schon in „Beteiligte".
     */
    private function quote(string $body): string
    {
        return collect(explode("\n", $body))
            ->reject(fn (string $line): bool => Str::startsWith(Str::lower(trim($line)), 'co-authored-by:'))
            ->pipe(fn (Collection $lines): Collection => $lines->skipUntil(fn (string $line): bool => trim($line) !== ''))
            ->reverse()
            ->skipUntil(fn (string $line): bool => trim($line) !== '')
            ->reverse()
            ->map(fn (string $line): string => trim($line) === '' ? '>' : '> '.$line)
            ->implode("\n");
    }
}
