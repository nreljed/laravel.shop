<?php

use Illuminate\Contracts\Console\Kernel;

/**
 * Standalone Recovery Endpoint
 *
 * Bootstraps the Laravel console kernel directly — bypasses HTTP middleware,
 * session, CSRF, and all web-kernel pipeline. Works even when Laravel's
 * web routes return 500.
 *
 * Usage:
 *   GET  /recover.php?token=...&cmd=recovery
 *   GET  /recover.php?token=...&cmd=status
 *   POST /recover.php  (token + cmd in body)
 *
 * POST is preferred for destructive commands.
 *
 * DELETE this file after recovery is complete.
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

// ── Helpers ──────────────────────────────────────────────────────────────

function jsonOut(mixed $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function parseEnvFile(string $path): array
{
    $vars = [];
    if (! is_file($path)) {
        return $vars;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $vars[trim($key)] = trim($value);
        }
    }

    return $vars;
}

// ── Paths ────────────────────────────────────────────────────────────────

$publicDir = __DIR__;
$appRoot = dirname($publicDir);

// ── Token verification ───────────────────────────────────────────────────

$env = parseEnvFile($appRoot.'/.env');
$expected = $env['DEPLOY_TOKEN'] ?? null;
$token = $_REQUEST['token'] ?? null;

if (! $expected || ! $token || ! hash_equals((string) $expected, (string) $token)) {
    jsonOut(['error' => 'Invalid or missing deploy token.'], 403);
}

// ── Command allowlist ────────────────────────────────────────────────────

$ALLOWED = [
    'recovery',
    'package:discover', 'config:cache', 'config:clear',
    'route:cache', 'route:clear',
    'view:cache', 'view:clear',
    'cache:clear', 'cache:forget', 'cache:prune-stale-tags',
    'migrate', 'migrate:status', 'migrate:fresh', 'migrate:rollback',
    'db:seed', 'db:is-empty',
    'storage:link',
    'app:create-admin',
    'permissions:sync', 'permissions:list',
    'queue:restart',
    'schedule:run', 'schedule:list',
    'about', 'route:list', 'event:list',
    'down', 'up',
];

$cmd = $_REQUEST['cmd'] ?? $_REQUEST['command'] ?? 'status';
$cmd = trim((string) $cmd);

// Parse args from ?args=--force,--seed or JSON body
$argsRaw = $_REQUEST['args'] ?? '';
if (is_string($argsRaw) && $argsRaw !== '') {
    $args = array_map('trim', explode(',', $argsRaw));
} elseif (is_array($argsRaw)) {
    $args = $argsRaw;
} else {
    $args = [];
}

// ── Bootstrap ────────────────────────────────────────────────────────────

try {
    require $appRoot.'/vendor/autoload.php';
    $app = require $appRoot.'/bootstrap/app.php';
} catch (Throwable $e) {
    jsonOut([
        'status' => 'error',
        'phase' => 'bootstrap',
        'error' => $e->getMessage(),
        'file' => $e->getFile().':'.$e->getLine(),
    ], 500);
}

// ── Handle 'recovery' meta-command ───────────────────────────────────────

if ($cmd === 'recovery') {
    set_time_limit(300);

    try {
        $kernel = $app->make(Kernel::class);
    } catch (Throwable $e) {
        jsonOut([
            'status' => 'error',
            'phase' => 'kernel_resolution',
            'error' => $e->getMessage(),
            'file' => $e->getFile().':'.$e->getLine(),
        ], 500);
    }

    $sequence = [
        'package:discover' => ['--force' => true],
        'migrate' => ['--force' => true],
        'app:create-admin' => [
            '--no-interaction' => true,
            '--email' => $env['ADMIN_EMAIL'] ?? '',
            '--password' => $env['ADMIN_PASSWORD'] ?? '',
            '--name' => $env['ADMIN_NAME'] ?? 'Admin',
        ],
        'config:cache' => [],
        'route:cache' => [],
        'view:cache' => [],
        'storage:link' => ['--force' => true],
    ];

    $results = [];
    foreach ($sequence as $c => $p) {
        try {
            $kernel->call($c, $p);
            $output = trim($kernel->getOutput());
            $results[$c] = ['status' => 'ok', 'output' => $output ?: 'done'];
        } catch (Throwable $e) {
            $results[$c] = ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    try {
        file_put_contents($appRoot.'/.deployed-version', 'recovered-'.date('c'));
    } catch (Throwable $e) {
        // non-fatal
    }

    jsonOut(['status' => 'ok', 'sequence' => $results]);
}

// ── Handle 'status' (no kernel needed) ───────────────────────────────────

if ($cmd === 'status') {
    $checks = [
        'php_version' => PHP_VERSION,
        'services_cache' => is_file($appRoot.'/bootstrap/cache/services.php'),
        'packages_cache' => is_file($appRoot.'/bootstrap/cache/packages.php'),
        'deployed_version' => is_file($appRoot.'/.deployed-version')
            ? trim(file_get_contents($appRoot.'/.deployed-version'))
            : 'missing',
        'env_exists' => is_file($appRoot.'/.env'),
        'storage_link' => is_link($appRoot.'/storage'),
        'vendor_exists' => is_dir($appRoot.'/vendor/laravel'),
    ];
    jsonOut(['status' => 'ok', 'checks' => $checks]);
}

// ── Validate command ─────────────────────────────────────────────────────

if (! in_array($cmd, $ALLOWED, true)) {
    jsonOut([
        'error' => "Command not allowed: {$cmd}",
        'allowed' => $ALLOWED,
    ], 403);
}

// ── Run single command ───────────────────────────────────────────────────

try {
    $kernel = $app->make(Kernel::class);
} catch (Throwable $e) {
    jsonOut([
        'status' => 'error',
        'phase' => 'kernel_resolution',
        'command' => $cmd,
        'error' => $e->getMessage(),
        'file' => $e->getFile().':'.$e->getLine(),
    ], 500);
}

set_time_limit(300);

try {
    $exitCode = $kernel->call($cmd, $args);
    $output = trim($kernel->getOutput());

    jsonOut([
        'status' => $exitCode === 0 ? 'ok' : 'error',
        'command' => $cmd,
        'args' => $args,
        'exit_code' => $exitCode,
        'output' => $output,
    ], $exitCode === 0 ? 200 : 500);
} catch (Throwable $e) {
    jsonOut([
        'status' => 'error',
        'command' => $cmd,
        'error' => $e->getMessage(),
        'file' => $e->getFile().':'.$e->getLine(),
    ], 500);
}
