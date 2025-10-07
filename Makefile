build ::
	docker-compose build --no-cache

up ::
	docker-compose up -d

down ::
	docker-compose down -v

phpstan ::
	docker-compose run --rm vending-machine vendor/bin/phpstan analyse

test ::
	docker-compose run --rm -e XDEBUG_MODE=off vending-machine vendor/bin/phpunit --no-coverage --display-deprecations --stderr

coverage ::
	docker-compose run --rm -e XDEBUG_MODE=coverage vending-machine vendor/bin/phpunit --configuration=phpunit.xml --coverage-text --coverage-clover=coverage/clover.xml

coverage-html ::
	docker-compose run --rm -e XDEBUG_MODE=coverage -v $(shell pwd)/coverage:/app/coverage vending-machine vendor/bin/phpunit --configuration=phpunit.xml --coverage-html=coverage/
