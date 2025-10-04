docker-up ::
	docker-compose up -d

docker-down ::
	docker-compose down -v

phpstan ::
	docker-compose run --rm vending-machine vendor/bin/phpstan analyse

test ::
	docker-compose run --rm vending-machine vendor/bin/phpunit

coverage ::
	docker-compose run --rm -e XDEBUG_MODE=coverage vending-machine vendor/bin/phpunit --configuration=phpunit.xml --coverage-text

coverage-html ::
	docker-compose run --rm -e XDEBUG_MODE=coverage -v %cd%/coverage:/app/coverage vending-machine vendor/bin/phpunit --configuration=phpunit.xml --coverage-html coverage/
