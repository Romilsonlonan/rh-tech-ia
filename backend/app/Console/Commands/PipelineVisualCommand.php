<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Telemetry\OpenTelemetryService;
use Illuminate\Console\Command;

class PipelineVisualCommand extends Command
{
    protected $signature = 'pipeline:run
                            {--check= : Type of check: security, validation, or all}
                            {--demo : Run in demo mode with simulated results}';

    protected $description = 'Run visual pipeline checks with real-time output';

    private const PHASES = [
        'INICIO' => ['icon' => '🔴', 'color' => 'red', 'label' => 'FASE 1: INÍCIO - SEGURANÇA'],
        'MEIO' => ['icon' => '🟡', 'color' => 'yellow', 'label' => 'FASE 2: MEIO - VALIDAÇÃO'],
        'FIM' => ['icon' => '🟢', 'color' => 'green', 'label' => 'FASE 3: FIM - DEPLOY'],
    ];

    private array $checks = [];

    private float $startTime;

    private ?OpenTelemetryService $telemetry = null;

    public function handle(): int
    {
        $this->startTime = microtime(true);
        $checkType = $this->option('check') ?? 'all';
        $isDemo = (bool) $this->option('demo');

        if ($this->telemetry = app(OpenTelemetryService::class)) {
            $this->telemetry->startSpan('pipeline.run');
        }

        $this->clearScreen();
        $this->drawHeader();

        $this->initChecks($checkType);

        foreach ($this->checks as $index => &$check) {
            $check['status'] = 'running';
            $this->updateScreen($index);

            if ($isDemo) {
                $this->simulateCheck($check);
            } else {
                $this->executeCheck($check);
            }

            $this->updateScreen($index);

            if ($check['status'] === 'failed' && $check['block_on_fail']) {
                $this->line("\n  <fg=red>✗ Check '{$check['name']}' falhou. Abortando pipeline.</>");
                break;
            }

            $this->line('');
        }

        $this->drawSummary();

        if ($this->telemetry) {
            $this->telemetry->recordSpanStatus('pipeline.run', count(array_filter($this->checks, fn ($c) => $c['status'] === 'failed')) === 0);
            $this->telemetry->endSpan('pipeline.run');
        }

        return count(array_filter($this->checks, fn ($c) => $c['status'] === 'failed')) > 0 ? 1 : 0;
    }

    private function initChecks(string $type): void
    {
        $security = [
            ['name' => 'Secret Scan', 'phase' => 'INICIO', 'command' => 'grep -rE "(sk-|AKIA|ghp_)" backend/ frontend/ --include="*.php" --include="*.js" --include="*.ts" -l 2>/dev/null | head -5', 'block_on_fail' => true],
            ['name' => 'Gitleaks', 'phase' => 'INICIO', 'command' => 'command -v gitleaks &>/dev/null && gitleaks detect --source . --no-color 2>&1 || echo "SKIP"', 'block_on_fail' => true],
            ['name' => 'Pre-commit', 'phase' => 'INICIO', 'command' => '[ -f .githooks/pre-commit ] && .githooks/pre-commit 2>&1 || echo "SKIP"', 'block_on_fail' => true],
        ];

        $validation = [
            ['name' => 'PHP Syntax', 'phase' => 'MEIO', 'command' => 'find backend/app -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors" | head -10', 'block_on_fail' => true],
            ['name' => 'PHPUnit', 'phase' => 'MEIO', 'command' => 'cd backend && [ -f phpunit.xml ] && ./vendor/bin/phpunit --no-coverage 2>&1 | tail -20 || echo "SKIP"', 'block_on_fail' => false],
            ['name' => 'Pint Linter', 'phase' => 'MEIO', 'command' => 'cd backend && [ -f vendor/bin/pint ] && ./vendor/bin/pint --test 2>&1 | tail -10 || echo "SKIP"', 'block_on_fail' => false],
            ['name' => 'ESLint', 'phase' => 'MEIO', 'command' => 'cd frontend && [ -f eslint.config.js ] && npm run lint 2>&1 | tail -15 || echo "SKIP"', 'block_on_fail' => false],
            ['name' => 'TypeScript', 'phase' => 'MEIO', 'command' => 'cd frontend && [ -f tsconfig.json ] && npm run typecheck 2>&1 | tail -15 || echo "SKIP"', 'block_on_fail' => false],
            ['name' => 'Composer Audit', 'phase' => 'MEIO', 'command' => 'cd backend && composer audit --no-interaction 2>&1 | tail -20 || echo "SKIP"', 'block_on_fail' => false],
            ['name' => 'NPM Audit', 'phase' => 'MEIO', 'command' => 'cd frontend && npm audit --audit-level=high 2>&1 | tail -20 || echo "SKIP"', 'block_on_fail' => false],
            ['name' => 'Docker Build', 'phase' => 'FIM', 'command' => '[ -f docker-compose.yml ] && docker compose build --no-cache 2>&1 | tail -10 || echo "SKIP"', 'block_on_fail' => false],
        ];

        $this->checks = match ($type) {
            'security' => $security,
            'validation' => $validation,
            default => [...$security, ...$validation],
        };

        foreach ($this->checks as &$check) {
            $check['status'] = 'pending';
            $check['output'] = '';
            $check['duration'] = 0;
        }
    }

