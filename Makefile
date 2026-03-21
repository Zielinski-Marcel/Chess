init: check-if-env-file-exist
	./vendor/bin/sail build

dev:
	./vendor/bin/sail up -d
	./vendor/bin/sail npm install
	./vendor/bin/sail npm run dev

stop:
	./vendor/bin/sail down

shell:
	./vendor/bin/sail shell

dusk:
	@docker-compose up -d
	@touch ./public/hot
	@rm ./public/hot
	@docker-compose exec -it app npm run build
	@echo waiting for dusk
	@docker-compose exec -it app php artisan dusk

testfront:
	@docker-compose exec -it app npm run test

migrate:
	./vendor/bin/sail artisan migrate --seed

test:
	@docker-compose exec -it app php artisan test

infection:
	@docker-compose exec -it app vendor/bin/infection

testall: dusk test testfront infection


check-if-env-file-exist:
	@if [ ! -f ".env" ]; then \
	  echo ".env file does not exist. Create a .env file and adjust it." ;\
	  exit 1;\
	fi; \
