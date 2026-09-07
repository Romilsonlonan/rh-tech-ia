# ===========================================
# Git Hooks Configuration
# ===========================================

O pre-commit hook está configurado no diretório `.githooks/`.

Para instalar automaticamente, execute:

```bash
git config core.hooksPath .githooks
```

## O que o hook verifica:

1. **Bloqueia arquivos .env** - Não permite commit de arquivos .env (exceto .env.example, .env.docker)

2. **Detecta dados sensíveis:**
   - AWS Access Keys (AKIA...)
   - OpenAI API Keys (sk-...)
   - GitHub/GitLab Tokens
   - Slack Tokens
   - Bearer Tokens
   - Senhas hardcoded
   - Chaves privadas (RSA, EC)
   - Credenciais MongoDB

3. **Verifica código de debug** - Detecta var_dump, dd, dump, console.log

4. **Valida sintaxe PHP** - Verifica syntax errors em arquivos PHP

5. **Valida sintaxe JS/TS** - Verifica syntax errors em arquivos JS/TS

6. **Linting opcional** - Executa Laravel Pint automaticamente

## Pular o hook (não recomendado):

```bash
git commit --no-verify -m "message"
```

## Desinstalar:

```bash
git config core.hooksPath .git/hooks
```
