# Verify

Run these and read the output before claiming any task, plan, or plugin is done. "Should pass" is not evidence.

Fresh checkout/worktree first (otherwise tests fail on `APP_KEY` / `ViteManifestNotFoundException` for reasons unrelated to the plugin):
```
cd host && [ -f .env ] || (cp .env.example .env && php artisan key:generate)   # never overwrite an existing .env
npm install --prefix host && npm run build --prefix host
```

Per plugin, from repo root:
```
composer update techysavvy/<kebab> --working-dir=host        # symlink + package:discover
cd host && php artisan route:list --name=<kebab>             # routes registered
cd host && php artisan test                                  # host suite incl. ToolListingTest
composer install --working-dir=plugins/<kebab> && (cd plugins/<kebab> && vendor/bin/phpunit)
cd host && ./vendor/bin/pint --test                          # CI lint gate
```
If the plugin owns assets:
```
npm install --prefix plugins/<kebab> && npm run build --prefix plugins/<kebab>
npm test --prefix plugins/<kebab>                            # if it has JS tests
```
If a dev server is running (`make serve`), load `/`: the card shows the right icon/name/description and clicking it lands on the tool's page.
