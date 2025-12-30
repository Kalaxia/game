cli:
	docker exec -it kalaxia_app sh

test:
	php vendor/bin/phpunit

consume_game_scheduler:
	php bin/console messenger:consume scheduler_game -vv

consume_async:
	php bin/console messenger:consume async -vv

restart_php:
	docker compose restart phpfpm supervisord
