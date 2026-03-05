# SaaS de Assinaturas - API (Laravel)

Esse repo eh um MVP de backend SaaS que eu montei para validar um fluxo real de assinaturas.
A ideia foi fugir de CRUD simples e focar em regra de negócio: plano com limite de uso, ciclo de assinatura, autenticação por token e processamento assíncrono.

## O que tem aqui

- Autenticacao com token Bearer
- Tenant simples por empresa (cada usuario pertence a uma empresa)
- Planos `grátis` e `pro`
- Limite de projetos por plano
- Ciclo de assinatura: criar, cancelar e reativar
- Job para avisar assinatura proxima do vencimento
- Ambiente dockerizado (app + nginx + postgres + redis)
- CI no GitHub Actions (lint + testes)

## Stack

- PHP 8.3
- Laravel 12
- PostgreSQL 16
- Redis 7
- Docker / Docker Compose
- PHPUnit
- Laravel Pint

## Como subir o projeto

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

API: `http://localhost:8080`

## Comandos uteis

```bash
make subir
make logs
make testar
make fila
make agenda
```

Se preferir sem Makefile:

```bash
docker compose exec app php artisan queue:work --tries=3
docker compose exec app php artisan schedule:work
docker compose exec app php artisan test
```

## Endpoints principais

Base URL: `/api/v1`

### Saude
- `GET /saude`

### Autenticacao
- `POST /autenticacao/cadastrar`
- `POST /autenticacao/entrar`
- `GET /autenticacao/eu`
- `POST /autenticacao/sair`

### Empresa
- `GET /empresa`
- `PUT /empresa`

### Planos e Assinaturas
- `GET /planos`
- `GET /assinaturas/atual`
- `POST /assinaturas`
- `POST /assinaturas/cancelar`
- `POST /assinaturas/reativar`

### Projetos
- `GET /projetos`
- `POST /projetos`
- `GET /projetos/{projetoId}`
- `PUT /projetos/{projetoId}`
- `DELETE /projetos/{projetoId}`

## Fluxo que eu uso para validar rapido

1. Cadastro (`/autenticacao/cadastrar`) e pegar token
2. Login (`/autenticacao/entrar`) para validar credenciais
3. Consultar planos (`/planos`)
4. Criar 3 projetos no plano `gratis`
5. Tentar criar o 4o e validar erro de limite
6. Upgrade para `pro` (`POST /assinaturas` com `codigo_plano=pro`)
7. Criar novo projeto (deve passar)
8. Cancelar e reativar assinatura
9. Logout e validar token invalido

## Exemplo de requests

Cadastro:

```bash
curl -X POST http://localhost:8080/api/v1/autenticacao/cadastrar \
  -H 'Content-Type: application/json' \
  -d '{
    "nome": "Matheus",
    "email": "matheus@teste.com",
    "senha": "segredo123",
    "nome_empresa": "Caetano Dev"
  }'
```

Criar assinatura pro:

```bash
curl -X POST http://localhost:8080/api/v1/assinaturas \
  -H 'Authorization: Bearer SEU_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"codigo_plano":"pro"}'
```

Criar projeto:

```bash
curl -X POST http://localhost:8080/api/v1/projetos \
  -H 'Authorization: Bearer SEU_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"nome":"Projeto API"}'
```

## Testes

```bash
docker compose exec app php artisan test
```

## Scheduler e fila

Comando que enfileira notificações de assinaturas próximas do vencimento:

```bash
php artisan assinaturas:notificar-vencendo --dias=3
```

## Decisões tecnicas

- Nomenclatura de domínio em português para deixar o contexto do projeto mais claro
- Regra de negócio isolada em servico (`ServicoAssinatura`) para evitar controller gordo
- Middleware proprio de token para manter controle sobre formato/expiracao
- Plano `grátis` como fallback quando nao existe assinatura ativa

## Proximos passos

- RBAC (owner/admin/member)
- Chave de idempotencia para endpoints sensiveis
- OpenAPI e colecao de requests versionada
- Observabilidade (metricas e tracing)
