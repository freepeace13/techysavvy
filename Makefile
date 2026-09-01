.PHONY: install serve test artisan

install:
	composer install --working-dir=host
	npm install --prefix plugins/ui
	npm install --prefix host

serve:
	cd host && php artisan serve

test:
	cd host && php artisan test

artisan:
	cd host && php artisan $(filter-out $@,$(MAKECMDGOALS))

%:
	@:
