# Agentes de IA - RH Tech IA

## Visão Geral

Este documento descreve a arquitetura, configuração e operação dos agentes de IA do projeto RH Tech IA. Os agentes são projetados para operar de forma independente, desde a configuração inicial até o monitoramento com OpenTelemetry.

---

## 🏗️ Arquitetura dos Agentes

```
┌─────────────────────────────────────────────────────────────────┐
│                        AGENTE PRINCIPAL                          │
│                    (AI Orchestration Layer)                      │
└─────────────────────────────────────────────────────────────────┘
                              │
        ┌─────────────────────┼─────────────────────┐
        ▼                     ▼                     ▼
┌───────────────┐   ┌───────────────────┐   ┌───────────────┐
│  Agente de    │   │  Agente de        │   │  Agente de    │
│  Recrutamento │   │  Entrevista       │   │  Análise      │
└───────────────┘   └───────────────────┘   └───────────────┘
        │                     │                     │
        └─────────────────────┼─────────────────────┘
                              ▼
                    ┌───────────────────┐
                    │  Vector Store     │
                    │  (Embedding DB)  │
                    └───────────────────┘
                              │
                              ▼
                    ┌───────────────────┐
                    │  MCP Client      │
                    │  (Tool Access)    │
                    └───────────────────┘
                              │
                              ▼
                    ┌───────────────────┐
                    │  OpenTelemetry   │
                    │  (Traces/Metrics) │
                    └───────────────────┘
```

---

## 🔐 Configuração de Secrets

### Variáveis de Ambiente Obrigatórias

O agente deve configurar os seguintes secrets ANTES de iniciar:

```env
# ==========================================
# OPENAI - IA e Embeddings
# ==========================================
OPENAI_API_KEY=sk-...

# ==========================================
# DATABASE - PostgreSQL
# ==========================================
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=rhtechia
DB_USERNAME=postgres
DB_PASSWORD=********

# ==========================================
# REDIS - Cache e Filas
# ==========================================
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# ==========================================
# MINIO - Storage S3-Compatible
# ==========================================
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_BUCKET=rhtechia
AWS_ENDPOINT=http://minio:9000

# ==========================================
# OPENTELEMETRY - Observabilidade
# ==========================================
OTEL_ENABLED=true
OTEL_SERVICE_NAME=rhtechia-ai-agent
OTEL_EXPORTER_OTLP_ENDPOINT=http://localhost:4318
```

### Fluxo de Configuração de Secrets

```
1. Agente inicia
       │
       ▼
2. Verificar .env existe?
   ├── NÃO → Copiar de .env.example
   │         Preencher valores obrigatórios
   │         Solicitar ao usuário via Kilo
   │
   └── SIM → Validar variáveis
                 │
                 ▼
          3. Todos os secrets OK?
              ├── NÃO → Listar faltantes
              │        Solicitar ao usuário
              │
              └── SIM → Inicializar agentes
                            │
                            ▼
                     4. Validar conectividade
                         ├── DB, Redis, MinIO
                         └── OTEL endpoint
```

---

## 🎯 Objetivos dos Agentes

### Agente de Recrutamento

| Atributo | Descrição |
|----------|----------|
| **Papel** | Recrutador Virtual |
| **Objetivo** | Analisar currículos e encontrar candidatos ideais |
| **Ferramentas** | VectorStore (similarity search), MCP (busca vagas) |
| **Entrada** | CV do candidato, requisitos da vaga |
| **Saída** | Score de compatibilidade, ranking de candidatos |

### Agente de Entrevista

| Atributo | Descrição |
|----------|----------|
| **Papel** | Entrevistador Virtual |
| **Objetivo** | Conduzir entrevistas técnicas e comportamentais |
| **Ferramentas** | RAG (histórico entrevistas), TTS/STT, MCP |
| **Entrada** | Perguntas da vaga, respostas do candidato |
| **Saída** | Análise de respostas, score, feedback |

### Agente de Análise

| Atributo | Descrição |
|----------|----------|
| **Papel** | Analista de Dados RH |
| **Objetivo** | Gerar insights e previsões sobre candidatos/vagas |
| **Ferramentas** | ML models, VectorStore, MCP analytics |
| **Entrada** | Dados históricos, métricas de entrevista |
| **Saída** | Relatórios, previsões, recomendações |

---

## 📊 OpenTelemetry - Rastreamento

### Por que Rastreamento?

```
┌────────────────────────────────────────────────────────────────┐
│  SEM TELEMETRY                    COM TELEMETRY               │
├────────────────────────────────────────────────────────────────┤
│  ❌ Latência invisível              ✅ Latência medida         │
│  ❌ Erros ocultos                   ✅ Erros rastreados        │
│  ❌ Performance desconhecida        ✅ Performance visível     │
│  ❌ Depuração difícil               ✅ Depuração precisa       │
└────────────────────────────────────────────────────────────────┘
```

