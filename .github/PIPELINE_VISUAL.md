# Pipeline CI/CD Visual

## Visão Geral do Fluxo

Este documento apresenta o pipeline CI/CD do RH Tech IA em um formato visual com indicadores de status.

---

## 🟥 FASE 1: INÍCIO (Pré-Processamento)

```
┌─────────────────────────────────────────────────────────────────────┐
│                         SEGURANÇA                                   │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   ┌──────────────┐    ┌──────────────┐    ┌──────────────┐        │
│   │              │    │              │    │              │        │
│   │   SECRET     │───▶│   SECRET     │───▶│   SECRET     │        │
│   │   SCAN       │    │   SCAN       │    │   SCAN       │        │
│   │              │    │              │    │              │        │
│   │  (grep)      │    │  (gitleaks)  │    │ (pre-commit) │        │
│   │              │    │              │    │              │        │
│   └──────┬───────┘    └──────┬───────┘    └──────┬───────┘        │
│          │                   │                   │                 │
│          │    🔴 BLOQUEAR SE ENCONTRAR          │                 │
│          │    - AKIA...                          │                 │
│          │    - sk-...                           │                 │
│          │    - ghp_...                          │                 │
│          │    - PRIVATE KEY                      │                 │
│          │                                       │                 │
│          └───────────────────────────────────────┘                 │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### Etapas de Segurança (INÍCIO)

| Etapa | Ferramenta | Status | Ação |
|-------|------------|--------|------|
| Secret Scan | `grep` patterns | 🔴 Crítico | Bloquear commit |
| Gitleaks | gitleaks-detect | 🔴 Crítico | Bloquear commit |
| Pre-commit Hook | Custom hooks | 🔴 Crítico | Bloquear commit |

---

## 🟨 FASE 2: MEIO (Validação e Testes)

```
┌─────────────────────────────────────────────────────────────────────┐
│                      BACKEND (Laravel)                               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   ┌──────────────┐    ┌──────────────┐    ┌──────────────┐          │
│   │              │    │              │    │              │          │
│   │   COMPOSER   │───▶│    PINT     │───▶│   PHPUNIT   │          │
│   │   INSTALL    │    │   (LINTER)  │    │   (TESTS)   │          │
│   │              │    │              │    │              │          │
│   │  vendor/     │    │  --test     │    │  --no-cov   │          │
│   │              │    │              │    │              │          │
│   └──────┬───────┘    └──────┬───────┘    └──────┬───────┘          │
│          │                   │                   │                   │
│          │                   │                   │                   │
│          ▼                   ▼                   ▼                   │
│   ┌──────────────┐    ┌──────────────┐    ┌──────────────┐          │
│   │              │    │              │    │              │          │
│   │  COMPOSER    │    │   PHPStan   │    │  MIGRATE    │          │
│   │   AUDIT      │    │  (STATIC)   │    │   (DB)      │          │
│   │              │    │              │    │              │          │
│   │ --no-inter   │    │  analyse    │    │  --force    │          │
│   │              │    │              │    │              │          │
│   └──────┬───────┘    └──────┬───────┘    └──────────────┘          │
│          │                   │                                       │
│          │    🟡 AVISO SE ENCONTRAR                                │
│          │    - high/critical vulnerabilities                       │
│          │    - code smells                                         │
│          │                                                           │
│          │                                                           │
│          └───────────────────────────────────────┘                   │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                      FRONTEND (React)                                │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   ┌──────────────┐    ┌──────────────┐    ┌──────────────┐          │
│   │              │    │              │    │              │          │
│   │    NPM      │───▶│   ESLINT    │───▶│  TYPESCRIPT  │          │
│   │   INSTALL   │    │   (LINT)    │    │   (CHECK)    │          │
│   │              │    │              │    │              │          │
│   │   npm ci    │    │  --max-warn │    │  --noEmit    │          │
│   │              │    │    0       │    │              │          │
│   └──────┬───────┘    └──────┬───────┘    └──────┬───────┘          │
│          │                   │                   │                   │
│          │                   │                   │                   │
│          ▼                   ▼                   ▼                   │
│   ┌──────────────┐    ┌──────────────┐    ┌──────────────┐          │
│   │              │    │              │    │              │          │
│   │   NPM       │    │   VITE      │    │   BUILD     │          │
│   │   AUDIT     │    │   (BUILD)   │    │  PRODUCTION │          │
│   │              │    │              │    │              │          │
│   │ --audit-    │    │   build     │    │  dist/      │          │
│   │   level=high│    │              │    │              │          │
│   └─────────────┘    └──────────────┘    └──────────────┘          │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### Etapas de Validação (MEIO)

| Etapa | Ferramenta | Status | Ação |
|-------|------------|--------|------|
| Dependencies | `composer` / `npm` | 🟡 Aviso | Deprecation warnings |
| Security Audit | `composer audit` / `npm audit` | 🟡 Aviso | Fail on high/critical |
| Lint | Pint / ESLint | 🟡 Aviso | Fail on errors |
| Static Analysis | PHPStan | 🟡 Aviso | Fail on errors |
| Tests | PHPUnit / Vitest | 🟡 Aviso | Fail if < 80% |
| Type Check | tsc | 🟡 Aviso | Fail on errors |
| Build | vite build | 🟡 Aviso | Fail on errors |

---

## 🟩 FASE 3: FIM (Deploy)