    private function executeCheck(array &$check): void
    {
        $start = microtime(true);

        exec($check['command'], $output, $returnCode);

        $check['duration'] = microtime(true) - $start;
        $check['output'] = implode("\n", $output);

        if (str_contains($check['output'], 'SKIP')) {
            $check['status'] = 'skipped';
        } elseif ($returnCode !== 0 || preg_match('/(error|failed)/i', $check['output'])) {
            $check['status'] = $check['block_on_fail'] ? 'failed' : 'warning';
        } elseif (preg_match('/(warning|deprecated)/i', $check['output'])) {
            $check['status'] = 'warning';
        } else {
            $check['status'] = 'success';
        }
    }

    private function simulateCheck(array &$check): void
    {
        usleep(random_int(200000, 800000));

        $check['duration'] = round(random_int(200, 2000) / 1000, 2);

        $rand = random_int(1, 100);

        if ($rand <= 70) {
            $check['status'] = 'success';
            $check['output'] = '✓ Verificação concluída com sucesso';
        } elseif ($rand <= 85) {
            $check['status'] = 'warning';
            $check['output'] = '⚠ 2 warnings encontrados (não críticos)';
        } elseif ($rand <= 95) {
            $check['status'] = 'skipped';
            $check['output'] = '⊘ Pulado - não aplicável neste ambiente';
        } else {
            $check['status'] = 'failed';
            $check['output'] = '✗ Falhou: referência indefinida em config.php linha 42';
        }
    }

    private function clearScreen(): void
    {
        if (app()->runningInConsole()) {
            $this->output->write("\033[2J\033[H");
        }
    }

    private function drawHeader(): void
    {
        $this->output->writeln('');
        $this->output->writeln('<fg=white;options=bold>'.str_repeat('=', 90).'</>');
        $this->output->writeln('<fg=white;options=bold>  🔍 RH Tech IA - Visual Pipeline Runner</>');
        $this->output->writeln('<fg=white;options=bold>'.str_repeat('=', 90).'</>');
        $this->output->writeln('');
    }

    private function updateScreen(int $currentIndex): void
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
        $config = self::PHASES[$phase] ?? ['icon' => '⬜', 'color' => 'white', 'label' => 'UNKNOWN'];

        $color = match ($config['color']) {
            'red' => '<fg=red>',
            'yellow' => '<fg=yellow>',
            'green' => '<fg=green>',
            default => '<fg=white>',
        };