### Span Hierarchy

```
┌─────────────────────────────────────────────────────────────┐
│ Agent Workflow (root span)                                  │
│  ├── init (span)                                            │
│  │    └── validate_secrets (span)                           │
│  │         └── check_db_connection (span)                    │
│  │         └── check_redis_connection (span)                 │
│  │         └── check_otel_connection (span)                  │
│  │                                                              │
│  ├── process_cv (span)                                       │
│  │    ├── embed_cv (span)                                    │
│  │    │    └── openai_embeddings (span)                       │
│  │    ├── search_vector_store (span)                         │
│  │    └── calculate_score (span)                             │
│  │                                                              │
│  ├── conduct_interview (span)                                │
│  │    ├── generate_questions (span)                          │
│  │    ├── process_response (span)                            │
│  │    └── analyze_sentiment (span)                           │
│  │                                                              │
│  └── generate_report (span)                                   │
│       └── format_output (span)                               │
└─────────────────────────────────────────────────────────────┘
```

### Atributos de Span

Cada span deve conter:

```php
$span->setAttribute('agent.name', 'recruitment');
$span->setAttribute('agent.task', 'cv_analysis');
$span->setAttribute('candidate.id', '12345');
$span->setAttribute('vacancy.id', '67890');
$span->setAttribute('embedding.model', 'text-embedding-3-small');
$span->setAttribute('vectorstore.retrieved_docs', 5);
$span->setAttribute('score.final', 0.87);
```

---

## 🔧 Código de Integração OTEL

### Estrutura Base do Agente

```php
<?php

declare(strict_types=1);

namespace App\AI\Agents;

use App\Telemetry\OpenTelemetryService;
use App\AI\VectorStore\VectorStoreClient;
use App\AI\MCP\MCPClient;

class RecruitmentAgent
{
    public function __construct(
        protected OpenTelemetryService $telemetry,
        protected VectorStoreClient $vectorStore,
        protected MCPClient $mcp,
    ) {}

    public function processCandidate(array $cvData, string $vacancyId): array
    {
        $rootSpan = $this->telemetry->startSpan('agent.recruitment.process');

        try {
            $this->telemetry->addEvent($rootSpan, 'secrets_validated');
            $this->telemetry->addEvent($rootSpan, 'candidate_received');

            // Embed CV
            $embeddingSpan = $this->telemetry->startSpan('agent.embed_cv');
            $embedding = $this->createEmbedding($cvData['content']);
            $embeddingSpan?->end();

            // Search similar candidates
            $searchSpan = $this->telemetry->startSpan('agent.search_similar');
            $similar = $this->vectorStore->search($embedding, 5);
            $searchSpan?->setAttribute('results.count', count($similar));
            $searchSpan?->end();

            // Calculate match score
            $scoreSpan = $this->telemetry->startSpan('agent.calculate_score');
            $score = $this->calculateMatchScore($cvData, $vacancyId, $similar);
            $scoreSpan?->setAttribute('score.final', $score);
            $scoreSpan?->end();

            $this->telemetry->addEvent($rootSpan, 'process_complete');

            return [
                'candidate_id' => $cvData['id'],
                'vacancy_id' => $vacancyId,
                'score' => $score,
                'similar_candidates' => $similar,
            ];

        } catch (\Throwable $e) {
            $this->telemetry->recordException($e, $rootSpan);
            throw $e;
        } finally {
            $rootSpan?->end();
        }
    }

    protected function createEmbedding(string $text): array
    {
        $span = $this->telemetry->startSpan('openai.embeddings');

        try {
            $response = $this->mcp->call('embeddings', [
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);

            $embedding = $response['data'][0]['embedding'];

            $span?->setAttribute('embedding.dimensions', count($embedding));
            $span?->setAttribute('embedding.model', 'text-embedding-3-small');

            return $embedding;
        } finally {
            $span?->end();
        }
    }

    protected function calculateMatchScore(
        array $cvData,
        string $vacancyId,
        array $similar
    ): float {
        $span = $this->telemetry->startSpan('agent.calculate_match');

        try {
            // Scoring logic...
            $score = 0.85;

            $span?->setAttribute('score.components', json_encode([
                'skills_match' => 0.9,
                'experience_match' => 0.8,
                'culture_match' => 0.85,
            ]));

            return $score;
        } finally {
            $span?->end();
        }
    }
}
```

---

## 🚀 Fluxo de Execução Independente

### Passo a Passo

```
┌─────────────────────────────────────────────────────────────────┐
│                         FASE 1: BOOTSTRAP                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. Agente inicia                                                │
│         │                                                         │
│         ▼                                                         │
│  2. Verificar ambiente                                           │
│     ├── PHP >= 8.2                                               │
│     ├── Composer installed                                       │
│     ├── .env configurado                                         │
│     └── Docker services rodando                                 │
│         │                                                         │
│         ▼                                                         │
│  3. Validar secrets obrigatórios                                │
│     ├── OPENAI_API_KEY                                           │
│     ├── DB credentials                                           │
│     └── OTEL endpoint                                            │
│         │                                                         │
│         ▼                                                         │
│  4. Inicializar serviços                                         │
│     ├── OpenTelemetryService                                     │
│     ├── VectorStore connection                                   │
│     └── MCP Client                                               │
│         │                                                         │
│         ▼                                                         │
│  5. Health check                                                 │
│     └── Registrar span "agent.initialized"                       │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                         FASE 2: OPERAÇÃO                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Para cada tarefa recebida:                                     │
│                                                                  │
│  1. Receber task via Kilo/Agent Manager                         │
│         │                                                         │
│         ▼                                                         │
│  2. Criar root span com task_id                                 │
│     $span = $telemetry->startSpan("agent.task.{$taskType}")    │
│         │                                                         │
│         ▼                                                         │
│  3. Executar sub-spans para cada operação                       │
│     ├── HTTP calls                                               │
│     ├── DB queries                                               │
│     ├── Vector store ops                                         │
│     └── AI/ML inference                                          │
│         │                                                         │
│         ▼                                                         │
│  4. Recording exceptions                                        │
│     $telemetry->recordException($e, $span)                     │
│         │                                                         │
│         ▼                                                         │
│  5. Finalizar span                                              │
│     $span->setStatus(TraceStatus::OK)                           │
│     $span->end()                                                 │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                         FASE 3: SHUTDOWN                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. Finalizar spans abertos                                     │
│  2. Enviar traces pendentes                                     │
│  3. Fechar conexões (DB, Redis, VectorStore)                    │
│  4. Log "agent.shutdown_complete"                              │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📁 Estrutura de Diretórios dos Agentes

```
backend/app/AI/
├── Agents/
│   ├── BaseAgent.php              # Classe base com OTEL
│   ├── RecruitmentAgent.php       # Agente de recrutamento
│   ├── InterviewAgent.php         # Agente de entrevista
│   └── AnalysisAgent.php         # Agente de análise
│
├── KnowledgeBase/
│   ├── agent-context.md          # Contexto para agentes
│   └── security-context.md       # Regras de segurança
│
├── MCP/
│   ├── MCPClient.php             # Client MCP
│   └── Servers/
│       ├── recruitment.server.php
│       └── analytics.server.php
│
├── RAG/
│   ├── RagPipeline.php           # Pipeline RAG
│   └── ChunkingStrategy.php      # Estratégias de chunking
│
├── VectorStore/
│   ├── VectorStoreClient.php     # Cliente vector store
│   └── EmbeddingService.php      # Serviço de embeddings
│
└── Telemetry/
    ├── OpenTelemetryService.php  # Serviço OTEL
    ├── SpanWrapper.php          # Wrapper de spans
    └── TelemetryConfig.php      # Configuração
```

---

## 🛡️ Regras de Segurança para Agentes

### ✅ FAZER

1. Usar variáveis de ambiente para todos os secrets
2. Registrar spans para todas as operações
3. Validar inputs antes de processar
4. Tratar exceções e registrar no OTEL
5. Logar apenas dados não-sensíveis

### ❌ NÃO FAZER

1. Hardcodar credenciais ou API keys
2. Commitar arquivos .env
3. Expor secrets em logs ou spans
4. Fazer commits diretos na main
5. Ignorar warnings do pre-commit hook

---

## 📊 Métricas de Monitoramento

### Key Metrics

| Métrica | Descrição | Alerta |
|---------|-----------|--------|
| `agent.tasks.total` | Total de tarefas processadas | - |
| `agent.tasks.duration` | Tempo médio por tarefa | > 30s |
| `agent.errors.total` | Total de erros | > 1% |
| `vectorstore.queries` | Queries no vector store | - |
| `mcp.calls.success` | Chamadas MCP bem-sucedidas | < 95% |
| `embedding.latency` | Latência de embeddings | > 500ms |

### Dashboard URLs

- **Jaeger**: http://localhost:16686
- **Prometheus**: http://localhost:9090
- **Grafana**: http://localhost:3000

---

## 🔗 Referências

- [OpenTelemetry PHP SDK](https://opentelemetry.io/docs/instrumentation/php/)
- [Laravel OpenTelemetry](https://github.com/open-telemetry/opentelemetry-php-contrib)
- [Vector Store Architecture](./docs/guides/vector-store.md)
- [MCP Protocol](./docs/guides/mcp.md)
- [Security Guidelines](./docs/guides/security.md)
