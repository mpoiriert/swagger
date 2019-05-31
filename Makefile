help:
	@echo "This makefile is intended mainly for development purpose."
	@echo ""
	@echo "Targets:"
	@echo "    install:             This will setup the development environment or reset the current one"
	@echo "    up:                  docker-compose up the development environment"
	@echo "    down:                docker-compose down the development environment"
	@echo "    provision:           provision the data, this should be executed after a clear"
	@echo "    build:               rebuild all the needed container"
	@echo "    php:                 will connect to the php container"

.ONESHELL:
down:
	docker-compose down --remove-orphans

up:
	docker-compose up -d

build:
	docker-compose build

install: build provision

php:
	docker exec -it php bash

test:
	docker-compose exec php php vendor/bin/phpunit

test-coverage:
	docker-compose exec php php vendor/bin/phpunit --coverage-html ./tmp/phpunit/report

provision: up
	docker-compose exec php composer install