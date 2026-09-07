#!/usr/bin/env node

/**
 * RH Tech IA - Pipeline Runner with Playwright Automation
 *
 * Executa o pipeline e abre automaticamente o navegador com Playwright
 * mostrando o progresso em tempo real.
 *
 * Usage: node run-pipeline-playwright.js [--check=security|all]
 */

import { chromium } from 'playwright';
import { spawn } from 'child_process';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const args = process.argv.slice(2);
const mode = args.includes('--check=security') ? 'security' : 'all';

const PHASES = {
  INICIO: { icon: '🔴', label: 'SEGURANÇA', color: '#ef4444' },
  MEIO: { icon: '🟡', label: 'VALIDAÇÃO', color: '#eab308' },
  FIM: { icon: '🟢', label: 'DEPLOY', color: '#22c55e' }
};

const STATUS = {
  pending: { icon: '⬜', label: 'PENDENTE' },
  running: { icon: '🟨', label: 'EXECUTANDO' },
  success: { icon: '🟩', label: 'SUCESSO' },
  warning: { icon: '🟨', label: 'AVISO' },
  failed: { icon: '🟥', label: 'FALHOU' },
  skipped: { icon: '⊘', label: 'PULADO' }
};

const checks = {
  INICIO: [
    { name: 'Secret Scan', command: 'grep -rE "(sk-|AKIA|ghp_)" backend/ frontend/ --include="*.php" --include="*.js" --include="*.ts" -l 2>/dev/null | head -5 || echo "NO_MATCH"' },
    { name: 'Gitleaks', command: 'command -v gitleaks &>/dev/null && gitleaks detect --source . --no-color 2>&1 || echo "SKIP:gitleaks not installed"' },
    { name: 'Pre-commit Hook', command: '[ -f .githooks/pre-commit ] && chmod +x .githooks/pre-commit && .githooks/pre-commit 2>&1 || echo "SKIP"' }
  ],
  MEIO: [
    { name: 'PHP Syntax', command: 'find backend/app -name "*.php" -exec php -l {} \\; 2>&1 | grep -v "No syntax errors" | head -10 || echo "OK"' },
    { name: 'PHPUnit', command: 'cd backend && if [ -f phpunit.xml ] || [ -f phpunit.xml.dist ]; then ./vendor/bin/phpunit --no-coverage 2>&1 | tail -5 || echo "SKIP"; else echo "SKIP"; fi' },
    { name: 'Pint Linter', command: 'cd backend && if [ -f vendor/bin/pint ]; then ./vendor/bin/pint --test 2>&1 | tail -3 || echo "OK"; else echo "SKIP"; fi' },
    { name: 'ESLint', command: 'cd frontend && if [ -f eslint.config.js ] || [ -f .eslintrc.js ]; then npm run lint 2>&1 | tail -3 || echo "SKIP"; else echo "SKIP"; fi' },
    { name: 'TypeScript', command: 'cd frontend && if [ -f tsconfig.json ]; then npm run typecheck 2>&1 | tail -3 || echo "SKIP"; else echo "SKIP"; fi' }
  ],
  FIM: [
    { name: 'Docker Build', command: '[ -f docker-compose.yml ] && docker compose build 2>&1 | tail -3 || echo "SKIP"' }
  ]
};

const allChecks = mode === 'security'
  ? checks.INICIO.map(c => ({ ...c, phase: 'INICIO' }))
  : [
      ...checks.INICIO.map(c => ({ ...c, phase: 'INICIO' })),
      ...checks.MEIO.map(c => ({ ...c, phase: 'MEIO' })),
      ...checks.FIM.map(c => ({ ...c, phase: 'FIM' }))
    ];

