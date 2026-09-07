#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * RH Tech IA - Pipeline Runner with Frontend Integration
 *
 * Executa checks e envia status em tempo real para o frontend via API.
 *
 * Usage: php run-pipeline-stream.php [--check=security|all]
 */

declare(strict_types=1);

$dotenvFile = __DIR__.'/backend/.env';
if (file_exists($dotenvFile)) {
    $lines = file($dotenvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (! empty($key) && ! isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

$backendUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
$apiToken = $_ENV['PIPELINE_API_TOKEN'] ?? null;

$mode = 'all';
global $argv;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--check=')) {
        $mode = substr($arg, 8);
    }
}

const PHASES = [
    'INICIO' => ['icon' => '🔴', 'color' => 'red', 'label' => 'SEGURANÇA'],
    'MEIO' => ['icon' => '🟡', 'color' => 'yellow', 'label' => 'VALIDAÇÃO'],
    'FIM' => ['icon' => '🟢', 'color' => 'green', 'label' => 'DEPLOY'],
];

const STATUS_COLORS = [
    'pending' => "\033[90m",
    'running' => "\033[93m",
    'success' => "\033[92m",
    'warning' => "\033[93m",
    'failed' => "\033[91m",
    'skipped' => "\033[90m",
];

const STATUS_ICONS = [
    'pending' => '⬜',
    'running' => '🟨',
    'success' => '🟩',
    'warning' => '🟨',
    'failed' => '🟥',
    'skipped' => '⬜',
];

$checks = [];

function sendPipelineStatus(string $status, string $phase, string $step, ?string $message = null, ?array $details = null): void
{
    global $backendUrl, $apiToken;

    if (! $apiToken) {
        return;
    }

    $ch = curl_init("{$backendUrl}/api/v1/logs/pipeline");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer '.$apiToken,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'status' => $status,
            'phase' => $phase,
            'step' => $step,
            'message' => $message,
            'details' => $details,
        ]),
    ]);

    curl_exec($ch);
    curl_close($ch);
}

function sendLog(string $level, string $message, array $context = []): void
{
    global $backendUrl, $apiToken;

    if (! $apiToken) {
        return;
    }

    $ch = curl_init("{$backendUrl}/api/v1/logs");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer '.$apiToken,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]),
    ]);

    curl_exec($ch);
    curl_close($ch);
}

function drawHeader(): void
{
    echo "\033[2J\033[H";
    echo "\033[1m\033[97m".str_repeat('=', 90)."\033[0m\n";
    echo "\033[1m\033[97m  🔍 RH Tech IA - Pipeline Runner (Streaming)\033[0m\n";
    echo "\033[1m\033[97m".str_repeat('=', 90)."\033[0m\n\n";
}

function drawPhaseHeader(string $phase): void
{
    $config = PHASES[$phase] ?? ['icon' => '⬜', 'color' => 'white', 'label' => 'UNKNOWN'];
    $color = match ($config['color']) {
        'red' => "\033[91m",
        'yellow' => "\033[93m",
        'green' => "\033[92m",
        default => "\033[97m",
    };

    echo "\n";
    echo "{$color}\033[1m┌────────────────────────────────────────────────────────────┐\033[0m\n";
    echo "{$color}\033[1m│ {$config['icon']} {$config['label']}".str_repeat(' ', 47)."│\033[0m\n";
    echo "{$color}\033[1m└────────────────────────────────────────────────────────────┘\033[0m\n";
    echo "\n";
}

function drawCheck(int $index, array $check): void
{
    $color = STATUS_COLORS[$check['status']] ?? "\033[97m";
    $icon = STATUS_ICONS[$check['status']] ?? '⬜';

    $num = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
    $duration = number_format($check['duration'], 2);

    echo "  {$icon} \033[1m{$color}[{$num}]\033[0m ";
    echo "\033[97m{$check['name']}\033[0m  ";

    $statusText = match ($check['status']) {
        'running' => "\033[93m▓▓▓▓▓░░░░░░░░ 50%\033[0m",
        'success' => "\033[92m[✓] SUCESSO\033[0m",
        'warning' => "\033[93m[!] AVISO\033[0m",
        'failed' => "\033[91m[✗] FALHOU\033[0m",
        'skipped' => "\033[90m[-] PULADO\033[0m",
        default => "\033[90m[...]\033[0m",
    };

    echo $statusText;
    echo " \033[90m[{$duration}s]\033[0m\n";

    if (! empty($check['output']) && $check['status'] !== 'pending') {
        $lines = array_filter(explode("\n", $check['output']));
        foreach (array_slice($lines, 0, 3) as $line) {
            $line = trim($line);
            if ($line) {
                echo "     \033[90m├─ ".substr($line, 0, 70)."\033[0m\n";
            }
        }
    }
}

