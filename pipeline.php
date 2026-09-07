#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * RH Tech IA - Visual Pipeline Runner
 *
 * Executa checks de segurança e validação com visualização em tempo real
 * similar ao Jenkins/GitHub Actions.
 *
 * Usage: php pipeline.php [--check=security|all]
 */

namespace App\Pipeline;

class VisualPipeline
{
    private const ESC = "\033";

    private const RESET = self::ESC.'[0m';

    private const BOLD = self::ESC.'[1m';

    private const RED = self::ESC.'[38;5;196m';

    private const GREEN = self::ESC.'[38;5;82m';

    private const YELLOW = self::ESC.'[38;5;226m';

    private const BLUE = self::ESC.'[38;5;75m';

    private const WHITE = self::ESC.'[38;5;255m';

    private const GRAY = self::ESC.'[38;5;240m';

    private const BG_RED = self::ESC.'[48;5;196m';

    private const BG_GREEN = self::ESC.'[48;5;82m';

    private const BG_YELLOW = self::ESC.'[48;5;226m';

    private const BG_BLUE = self::ESC.'[48;5;75m';

    private const BG_GRAY = self::ESC.'[48;5;240m';

    private array $checks = [];

    private float $startTime;

    private string $mode = 'all';

    public function __construct()
    {
        $this->parseArgs();
        $this->startTime = microtime(true);
    }