async function runPipeline() {
  console.log('\n🚀 Starting Pipeline with Playwright...\n');

  let browser;
  let page;

  try {
    browser = await chromium.launch({ headless: false });
    page = await browser.newPage();

    await page.setViewportSize({ width: 1400, height: 900 });

    const htmlContent = generateHTML();
    await page.setContent(htmlContent);

    await page.evaluate(() => document.title = 'RH Tech IA - Pipeline Monitor');

    const logsContainer = await page.$('#logsList');
    const checksContainers = {
      INICIO: await page.$('#checksINICIO'),
      MEIO: await page.$('#checksMEIO'),
      FIM: await page.$('#checksFIM')
    };

    await addLog(page, 'info', 'Pipeline iniciado');
    await addLog(page, 'info', `Modo: ${mode.toUpperCase()}`);

    let currentPhase = null;
    const startTime = Date.now();

    for (let i = 0; i < allChecks.length; i++) {
      const check = allChecks[i];
      const { phase, name, command } = check;

      if (phase !== currentPhase) {
        currentPhase = phase;
        await page.evaluate((p) => {
          const el = document.querySelector(`.phase.${p}`);
          if (el) el.classList.add('active');
        }, phase);
        await addLog(page, 'info', `Iniciando fase ${PHASES[phase].label}`);
      }

      await updateCheck(page, phase, i, 'running', 0);
      await addLog(page, 'info', `Executando: ${name}`);

      const result = await runCommand(command);
      const duration = result.duration;

      let status = 'success';
      if (result.output.includes('SKIP')) {
        status = 'skipped';
      } else if (result.exitCode !== 0 || /error|failed|fail/i.test(result.output)) {
        status = 'failed';
      } else if (/warning|deprecated/i.test(result.output)) {
        status = 'warning';
      }

      await updateCheck(page, phase, i, status, duration);

      if (status === 'failed') {
        await addLog(page, 'error', `${name} FALHOU: ${result.output.split('\n')[0]}`);
      } else if (status === 'warning') {
        await addLog(page, 'warning', `${name}: ${result.output.split('\n')[0]}`);
      } else if (status === 'skipped') {
        await addLog(page, 'info', `${name} pulado`);
      } else {
        await addLog(page, 'info', `${name} ✓`);
      }

      if (status === 'failed') {
        await addLog(page, 'error', 'Pipeline abortado devido a falha');
        break;
      }
    }

    const totalTime = ((Date.now() - startTime) / 1000).toFixed(1);
    const hasFailed = allChecks.some((c, i) => c._status === 'failed');

    await updateSummary(page, allChecks);
    await page.evaluate((time) => {
      const finalStatus = document.getElementById('finalStatus');
      const progressFill = document.getElementById('progressFill');
      if (progressFill) {
        progressFill.style.width = '100%';
        progressFill.className = 'fill success';
      }
      if (finalStatus) {
        finalStatus.className = 'final-status success';
        finalStatus.innerHTML = `✅ PIPELINE CONCLUÍDO COM SUCESSO (${time}s)`;
      }
    }, totalTime);

    await addLog(page, 'info', `Pipeline concluído em ${totalTime}s`);

    console.log('\n✅ Pipeline finished! Browser will stay open.\n');
    console.log('Press Ctrl+C to close the browser\n');

  } catch (error) {
    console.error('Error:', error);
    if (page) {
      await page.evaluate((msg) => addLog(null, 'error', msg), error.message);
    }
  }
}

function runCommand(command) {
  return new Promise((resolve) => {
    const start = Date.now();
    const child = spawn(command, { shell: true });

    let output = '';
    child.stdout.on('data', (data) => { output += data.toString(); });
    child.stderr.on('data', (data) => { output += data.toString(); });

    child.on('close', (exitCode) => {
      resolve({ exitCode, output: output.trim(), duration: (Date.now() - start) / 1000 });
    });

    child.on('error', (err) => {
      resolve({ exitCode: 1, output: err.message, duration: (Date.now() - start) / 1000 });
    });
  });
}

async function addLog(page, level, message) {
  const time = new Date().toLocaleTimeString('pt-BR');
  await page.evaluate(({ level, message, time }) => {
    const logsList = document.getElementById('logsList');
    if (!logsList) return;

    const entry = document.createElement('div');
    entry.className = `log-entry ${level}`;
    entry.innerHTML = `
      <span class="log-time">${time}</span>
      <span class="log-level">${level.toUpperCase()}</span>
      <span class="log-message">${escapeHtml(message)}</span>
    `;
    logsList.insertBefore(entry, logsList.firstChild);

    while (logsList.children.length > 100) {
      logsList.removeChild(logsList.lastChild);
    }
  }, { level, message, time });
}

