# Segurança no Desenvolvimento

## Visão Geral

Este documento descreve as práticas de segurança implementadas no projeto RH Tech IA.

---

## 🔒 Pre-Commit Hook

O hook de pré-commit (`.githooks/pre-commit`) é executado automaticamente antes de cada commit.

### O que é verificado:

| Check | Descrição | Bloqueia? |
|-------|-----------|-----------|
| `.env` files | Arquivos de ambiente | ✅ CRÍTICO |
| AWS Keys | `AKIA...` | ✅ CRÍTICO |
| OpenAI Keys | `sk-...` | ✅ CRÍTICO |
| GitHub Tokens | `ghp_...` | ✅ CRÍTICO |
| GitLab Tokens | `glpat-...` | ✅ CRÍTICO |
| Slack Tokens | `xox...` | ✅ CRÍTICO |
| Private Keys | RSA/EC keys | ✅ CRÍTICO |
| MongoDB Creds | `mongodb+srv://` | ✅ CRÍTICO |
| API Keys | Hardcoded keys | ✅ CRÍTICO |
| Debug Code | `var_dump`, `dd` | ⚠️ AVISO |
| PHP Syntax | Erros de sintaxe | ⚠️ AVISO |
| npm audit | Vulnerabilidades | ✅ SE CRITICAL/HIGH |

### Configuração:

```bash
# Hook está em .githooks/pre-commit
git config core.hooksPath .githooks
```

### Para pular (NÃO RECOMENDADO):

```bash
git commit --no-verify -m "message"
```

---

## 📦 Security Audit

### Pre-commit Audit

Antes de cada commit:
1. Escaneia código staged para secrets
2. Roda `npm audit` (frontend)
3. Roda `composer audit` (backend)
4. Bloqueia se vulnerabilidades critical/high

### CI/CD Security Job

O job `security` roda ANTES dos outros jobs:

```yaml
security:
  name: Security Audit
  needs: none
  steps:
    - npm audit --audit-level=high
    - composer audit
```

Se falhar, TODO o pipeline falha.

---

## 🚫 Arquivos Bloqueados

```
.env
.env.local
.env.production
*.pem
*.key
id_rsa*
```

### Arquivos Permitidos (templates):

```
.env.example     ✅
.env.docker      ✅
.env.testing     ✅
```

---

## 🔑 Padrões Detectados

| Tipo | Padrão | Severidade |
|------|--------|------------|
| AWS Access Key | `AKIA[0-9A-Z]{16}` | CRITICAL |
| OpenAI API Key | `sk-[0-9a-zA-Z]{40,}` | CRITICAL |
| GitHub Token | `ghp_[0-9a-zA-Z]{36}` | CRITICAL |
| GitLab Token | `glpat-[0-9a-zA-Z]{20}` | CRITICAL |
| Slack Token | `xox[baprs]-[0-9a-zA-Z]{10,48}` | CRITICAL |
| Private Key | `-----BEGIN (RSA |EC )?PRIVATE KEY-----` | CRITICAL |
| MongoDB URI | `mongodb+srv://user:pass@` | CRITICAL |
| Generic API Key | `api_key = "..."` (20+ chars) | HIGH |

---

## ⚙️ Configuração de Variáveis

### Frontend (.env)

```env
VITE_API_URL=http://localhost:8000/api
VITE_APP_ENV=development
```

### Backend (.env.docker)

```env
APP_NAME="RH Tech IA"
DB_CONNECTION=pgsql
REDIS_HOST=redis
OPENAI_API_KEY=your-key-here
```

### MinIO (.env.docker)

```env
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_BUCKET=rhtechia
```

---

## 🔐 Melhores Práticas

1. **NUNCA** commite arquivos `.env`
2. Use `.env.example` com valores placeholder
3. Adicione `node_modules/` e `vendor/` ao `.gitignore`
4. Rode `npm audit` e `composer audit` regularmente
5. Mantenha dependências atualizadas
6. Use tokens de acesso com scopes mínimos

---

## 📁 Estrutura de Diretórios

```
.githooks/
├── pre-commit          # Hook de segurança
└── README.md

.github/workflows/
├── ci.yml             # Pipeline CI/CD
└── docker.yml         # Build Docker
```
