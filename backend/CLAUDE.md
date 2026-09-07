# RH Tech IA - Agente Claude

## Visão Geral

Este é um projeto Laravel 13 com agentes de IA autônomos para processos de RH. O sistema utiliza OpenTelemetry para observabilidade completa.

---

## 🏗️ Arquitetura do Sistema

```
Frontend (React + TypeScript + Tailwind v4)
                │
                ▼
    ┌───────────────────────┐
    │   API Gateway/Laravel │
    │   Auth: Sanctum       │
    │   Security Middleware │
    └───────────────────────┘
                │
    ┌───────────┼───────────┐
    ▼           ▼           ▼
┌────────┐ ┌────────┐ ┌──────────┐
│  AI    │ │Vector  │ │   MCP    │
│ Agents │ │Store   │ │  Client  │
└────────┘ └────────┘ └──────────┘
    │           │           │
    └───────────┼───────────┘
                ▼
        ┌─────────────┐
        │ OpenTelemetry│
        │  (Traces)   │
        └─────────────┘
```

---

## 🔑 Configuração de Secrets

### Variáveis Obrigatórias

```env
# OPENAI
OPENAI_API_KEY=sk-...

# DATABASE
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=rhtechia
DB_USERNAME=postgres
DB_PASSWORD=********

# REDIS
REDIS_HOST=redis
REDIS_PORT=6379

# S3/MINIO
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_BUCKET=rhtechia
AWS_ENDPOINT=http://minio:9000

# OPENTELEMETRY
OTEL_ENABLED=true
OTEL_SERVICE_NAME=rhtechia-backend
OTEL_EXPORTER_OTLP_ENDPOINT=http://localhost:4318
```

---

## 🤖 Agentes Disponíveis

### RecruitmentAgent

```php
use App\AI\Agents\RecruitmentAgent;

$agent = app(RecruitmentAgent::class);
$result = $agent->processCandidate($cvData, $vacancyId);
```

**Responsabilidades:**
- Análise de currículos
- Matching de candidatos com vagas
- Embedding e busca vetorial

### InterviewAgent

```php
use App\AI\Agents\InterviewAgent;

$agent = app(InterviewAgent::class);
$result = $agent->conductInterview($candidateId, $vacancyId);
```

**Responsabilidades:**
- Geração de perguntas
- Análise de respostas
- Avaliação de soft skills

### AnalysisAgent

```php
use App\AI\Agents\AnalysisAgent;

$agent = app(AnalysisAgent::class);
$report = $agent->generateInsights($dateRange);
```

**Responsabilidades:**
- Métricas de recrutamento
- Previsões de turnover
- Análise de tendências

---

## 📊 OpenTelemetry

### Estrutura de Spans

```php
$telemetry = app(\App\Telemetry\OpenTelemetryService::class);

// Span principal do agente
$rootSpan = $telemetry->startSpan('agent.recruitment.process');

// Sub-spans
$span = $telemetry->startSpan('agent.search_vectorstore');
$span?->setAttribute('query.count', 5);
$span?->end();

// Registrar exceção
$telemetry->recordException($e, $rootSpan);

$rootSpan?->end();
```

### Atributos Importantes

```php
$span->setAttribute('agent.name', 'recruitment');
$span->setAttribute('agent.task', 'cv_analysis');
$span->setAttribute('candidate.id', '123');
$span->setAttribute('embedding.model', 'text-embedding-3-small');
$span->setAttribute('score.final', 0.87);
```

### Endpoints de Observabilidade

| Serviço | URL |
|---------|-----|
| Jaeger UI | http://localhost:16686 |
| Prometheus | http://localhost:9090 |
| Grafana | http://localhost:3000 |

---

## 🛡️ Regras de Segurança

### ✅ Obrigatório

1. Todos os secrets via variáveis de ambiente
2. Validação de inputs com `Validator`
3. Sanitização de dados antes de armazenar
4. Logging via `Log::channel('daily')`

### ❌ Proibido

1. Credenciais hardcoded
2. `.env` em commits
3. Secrets em logs ou spans
4. SQL queries sem prepared statements

---

## 📁 Pastas Importantes

```
backend/
├── app/AI/Agents/        # Agentes de IA
├── app/AI/VectorStore/   # Vector store
├── app/AI/MCP/           # MCP client
├── app/Telemetry/        # OpenTelemetry
├── app/Http/Controllers/Api/V1/  # API controllers
├── app/Http/Middleware/  # Security headers
├── docs/guides/          # Guias técnicos
└── tests/               # Testes
```

---

## 🚀 Comandos Úteis

```bash
# Instalar dependências
composer install

# Gerar app key
php artisan key:generate

# Rodar migrations
php artisan migrate

# Iniciar servidor
php artisan serve

# Rodar testes
php artisan test

# Verificar código
./vendor/bin/pint
```

---

## 📚 Guias

- [Observability](./docs/guides/observability.md)
- [Security](./docs/guides/security.md)
- [Getting Started](./docs/guides/getting-started.md)
- [API Reference](./docs/api/index.md)