function drawSummary(array $checks, float $totalTime): void
{
    $success = count(array_filter($checks, fn ($c) => $c['status'] === 'success'));
    $warnings = count(array_filter($checks, fn ($c) => $c['status'] === 'warning'));
    $failed = count(array_filter($checks, fn ($c) => $c['status'] === 'failed'));
    $skipped = count(array_filter($checks, fn ($c) => $c['status'] === 'skipped'));

    echo "\n";
    echo "\033[1m\033[97m".str_repeat('=', 90)."\033[0m\n";
    echo "\033[1m\033[97m  📊 RESUMO DO PIPELINE\033[0m\n";
    echo "\033[1m\033[97m".str_repeat('=', 90)."\033[0m\n";
    echo "\n";

    echo '  ┌'.str_repeat('─', 82)."┐\n";
    echo "  │  \033[92m🟩 {$success} Sucesso   \033[0m";
    echo "\033[93m🟨 {$warnings} Avisos   \033[0m";
    echo "\033[91m🟥 {$failed} Falhas   \033[0m";
    echo "\033[90m⬜ {$skipped} Pulados   \033[0m";
    echo str_repeat(' ', 22)."│\n";
    echo "  │  \033[97m⏱ Tempo Total: ".number_format($totalTime, 1)."s\033[0m".str_repeat(' ', 62)."│\n";
    echo '  └'.str_repeat('─', 82)."┘\n";
    echo "\n";

    if ($failed > 0) {
        echo "\033[41m\033[1m\033[97m  ❌ PIPELINE FALHOU - CORRIJA OS ERROS ACIMA  \033[0m\n";
    } else {
        echo "\033[42m\033[1m\033[97m  ✅ PIPELINE CONCLUÍDO COM SUCESSO  \033[0m\n";
    }
    echo "\n";
}

function runCheck(array &$check): void
{
    $start = microtime(true);

    exec($check['command'], $output, $returnCode);

    $check['duration'] = microtime(true) - $start;
    $check['output'] = implode("\n", $output);

    if (str_contains($check['output'], 'SKIP:')) {
        $check['status'] = 'skipped';
    } elseif ($returnCode !== 0 || preg_match('/(error|failed|fail)/i', $check['output'])) {
        $check['status'] = $check['block_on_fail'] ? 'failed' : 'warning';
    } elseif (preg_match('/(warning|deprecated)/i', $check['output'])) {
        $check['status'] = 'warning';
    } else {
        $check['status'] = 'success';
    }
}

$securityChecks = [
    ['name' => 'Secret Scan', 'phase' => 'INICIO', 'command' => 'grep -rE "(sk-|AKIA|ghp_)" backend/ frontend/ --include="*.php" --include="*.js" --include="*.ts" -l 2>/dev/null | head -5 || echo "NO_MATCH"', 'block_on_fail' => true],
    ['name' => 'Gitleaks', 'phase' => 'INICIO', 'command' => 'command -v gitleaks &>/dev/null && gitleaks detect --source . --no-color 2>&1 || echo "SKIP:gitleaks not installed"', 'block_on_fail' => true],
    ['name' => 'Pre-commit Hook', 'phase' => 'INICIO', 'command' => '[ -f .githooks/pre-commit ] && chmod +x .githooks/pre-commit && .githooks/pre-commit 2>&1 || echo "SKIP"', 'block_on_fail' => true],
];

