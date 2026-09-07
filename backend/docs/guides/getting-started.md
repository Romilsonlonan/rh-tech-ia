# Guias

## Instalação

1. Clone o repositório
2. `cd backend && composer install`
3. Copie `.env.example` para `.env`
4. `php artisan key:generate`
5. `php artisan serve`

## Configuração

### Variáveis de Ambiente

```env
APP_NAME="RH Tech IA"
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rhtechia
DB_USERNAME=root
DB_PASSWORD=

OPENAI_API_KEY=sk-...
VECTOR_DB_TYPE=pgvector
```

## Estrutura do Projeto

```
backend/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/V1/       # Controllers da API v1
│   │   └── Web/           # Controllers Web (Blade)
│   ├── Models/            # Eloquent Models
│   ├── Services/          # Lógica de negócio
│   ├── Repositories/      # Padrão Repository
│   └── AI/               # Agentes, RAG, MCP
├── routes/
│   ├── api.php           # Rotas da API
│   └── web.php           # Rotas Web
└── database/
    ├── migrations/        # Estrutura do banco
    ├── seeders/         # Dados iniciais
    └── factories/        # factories para testes
```

## Comandos Úteis

```bash
php artisan migrate          # Executar migrations
php artisan db:seed          # Popular banco
php artisan route:list      # Listar rotas
php artisan make:controller # Criar controller
php artisan make:model      # Criar model
php artisan make:migration  # Criar migration
```