```
┌─────────────────────────────────────────────────────────────────────┐
│                         DEPLOY                                       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   ┌──────────────┐    ┌──────────────┐    ┌──────────────┐          │
│   │              │    │              │    │              │          │
│   │    BUILD    │───▶│   PUSH TO   │───▶│   DEPLOY    │          │
│   │   IMAGES    │    │  REGISTRY   │    │   SERVER    │          │
│   │              │    │              │    │              │          │
│   │  docker/     │    │  ghcr.io/   │    │  ssh-action │          │
│   │              │    │              │    │              │          │
│   └──────┬───────┘    └──────┬───────┘    └──────┬───────┘          │
│          │                   │                   │                   │
│          │                   │                   │                   │
│          ▼                   ▼                   ▼                   │
│   ┌──────────────┐    ┌──────────────┐    ┌──────────────┐          │
│   │              │    │              │    │              │          │
│   │   HEALTH    │    │   SLACK     │    │  NOTIFY    │          │
│   │   CHECK     │    │   NOTIFY    │    │  COMPLETE  │          │
│   │              │    │              │    │              │          │
│   │  /health    │    │  #deploys   │    │  ✅ SUCESSO│          │
│   │              │    │              │    │              │          │
│   └──────────────┘    └──────────────┘    └──────────────┘          │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### Etapas de Deploy (FIM)

| Etapa | Ação | Status |
|-------|------|--------|
| Build Docker | Build images | 🟢 Sucesso |
| Push Registry | Push to ghcr.io | 🟢 Sucesso |
| Deploy Server | SSH & deploy | 🟢 Sucesso |
| Health Check | Verify /health | 🟢 Sucesso |
| Notify | Slack notification | 🟢 Sucesso |

---

## 📊 Pipeline Completo Visual

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                              │
│   ┌─────────────────────────────────────────────────────────────────────┐   │
│   │                      🔴 INÍCIO - SEGURANÇA                           │   │
│   │                                                                      │   │
│   │   [SECRET SCAN] ──▶ [GITLEAKS] ──▶ [PRE-COMMIT HOOKS]              │   │
│   │                                                                      │   │
│   │   Se FALHAR ──▶ ❌ BLOQUEAR COMMIT                                   │   │
│   └─────────────────────────────────────────────────────────────────────┘   │
│                                    │                                       │
│                                    ▼                                       │
│   ┌─────────────────────────────────────────────────────────────────────┐   │
│   │                      🟡 MEIO - VALIDAÇÃO                            │   │
│   │                                                                      │   │
│   │   ┌─────────────────────────┐    ┌─────────────────────────┐        │   │
│   │   │      BACKEND           │    │       FRONTEND          │        │   │
│   │   │                         │    │                         │        │   │
│   │   │  [COMPOSER] ──▶ [PINT] │    │  [NPM] ──▶ [ESLINT]    │        │   │
│   │   │       │                │    │      │                 │        │   │
│   │   │       ▼                │    │      ▼                 │        │   │
│   │   │  [AUDIT] ──▶ [PHPUNIT] │    │ [TSC] ──▶ [VITEST]     │        │   │
│   │   │       │                │    │      │                 │        │   │
│   │   │       ▼                │    │      ▼                 │        │   │
│   │   │   [MIGRATE]            │    │   [BUILD]              │        │   │
│   │   │                         │    │                         │        │   │
│   │   └─────────────────────────┘    └─────────────────────────┘        │   │
│   │                                                                      │   │
│   │   Se AVISO ──▶ ⚠️ REPORTAR MAS CONTINUAR                           │   │
│   │   Se ERRO ──▶ ❌ FALHAR BUILD                                       │   │
│   └─────────────────────────────────────────────────────────────────────┘   │
│                                    │                                       │
│                                    ▼                                       │
│   ┌─────────────────────────────────────────────────────────────────────┐   │
│   │                      🟢 FIM - DEPLOY                                │   │
│   │                                                                      │   │
│   │   [BUILD DOCKER] ──▶ [PUSH REGISTRY] ──▶ [DEPLOY SERVER]          │   │
│   │          │                  │                  │                    │   │
│   │          ▼                  ▼                  ▼                    │   │
│   │   [HEALTH CHECK] ──▶ [SLACK NOTIFY] ──▶ [✅ DONE]                 │   │
│   │                                                                      │   │
│   │   Se SUCESSO ──▶ 🚀 APLICAÇÃO NO AR                                 │   │
│   │   Se FALHAR ──▶ ❌ ROLLBACK AUTOMÁTICO                              │   │
│   └─────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 🎨 Legenda de Cores

| Cor | Fase | Significado | Ação |
|-----|------|-------------|------|
| 🔴 Vermelho | INÍCIO | Falha crítica | **BLOQUEAR** |
| 🟡 Amarelo | MEIO | Aviso ou falha | **AVISAR / REJEITAR** |
| 🟢 Verde | FIM | Sucesso | **APROVAR / CONTINUAR** |

---

## 📋 Status do Pipeline

| Job | Status | Tempo |
|-----|--------|-------|
| Security Audit | 🟢 Pass | ~30s |
| Backend Tests | 🟢 Pass | ~2min |
| Frontend Tests | 🟢 Pass | ~1min |
| Deploy | 🟢 Pass | ~3min |
| **Total** | 🟢 **Sucesso** | **~6min** |

---

## 🔗 Links Úteis

- [GitHub Actions](https://github.com/Romilsonlonan/rh-tech-ia/actions)
- [SonarCloud](https://sonarcloud.io) (se configurado)
- [Snyk](https://snyk.io) (se configurado)