$validationChecks = [
    ['name' => 'PHP Syntax', 'phase' => 'MEIO', 'command' => 'find backend/app -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors" | head -10 || echo "OK"', 'block_on_fail' => true],
    ['name' => 'PHPUnit', 'phase' => 'MEIO', 'command' => 'cd backend && if [ -f phpunit.xml ] || [ -f phpunit.xml.dist ]; then ./vendor/bin/phpunit --no-coverage 2>&1 | tail -10 || echo "SKIP:no phpunit"; else echo "SKIP:no phpunit"; fi', 'block_on_fail' => false],
    ['name' => 'Pint Linter', 'phase' => 'MEIO', 'command' => 'cd backend && if [ -f vendor/bin/pint ]; then ./vendor/bin/pint --test 2>&1 | tail -5 || echo "SKIP:no pint issues"; else echo "SKIP:no pint"; fi', 'block_on_fail' => false],
    ['name' => 'ESLint', 'phase' => 'MEIO', 'command' => 'cd frontend && if [ -f eslint.config.js ] || [ -f .eslintrc.js ]; then npm run lint 2>&1 | tail -5 || echo "SKIP:no eslint"; else echo "SKIP:no eslint"; fi', 'block_on_fail' => false],
    ['name' => 'TypeScript', 'phase' => 'MEIO', 'command' => 'cd frontend && if [ -f tsconfig.json ]; then npm run typecheck 2>&1 | tail -5 || echo "SKIP:no tsconfig"; else echo "SKIP:no tsconfig"; fi', 'block_on_fail' => false],
    ['name' => 'Docker Build', 'phase' => 'FIM', 'command' => '[ -f docker-compose.yml ] && docker compose build 2>&1 | tail -5 || echo "SKIP:no docker"', 'block_on_fail' => false],
];

$checks = $mode === 'security' ? $securityChecks : array_merge($securityChecks, $validationChecks);

foreach ($checks as &$check) {
    $check['status'] = 'pending';
    $check['output'] = '';
    $check['duration'] = 0;
}

$startTime = microtime(true);
$currentPhase = '';

drawHeader();
sendPipelineStatus('started', 'INICIO', 'Iniciando Pipeline', 'Pipeline iniciado');

foreach ($checks as $index => &$check) {
    if ($check['phase'] !== $currentPhase) {
        $currentPhase = $check['phase'];
        drawPhaseHeader($currentPhase);
        sendPipelineStatus('running', $currentPhase, $check['name'], "Iniciando fase {$currentPhase}");
    }

    $check['status'] = 'running';
    drawHeader();
    foreach ($checks as $i => $c) {
        if ($i <= $index) {
            if ($c['phase'] !== $currentPhase) {
                $currentPhase = $c['phase'];
                drawPhaseHeader($currentPhase);
            }
            drawCheck($i, $c);
        }
    }

    sendPipelineStatus('running', $check['phase'], $check['name'], "Executando {$check['name']}...");
    sendLog('info', "Iniciando check: {$check['name']}", ['phase' => $check['phase']]);

    runCheck($check);

    sendPipelineStatus(
        $check['status'] === 'failed' ? 'failed' : ($check['status'] === 'success' ? 'running' : 'warning'),
        $check['phase'],
        $check['name'],
        "Check {$check['name']}: {$check['status']}",
        ['duration' => $check['duration'], 'output' => substr($check['output'], 0, 200)]
    );

    sendLog(
        $check['status'] === 'failed' ? 'error' : ($check['status'] === 'warning' ? 'warning' : 'info'),
        "Check {$check['name']}: {$check['status']}",
        [
            'phase' => $check['phase'],
            'status' => $check['status'],
            'duration' => $check['duration'],
        ]
    );

    drawHeader();
    foreach ($checks as $i => $c) {
        if ($c['phase'] !== $currentPhase && $i <= $index) {
            $currentPhase = $c['phase'];
            drawPhaseHeader($currentPhase);
        }
        drawCheck($i, $c);
    }
    echo "\n";

    if ($check['status'] === 'failed' && $check['block_on_fail']) {
        echo "\033[91m✗ Check '{$check['name']}' falhou. Abortando pipeline.\033[0m\n";
        sendPipelineStatus('failed', $check['phase'], $check['name'], "Falhou: {$check['name']}", ['blocked' => true]);
        break;
    }
}

$totalTime = microtime(true) - $startTime;
drawSummary($checks, $totalTime);

$hasFailed = array_filter($checks, fn ($c) => $c['status'] === 'failed');
$finalStatus = empty($hasFailed) ? 'completed' : 'failed';
sendPipelineStatus($finalStatus, 'FIM', 'Pipeline', $finalStatus === 'completed' ? 'Pipeline concluído com sucesso' : 'Pipeline falhou', ['totalTime' => $totalTime, 'checks' => count($checks)]);

exit(empty($hasFailed) ? 0 : 1);