    private function parseArgs(): void
    {
        global $argv;
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--check=')) {
                $this->mode = substr($arg, 8);
            }
        }
    }

    public function run(): int
    {
        $this->clearScreen();
        $this->drawHeader();

        $this->initChecks();
        $this->drawPipeline();

        foreach ($this->checks as $index => &$check) {
            $check['status'] = 'running';
            $this->updateCheck($index);
            $this->runCheck($check);
            $this->updateCheck($index);
            $this->drawPipeline();
        }

        $this->drawFooter();

        return $this->getExitCode();
    }

    private function initChecks(): void
    {
        $this->checks = match ($this->mode) {
            'security' => $this->getSecurityChecks(),
            default => array_merge($this->getSecurityChecks(), $this->getValidationChecks()),
        };
    }

    private function getSecurityChecks(): array
    {
        return [
            [
                'name' => 'Secret Scan',
                'phase' => 'INICIO',
                'command' => 'grep -rE "(sk-|AKIA|ghp_|glpat-|xox[baprs])" --include="*.php" --include="*.js" --include="*.ts" --include="*.env" -l . 2>/dev/null | head -5',
                'block_on_fail' => true,
                'status' => 'pending',
                'output' => '',
                'duration' => 0,
            ],
            [
                'name' => 'Gitleaks',
                'phase' => 'INICIO',
                'command' => 'command -v gitleaks &>/dev/null && gitleaks detect --source . --no-color 2>&1 || echo "SKIP:gitleaks not installed"',
                'block_on_fail' => true,
                'status' => 'pending',
                'output' => '',
                'duration' => 0,
            ],
            [
                'name' => 'Pre-commit Hook',
                'phase' => 'INICIO',
                'command' => 'if [ -f .githooks/pre-commit ]; then chmod +x .githooks/pre-commit && .githooks/pre-commit 2>&1 || echo "HOOK_FAILED"; fi',
                'block_on_fail' => true,
                'status' => 'pending',
                'output' => '',
                'duration' => 0,
            ],
        ];
    }

    private function getValidationChecks(): array
    {
        return [
            [
                'name' => 'PHP Syntax',
                'phase' => 'MEIO',
                'command' => 'find backend/app -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors" | head -10',
                'block_on_fail' => true,
                'status' => 'pending',
                'output' => '',
                'duration' => 0,
            ],
            [
                'name' => 'PHPUnit',
                'phase' => 'MEIO',
                'command' => 'cd backend && if [ -f phpunit.xml ] || [ -f phpunit.xml.dist ]; then ./vendor/bin/phpunit --no-coverage 2>&1 | tail -20; else echo "SKIP:no phpunit"; fi',
                'block_on_fail' => false,
                'status' => 'pending',
                'output' => '',
                'duration' => 0,
            ],
            [
                'name' => 'Pint Linter',
                'phase' => 'MEIO',
                'command' => 'cd backend && if [ -f vendor/bin/pint ]; then ./vendor/bin/pint --test 2>&1 | tail -10; else echo "SKIP:no pint"; fi',
                'block_on_fail' => false,
                'status' => 'pending',
                'output' => '',
                'duration' => 0,
            ],
            [
                'name' => 'ESLint',
                'phase' => 'MEIO',
                'command' => 'cd frontend && if [ -f eslint.config.js ] || [ -f .eslintrc.js ]; then npm run lint 2>&1 | tail -15; else echo "SKIP:no eslint"; fi',
                'block_on_fail' => false,
                'status' => 'pending',
                'output' => '',
                'duration' => 0,
            ],
            [
                'name' => 'TypeScript',
                'phase' => 'MEIO',
                'command' => 'cd frontend && if [ -f tsconfig.json ]; then npm run typecheck 2>&1 | tail -15; else echo "SKIP:no tsconfig"; fi',
                'block_on_fail' => false,
                'status' => 'pending',
                'output' => '',
                'duration' => 0,
            ],
            [
                'name' => 'Composer Audit',
                'phase' => 'MEIO',
                'command' => 'cd backend && composer audit --no-interaction 2>&1 | tail -20',
                'block_on_fail' => false,
                'status' => 'pending',
                'output' => '',
                'duration' => 0,
            ],
            [
                'name' => 'NPM Audit',
                'phase' => 'MEIO',
                'command' => 'cd frontend && npm audit --audit-level=high 2>&1 | tail -20',
                'block_on_fail' => false,
                'status' => 'pending',
                'output' => '',
                'duration' => 0,
            ],
            [
                'name' => 'Docker Build',
                'phase' => 'FIM',
                'command' => 'if [ -f docker-compose.yml ]; then docker compose build --no-cache 2>&1 | tail -10; else echo "SKIP:no docker"; fi',
                'block_on_fail' => false,
                'status' => 'pending',
                'output' => '',
                'duration' => 0,
            ],
        ];
    }

    private function runCheck(array &$check): void
    {
        $start = microtime(true);

        exec($check['command'], $output, $returnCode);
        $check['duration'] = microtime(true) - $start;
        $check['output'] = implode("\n", $output);

        if (str_contains($check['output'], 'SKIP:')) {
            $check['status'] = 'skipped';
            $check['output'] = str_replace('SKIP:', '', $check['output']);
        } elseif ($returnCode !== 0 || preg_match('/(error|failed|fail)/i', $check['output'])) {
            if ($check['block_on_fail']) {
                $check['status'] = 'failed';
            } else {
                $check['status'] = 'warning';
            }
        } elseif (preg_match('/(warning|warn|deprecated)/i', $check['output'])) {
            $check['status'] = 'warning';
        } else {
            $check['status'] = 'success';
        }
    }

    private function clearScreen(): void
    {
        echo self::ESC.'[2J';
        echo self::ESC.'[H';
    }

    private function drawHeader(): void
    {
        $width = 100;
        echo "\n";
        echo self::BOLD.self::WHITE.str_repeat('=', $width).self::RESET."\n";
        echo self::BOLD.self::WHITE.'  🔍 RH Tech IA - Visual Pipeline Runner'.self::RESET."\n";
        echo self::BOLD.self::WHITE.str_repeat('=', $width).self::RESET."\n";
        echo "\n";
    }

    private function drawPipeline(): void
    {
        $phase = '';
        foreach ($this->checks as $index => $check) {
            if ($check['phase'] !== $phase) {
                $phase = $check['phase'];
                $this->drawPhaseHeader($phase);
            }
            $this->drawCheckBox($index, $check);
        }
    }

    private function drawPhaseHeader(string $phase): void
    {
        $phaseConfig = match ($phase) {
            'INICIO' => ['color' => self::RED, 'bg' => self::BG_RED, 'icon' => '🔴', 'desc' => 'SEGURANÇA'],
            'MEIO' => ['color' => self::YELLOW, 'bg' => self::BG_YELLOW, 'icon' => '🟡', 'desc' => 'VALIDAÇÃO'],
            'FIM' => ['color' => self::GREEN, 'bg' => self::BG_GREEN, 'icon' => '🟢', 'desc' => 'DEPLOY'],
            default => ['color' => self::WHITE, 'bg' => self::BG_GRAY, 'icon' => '⬜', 'desc' => 'UNKNOWN'],
        };

        echo "\n";
        echo self::BOLD.$phaseConfig['color'].'┌'.str_repeat('─', 60).'┐'.self::RESET."\n";
        echo self::BOLD.$phaseConfig['color'].'│ '.$phaseConfig['icon'].' '.$phaseConfig['desc'].str_repeat(' ', 47).'│'.self::RESET."\n";
        echo self::BOLD.$phaseConfig['color'].'└'.str_repeat('─', 60).'┘'.self::RESET."\n";
        echo "\n";
    }

    private function drawCheckBox(int $index, array $check): void
    {
        $statusConfig = match ($check['status']) {
            'pending' => ['box' => '⬜', 'color' => self::GRAY, 'text' => 'PENDENTE'],
            'running' => ['box' => '🟨', 'color' => self::YELLOW, 'text' => 'EXECUTANDO...'],
            'success' => ['box' => '🟩', 'color' => self::GREEN, 'text' => 'SUCESSO ✓'],
            'warning' => ['box' => '🟨', 'color' => self::YELLOW, 'text' => 'AVISO ⚠'],
            'failed' => ['box' => '🟥', 'color' => self::RED, 'text' => 'FALHOU ✗'],
            'skipped' => ['box' => '⬜', 'color' => self::GRAY, 'text' => 'PULADO'],
            default => ['box' => '⬜', 'color' => self::GRAY, 'text' => '???'],
        };

        $num = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
        $duration = number_format($check['duration'], 2);

        echo "  {$statusConfig['box']} ";
        echo self::BOLD.$statusConfig['color']."[{$num}] ".self::RESET;
        echo self::BOLD.self::WHITE.str_pad($check['name'], 20, ' ', STR_PAD_RIGHT).self::RESET;

        if ($check['status'] === 'running') {
            echo self::YELLOW.'▓▓▓▓▓▓▓▓▓░░░░░░░░ 50%  '.self::RESET;
        } elseif ($check['status'] === 'success') {
            echo self::GREEN."{$statusConfig['text']} ".self::RESET;
        } elseif ($check['status'] === 'warning') {
            echo self::YELLOW."{$statusConfig['text']} ".self::RESET;
        } elseif ($check['status'] === 'failed') {
            echo self::RED."{$statusConfig['text']} ".self::RESET;
        } elseif ($check['status'] === 'skipped') {
            echo self::GRAY."{$statusConfig['text']} ".self::RESET;
        } else {
            echo self::GRAY."{$statusConfig['text']} ".self::RESET;
        }

        echo self::GRAY."[{$duration}s]".self::RESET."\n";

        if ($check['output'] && $check['status'] !== 'pending') {
            $lines = array_filter(explode("\n", $check['output']));
            $outputLines = array_slice($lines, 0, 3);
            foreach ($outputLines as $line) {
                $line = trim($line);
                if ($line) {
                    echo '     '.self::GRAY.'├─ '.self::RESET;
                    echo self::GRAY.substr($line, 0, 70).self::RESET."\n";
                }
            }
        }
    }

    private function drawFooter(): void
    {
        $totalTime = number_format(microtime(true) - $this->startTime, 2);
        $success = count(array_filter($this->checks, fn ($c) => $c['status'] === 'success'));
        $warnings = count(array_filter($this->checks, fn ($c) => $c['status'] === 'warning'));
        $failed = count(array_filter($this->checks, fn ($c) => $c['status'] === 'failed'));
        $skipped = count(array_filter($this->checks, fn ($c) => $c['status'] === 'skipped'));

        echo "\n";
        echo self::BOLD.self::WHITE.str_repeat('=', 100).self::RESET."\n";
        echo self::BOLD.self::WHITE.'  📊 RESUMO DO PIPELINE'.self::RESET."\n";
        echo self::BOLD.self::WHITE.str_repeat('=', 100).self::RESET."\n";

        echo "\n";
        echo '  ┌'.str_repeat('─', 95)."┐\n";

        $statusBar = '  │  ';
        $statusBar .= self::GREEN."🟩 {$success} Sucesso  ".self::RESET;
        $statusBar .= self::YELLOW."🟨 {$warnings} Avisos  ".self::RESET;
        $statusBar .= self::RED."🟥 {$failed} Falhas  ".self::RESET;
        $statusBar .= self::GRAY."⬜ {$skipped} Pulados  ".self::RESET;
        $statusBar .= str_repeat(' ', 20)."│\n";
        echo $statusBar;

        echo '  │  '.self::WHITE."⏱ Tempo Total: {$totalTime}s".str_repeat(' ', 60).self::RESET."│\n";
        echo '  └'.str_repeat('─', 95)."┘\n";

        $exitCode = $this->getExitCode();
        if ($exitCode === 0) {
            echo "\n";
            echo self::BG_GREEN.self::BOLD.self::WHITE.'  ✅ PIPELINE CONCLUÍDO COM SUCESSO  '.self::RESET."\n";
        } else {
            echo "\n";
            echo self::BG_RED.self::BOLD.self::WHITE.'  ❌ PIPELINE FALHOU - CORRIJA OS ERROS ACIMA  '.self::RESET."\n";
        }

        echo "\n";
    }

    private function getExitCode(): int
    {
        $hasFailed = array_filter($this->checks, fn ($c) => $c['status'] === 'failed');

        return empty($hasFailed) ? 0 : 1;
    }
}

require_once __DIR__.'/vendor/autoload.php';

$pipeline = new VisualPipeline;
exit($pipeline->run());
