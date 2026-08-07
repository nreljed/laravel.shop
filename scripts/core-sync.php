<?php

/**
 * Sync or verify the dashboard-core package in this project.
 *
 * The core's source of truth is a local master tree (by default
 * ../laravel/app/packages/dashboard-core relative to the shop root). This
 * tool mirrors that tree into packages/dashboard-core and records a content
 * hash in packages/dashboard-core/.core-sha so freshness can be checked.
 *
 * Usage:
 *   php scripts/core-sync.php                sync master -> packages/dashboard-core
 *   php scripts/core-sync.php --check        report freshness (exit 0=up to date, 1=drift)
 *   php scripts/core-sync.php --source <dir> override the master source directory
 *   php scripts/core-sync.php --source=<dir>
 *
 * Exit codes: 0 = clean/up to date, 1 = drift detected, 2 = source unavailable.
 */
$check = false;
$source = null;

for ($i = 1, $n = count($argv); $i < $n; $i++) {
    $arg = $argv[$i];

    if ($arg === '--check') {
        $check = true;
    } elseif ($arg === '--source') {
        $source = $argv[++$i] ?? null;
    } elseif (str_starts_with($arg, '--source=')) {
        $source = substr($arg, strlen('--source='));
    }
}

$projectRoot = dirname(__DIR__);
$target = $projectRoot.'/packages/dashboard-core';

if ($source === null) {
    $source = getenv('CORE_SOURCE_DIR') ?: dirname(__DIR__, 3).'/laravel/app/packages/dashboard-core';
}

$source = rtrim((string) $source, '/\\');

/** Files and directories that are never mirrored between master and project. */
function ignoredPaths(): array
{
    return ['vendor', 'node_modules', '.git', '.core-sha', '.phpunit.result.cache'];
}

/**
 * Deterministic content hash for a directory tree. Paths are sorted and each
 * file contributes "<rel-path>\0<sha256>\0". Ignored entries are excluded.
 */
function treeHash(string $dir): string
{
    $entries = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        /** @var SplFileInfo $file */
        if (! $file->isFile()) {
            continue;
        }

        $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
        $segments = explode('/', $rel);

        if (array_intersect($segments, ignoredPaths()) !== []) {
            continue;
        }

        $entries[$rel] = hash_file('sha256', $file->getPathname());
    }

    ksort($entries);

    $hash = hash_init('sha256');
    foreach ($entries as $rel => $fileHash) {
        hash_update($hash, $rel."\0".$fileHash."\0");
    }

    return hash_final($hash);
}

/**
 * @return array{added: array<int, string>, changed: array<int, string>, removed: array<int, string>}
 */
function diffTrees(string $source, string $target): array
{
    /** @return array<string, string> */
    $index = function (string $dir): array {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if (! $file->isFile()) {
                continue;
            }

            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
            $segments = explode('/', $rel);

            if (array_intersect($segments, ignoredPaths()) !== []) {
                continue;
            }

            $files[$rel] = hash_file('sha256', $file->getPathname());
        }

        return $files;
    };

    $sourceFiles = $index($source);
    $targetFiles = $index($target);

    $added = [];
    $changed = [];
    $removed = [];

    foreach ($sourceFiles as $rel => $hash) {
        if (! isset($targetFiles[$rel])) {
            $added[] = $rel;
        } elseif ($targetFiles[$rel] !== $hash) {
            $changed[] = $rel;
        }
    }

    foreach (array_keys($targetFiles) as $rel) {
        if (! isset($sourceFiles[$rel])) {
            $removed[] = $rel;
        }
    }

    return ['added' => $added, 'changed' => $changed, 'removed' => $removed];
}

function out(string $message = ''): void
{
    fwrite(STDOUT, $message.PHP_EOL);
}

function fail(string $message): void
{
    fwrite(STDERR, $message.PHP_EOL);
}

if (! is_dir($source)) {
    fail("Core source not found: $source");
    fail('Set the path with --source=<dir> or the CORE_SOURCE_DIR env var.');
    exit(2);
}

if (! is_dir($target)) {
    fail("Target package dir missing: $target (did the core package get removed?)");
    exit(2);
}

$sourceHash = treeHash($source);
$mirrorHash = treeHash($target);
$recordedHash = is_file($target.'/.core-sha') ? trim((string) file_get_contents($target.'/.core-sha')) : '';

if ($check) {
    $outdated = $mirrorHash !== $sourceHash;
    $moved = $recordedHash !== '' && $recordedHash !== $sourceHash;
    $unknown = $recordedHash === '';

    if ($unknown) {
        out('Core freshness: never synced (no .core-sha recorded).');
        out('Run: php scripts/core-sync.php');
        exit(1);
    }

    if (! $outdated && ! $moved) {
        out("Core freshness: UP TO DATE (hash {$sourceHash})");

        exit(0);
    }

    out('Core freshness: OUT OF DATE');
    out("  master hash:  {$sourceHash}");

    if ($moved) {
        out("  last synced:  {$recordedHash}");
    }

    if ($outdated) {
        $diff = diffTrees($source, $target);
        out('  packages/dashboard-core differs from master:');
        foreach ($diff['added'] as $rel) {
            out("    + $rel");
        }
        foreach ($diff['changed'] as $rel) {
            out("    ~ $rel");
        }
        foreach ($diff['removed'] as $rel) {
            out("    - $rel");
        }
    }

    out('Run: php scripts/core-sync.php (or composer run core:sync)');

    exit(1);
}

if ($sourceHash === $mirrorHash && $recordedHash === $sourceHash) {
    out("Core already up to date (hash {$sourceHash}).");

    exit(0);
}

$diff = diffTrees($source, $target);

foreach ($diff['removed'] as $rel) {
    $path = $target.'/'.$rel;
    if (is_file($path) && unlink($path)) {
        out("removed  $rel");
    }
}

foreach (array_merge($diff['added'], $diff['changed']) as $rel) {
    $from = $source.'/'.$rel;
    $to = $target.'/'.$rel;

    if (! is_dir(dirname($to))) {
        mkdir(dirname($to), 0777, true);
    }

    if (copy($from, $to)) {
        out('synced   '.$rel);
    } else {
        fail("failed to copy: $rel");
        exit(1);
    }
}

// Publish any overridable configs that the core added and the project lacks.
$manifestPath = $target.'/core-manifest.json';

if (is_file($manifestPath)) {
    $manifest = json_decode((string) file_get_contents($manifestPath), true);

    foreach ($manifest['overridable_config'] ?? [] as $config) {
        $projectConfig = $projectRoot.'/'.$config;

        if (! is_file($projectConfig)) {
            $packageConfig = $target.'/'.$config;

            if (is_file($packageConfig) && copy($packageConfig, $projectConfig)) {
                out('published '.$config.' (new overridable config)');
            }
        }
    }
}

file_put_contents($target.'/.core-sha', $sourceHash);

out('');
out('Core synced.');
out("  hash: {$sourceHash}");
out('  next: composer run core:sync  (refresh the vendor copy)');
out('        npm run build           (rebuild frontend assets)');
out('        commit + push packages/ (CI + deploy)');
out('');

exit(0);
