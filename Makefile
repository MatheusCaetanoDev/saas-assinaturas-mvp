.PHONY: subir descer construir logs shell instalar-composer gerar-chave migrar semear testar fila agenda up down build bash composer-install key seed test queue schedule

subir:
	docker compose up -d --build

descer:
	docker compose down

construir:
	docker compose build --no-cache

logs:
	docker compose logs -f app nginx postgres redis

shell:
	docker compose exec app sh

instalar-composer:
	docker compose exec app composer install

gerar-chave:
	docker compose exec app php artisan key:generate

migrar:
	docker compose exec app php artisan migrate

semear:
	docker compose exec app php artisan db:seed

testar:
	docker compose exec app php artisan test

fila:
	docker compose exec app php artisan queue:work --tries=3

agenda:
	docker compose exec app php artisan schedule:work

up: subir

down: descer

build: construir

bash: shell

composer-install: instalar-composer

key: gerar-chave

seed: semear

test: testar

queue: fila

schedule: agenda
