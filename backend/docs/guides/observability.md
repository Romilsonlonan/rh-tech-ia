# Observabilidade com OpenTelemetry

## Visão Geral

Este projeto utiliza **OpenTelemetry** para coleta de traces, métricas e logs de observabilidade.

---

## 📊 O que é OpenTelemetry?

OpenTelemetry (OTel) é um framework de observabilidade que fornece:
- **Traces**: Rastreamento de requisições através de serviços
- **Metrics**: Coleta de métricas (latência, throughput, erros)
- **Logs**: Agregação de logs estruturados

---

## 🏗️ Arquitetura

```
┌─────────────┐     ┌──────────────┐     ┌──────────────┐
│   App       │────▶│  Collector  │────▶│  Backend     │
│  (Laravel)  │     │  (Otel)     │     │  (Jaeger,   │
│             │     │              │     │   Tempo,     │
│  Traces     │     │  Traces     │     │   etc)       │
│  Metrics    │     │  Metrics    │     │              │
│  Logs       │     │  Logs       │     │              │
└─────────────┘     └──────────────┘     └──────────────┘
```

---

## 📁 Estrutura

```
backend/app/Telemetry/
├── OpenTelemetryService.php   # Serviço principal
├── SpanWrapper.php            # Wrapper para spans
├── TelemetryConfig.php       # Configuração do OT
└── TelemetryServiceProvider.php # Laravel provider

backend/app/Http/Middleware/
└── OpenTelemetryMiddleware.php # Middleware para traces HTTP
```

---

## ⚙️ Configuração

### Variáveis de Ambiente

```env
OTEL_ENABLED=true
OTEL_SERVICE_NAME=rhtechia-backend
OTEL_EXPORTER_OTLP_ENDPOINT=http://localhost:4318
```

### Laravel Provider

Registrado em `bootstrap/providers.php`:
```php
App\Providers\TelemetryServiceProvider::class,
```

### Middleware

O middleware `OpenTelemetryMiddleware` é adicionado automaticamente às requisições HTTP.

---

## 🔧 Uso

### No Código

```php
use App\Telemetry\OpenTelemetryService;

class UserController extends Controller
{
    public function __construct(
        protected OpenTelemetryService $telemetry
    ) {}

    public function index()
    {
        $span = $this->telemetry->startSpan('user.index');
        
        try {
            $users = User::all();
            return response()->json($users);
        } catch (\Exception $e) {
            $this->telemetry->recordException($e, $span);
            throw $e;
        } finally {
            $span?->end();
        }
    }
}
```

---

## 🐳 Stack de Observabilidade

Para subir a stack completa:

```bash
docker-compose -f docker-compose.observability.yml up -d
```

**Serviços:**
- Jaeger UI: http://localhost:16686
- Prometheus: http://localhost:9090
- Grafana: http://localhost:3000 (admin/admin123)

---

## 📈 Backends Recomendados

| Tipo | Backend |
|------|---------|
| Traces | Jaeger, Tempo |
| Metrics | Prometheus, Grafana |
| Logs | Loki, ELK |

---

## 📚 Referências

- [OpenTelemetry PHP SDK](https://opentelemetry.io/docs/instrumentation/php/)
- [Jaeger](https://www.jaegertracing.io/)
- [Grafana](https://grafana.com/)
