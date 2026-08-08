<?php

use Illuminate\Support\Facades\Process;

afterEach(function () {
    @unlink(base_path('storage/framework/testing/Dokumentation.md'));
});

test('the documentation is written from the git history', function () {
    $path = 'storage/framework/testing/Dokumentation.md';

    $this->artisan('dokumentation:generate', ['--path' => $path])->assertSuccessful();

    $written = file_get_contents(base_path($path));
    $newest = trim(Process::path(base_path())->run('git log -1 --pretty=format:%s')->output());

    expect($written)
        ->toContain('# Dokumentation')
        ->toContain('## Beteiligte')
        ->toContain($newest);
});

test('every commit of the history appears exactly once', function () {
    $path = 'storage/framework/testing/Dokumentation.md';

    $this->artisan('dokumentation:generate', ['--path' => $path])->assertSuccessful();

    $written = file_get_contents(base_path($path));
    $hashes = array_filter(explode("\n", trim(
        Process::path(base_path())->run('git log --pretty=format:%h')->output(),
    )));

    foreach ($hashes as $hash) {
        expect(substr_count($written, '`'.$hash.'`'))->toBe(1, "Commit {$hash} fehlt oder steht doppelt.");
    }
});

test('co-author trailers are left out of the quoted message', function () {
    $path = 'storage/framework/testing/Dokumentation.md';

    $this->artisan('dokumentation:generate', ['--path' => $path])->assertSuccessful();

    expect(file_get_contents(base_path($path)))->not->toContain('Co-Authored-By');
});