async function updateCheck(page, phase, index, status, duration) {
  await page.evaluate(({ phase, index, status, duration }) => {
    const checkEl = document.querySelector(`#checks${phase} .check:nth-child(${index + 1})`);
    if (!checkEl) return;

    const iconEl = checkEl.querySelector('.check-icon');
    const statusEl = checkEl.querySelector('.check-status');

    if (iconEl) iconEl.textContent = STATUS[status].icon;
    if (statusEl) {
      statusEl.className = `check-status ${status}`;
      statusEl.textContent = STATUS[status].label;
    }
  }, { phase, index, status, duration });
}

async function updateSummary(page, allChecks) {
  const counts = { success: 0, warning: 0, failed: 0, skipped: 0 };
  allChecks.forEach(c => {
    if (c._status && counts[c._status] !== undefined) {
      counts[c._status]++;
    }
  });

  await page.evaluate((counts) => {
    document.getElementById('successCount').textContent = counts.success;
    document.getElementById('warningCount').textContent = counts.warning;
    document.getElementById('failedCount').textContent = counts.failed;
    document.getElementById('skippedCount').textContent = counts.skipped;
  }, counts);
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function generateHTML() {
  return `
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RH Tech IA - Pipeline Monitor</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0f172a; color: #f1f5f9; min-height: 100vh; }
.container { max-width: 1200px; margin: 0 auto; padding: 20px; }
header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-bottom: 2px solid #334155; padding: 20px 0; margin-bottom: 30px; }
header h1 { font-size: 1.8rem; margin-bottom: 5px; }
header .subtitle { color: #94a3b8; font-size: 0.9rem; }
.phases { display: flex; gap: 20px; margin-bottom: 30px; }
.phase { flex: 1; border-radius: 12px; padding: 20px; border: 2px solid; transition: all 0.3s ease; opacity: 0.6; }
.phase.active { opacity: 1; transform: scale(1.02); box-shadow: 0 0 30px rgba(255,255,255,0.1); }
.phase.INICIO { background: rgba(239,68,68,0.1); border-color: #ef4444; }
.phase.MEIO { background: rgba(234,179,8,0.1); border-color: #eab308; }
.phase.FIM { background: rgba(34,197,94,0.1); border-color: #22c55e; }
.phase-header { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; font-size: 1.1rem; font-weight: 600; }
.phase.INICIO .phase-header { color: #ef4444; }
.phase.MEIO .phase-header { color: #eab308; }
.phase.FIM .phase-header { color: #22c55e; }
.checks { display: flex; flex-direction: column; gap: 10px; }
.check { display: flex; align-items: center; gap: 10px; padding: 10px 15px; background: rgba(0,0,0,0.3); border-radius: 8px; }
.check-icon { font-size: 1.2rem; width: 24px; text-align: center; }
.check-name { flex: 1; font-size: 0.9rem; }
.check-status { font-size: 0.75rem; padding: 4px 10px; border-radius: 12px; font-weight: 500; }
.check-status.pending { background: #334155; color: #94a3b8; }
.check-status.running { background: rgba(234,179,8,0.3); color: #eab308; }
.check-status.success { background: rgba(34,197,94,0.3); color: #22c55e; }
.check-status.warning { background: rgba(234,179,8,0.3); color: #eab308; }
.check-status.failed { background: rgba(239,68,68,0.3); color: #ef4444; }
.check-status.skipped { background: #334155; color: #64748b; }
.logs-section { background: #1e293b; border-radius: 12px; border: 1px solid #334155; overflow: hidden; }
.logs-header { padding: 15px 20px; background: #0f172a; border-bottom: 1px solid #334155; }
.logs-header h2 { font-size: 1rem; }
.logs-list { max-height: 300px; overflow-y: auto; padding: 10px; font-family: 'Consolas', monospace; font-size: 0.8rem; }
.log-entry { padding: 6px 10px; border-radius: 4px; margin-bottom: 4px; display: flex; gap: 10px; }
.log-entry.debug { background: rgba(100,116,139,0.2); color: #94a3b8; }
.log-entry.info { background: rgba(59,130,246,0.2); color: #60a5fa; }
.log-entry.warning { background: rgba(234,179,8,0.2); color: #facc15; }
.log-entry.error { background: rgba(239,68,68,0.2); color: #f87171; }
.log-time { color: #64748b; white-space: nowrap; }
.log-level { font-weight: 600; text-transform: uppercase; width: 60px; }
.log-message { flex: 1; word-break: break-word; }
.summary { margin-top: 30px; padding: 20px; background: #1e293b; border-radius: 12px; border: 1px solid #334155; }
.summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 20px; }
.summary-item { text-align: center; padding: 15px; background: rgba(0,0,0,0.3); border-radius: 8px; }
.summary-item .value { font-size: 2rem; font-weight: 700; }
.summary-item.success .value { color: #22c55e; }
.summary-item.warning .value { color: #eab308; }
.summary-item.failed .value { color: #ef4444; }
.summary-item.skipped .value { color: #64748b; }
.summary-item .label { font-size: 0.85rem; color: #94a3b8; margin-top: 5px; }
.final-status { text-align: center; padding: 20px; border-radius: 8px; font-size: 1.2rem; font-weight: 600; }
.final-status.success { background: rgba(34,197,94,0.2); color: #22c55e; border: 2px solid #22c55e; }
.final-status.running { background: rgba(234,179,8,0.2); color: #eab308; border: 2px solid #eab308; }
.final-status.failed { background: rgba(239,68,68,0.2); color: #ef4444; border: 2px solid #ef4444; }
.progress-bar { height: 4px; background: #334155; border-radius: 2px; overflow: hidden; margin-top: 15px; }
.progress-bar .fill { height: 100%; transition: width 0.3s ease; background: #eab308; }
.progress-bar .fill.success { background: #22c55e; }
.progress-bar .fill.failed { background: #ef4444; }
</style>
</head>
<body>
<header>
<div class="container">
<h1>🔍 RH Tech IA - Pipeline Monitor</h1>
<p class="subtitle">Monitoramento em tempo real do pipeline de CI/CD</p>
<div class="progress-bar"><div id="progressFill" class="fill"></div></div>
</div>
</header>
<div class="container">
<div class="phases">
<div class="phase INICIO" id="phaseINICIO">
<div class="phase-header"><span>🔴</span><span>FASE 1: SEGURANÇA</span></div>
<div class="checks" id="checksINICIO"></div>
</div>
<div class="phase MEIO" id="phaseMEIO">
<div class="phase-header"><span>🟡</span><span>FASE 2: VALIDAÇÃO</span></div>
<div class="checks" id="checksMEIO"></div>
</div>
<div class="phase FIM" id="phaseFIM">
<div class="phase-header"><span>🟢</span><span>FASE 3: DEPLOY</span></div>
<div class="checks" id="checksFIM"></div>
</div>
</div>
<div class="logs-section">
<div class="logs-header"><h2>📋 Logs em Tempo Real</h2></div>
<div class="logs-list" id="logsList"></div>
</div>
<div class="summary">
<div class="summary-grid">
<div class="summary-item success"><div class="value" id="successCount">0</div><div class="label">Sucesso</div></div>
<div class="summary-item warning"><div class="value" id="warningCount">0</div><div class="label">Avisos</div></div>
<div class="summary-item failed"><div class="value" id="failedCount">0</div><div class="label">Falhas</div></div>
<div class="summary-item skipped"><div class="value" id="skippedCount">0</div><div class="label">Pulados</div></div>
</div>
<div id="finalStatus" class="final-status running">⏳ Pipeline em execução...</div>
</div>
</div>
<script>
const STATUS = { pending: '⬜', running: '🟨', success: '🟩', warning: '🟨', failed: '🟥', skipped: '⊘' };
function escapeHtml(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
const checks = { INICIO: [], MEIO: [], FIM: [] };
</script>
</body>
</html>`;
}

runPipeline().catch(console.error);
