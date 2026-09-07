# Conhecimento dos Agentes de IA

## Sistema RH Tech IA - Base de Conhecimento

---

## 🏗️ Arquitetura de Segurança

### Pre-commit Hook (`.githooks/pre-commit`)

O pre-commit hook é a PRIMEIRA linha de defesa contra vazamento de credenciais.

**Fluxo de Execução:**

```
git commit
    ↓
Pre-commit Hook
    ↓
├── 1. Verificar .env files
├── 2. Escanear secrets no código
├── 3. npm audit (frontend)
├── 4. composer audit (backend)
├── 5. Verificar debug code
├── 6. PHP/JS syntax check
    ↓
✅ Passou → Commit permitido
❌ Falhou → Commit bloqueado
```

---

## 🔑 Tipos de Credenciais

### AWS Keys
```
AKIAXXXXXXXXXXXXXXXX
```
**Ação:** BLOQUEAR IMEDIATAMENTE

### OpenAI Keys
```
sk-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
```
**Ação:** BLOQUEAR IMEDIATAMENTE

### Tokens de Acesso
```
ghp_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
glpat-XXXXXXXXXXXXXXXXXXXX
xox[baprs]-XXXXXXXXXXXXXXXXXXXXXXXXXX
```
**Ação:** BLOQUEAR IMEDIATAMENTE

### Private Keys
```
-----BEGIN RSA PRIVATE KEY-----
-----BEGIN EC PRIVATE KEY-----
```
**Ação:** BLOQUEAR IMEDIATAMENTE

---

## 📋 Regras para Agentes

### Ao Processar Commits:

1. **ANTES de aceitar código:**
   - Verificar se há `.env` no staged
   - Escanear por `sk-`, `ghp_`, `AKIA`, etc.
   - Se encontrado → REJEITAR com mensagem clara

2. **Ao fazer merge:**
   - Verificar se pre-commit hook está configurado
   - Garantir que CI/CD tem job de security
   - Alertar se vulnerabilidades forem encontradas

3. **Ao criar código:**
   - NÃO hardcodar credenciais
   - Usar variáveis de ambiente
   - Usar `.env.example` com placeholders

---

## 🛡️ Middlewares de Segurança (Laravel)

### SecurityHeaders.php

Adiciona headers de segurança HTTP:

```php
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Strict-Transport-Security: max-age=31536000
```

### ThrottleApiRequests.php

Rate limiting para API:

```php
Limit::perMinute(60)   // API geral
Limit::perMinute(5)     // Login
Limit::perHour(3)       // Registro
```

---

## 📦 Dependency Scanning

### Frontend (npm)

```bash
npm audit --audit-level=high
```

**Níveis de severidade:**
- `critical` → BLOQUEAR
- `high` → BLOQUEAR
- `moderate` → AVISO
- `low` → IGNORAR

### Backend (composer)

```bash
composer audit
```

**Níveis de severidade:**
- `critical` → BLOQUEAR
- `high` → BLOQUEAR
- `medium` → AVISO
- `low` → IGNORAR

---

## 🚫 Arquivos que NUNCA devem ser commitados

| Arquivo | Reason |
|---------|--------|
| `.env` | Contém segredos reais |
| `*.pem` | Private keys |
| `*.key` | Private keys |
| `id_rsa*` | SSH keys |
| `config/local/*.json` | Credenciais locais |
| `secrets.yaml` | Secrets |

---

## ✅ Arquivos Seguros para Commit

| Arquivo | Reason |
|---------|--------|
| `.env.example` | Template sem valores reais |
| `.env.docker` | Apenas para Docker (sem segredos reais) |
| `.env.testing` | Apenas para testes |
| `*.example` | Templates de configuração |

---

## 🎯 Prompt para Agentes

Ao trabalhar neste projeto, LEMBRE-SE:

1. **NUNCA** sugira hardcodar credenciais
2. **SEMPRE** use `process.env` ou `config()`
3. **VERIFIQUE** se `.env` está no `.gitignore`
4. **ALERTE** se ver credenciais sendo expostas
5. **USE** `npm audit` e `composer audit` regularmente

---

## 📊 Metrics de Segurança

| Métrica | Valor |
|---------|-------|
| Pre-commit hooks | 9 |
| Secrets detectados | 10+ padrões |
| CI/CD security jobs | 1 |
| Taxa de блокировка | 100% para credenciais |
