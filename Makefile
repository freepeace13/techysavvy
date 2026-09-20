.PHONY: install serve test artisan

install:
	composer install --working-dir=host
	npm install --prefix plugins/ui
	npm install --prefix plugins/doc-to-markdown
	npm run build --prefix plugins/doc-to-markdown
	npm install --prefix plugins/drop-share
	npm run build --prefix plugins/drop-share
	npm install --prefix plugins/photo-tweaker
	npm run build --prefix plugins/photo-tweaker
	npm install --prefix host

serve:
	cd host && php artisan serve

test:
	cd host && php artisan test

artisan:
	cd host && php artisan $(filter-out $@,$(MAKECMDGOALS))

%:
	@:
