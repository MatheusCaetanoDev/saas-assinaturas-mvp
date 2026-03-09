# SaaS de Assinaturas - API (Laravel)

Esse repo eh um MVP de backend SaaS que eu montei para validar um fluxo real de assinaturas.
A ideia foi fugir de CRUD simples e focar em regra de negocio: plano com limite de uso, ciclo de assinatura, autenticacao por token e processamento assincrono.

## O que tem aqui

- Autenticacao com token Bearer
- Tenant simples por empresa (cada usuario pertence a uma empresa)
- RBAC basico com papeis `owner`, `admin` e `member`
- Planos `gratis` e `pro`
- Limite de projetos por plano
- Ciclo de assinatura: criar, cancelar e reativar
- Job para avisar assinatura proxima do vencimento
- Ambiente dockerizado (app + nginx + postgres + redis)
- CI no GitHub Actions (lint + testes)
- OpenAPI versionada em `docs/openapi.yaml`

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

API: `http://localhost:8081`

Se quiser trocar a porta local, defina `APP_PORT` no `.env` antes de subir os containers.

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

## OpenAPI / Swagger

Arquivo da especificacao:

- `docs/openapi.yaml`

Para visualizar em UI Swagger sem subir nada em VPS:

1. Abra o Swagger Editor (online)
2. Importe `docs/openapi.yaml`
3. Teste os endpoints com o servidor local `http://localhost:8081/api/v1`

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
- `PUT /empresa` (somente `owner`)

### Planos e Assinaturas
- `GET /planos`
- `GET /assinaturas/atual`
- `POST /assinaturas` (somente `owner/admin`)
- `POST /assinaturas/cancelar` (somente `owner/admin`)
- `POST /assinaturas/reativar` (somente `owner/admin`)

### Projetos
- `GET /projetos`
- `POST /projetos`
- `GET /projetos/{projetoId}`
- `PUT /projetos/{projetoId}` (somente `owner/admin`)
- `DELETE /projetos/{projetoId}` (somente `owner/admin`)

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
curl -X POST http://localhost:8081/api/v1/autenticacao/cadastrar \
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
curl -X POST http://localhost:8081/api/v1/assinaturas \
  -H 'Authorization: Bearer SEU_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"codigo_plano":"pro"}'
```

Criar projeto:

```bash
curl -X POST http://localhost:8081/api/v1/projetos \
  -H 'Authorization: Bearer SEU_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"nome":"Projeto API"}'
```

## Testes

```bash
docker compose exec app php artisan test
```

## Scheduler e fila

Comando que enfileira notificacoes de assinaturas proximas do vencimento:

```bash
php artisan assinaturas:notificar-vencendo --dias=3
```

## Decisoes tecnicas

- Nomenclatura de dominio em portugues para deixar o contexto do projeto mais claro
- Regra de negocio isolada em servico (`ServicoAssinatura`) para evitar controller gordo
- Middleware proprio de token para manter controle sobre formato/expiracao
- Middleware de RBAC por papel aplicado diretamente nas rotas sensiveis
- Plano `gratis` como fallback quando nao existe assinatura ativa

## Proximos passos

- Convite de usuarios por empresa (onboarding interno)
- Chave de idempotencia para endpoints sensiveis
- Observabilidade (metricas e tracing)
- Rate limiting por rota
