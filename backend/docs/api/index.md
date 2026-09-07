# RH Tech IA - Documentação da API

## Visão Geral
API REST para o sistema de RH com inteligência artificial.

**Base URL:** `http://localhost:8000/api/v1`

---

## Autenticação

### Login
```
POST /api/v1/login
```

**Body:**
```json
{
  "email": "usuario@email.com",
  "password": "senha123"
}
```

**Resposta:**
```json
{
  "success": true,
  "message": "Login realizado com sucesso.",
  "data": {
    "user": { "id": 1, "name": "João", "email": "joao@email.com" },
    "token": "1|abc123..."
  }
}
```

---

## Usuário

### Ver Perfil
```
GET /api/v1/user
Authorization: Bearer {token}
```

### Atualizar Perfil
```
PUT /api/v1/user
Authorization: Bearer {token}
```

**Body:**
```json
{
  "name": "João Silva",
  "email": "joao.novo@email.com"
}
```

---

## Códigos de Erro

| Código | Descrição |
|--------|-----------|
| 200 | Sucesso |
| 201 | Criado |
| 400 | Bad Request |
| 401 | Não autenticado |
| 403 | Acesso negado |
| 404 | Não encontrado |
| 422 | Erro de validação |
| 500 | Erro interno |
