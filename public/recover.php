<?php

/**
 * Standalone Recovery Endpoint
 *
 * Bootstraps the Laravel console kernel directly — bypasses HTTP middleware,
 * session, CSRF, and all web-kernel pipeline. Works even when Laravel's
 * web routes return 500.
 *
 * Usage:
 *   GET  /recover.php?token=...&cmd=status
 *   POST /recover.php  (token + cmd in body)
 *
 * POST is preferred for destructive commands.
 *
 * DELETE this file after recovery is complete.
 */

header('Content-Type: application/json; charset=utf-8');

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
    // Recovery
    'recovery',
    // Cache & discovery
    'package:discover', 'config:cache', 'config:clear',
    'route:cache', 'route:clear',
    'view:cache', 'view:clear',
    'cache:clear', 'cache:forget', 'cache:prune-stale-tags',
    // Database
    'migrate', 'migrate:status', 'migrate:fresh', 'migrate:rollback',
    'db:seed', 'db:is-empty',
    // Storage
    'storage:link',
    // Admin
    'app:create-admin',
    // Permissions
    'permissions:sync', 'permissions:list',
    // Queue
    'queue:restart', 'queue:work', 'queue:listen',
    // Schedule
    'schedule:run', 'schedule:list', 'schedule:work',
    // Diagnostics
    'about', 'env:decrypt', 'route:list', 'event:list',
    // Maintenance
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

require $appRoot.'/vendor/autoload.php';
$app = require $appRoot.'/bootstrap/app.php';

// ── Handle 'recovery' meta-command ───────────────────────────────────────

if ($cmd === 'recovery') {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    set_time_limit(300);

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
        } catch (\Throwable $e) {
            $results[$c] = ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    file_put_contents($appRoot.'/.deployed-version', 'recovered');
    jsonOut(['status' => 'ok', 'sequence' => $results]);
}

// ── Validate command ─────────────────────────────────────────────────────

if (! in_array($cmd, $ALLOWED, true)) {
    jsonOut([
        'error' => "Command not allowed: {$cmd}",
        'allowed' => $ALLOWED,
    ], 403);
}

// ── Run single command ───────────────────────────────────────────────────

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
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
} catch (\Throwable $e) {
    jsonOut([
        'status' => 'error',
        'command' => $cmd,
        'error' => $e->getMessage(),
        'file' => $e->getFile().':'.$e->getLine(),
    ], 500);
}