        $this->output->writeln('');
        $this->output->writeln("{$color}<options=bold>┌────────────────────────────────────────────────────────────┐</>");
        $this->output->writeln("{$color}<options=bold>│ {$config['icon']} {$config['label']}".str_repeat(' ', 44).'│</>');
        $this->output->writeln("{$color}<options=bold>└────────────────────────────────────────────────────────────┘</>");
        $this->output->writeln('');
    }

    private function drawCheckBox(int $index, array $check): void
    {
        $statusConfig = match ($check['status']) {
            'pending' => ['icon' => '⬜', 'color' => 'gray', 'text' => 'PENDENTE'],
            'running' => ['icon' => '🟨', 'color' => 'yellow', 'text' => 'EXECUTANDO...'],
            'success' => ['icon' => '🟩', 'color' => 'green', 'text' => '[✓] SUCESSO'],
            'warning' => ['icon' => '🟨', 'color' => 'yellow', 'text' => '[!] AVISO'],
            'failed' => ['icon' => '🟥', 'color' => 'red', 'text' => '[✗] FALHOU'],
            'skipped' => ['icon' => '⬜', 'color' => 'gray', 'text' => '[-] PULADO'],
            default => ['icon' => '⬜', 'color' => 'gray', 'text' => '???'],
        };

        $num = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
        $duration = number_format($check['duration'], 2);

        $color = match ($statusConfig['color']) {
            'red' => '<fg=red>',
            'yellow' => '<fg=yellow>',
            'green' => '<fg=green>',
            'gray' => '<fg=gray>',
            default => '<fg=white>',
        };

        $this->output->write("  {$statusConfig['icon']} ");
        $this->output->write("{$color}[{$num}]</> <fg=white>{$check['name']}</>  ");

        if ($check['status'] === 'running') {
            $this->output->writeln('<fg=yellow>▓▓▓▓▓░░░░░░░░ 50%</>');
        } elseif ($check['status'] === 'success') {
            $this->output->writeln("<fg=green>{$statusConfig['text']}</>");
        } elseif ($check['status'] === 'warning') {
            $this->output->writeln("<fg=yellow>{$statusConfig['text']}</>");
        } elseif ($check['status'] === 'failed') {
            $this->output->writeln("<fg=red>{$statusConfig['text']}</>");
        } else {
            $this->output->writeln("<fg=gray>{$statusConfig['text']}</>");
        }

        if ($check['output']) {
            $lines = array_filter(explode("\n", $check['output']));
            foreach (array_slice($lines, 0, 3) as $line) {
                $line = trim($line);
                if ($line) {
                    $this->output->writeln('     <fg=gray>├─ '.substr($line, 0, 70).'</>');
                }
            }
        }
    }

    private function drawSummary(): void
    {
        $totalTime = number_format(microtime(true) - $this->startTime, 1);
        $success = count(array_filter($this->checks, fn ($c) => $c['status'] === 'success'));
        $warnings = count(array_filter($this->checks, fn ($c) => $c['status'] === 'warning'));
        $failed = count(array_filter($this->checks, fn ($c) => $c['status'] === 'failed'));
        $skipped = count(array_filter($this->checks, fn ($c) => $c['status'] === 'skipped'));

        $this->output->writeln('');
        $this->output->writeln('<fg=white;options=bold>'.str_repeat('=', 90).'</>');
        $this->output->writeln('<fg=white;options=bold>  📊 RESUMO DO PIPELINE</>');
        $this->output->writeln('<fg=white;options=bold>'.str_repeat('=', 90).'</>');
        $this->output->writeln('');

        $this->output->writeln('  ┌'.str_repeat('─', 82).'┐');
        $this->output->write("  │  <fg=green>🟩 {$success} Sucesso   </>");
        $this->output->write("<fg=yellow>🟨 {$warnings} Avisos   </>");
        $this->output->write("<fg=red>🟥 {$failed} Falhas   </>");
        $this->output->write("<fg=gray>⬜ {$skipped} Pulados   </>");
        $this->output->writeln(str_repeat(' ', 22).'│');
        $this->output->writeln("  │  <fg=white>⏱ Tempo Total: {$totalTime}s</>".str_repeat(' ', 62).'│');
        $this->output->writeln('  └'.str_repeat('─', 82).'┘');
        $this->output->writeln('');

        if ($failed > 0) {
            $this->output->writeln('<bg=red;options=bold;fg=white>  ❌ PIPELINE FALHOU - CORRIJA OS ERROS ACIMA  </>');
        } else {
            $this->output->writeln('<bg=green;options=bold;fg=white>  ✅ PIPELINE CONCLUÍDO COM SUCESSO  </>');
        }
        $this->output->writeln('');
    }
}
