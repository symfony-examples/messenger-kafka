DC=docker-compose
PHP_CONTAINER=php
EXEC_PHP=$(DC) exec $(PHP_CONTAINER) php
KAFKA_SERVERS=kafka:9092
KAFKA_CONTAINER=kafka
EXEC_KAFKA=$(DC) exec $(KAFKA_CONTAINER)

.DEFAULT_GOAL := help
.PHONY: help
help : Makefile # Print commands help.
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

##
## Project commands
##----------------------------------------------------------------------------------------------------------------------
.PHONY: logs shell kafka prune install-local

logs: ## View containers logs.
	$(DC) logs -f $(filter-out $@,$(MAKECMDGOALS))

shell: ## Run bash shell in php container.
	$(DC) exec $(PHP_CONTAINER) sh

kafka: ## Run bash shell in kafka container.
	$(DC) exec $(KAFKA_CONTAINER) sh

prune:
	$(DC) down -v

install-local: ## Install project
	@echo "[Docker] Down project if exist"
	$(MAKE) prune
	@echo "[Docker] Build & Run container"
	$(DC) up -d --build
	@echo "[Composer] Install dependencies"
	$(MAKE) composer install
	@echo "[Kafka] Create order_topic_test topic"
	$(MAKE) topic-create order_topic_test

##
## Symfony commands
##----------------------------------------------------------------------------------------------------------------------
.PHONY: composer console

composer: ## Run composer in php container.
	$(DC) exec $(PHP_CONTAINER) composer $(filter-out $@,$(MAKECMDGOALS))

console: ## Run symfony console in php container.
	$(EXEC_PHP) bin/console $(filter-out $@,$(MAKECMDGOALS))

##
## Kafka commands
##----------------------------------------------------------------------------------------------------------------------
.PHONY: topics topic topic-create producer-create consumer-groups consumer-group

topics: ## Display list of topics
	$(EXEC_KAFKA) kafka-topics.sh --list --bootstrap-server $(KAFKA_SERVERS)

topic: ## Describe existing topic
	$(EXEC_KAFKA) kafka-topics.sh --describe --bootstrap-server $(KAFKA_SERVERS) --topic $(filter-out $@,$(MAKECMDGOALS))

topic-create: ## Create new topic
	$(EXEC_KAFKA) kafka-topics.sh --create --bootstrap-server $(KAFKA_SERVERS) --topic $(filter-out $@,$(MAKECMDGOALS))

producer-create: ## Create a topic producer
	$(EXEC_KAFKA) kafka-console-producer.sh --bootstrap-server $(KAFKA_SERVERS) --topic $(filter-out $@,$(MAKECMDGOALS))

consumer-groups: ## Display list of consumer group
	$(EXEC_KAFKA) kafka-consumer-groups.sh --list --bootstrap-server $(KAFKA_SERVERS)

consumer-group: ## Describe existing consumer group
	$(EXEC_KAFKA) kafka-consumer-groups.sh --describe --bootstrap-server $(KAFKA_SERVERS) --group $(filter-out $@,$(MAKECMDGOALS))

##
## Security
##----------------------------------------------------------------------------------------------------------------------
.PHONY: security validate

security: ## Identify vulnerabilities in PHP dependencies.
	$(DC) exec $(PHP_CONTAINER) symfony security:check

validate: ## Run php cs fixer
	$(DC) exec $(PHP_CONTAINER) composer validate

##
## Quality tools
##----------------------------------------------------------------------------------------------------------------------
.PHONY: fix check-cs phpstan cpd md unit ci

fix: ## Run php cs fixer
	$(EXEC_PHP) ./vendor/bin/php-cs-fixer fix -vvv --config=.php-cs-fixer.dist.php --cache-file=.php-cs-fixer.cache

check-cs: ## Run php cs fixer
	$(EXEC_PHP) ./vendor/bin/php-cs-fixer fix -vvv --config=.php-cs-fixer.dist.php --cache-file=.php-cs-fixer.cache --dry-run

phpstan: ## Run phpstan code static analyze
	$(EXEC_PHP) ./vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=256M

cpd: ## Run phpcpd to detect duplicated code source
	$(DC) exec $(PHP_CONTAINER) phpcpd src

md: ## Run phpmd to detect code smells
	$(DC) exec $(PHP_CONTAINER) phpmd src,tests ansi phpmd.xml.dist

unit: ## Run unit tests
	$(EXEC_PHP) vendor/bin/phpunit

unit-coverage: ## Run unit tests with code coverage generate
	$(EXEC_PHP) vendor/bin/phpunit --coverage-text

ci: ## Run all tests and code quality
	$(MAKE) fix
	$(MAKE) cpd
	$(MAKE) md
	$(MAKE) phpstan
	$(MAKE) unit