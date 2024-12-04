include .env

ROOT_DIR:=$(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))

all: build start composer-install var-preps clear-cache-all db-migrate
restart: start stop
build:
	@echo "Building containers"
	@docker compose --env-file .env build
start:
	@echo "Starting containers"
	@docker compose --env-file .env up -d --remove-orphans
stop:
	@echo "Stopping containers"
	@docker compose --env-file .env down
composer-install:
	@echo "Running composer install"
	@docker exec -it ${APP_NAME}.service.app composer install
composer-update:
	@echo "Running composer update"
	@docker exec -it ${APP_NAME}.service.app composer update
var-preps:
	@echo "Settings on var dir"
	@sudo touch /var/supervisor.pid
	@sudo chmod -R 777 var/*

db-migrate:
	@echo "Running database migrations"
	@docker exec -it -u www-data  ${APP_NAME}.service.app php bin/console --no-interaction doctrine:migration:migrate

db-create-test:
	@echo "Creating test database"
	@docker exec -it -u www-data  ${APP_NAME}.service.app php bin/console --no-interaction doctrine:database:create --env=test
	@docker exec -it -u www-data  ${APP_NAME}.service.app php bin/console --no-interaction doctrine:migration:migrate --env=test

db-reset:
	@echo "Resetting database"
	@docker exec -it -u www-data  ${APP_NAME}.service.app php bin/console --no-interaction doctrine:schema:drop --full-database --force
	@docker exec -it -u www-data  ${APP_NAME}.service.app php bin/console --no-interaction doctrine:schema:drop --full-database --force --env=test
	@docker exec -it -u www-data  ${APP_NAME}.service.app php bin/console --no-interaction doctrine:schema:create
	@docker exec -it -u www-data  ${APP_NAME}.service.app php bin/console --no-interaction doctrine:schema:create --env=test

benchmark:
	@echo "Running benchmark"
	@docker exec -it -u www-data  ${APP_NAME}.service.app php Tool/benchmark.php

pre-commit:
	@echo "Running pre-commit"
	docker exec -it  ${APP_NAME}.service.app vendor/bin/php-cs-fixer fix --using-cache=no --rules=@PSR12 src/
	docker exec -it  ${APP_NAME}.service.app vendor/bin/phpstan analyse -c phpstan.neon


php:
	@echo "Running php"
	@docker exec -it  ${APP_NAME}.service.app bash

clear-cache:
	@echo "Clearing global cache"
	@docker exec -it -u www-data  ${APP_NAME}.service.app php bin/console --no-interaction cache:pool:clear cache.global_clearer
clear-all: clear-cache-all clear-logs-all
clear-cache-all:
	@echo "Clearing all cache"
	@rm -rf var/cache/*
clear-logs-all:
	@echo "Clearing all logs"
	@rm -rf var/log/*
