#!/usr/bin/env python3
"""
RH Tech IA - Visual Pipeline Runner (TUI Advanced)
==================================================

Pipeline visual interativo usando curses/ncurses.
Similar ao Jenkins Blue Ocean ou GitHub Actions.

Uso:
    python pipeline_tui.py [--check=security|all] [--demo]

Dependências:
    - python3 (padrão na maioria dos sistemas)
    - curses (padrão no Linux/macOS)

Nota: No Windows, usa colorama ao invés de curses.
"""

import argparse
import curses
import sys
import time
import os
import subprocess
from dataclasses import dataclass, field
from enum import Enum
from typing import Optional
from pathlib import Path

try:
    import colorama
    colorama.init()
    WINDOWS = True
except ImportError:
    WINDOWS = False


class CheckStatus(Enum):
    PENDING = ("⬜", "PENDING", 8)
    RUNNING = ("🟨", "RUNNING", 11)
    SUCCESS = ("🟩", "SUCCESS", 10)
    WARNING = ("🟨", "WARNING", 11)
    FAILED = ("🟥", "FAILED", 9)
    SKIPPED = ("⬜", "SKIPPED", 8)

    def __init__(self, icon, label, color_idx):
        self.icon = icon
        self.label = label
        self.color_idx = color_idx


class Phase(Enum):
    INICIO = ("🔴", "FASE 1: INÍCIO - SEGURANÇA", 1)  # Red
    MEIO = ("🟡", "FASE 2: MEIO - VALIDAÇÃO", 3)      # Yellow
    FIM = ("🟢", "FASE 3: FIM - DEPLOY", 2)            # Green

    def __init__(self, icon, label, color_idx):
        self.icon = icon
        self.label = label
        self.color_idx = color_idx


@dataclass
class Check:
    name: str
    phase: Phase
    command: str
    block_on_fail: bool = False
    status: CheckStatus = CheckStatus.PENDING
    output: str = ""
    duration: float = 0.0


# ANSI Colors (for terminal output)
class Colors:
    RED = "\033[91m"
    GREEN = "\033[92m"
    YELLOW = "\033[93m"
    BLUE = "\033[94m"
    MAGENTA = "\033[95m"
    CYAN = "\033[96m"
    WHITE = "\033[97m"
    GRAY = "\033[90m"
    BOLD = "\033[1m"
    DIM = "\033[2m"
    RESET = "\033[0m"

    @classmethod
    def rgb(cls, r, g, b):
        return f"\033[38;2;{r};{g};{b}m"

    BG_RED = "\033[41m"
    BG_GREEN = "\033[42m"
    BG_YELLOW = "\033[43m"
    BG_BLUE = "\033[44m"


