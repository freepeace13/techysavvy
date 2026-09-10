.PHONY: install serve test artisan

install:
	composer install --working-dir=host
	npm install --prefix plugins/ui
	npm install --prefix plugins/doc-to-markdown
	npm run build --prefix plugins/doc-to-markdown
	npm install --prefix host
	cd host && php artisan vendor:publish --tag=doc-to-markdown-assets --force

serve:
	cd host && php artisan serve

test:
	cd host && php artisan test

artisan:
	cd host && php artisan $(filter-out $@,$(MAKECMDGOALS))

%:
	@:
