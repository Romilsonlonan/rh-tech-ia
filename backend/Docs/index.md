# RH Tech IA - Documentation

## Visão Geral
Sistema de RH com inteligência artificial, agentes MCP e RAG.

## Arquitetura

```
backend/app/AI/
├── Agents/          # Agentes IA (RecruiterAgent, TrainingAgent)
├── KnowledgeBase/   # Pipeline RAG e embeddings
├── MCP/            # Model Context Protocol
└── VectorStore/    # Armazenamento vetorial
```

## Tecnologias

- **LLM**: OpenAI GPT-4o
- **Embeddings**: OpenAI text-embedding-3-small
- **Vector DB**: PostgreSQL + pgvector / Elasticsearch / Qdrant
- **MCP**: Protocolo para agentes

## Instalação

### 1. PgVector (PostgreSQL)
```bash
docker run -d --name pgvector -p 5432:5432 \
  -e POSTGRES_PASSWORD=secret \
  -e POSTGRES_DB=rhtechia \
  pgvector/pgvector:pg16
```

### 2. Dependências PHP
```bash
composer require openai-php/laravel
composer require elasticsearch/elasticsearch
composer require pgvector/laravel
```

### 3. Variáveis de Ambiente
```env
OPENAI_API_KEY=sk-...
VECTOR_DB_TYPE=pgvector
```

## Uso

### Ingestão de Documentos
```php
$rag = app(RAGPipeline::class);
$rag->ingestDocument($content, ['source' => 'handbook']);
```

### Query RAG
```php
$answer = $rag->query("Quais são os benefícios?");
```

### Execução de Agente
```php
$agent = app(RecruiterAgent::class);
$response = $agent->execute([
    'vacancy_id' => 1,
    'candidates' => $candidates,
]);
```