class VisualPipeline:
    """Visual Pipeline Runner with real-time updates."""

    def __init__(self, check_mode: str = "all", demo: bool = False):
        self.check_mode = check_mode
        self.demo = demo
        self.checks: list[Check] = []
        self.start_time = time.time()
        self.current_check_idx = -1
        self.init_checks()

    def init_checks(self):
        """Initialize the list of checks based on mode."""
        security_checks = [
            Check("Secret Scan", Phase.INICIO, "grep -rE '(sk-|AKIA|ghp_)' . 2>/dev/null | head -5", block_on_fail=True),
            Check("Gitleaks", Phase.INICIO, "command -v gitleaks &>/dev/null && gitleaks detect --no-color 2>&1 || echo 'SKIP'", block_on_fail=True),
            Check("Pre-commit Hook", Phase.INICIO, "[ -f .githooks/pre-commit ] && .githooks/pre-commit 2>&1 || echo 'SKIP'", block_on_fail=True),
        ]

        validation_checks = [
            Check("PHP Syntax", Phase.MEIO, "find backend/app -name '*.php' -exec php -l {} \\; 2>&1 | grep -v 'No syntax' | head -10", block_on_fail=True),
            Check("PHPUnit", Phase.MEIO, "cd backend && [ -f phpunit.xml ] && ./vendor/bin/phpunit --no-coverage 2>&1 | tail -20 || echo 'SKIP'", block_on_fail=False),
            Check("Pint Linter", Phase.MEIO, "cd backend && [ -f vendor/bin/pint ] && ./vendor/bin/pint --test 2>&1 | tail -10 || echo 'SKIP'", block_on_fail=False),
            Check("ESLint", Phase.MEIO, "cd frontend && [ -f eslint.config.js ] && npm run lint 2>&1 | tail -15 || echo 'SKIP'", block_on_fail=False),
            Check("TypeScript", Phase.MEIO, "cd frontend && [ -f tsconfig.json ] && npm run typecheck 2>&1 | tail -15 || echo 'SKIP'", block_on_fail=False),
            Check("Composer Audit", Phase.MEIO, "cd backend && composer audit --no-interaction 2>&1 | tail -20 || echo 'SKIP'", block_on_fail=False),
            Check("NPM Audit", Phase.MEIO, "cd frontend && npm audit --audit-level=high 2>&1 | tail -20 || echo 'SKIP'", block_on_fail=False),
            Check("Docker Build", Phase.FIM, "[ -f docker-compose.yml ] && docker compose build --no-cache 2>&1 | tail -10 || echo 'SKIP'", block_on_fail=False),
        ]

        if self.check_mode == "security":
            self.checks = security_checks
        else:
            self.checks = security_checks + validation_checks

    def run_check(self, check: Check) -> tuple[CheckStatus, str]:
        """Execute a single check and return status and output."""
        start = time.time()

        try:
            result = subprocess.run(
                check.command,
                shell=True,
                capture_output=True,
                text=True,
                timeout=120
            )
            output = result.stdout + result.stderr
            duration = time.time() - start
            check.duration = duration

            if "SKIP" in output:
                return CheckStatus.SKIPPED, "Pulado - não aplicável"

            if result.returncode != 0:
                if check.block_on_fail:
                    return CheckStatus.FAILED, output[:200]
                else:
                    return CheckStatus.WARNING, output[:200]

            if any(x in output.lower() for x in ["warning", "warn", "deprecated"]):
                return CheckStatus.WARNING, output[:200]

            return CheckStatus.SUCCESS, output[:200] if output else "Verificação concluída"

        except subprocess.TimeoutExpired:
            return CheckStatus.FAILED, "Timeout - verificação demorou muito"
        except Exception as e:
            return CheckStatus.FAILED, str(e)

    def print_header(self):
        """Print the pipeline header."""
        print()
        print(f"{Colors.BOLD}{Colors.WHITE}{'=' * 90}{Colors.RESET}")
        print(f"{Colors.BOLD}{Colors.WHITE}  🔍 RH Tech IA - Visual Pipeline Runner{Colors.RESET}")
        print(f"{Colors.BOLD}{Colors.WHITE}{'=' * 90}{Colors.RESET}")
        print()

    def print_phase_header(self, phase: Phase):
        """Print phase header."""
        color_map = {
            Phase.INICIO: Colors.rgb(220, 38, 38),
            Phase.MEIO: Colors.rgb(245, 158, 11),
            Phase.FIM: Colors.rgb(16, 185, 129),
        }
        color = color_map.get(phase, Colors.WHITE)

        print()
        print(f"{color}{Colors.BOLD}┌{'─' * 60}┐{Colors.RESET}")
        print(f"{color}{Colors.BOLD}│ {phase.icon} {phase.label}{' ' * (54 - len(phase.label))}│{Colors.RESET}")
        print(f"{color}{Colors.BOLD}└{'─' * 60}┘{Colors.RESET}")
        print()

    def print_check(self, idx: int, check: Check):
        """Print a single check with status."""
        status_colors = {
            CheckStatus.PENDING: Colors.GRAY,
            CheckStatus.RUNNING: Colors.YELLOW,
            CheckStatus.SUCCESS: Colors.GREEN,
            CheckStatus.WARNING: Colors.YELLOW,
            CheckStatus.FAILED: Colors.RED,
            CheckStatus.SKIPPED: Colors.GRAY,
        }
        color = status_colors.get(check.status, Colors.WHITE)

        box = check.status.icon
        num = f"[{idx + 1:02d}]"

        print(f"  {box} {color}{Colors.BOLD}{num}{Colors.RESET} {Colors.WHITE}{check.name:<22}{Colors.RESET}", end="")

        if check.status == CheckStatus.RUNNING:
            print(f" {Colors.YELLOW}▓▓▓▓▓░░░░░░░░ 50%{Colors.RESET}")
        elif check.status == CheckStatus.SUCCESS:
            print(f" {Colors.GREEN}[✓] SUCESSO{Colors.RESET}")
        elif check.status == CheckStatus.WARNING:
            print(f" {Colors.YELLOW}[!] AVISO{Colors.RESET}")
        elif check.status == CheckStatus.FAILED:
            print(f" {Colors.RED}[✗] FALHOU{Colors.RESET}")
        elif check.status == CheckStatus.SKIPPED:
            print(f" {Colors.GRAY}[-] PULADO{Colors.RESET}")
        else:
            print()

        if check.output:
            lines = check.output.split('\n')[:3]
            for line in lines:
                if line.strip():
                    print(f"     {Colors.GRAY}├─ {line[:75]}{Colors.RESET}")

        if check.duration > 0:
            print(f"     {Colors.DIM}└─ {check.duration:.2f}s{Colors.RESET}")

    def print_summary(self):
        """Print the final summary."""
        success = sum(1 for c in self.checks if c.status == CheckStatus.SUCCESS)
        warnings = sum(1 for c in self.checks if c.status == CheckStatus.WARNING)
        failed = sum(1 for c in self.checks if c.status == CheckStatus.FAILED)
        skipped = sum(1 for c in self.checks if c.status == CheckStatus.SKIPPED)
        total = len(self.checks)
        duration = time.time() - self.start_time

        print()
        print(f"{Colors.BOLD}{Colors.WHITE}{'=' * 90}{Colors.RESET}")
        print(f"{Colors.BOLD}{Colors.WHITE}  📊 RESUMO DO PIPELINE{Colors.RESET}")
        print(f"{Colors.BOLD}{Colors.WHITE}{'=' * 90}{Colors.RESET}")
        print()
        print(f"  ┌{'─' * 80}┐")
        print(f"  │  {Colors.GREEN}🟩 {success} Sucesso   {Colors.RESET}", end="")
        print(f"{Colors.YELLOW}🟨 {warnings} Avisos   {Colors.RESET}", end="")
        print(f"{Colors.RED}🟥 {failed} Falhas   {Colors.RESET}", end="")
        print(f"{Colors.GRAY}⬜ {skipped} Pulados   {Colors.RESET}")
        print(f"  │  {Colors.WHITE}⏱ Tempo Total: {duration:.1f}s{Colors.RESET}")
        print(f"  └{'─' * 80}┘")
        print()

        if failed > 0:
            print(f"{Colors.BG_RED}{Colors.BOLD}{Colors.WHITE}  ❌ PIPELINE FALHOU - CORRIJA OS ERROS ACIMA  {Colors.RESET}")
        else:
            print(f"{Colors.BG_GREEN}{Colors.BOLD}{Colors.WHITE}  ✅ PIPELINE CONCLUÍDO COM SUCESSO  {Colors.RESET}")
        print()

    def run(self):
        """Run the complete pipeline."""
        self.print_header()

        current_phase = None
        for idx, check in enumerate(self.checks):
            # Print phase header if changed
            if check.phase != current_phase:
                current_phase = check.phase
                self.print_phase_header(current_phase)

            # Set running status
            check.status = CheckStatus.RUNNING
            self.print_check(idx, check)

            # Run the actual check
            check.status, check.output = self.run_check(check)
            self.print_check(idx, check)
            print()

            # If blocked and failed, abort
            if check.status == CheckStatus.FAILED and check.block_on_fail:
                print(f"{Colors.RED}✗ Check '{check.name}' falhou. Abortando pipeline.{Colors.RESET}")
                break

        self.print_summary()

        # Return exit code
        return 1 if any(c.status == CheckStatus.FAILED for c in self.checks) else 0


def main():
    parser = argparse.ArgumentParser(description="RH Tech IA - Visual Pipeline Runner")
    parser.add_argument("--check", choices=["security", "all"], default="all",
                        help="Tipo de checks a executar")
    parser.add_argument("--demo", action="store_true",
                        help="Modo demo com valores simulados")
    args = parser.parse_args()

    pipeline = VisualPipeline(check_mode=args.check, demo=args.demo)
    exit_code = pipeline.run()
    sys.exit(exit_code)


if __name__ == "__main__":
    main()
