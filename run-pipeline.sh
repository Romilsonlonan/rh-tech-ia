#!/bin/bash

# =============================================================================
# RH Tech IA - Pipeline Visual Runner
# =============================================================================
# Wrapper Bash para o pipeline PHP com suporte a cores e animações
#
# Uso:
#   ./run-pipeline.sh [opções]
#
# Opções:
#   --check=security   Executa apenas checks de segurança
#   --check=all        Executa todos os checks (default)
#   --no-emoji         Desabilita emojis
#   --verbose          Saída detalhada
#   --help             Mostra esta ajuda
# =============================================================================

set -e

# Cores (suporta terminals que não tem cores)
if [ -t 1 ]; then
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[0;33m'
    BLUE='\033[0;34m'
    WHITE='\033[0;37m'
    GRAY='\033[0;90m'
    BOLD='\033[1m'
    NC='\033[0m'
    BG_RED='\033[41m'
    BG_GREEN='\033[42m'
    BG_YELLOW='\033[43m'
    BG_BLUE='\033[44m'
else
    RED=''
    GREEN=''
    YELLOW=''
    BLUE=''
    WHITE=''
    GRAY=''
    BOLD=''
    NC=''
    BG_RED=''
    BG_GREEN=''
    BG_YELLOW=''
    BG_BLUE=''
fi

# Configurações
CHECK_MODE="all"
USE_EMOJI=true
VERBOSE=false
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Parse argumentos
while [[ $# -gt 0 ]]; do
    case $1 in
        --check=*)
            CHECK_MODE="${1#*=}"
            shift
            ;;
        --no-emoji)
            USE_EMOJI=false
            shift
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        --help)
            echo "Uso: $0 [opções]"
            echo ""
            echo "Opções:"
            echo "  --check=security   Executa apenas checks de segurança"
            echo "  --check=all        Executa todos os checks (default)"
            echo "  --no-emoji         Desabilita emojis"
            echo "  --verbose          Saída detalhada"
            echo "  --help             Mostra esta ajuda"
            exit 0
            ;;
        *)
            echo "Opção desconhecida: $1"
            exit 1
            ;;
    esac
done

# Funções de desenho
clear_screen() {
    if [ -t 1 ]; then
        echo -en "\033[2J\033[H"
    fi
}

draw_box() {
    local color="$1"
    local title="$2"
    local width="${3:-60}"
    local fill="${4:- }"

    echo -e "${color}${BOLD}┌${fill}${fill// /$fill}${fill}┐${NC}"
    echo -e "${color}${BOLD}│${NC} ${title} ${fill// /$fill}│${NC}"
    echo -e "${color}${BOLD}└${fill}${fill// /$fill}${fill}┘${NC}"
}

draw_header() {
    clear_screen
    echo ""
    echo -e "${BOLD}${WHITE}════════════════════════════════════════════════════════════════════════════${NC}"
    echo -e "${BOLD}${WHITE}  $( [ "$USE_EMOJI" = true ] && echo '🔍' ) RH Tech IA - Pipeline Visual Runner${NC}"
    echo -e "${BOLD}${WHITE}════════════════════════════════════════════════════════════════════════════${NC}"
    echo ""
}

draw_phase_header() {
    local phase="$1"
    local icon="$2"
    local color="$3"

    echo ""
    echo -e "${color}${BOLD}┌────────────────────────────────────────────────────────────┐${NC}"
    printf "${color}${BOLD}│ ${icon} ${phase}${NC}"
    printf "%*s│\n" $((54 - ${#phase} - 3)) ""
    echo -e "${color}${BOLD}└────────────────────────────────────────────────────────────┘${NC}"
    echo ""
}

draw_check() {
    local num="$1"
    local name="$2"
    local status="$3"
    local duration="$4"
    local output="$5"

    local box icon color text

    case "$status" in
        pending)
            box="⬜"
            icon="  "
            color="$GRAY"
            text="PENDENTE"
            ;;
        running)
            box="🟨"
            icon="⏳"
            color="$YELLOW"
            text="EXECUTANDO..."
            ;;
        success)
            box="🟩"
            icon="✓"
            color="$GREEN"
            text="SUCESSO"
            ;;
        warning)
            box="🟨"
            icon="⚠"
            color="$YELLOW"
            text="AVISO"
            ;;
        failed)
            box="🟥"
            icon="✗"
            color="$RED"
            text="FALHOU"
            ;;
        skipped)
            box="⬜"
            icon="⊘"
            color="$GRAY"
            text="PULADO"
            ;;
    esac

    if [ "$USE_EMOJI" = false ]; then
        box="[ ]"
        case "$status" in
            success) box="[✓]";;
            warning) box="[!]";;
            failed) box="[✗]";;
            running) box="[>]";;
            skipped) box="[-]";;
        esac
    fi

    printf "  ${box} ${color}[%02d] %-20s${NC} " "$num" "$name"

    case "$status" in
        running)
            echo -e "${YELLOW}▓▓▓▓▓░░░░░░░░ 50%${NC}"
            ;;
        success)
            echo -e "${GREEN}[✓] SUCESSO${NC}"
            ;;
        warning)
            echo -e "${YELLOW}[!] AVISO${NC}"
            ;;
        failed)
            echo -e "${RED}[✗] FALHOU${NC}"
            ;;
        skipped)
            echo -e "${GRAY}[-] PULADO${NC}"
            ;;
        *)
            echo -e "${GRAY}[...]${NC}"
            ;;
    esac

    printf "     ${GRAY}├─ ${NC}%s\n" "$(echo "$output" | head -1 | cut -c1-70)"

    if [ "$VERBOSE" = true ] && [ -n "$output" ]; then
        echo "$output" | tail -n +2 | while read -r line; do
            echo "     ${GRAY}│  ${line:0:70}${NC}"
        done
    fi
}

draw_summary() {
    local total="$1"
    local success="$2"
    local warnings="$3"
    local failed="$4"
    local skipped="$5"
    local duration="$6"

    echo ""
    echo -e "${BOLD}${WHITE}════════════════════════════════════════════════════════════════════════════${NC}"
    echo -e "${BOLD}${WHITE}  📊 RESUMO DO PIPELINE${NC}"
    echo -e "${BOLD}${WHITE}════════════════════════════════════════════════════════════════════════════${NC}"
    echo ""
    echo -e "  ┌───────────────────────────────────────────────────────────────────────────┐"
    printf "  │  "
    echo -en "${GREEN}🟩 ${success} Sucesso   ${NC}"
    echo -en "${YELLOW}🟨 ${warnings} Avisos   ${NC}"
    echo -en "${RED}🟥 ${failed} Falhas   ${NC}"
    echo -en "${GRAY}⬜ ${skipped} Pulados   ${NC}"
    echo -en "                                               │\n"
    echo -e "  │  ${WHITE}⏱ Tempo Total: ${duration}s${NC}                                                         │"
    echo -e "  └───────────────────────────────────────────────────────────────────────────┘"
    echo ""

    if [ "$failed" -eq 0 ]; then
        echo -e "${BG_GREEN}${BOLD}${WHITE}  ✅ PIPELINE CONCLUÍDO COM SUCESSO  ${NC}"
    else
        echo -e "${BG_RED}${BOLD}${WHITE}  ❌ PIPELINE FALHOU - CORRIJA OS ERROS ACIMA  ${NC}"
    fi
    echo ""
}

# Simular execução de check
run_check() {
    local name="$1"
    local cmd="$2"
    local block_on_fail="$3"

    # Simular delay baseado no nome do check
    local delay=0.5
    case "$name" in
        "Secret Scan") delay=0.3;;
        "Gitleaks") delay=0.4;;
        "PHPUnit") delay=1.2;;
        "Pint Linter") delay=0.8;;
        "ESLint") delay=0.6;;
        "Docker Build") delay=2.0;;
    esac

    sleep "$delay"
    return 0
}

# Main
main() {
    local start_time=$(date +%s)
    local checks=()
    local phase=""

    # Define checks
    if [ "$CHECK_MODE" = "security" ]; then
        checks=(
            "Secret Scan|INICIO|true"
            "Gitleaks|INICIO|true"
            "Pre-commit Hook|INICIO|true"
        )
    else
        checks=(
            "Secret Scan|INICIO|true"
            "Gitleaks|INICIO|true"
            "Pre-commit Hook|INICIO|true"
            "PHP Syntax|MEIO|true"
            "PHPUnit|MEIO|false"
            "Pint Linter|MEIO|false"
            "ESLint|MEIO|false"
            "TypeScript|MEIO|false"
            "Composer Audit|MEIO|false"
            "NPM Audit|MEIO|false"
            "Docker Build|FIM|false"
        )
    fi

    draw_header

    local total=${#checks[@]}
    local current=0
    local success=0
    local warnings=0
    local failed=0
    local skipped=0

    local prev_phase=""

    for check_spec in "${checks[@]}"; do
        IFS='|' read -r name phase block <<< "$check_spec"

        current=$((current + 1))

        # Desenhar header da fase se mudou
        if [ "$phase" != "$prev_phase" ]; then
            prev_phase="$phase"
            case "$phase" in
                INICIO)
                    draw_phase_header "FASE 1: INÍCIO - SEGURANÇA" "🔴" "$RED"
                    ;;
                MEIO)
                    draw_phase_header "FASE 2: MEIO - VALIDAÇÃO" "🟡" "$YELLOW"
                    ;;
                FIM)
                    draw_phase_header "FASE 3: FIM - DEPLOY" "🟢" "$GREEN"
                    ;;
            esac
        fi

        # Simular check
        draw_check "$current" "$name" "running" "0.0s" "Executando..."

        sleep 0.3

        # Simular resultado (aleatório para demo)
        local rand=$((RANDOM % 100))
        local status output

        if [ "$rand" -lt 70 ]; then
            status="success"
            output="✓ Verificação concluída com sucesso"
            success=$((success + 1))
        elif [ "$rand" -lt 85 ]; then
            status="warning"
            output="⚠ 1 warning encontrado (não crítico)"
            warnings=$((warnings + 1))
        elif [ "$rand" -lt 95 ]; then
            status="skipped"
            output="⊘ Pulado (não aplicável)"
            skipped=$((skipped + 1))
        else
            status="failed"
            output="✗ Falhou: erro de sintaxe ou configuração"
            failed=$((failed + 1))
        fi

        local duration=$(echo "scale=2; $RANDOM / 10000 + 0.2" | bc)
        draw_check "$current" "$name" "$status" "${duration}s" "$output"

        # Se falhou e block_on_fail=true, abortar
        if [ "$status" = "failed" ] && [ "$block" = "true" ]; then
            echo -e "\n  ${RED}✗ Check '$name' falhou. Abortando pipeline.${NC}"
            break
        fi

        echo ""
    done

    # Calcular duração
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))

    draw_summary "$total" "$success" "$warnings" "$failed" "$skipped" "$duration"

    # Exit code
    if [ "$failed" -gt 0 ]; then
        exit 1
    fi
    exit 0
}

main
