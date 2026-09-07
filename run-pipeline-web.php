#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * RH Tech IA - Pipeline Web Monitor
 *
 * Inicia servidor web na porta 3001 e abre o navegador.
 * O pipeline-stream.php deve ser executado em paralelo.
 *
 * Usage: php run-pipeline-web.php [--check=security|all]
 */
$mode = 'all';
$port = 3001;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--check=')) {
        $mode = substr($arg, 8);
    }
}

$monitorUrl = "http://localhost:{$port}/pipeline-monitor.html";
$docroot = __DIR__;

echo "\n";
echo "\033[1m\033[97m".str_repeat('=', 70)."\033[0m\n";
echo "\033[1m\033[97m  🚀 RH Tech IA - Pipeline Web Monitor\033[0m\n";
echo "\033[1m\033[97m".str_repeat('=', 70)."\033[0m\n\n";

echo "📡 Monitor URL: \033[94m{$monitorUrl}\033[0m\n";
echo "📋 Modo: \033[94m".strtoupper($mode)."\033[0m\n\n";

echo "⏳ Verificando se o backend está rodando...\n";

$ch = curl_init('http://localhost:8000/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "\033[93m⚠️  Backend não está rodando em localhost:8000\033[0m\n";
    echo "   Inicie o backend primeiro:\n";
    echo "   \033[92m   cd backend && php artisan serve\033[0m\n\n";
}

echo "🌐 Abrindo navegador...\n";

$os = strtolower(PHP_OS);
$openCmd = null;

if (str_contains($os, 'linux')) {
    $openCmd = 'xdg-open';
} elseif (str_contains($os, 'darwin')) {
    $openCmd = 'open';
} elseif (str_contains($os, 'windows')) {
    $openCmd = 'start';
}

if ($openCmd) {
    exec("{$openCmd} {$monitorUrl} 2>/dev/null &");
}

echo "\n";
echo "\033[1m\033[97m".str_repeat('=', 70)."\033[0m\n";
echo "\033[1m\033[97m  📊 Pipeline Monitor Started!\033[0m\n";
echo "\033[1m\033[97m".str_repeat('=', 70)."\033[0m\n\n";
echo "   👉 \033[94m{$monitorUrl}\033[0m\n\n";
echo "   Execute o pipeline em OUTRO terminal:\n\n";
echo "   \033[92m   php run-pipeline-stream.php --check={$mode}\033[0m\n\n";

echo "\033[93m   Ctrl+C para parar o servidor web\033[0m\n\n";

$pidFile = __DIR__.'/storage/logs/web-server.pid';
if (! is_dir(dirname($pidFile))) {
    mkdir(dirname($pidFile), 0755, true);
}
file_put_contents($pidFile, getmypid());

pcntl_signal(SIGINT, function () use ($pidFile) {
    echo "\n\033[92m✓ Servidor parado\033[0m\n";
    if (file_exists($pidFile)) {
        unlink($pidFile);
    }
    exit(0);
});

echo "\033[92m✓\033[0m Servidor PHP iniciado em \033[94m:{$port}\033[0m\n\n";

$cmd = sprintf(
    'php -S localhost:%d -t %s > /dev/null 2>&1 &',
    $port,
    escapeshellarg($docroot)
);
exec($cmd);

echo "\033[1m\033[97m".str_repeat('=', 70)."\033[0m\n\n";

while (true) {
    pcntl_signal_dispatch();
    sleep(1);
}
