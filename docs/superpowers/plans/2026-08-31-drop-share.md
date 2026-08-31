# Drop Share Plugin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `plugins/drop-share`, a public tool that lets anyone upload a file, get a random phrase for it, and download it again with that phrase — files auto-expire and are purged, storage/size/lifespan are config-driven, uploads are rate-limited, and file contents are encrypted at rest.

**Architecture:** Standard techysavvy plugin (Composer path package, self-registering `ServiceProvider`). A `DropShareService` owns upload/retrieve/prune business logic (encryption, storage, phrase uniqueness); thin controllers handle HTTP concerns; a `PhraseGenerator` wraps the `drenso/genphrase` library; an Artisan command + scheduler entry (registered by the plugin itself, no host edits) prunes expired rows/files.

**Tech Stack:** Laravel 13 (host), PHP 8.3, `drenso/genphrase` ^2.2 for phrase generation, `orchestra/testbench` ^11.2 for this plugin's own booted HTTP/DB tests (its own `require-dev`, not a repo-wide convention), Laravel's `Crypt` facade (AES-256-CBC) for at-rest encryption, Laravel's `RateLimiter`/`throttle` middleware for abuse protection.

**Spec:** `docs/superpowers/specs/2026-08-31-drop-share-design.md`

## Global Constraints

- Plugin name/casing: kebab `drop-share`, Studly `DropShare`, display "Drop Share", icon `📦`.
- Config (`config/drop-share.php`, env-overridable): `disk` (default `local`), `max_upload_kb` (default `25600`), `lifespan_hours` (default `24`), `prune_interval_minutes` (default `5`). Nothing storage/size/lifespan-related may be hardcoded outside this file.
- Table name: `drop_share_uploads`. Model: `Techysavvy\DropShare\Models\SharedFile`.
- File bytes are encrypted at rest via `Crypt::encryptString`/`Crypt::decryptString`; plaintext is never written to disk.
- Route names: `drop-share.home`, `drop-share.upload`, `drop-share.download`. Rate limiter names: `drop-share-uploads` (5/min/IP), `drop-share-downloads` (10/min/IP).
- Phrase format: 4 lowercase words, hyphen-separated (e.g. `correct-horse-battery-staple`), via `drenso/genphrase`'s `Drenso\GenPhrase\Password` class.
- Never edit `host/config/app.php`; never hardcode this plugin into `host/tests/Feature/ToolListingTest.php`.
- Nothing in `host/` or another plugin reaches into `drop-share`'s `src/` directly.

---

## Task 1: Scaffold the plugin package and wire it into host

**Files:**
- Create: `plugins/drop-share/composer.json`
- Create: `plugins/drop-share/src/DropShareTool.php`
- Create: `plugins/drop-share/src/DropShareServiceProvider.php`
- Create: `plugins/drop-share/routes/web.php`
- Create: `plugins/drop-share/resources/views/home.blade.php`
- Modify: `host/composer.json` (add `"techysavvy/drop-share": "*"` to `require`)

**Interfaces:**
- Produces: `Techysavvy\DropShare\DropShareTool implements Techysavvy\Core\ToolContract` (`icon()`, `name()`, `description()`, `url()`).
- Produces: `Techysavvy\DropShare\DropShareServiceProvider` — `boot()` will keep growing across later tasks; at the end of this task it only loads routes/views and registers the tool.
- Produces: named route `drop-share.home` (`GET /drop-share`).

- [ ] **Step 1: Create the plugin's `composer.json`**

`plugins/drop-share/composer.json`:
```json
{
    "name": "techysavvy/drop-share",
    "description": "Upload a file, get a phrase, share it — auto-deletes after expiry.",
    "type": "library",
    "version": "1.0.0",
    "require": {
        "php": "^8.3",
        "illuminate/support": "^13.0",
        "techysavvy/core": "*",
        "techysavvy/ui": "*",
        "drenso/genphrase": "^2.2"
    },
    "require-dev": {
        "orchestra/testbench": "^11.2",
        "phpunit/phpunit": "^12.5"
    },
    "autoload": {
        "psr-4": {
            "Techysavvy\\DropShare\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Techysavvy\\DropShare\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Techysavvy\\DropShare\\DropShareServiceProvider"
            ]
        }
    },
    "minimum-stability": "stable"
}
```

- [ ] **Step 2: Create `DropShareTool`**

`plugins/drop-share/src/DropShareTool.php`:
```php
<?php

namespace Techysavvy\DropShare;

use Techysavvy\Core\ToolContract;

class DropShareTool implements ToolContract
{
    public function icon(): string
    {
        return '📦';
    }

    public function name(): string
    {
        return 'Drop Share';
    }

    public function description(): string
    {
        return 'Upload a file, get a phrase, share it — auto-deletes after expiry.';
    }

    public function url(): string
    {
        return route('drop-share.home');
    }
}
```

- [ ] **Step 3: Create the routes file with a placeholder home route**

`plugins/drop-share/routes/web.php`:
```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/drop-share', fn () => view('drop-share::home'))->name('drop-share.home');
```

- [ ] **Step 4: Create a placeholder home view**

`plugins/drop-share/resources/views/home.blade.php`:
```blade
<x-brand::layout title="Drop Share">
    <h1>Drop Share</h1>
    <p>Upload a file, get a phrase, share it.</p>
</x-brand::layout>
```

(This is replaced with the real two-column layout in Task 9.)

- [ ] **Step 5: Create `DropShareServiceProvider`**

`plugins/drop-share/src/DropShareServiceProvider.php`:
```php
<?php

namespace Techysavvy\DropShare;

use Illuminate\Support\ServiceProvider;
use Techysavvy\Core\ToolRegistry;

class DropShareServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'drop-share');

        $this->app->make(ToolRegistry::class)->register(new DropShareTool());
    }
}
```

- [ ] **Step 6: Wire the package into `host/composer.json`**

Add `"techysavvy/drop-share": "*"` to the `require` block, alongside the other `techysavvy/*` entries.

- [ ] **Step 7: Install and verify**

Run, from repo root:
```bash
composer update techysavvy/drop-share --working-dir=host
cd host && php artisan route:list --name=drop-share
```
Expected: `GET|HEAD drop-share ... drop-share.home` appears in the route list.

- [ ] **Step 8: Run the existing host test suite to confirm nothing broke**

```bash
cd host && php artisan test --filter=ToolListingTest
```
Expected: PASS — `ToolListingTest` is registry-driven and now also covers the new `Drop Share` card automatically.

- [ ] **Step 9: Commit**

```bash
git add plugins/drop-share host/composer.json host/composer.lock
git commit -m "feat(drop-share): scaffold plugin package and wire into host"
```

---

## Task 2: Testbench test scaffold + tool-registration test

**Files:**
- Create: `plugins/drop-share/tests/TestCase.php`
- Create: `plugins/drop-share/phpunit.xml`
- Create: `plugins/drop-share/tests/Feature/ToolRegistrationTest.php`

**Interfaces:**
- Consumes: `Techysavvy\DropShare\DropShareServiceProvider` (Task 1), `Techysavvy\Core\CoreServiceProvider`, `Techysavvy\Ui\UiServiceProvider`, `Techysavvy\Core\ToolRegistry::all()` (existing).
- Produces: `Techysavvy\DropShare\Tests\TestCase` — the base class every later Feature test in this plugin extends.

- [ ] **Step 1: Create the plugin-local Testbench `TestCase`**

`plugins/drop-share/tests/TestCase.php`:
```php
<?php

namespace Techysavvy\DropShare\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Techysavvy\Core\CoreServiceProvider;
use Techysavvy\DropShare\DropShareServiceProvider;
use Techysavvy\Ui\UiServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CoreServiceProvider::class,
            UiServiceProvider::class,
            DropShareServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('filesystems.disks.local', [
            'driver' => 'local',
            'root' => storage_path('app/private'),
        ]);

        // Pin cache/session to the 'array' driver so RateLimiter state and
        // session-flashed data are deterministic within a single test and
        // never leak between tests via a shared file/db-backed store.
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
    }
}
```

- [ ] **Step 2: Create `phpunit.xml`**

`plugins/drop-share/phpunit.xml`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/12.5/phpunit.xsd"
         bootstrap="../../host/vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

- [ ] **Step 3: Write the tool-registration feature test**

`plugins/drop-share/tests/Feature/ToolRegistrationTest.php`:
```php
<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Techysavvy\Core\ToolRegistry;
use Techysavvy\DropShare\DropShareTool;
use Techysavvy\DropShare\Tests\TestCase;

class ToolRegistrationTest extends TestCase
{
    public function test_it_registers_itself_into_the_tool_registry(): void
    {
        $tools = $this->app->make(ToolRegistry::class)->all();

        $dropShare = $tools->first(fn ($tool) => $tool instanceof DropShareTool);

        $this->assertNotNull($dropShare);
        $this->assertSame('Drop Share', $dropShare->name());
        $this->assertSame('📦', $dropShare->icon());
    }

    public function test_home_route_responds_successfully(): void
    {
        $response = $this->get('/drop-share');

        $response->assertOk();
        $response->assertSee('Drop Share');
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

From `plugins/drop-share/`:
```bash
../../host/vendor/bin/phpunit --testsuite=Feature
```
Expected: PASS (2 tests) — this proves the plugin already boots end-to-end via Testbench, which every later Feature test in this plugin relies on.

- [ ] **Step 5: Commit**

```bash
git add plugins/drop-share/tests plugins/drop-share/phpunit.xml plugins/drop-share/composer.json
git commit -m "test(drop-share): add Testbench scaffold and tool-registration test"
```

---

## Task 3: Config file

**Files:**
- Create: `plugins/drop-share/config/drop-share.php`
- Modify: `plugins/drop-share/src/DropShareServiceProvider.php`
- Test: `plugins/drop-share/tests/Feature/ConfigTest.php`

**Interfaces:**
- Produces: `config('drop-share.disk')`, `config('drop-share.max_upload_kb')`, `config('drop-share.lifespan_hours')`, `config('drop-share.prune_interval_minutes')` — every later task reads these, never a hardcoded literal.

- [ ] **Step 1: Write the failing test**

`plugins/drop-share/tests/Feature/ConfigTest.php`:
```php
<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Techysavvy\DropShare\Tests\TestCase;

class ConfigTest extends TestCase
{
    public function test_default_config_values_are_merged(): void
    {
        $this->assertSame('local', config('drop-share.disk'));
        $this->assertSame(25600, config('drop-share.max_upload_kb'));
        $this->assertSame(24, config('drop-share.lifespan_hours'));
        $this->assertSame(5, config('drop-share.prune_interval_minutes'));
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

```bash
../../host/vendor/bin/phpunit --filter=test_default_config_values_are_merged
```
Expected: FAIL — `config('drop-share.disk')` returns `null` (config not yet merged).

- [ ] **Step 3: Create the config file**

`plugins/drop-share/config/drop-share.php`:
```php
<?php

return [
    // Any disk name defined in filesystems.php ('local', 'public', 's3', ...).
    'disk' => env('DROP_SHARE_DISK', 'local'),

    // Max upload size, in kilobytes.
    'max_upload_kb' => env('DROP_SHARE_MAX_UPLOAD_KB', 25600), // 25MB

    // How long an uploaded file lives before it's eligible for deletion.
    'lifespan_hours' => env('DROP_SHARE_LIFESPAN_HOURS', 24),

    // How often the prune command runs, when scheduled by this plugin.
    'prune_interval_minutes' => env('DROP_SHARE_PRUNE_INTERVAL_MINUTES', 5),
];
```

- [ ] **Step 4: Merge it in the service provider**

Modify `plugins/drop-share/src/DropShareServiceProvider.php`, add as the first line of `boot()`:
```php
        $this->mergeConfigFrom(__DIR__.'/../config/drop-share.php', 'drop-share');
```
(full `boot()` method now):
```php
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/drop-share.php', 'drop-share');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'drop-share');

        $this->app->make(ToolRegistry::class)->register(new DropShareTool());
    }
```

- [ ] **Step 5: Run the test to verify it passes**

```bash
../../host/vendor/bin/phpunit --filter=test_default_config_values_are_merged
```
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add plugins/drop-share/config plugins/drop-share/src/DropShareServiceProvider.php plugins/drop-share/tests
git commit -m "feat(drop-share): add config-driven disk/size/lifespan settings"
```

---

## Task 4: Migration and `SharedFile` model

**Files:**
- Create: `plugins/drop-share/database/migrations/2026_08_31_000001_create_drop_share_uploads_table.php`
- Create: `plugins/drop-share/src/Models/SharedFile.php`
- Modify: `plugins/drop-share/src/DropShareServiceProvider.php`
- Test: `plugins/drop-share/tests/Feature/SharedFileModelTest.php`

**Interfaces:**
- Produces: `Techysavvy\DropShare\Models\SharedFile` — Eloquent model over `drop_share_uploads`, `$fillable = ['phrase', 'disk_path', 'original_name', 'mime_type', 'size', 'expires_at']`, `expires_at` cast to `datetime`. Every later task (`DropShareService`, controllers, prune command) depends on this exact shape.

- [ ] **Step 1: Write the failing test**

`plugins/drop-share/tests/Feature/SharedFileModelTest.php`:
```php
<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Techysavvy\DropShare\Models\SharedFile;
use Techysavvy\DropShare\Tests\TestCase;

class SharedFileModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_and_casts_expires_at_to_a_carbon_instance(): void
    {
        $sharedFile = SharedFile::create([
            'phrase' => 'correct-horse-battery-staple',
            'disk_path' => 'drop-share/abc123',
            'original_name' => 'notes.txt',
            'mime_type' => 'text/plain',
            'size' => 42,
            'expires_at' => Carbon::now()->addHours(24),
        ]);

        $fresh = SharedFile::query()->first();

        $this->assertSame($sharedFile->id, $fresh->id);
        $this->assertSame('correct-horse-battery-staple', $fresh->phrase);
        $this->assertInstanceOf(Carbon::class, $fresh->expires_at);
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

```bash
../../host/vendor/bin/phpunit --filter=SharedFileModelTest
```
Expected: FAIL — table `drop_share_uploads` doesn't exist / class `SharedFile` not found.

- [ ] **Step 3: Create the migration**

`plugins/drop-share/database/migrations/2026_08_31_000001_create_drop_share_uploads_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drop_share_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('phrase')->unique();
            $table->string('disk_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drop_share_uploads');
    }
};
```

- [ ] **Step 4: Create the model**

`plugins/drop-share/src/Models/SharedFile.php`:
```php
<?php

namespace Techysavvy\DropShare\Models;

use Illuminate\Database\Eloquent\Model;

class SharedFile extends Model
{
    protected $table = 'drop_share_uploads';

    protected $fillable = [
        'phrase',
        'disk_path',
        'original_name',
        'mime_type',
        'size',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'size' => 'integer',
    ];
}
```

- [ ] **Step 5: Load migrations in the service provider**

Modify `plugins/drop-share/src/DropShareServiceProvider.php`, add to `boot()` after `loadViewsFrom`:
```php
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
```

- [ ] **Step 6: Run the test to verify it passes**

```bash
../../host/vendor/bin/phpunit --filter=SharedFileModelTest
```
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add plugins/drop-share/database plugins/drop-share/src/Models plugins/drop-share/src/DropShareServiceProvider.php plugins/drop-share/tests
git commit -m "feat(drop-share): add drop_share_uploads migration and SharedFile model"
```

---

## Task 5: `PhraseGenerator`

**Files:**
- Create: `plugins/drop-share/src/Services/PhraseGenerator.php`
- Test: `plugins/drop-share/tests/Unit/PhraseGeneratorTest.php`

**Interfaces:**
- Produces: `Techysavvy\DropShare\Services\PhraseGenerator::generate(): string` — returns a 4-word, lowercase, hyphen-separated phrase. Consumed by `DropShareService` (Task 6).
- This is pure logic (no Laravel app needed) — lives in the `Unit` suite, plain `PHPUnit\Framework\TestCase`, not `Techysavvy\DropShare\Tests\TestCase`.

- [ ] **Step 1: Write the failing test**

`plugins/drop-share/tests/Unit/PhraseGeneratorTest.php`:
```php
<?php

namespace Techysavvy\DropShare\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Techysavvy\DropShare\Services\PhraseGenerator;

class PhraseGeneratorTest extends TestCase
{
    public function test_it_generates_a_four_word_lowercase_hyphenated_phrase(): void
    {
        $phrase = (new PhraseGenerator())->generate();

        $this->assertMatchesRegularExpression('/^[a-z]+(-[a-z]+){3}$/', $phrase);
    }

    public function test_it_generates_different_phrases_across_calls(): void
    {
        $generator = new PhraseGenerator();

        $phrases = array_map(fn () => $generator->generate(), range(1, 5));

        $this->assertGreaterThan(1, count(array_unique($phrases)));
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

```bash
../../host/vendor/bin/phpunit --testsuite=Unit
```
Expected: FAIL — class `PhraseGenerator` not found.

- [ ] **Step 3: Implement `PhraseGenerator`**

`plugins/drop-share/src/Services/PhraseGenerator.php`:
```php
<?php

namespace Techysavvy\DropShare\Services;

use Drenso\GenPhrase\Password;

class PhraseGenerator
{
    /**
     * 40 bits of entropy against GenPhrase's ~4096-word (12 bits/word)
     * default english wordlist reliably yields exactly 4 words
     * (ceil(40 / 12) = 4), matching this tool's phrase-strength decision.
     */
    private const ENTROPY_BITS = 40.0;

    public function generate(): string
    {
        $generator = new Password();
        $generator->disableWordModifier(true);
        $generator->setSeparators('-');
        $generator->alwaysUseSeparators(true);

        return $generator->generate(self::ENTROPY_BITS);
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
../../host/vendor/bin/phpunit --testsuite=Unit
```
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add plugins/drop-share/src/Services/PhraseGenerator.php plugins/drop-share/tests/Unit
git commit -m "feat(drop-share): add PhraseGenerator wrapping drenso/genphrase"
```

---

## Task 6: `DropShareService` — store, retrieve, prune, encryption

**Files:**
- Create: `plugins/drop-share/src/Services/DropShareService.php`
- Test: `plugins/drop-share/tests/Feature/DropShareServiceTest.php`

**Interfaces:**
- Consumes: `Techysavvy\DropShare\Services\PhraseGenerator::generate(): string` (Task 5), `Techysavvy\DropShare\Models\SharedFile` (Task 4), `config('drop-share.disk')`, `config('drop-share.lifespan_hours')` (Task 3).
- Produces:
  - `DropShareService::store(\Illuminate\Http\UploadedFile $file): SharedFile`
  - `DropShareService::findValid(string $phrase): ?SharedFile`
  - `DropShareService::buildDownloadResponse(SharedFile $sharedFile): \Symfony\Component\HttpFoundation\Response`
  - `DropShareService::pruneExpired(): int`

  Consumed by controllers (Task 7, 8) and the prune command (Task 10).

- [ ] **Step 1: Write the failing tests**

`plugins/drop-share/tests/Feature/DropShareServiceTest.php`:
```php
<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Techysavvy\DropShare\Models\SharedFile;
use Techysavvy\DropShare\Services\DropShareService;
use Techysavvy\DropShare\Tests\TestCase;

class DropShareServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_encrypts_the_file_and_creates_a_row(): void
    {
        Storage::fake(config('drop-share.disk'));

        $file = UploadedFile::fake()->createWithContent('notes.txt', 'hello world');

        $sharedFile = $this->app->make(DropShareService::class)->store($file);

        $this->assertDatabaseHas('drop_share_uploads', [
            'id' => $sharedFile->id,
            'original_name' => 'notes.txt',
        ]);
        $this->assertMatchesRegularExpression('/^[a-z]+(-[a-z]+){3}$/', $sharedFile->phrase);

        $stored = Storage::disk(config('drop-share.disk'))->get($sharedFile->disk_path);
        $this->assertNotSame('hello world', $stored);
        $this->assertSame('hello world', Crypt::decryptString($stored));
    }

    public function test_find_valid_returns_null_for_unknown_phrase(): void
    {
        $result = $this->app->make(DropShareService::class)->findValid('does-not-exist-anywhere');

        $this->assertNull($result);
    }

    public function test_find_valid_returns_null_for_expired_phrase(): void
    {
        SharedFile::create([
            'phrase' => 'expired-phrase-four-words',
            'disk_path' => 'drop-share/whatever',
            'original_name' => 'x.txt',
            'mime_type' => 'text/plain',
            'size' => 1,
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $result = $this->app->make(DropShareService::class)->findValid('expired-phrase-four-words');

        $this->assertNull($result);
    }

    public function test_build_download_response_returns_decrypted_content(): void
    {
        Storage::fake(config('drop-share.disk'));

        $file = UploadedFile::fake()->createWithContent('notes.txt', 'secret content');
        $service = $this->app->make(DropShareService::class);
        $sharedFile = $service->store($file);

        $response = $service->buildDownloadResponse($sharedFile);

        $this->assertSame('secret content', $response->getContent());
        $this->assertStringContainsString('notes.txt', $response->headers->get('Content-Disposition'));
    }

    public function test_prune_expired_deletes_expired_rows_and_files_only(): void
    {
        Storage::fake(config('drop-share.disk'));
        $disk = config('drop-share.disk');

        Storage::disk($disk)->put('drop-share/expired-path', 'x');
        $expired = SharedFile::create([
            'phrase' => 'expired-one-two-three',
            'disk_path' => 'drop-share/expired-path',
            'original_name' => 'a.txt',
            'mime_type' => 'text/plain',
            'size' => 1,
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        Storage::disk($disk)->put('drop-share/valid-path', 'y');
        $valid = SharedFile::create([
            'phrase' => 'valid-one-two-three',
            'disk_path' => 'drop-share/valid-path',
            'original_name' => 'b.txt',
            'mime_type' => 'text/plain',
            'size' => 1,
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $deletedCount = $this->app->make(DropShareService::class)->pruneExpired();

        $this->assertSame(1, $deletedCount);
        $this->assertDatabaseMissing('drop_share_uploads', ['id' => $expired->id]);
        $this->assertDatabaseHas('drop_share_uploads', ['id' => $valid->id]);
        Storage::disk($disk)->assertMissing('drop-share/expired-path');
        Storage::disk($disk)->assertExists('drop-share/valid-path');
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
../../host/vendor/bin/phpunit --filter=DropShareServiceTest
```
Expected: FAIL — class `DropShareService` not found.

- [ ] **Step 3: Implement `DropShareService`**

`plugins/drop-share/src/Services/DropShareService.php`:
```php
<?php

namespace Techysavvy\DropShare\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Techysavvy\DropShare\Models\SharedFile;

class DropShareService
{
    private const MAX_PHRASE_ATTEMPTS = 5;

    public function __construct(private readonly PhraseGenerator $phraseGenerator)
    {
    }

    public function store(UploadedFile $file): SharedFile
    {
        $encrypted = Crypt::encryptString(file_get_contents($file->getRealPath()));
        $diskPath = 'drop-share/'.(string) Str::uuid();

        Storage::disk(config('drop-share.disk'))->put($diskPath, $encrypted);

        return SharedFile::create([
            'phrase' => $this->uniquePhrase(),
            'disk_path' => $diskPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'expires_at' => now()->addHours((int) config('drop-share.lifespan_hours')),
        ]);
    }

    public function findValid(string $phrase): ?SharedFile
    {
        $sharedFile = SharedFile::where('phrase', $phrase)->first();

        if (! $sharedFile || $sharedFile->expires_at->isPast()) {
            return null;
        }

        return $sharedFile;
    }

    public function buildDownloadResponse(SharedFile $sharedFile): Response
    {
        $encrypted = Storage::disk(config('drop-share.disk'))->get($sharedFile->disk_path);
        $decrypted = Crypt::decryptString($encrypted);

        return response($decrypted, 200, [
            'Content-Type' => $sharedFile->mime_type,
            'Content-Disposition' => 'attachment; filename="'.$sharedFile->original_name.'"',
        ]);
    }

    public function pruneExpired(): int
    {
        $expired = SharedFile::where('expires_at', '<=', now())->get();

        foreach ($expired as $sharedFile) {
            Storage::disk(config('drop-share.disk'))->delete($sharedFile->disk_path);
            $sharedFile->delete();
        }

        return $expired->count();
    }

    private function uniquePhrase(): string
    {
        for ($attempt = 0; $attempt < self::MAX_PHRASE_ATTEMPTS; $attempt++) {
            $phrase = $this->phraseGenerator->generate();

            if (! SharedFile::where('phrase', $phrase)->exists()) {
                return $phrase;
            }
        }

        throw new RuntimeException('Unable to generate a unique drop-share phrase after '.self::MAX_PHRASE_ATTEMPTS.' attempts.');
    }
}
```

- [ ] **Step 4: Run the tests to verify they pass**

```bash
../../host/vendor/bin/phpunit --filter=DropShareServiceTest
```
Expected: PASS (5 tests).

- [ ] **Step 5: Commit**

```bash
git add plugins/drop-share/src/Services/DropShareService.php plugins/drop-share/tests/Feature/DropShareServiceTest.php
git commit -m "feat(drop-share): add DropShareService for encrypted store/retrieve/prune"
```

---

## Task 7: Upload endpoint (validation, controller, rate limiting)

**Files:**
- Create: `plugins/drop-share/src/Http/Requests/StoreUploadRequest.php`
- Create: `plugins/drop-share/src/Http/Controllers/UploadController.php`
- Modify: `plugins/drop-share/routes/web.php`
- Modify: `plugins/drop-share/src/DropShareServiceProvider.php`
- Test: `plugins/drop-share/tests/Feature/UploadTest.php`

**Interfaces:**
- Consumes: `DropShareService::store()` (Task 6), `config('drop-share.max_upload_kb')` (Task 3).
- Produces: `POST /drop-share/upload` (route name `drop-share.upload`), redirects to `drop-share.home` flashing `drop_share_phrase` into session on success. Rate limiter `drop-share-uploads` (5/min/IP), consumed only here.

- [ ] **Step 1: Write the failing tests**

`plugins/drop-share/tests/Feature/UploadTest.php`:
```php
<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Techysavvy\DropShare\Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_upload_redirects_with_a_phrase_flashed(): void
    {
        Storage::fake(config('drop-share.disk'));

        $file = UploadedFile::fake()->create('report.pdf', 100);

        $response = $this->post(route('drop-share.upload'), ['file' => $file]);

        $response->assertRedirect(route('drop-share.home'));
        $response->assertSessionHas('drop_share_phrase');

        $phrase = session('drop_share_phrase');
        $this->assertMatchesRegularExpression('/^[a-z]+(-[a-z]+){3}$/', $phrase);
    }

    public function test_upload_without_a_file_fails_validation(): void
    {
        $response = $this->post(route('drop-share.upload'), []);

        $response->assertSessionHasErrors('file');
    }

    public function test_upload_larger_than_configured_limit_fails_validation(): void
    {
        config(['drop-share.max_upload_kb' => 10]);

        $file = UploadedFile::fake()->create('too-big.bin', 50); // 50KB > 10KB limit

        $response = $this->post(route('drop-share.upload'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
    }

    public function test_uploads_are_throttled_after_the_configured_limit(): void
    {
        Storage::fake(config('drop-share.disk'));

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('drop-share.upload'), [
                'file' => UploadedFile::fake()->create("file{$i}.txt", 1),
            ])->assertRedirect();
        }

        $response = $this->post(route('drop-share.upload'), [
            'file' => UploadedFile::fake()->create('one-too-many.txt', 1),
        ]);

        $response->assertStatus(429);
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
../../host/vendor/bin/phpunit --filter=UploadTest
```
Expected: FAIL — route `drop-share.upload` doesn't exist (404s / route helper throws).

- [ ] **Step 3: Create `StoreUploadRequest`**

`plugins/drop-share/src/Http/Requests/StoreUploadRequest.php`:
```php
<?php

namespace Techysavvy\DropShare\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:'.config('drop-share.max_upload_kb')],
        ];
    }
}
```

- [ ] **Step 4: Create `UploadController`**

`plugins/drop-share/src/Http/Controllers/UploadController.php`:
```php
<?php

namespace Techysavvy\DropShare\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Techysavvy\DropShare\Http\Requests\StoreUploadRequest;
use Techysavvy\DropShare\Services\DropShareService;

class UploadController
{
    public function store(StoreUploadRequest $request, DropShareService $service): RedirectResponse
    {
        $sharedFile = $service->store($request->file('file'));

        return redirect()
            ->route('drop-share.home')
            ->with('drop_share_phrase', $sharedFile->phrase);
    }
}
```

- [ ] **Step 5: Add the upload route**

Modify `plugins/drop-share/routes/web.php` to:
```php
<?php

use Illuminate\Support\Facades\Route;
use Techysavvy\DropShare\Http\Controllers\UploadController;

Route::get('/drop-share', fn () => view('drop-share::home'))->name('drop-share.home');

Route::post('/drop-share/upload', [UploadController::class, 'store'])
    ->name('drop-share.upload')
    ->middleware('throttle:drop-share-uploads');
```

- [ ] **Step 6: Register the rate limiter in the service provider**

Modify `plugins/drop-share/src/DropShareServiceProvider.php` — add imports and register the limiter in `boot()`:
```php
<?php

namespace Techysavvy\DropShare;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Techysavvy\Core\ToolRegistry;

class DropShareServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/drop-share.php', 'drop-share');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'drop-share');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->app->make(ToolRegistry::class)->register(new DropShareTool());

        RateLimiter::for('drop-share-uploads', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
```

- [ ] **Step 7: Run the tests to verify they pass**

```bash
../../host/vendor/bin/phpunit --filter=UploadTest
```
Expected: PASS (4 tests).

- [ ] **Step 8: Commit**

```bash
git add plugins/drop-share/src/Http plugins/drop-share/routes/web.php plugins/drop-share/src/DropShareServiceProvider.php plugins/drop-share/tests/Feature/UploadTest.php
git commit -m "feat(drop-share): add rate-limited upload endpoint"
```

---

## Task 8: Download endpoint (controller, rate limiting)

**Files:**
- Create: `plugins/drop-share/src/Http/Controllers/DownloadController.php`
- Modify: `plugins/drop-share/routes/web.php`
- Modify: `plugins/drop-share/src/DropShareServiceProvider.php`
- Test: `plugins/drop-share/tests/Feature/DownloadTest.php`

**Interfaces:**
- Consumes: `DropShareService::findValid()`, `DropShareService::buildDownloadResponse()` (Task 6).
- Produces: `POST /drop-share/download` (route name `drop-share.download`). On invalid/expired phrase, redirects to `drop-share.home` flashing `drop_share_error`. Rate limiter `drop-share-downloads` (10/min/IP).

- [ ] **Step 1: Write the failing tests**

`plugins/drop-share/tests/Feature/DownloadTest.php`:
```php
<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Techysavvy\DropShare\Models\SharedFile;
use Techysavvy\DropShare\Services\DropShareService;
use Techysavvy\DropShare\Tests\TestCase;

class DownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_phrase_downloads_the_original_file(): void
    {
        Storage::fake(config('drop-share.disk'));

        $file = UploadedFile::fake()->createWithContent('report.txt', 'the actual content');
        $sharedFile = $this->app->make(DropShareService::class)->store($file);

        $response = $this->post(route('drop-share.download'), ['phrase' => $sharedFile->phrase]);

        $response->assertOk();
        $this->assertSame('the actual content', $response->getContent());
        $this->assertStringContainsString('report.txt', $response->headers->get('Content-Disposition'));
    }

    public function test_invalid_phrase_redirects_with_an_error(): void
    {
        $response = $this->post(route('drop-share.download'), ['phrase' => 'nonexistent-phrase-here-now']);

        $response->assertRedirect(route('drop-share.home'));
        $response->assertSessionHas('drop_share_error');
    }

    public function test_expired_phrase_is_treated_as_not_found(): void
    {
        SharedFile::create([
            'phrase' => 'gone-now-four-words',
            'disk_path' => 'drop-share/whatever',
            'original_name' => 'x.txt',
            'mime_type' => 'text/plain',
            'size' => 1,
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $response = $this->post(route('drop-share.download'), ['phrase' => 'gone-now-four-words']);

        $response->assertRedirect(route('drop-share.home'));
        $response->assertSessionHas('drop_share_error');
    }

    public function test_downloads_are_throttled_after_the_configured_limit(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('drop-share.download'), ['phrase' => 'nonexistent-phrase-here-now']);
        }

        $response = $this->post(route('drop-share.download'), ['phrase' => 'nonexistent-phrase-here-now']);

        $response->assertStatus(429);
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
../../host/vendor/bin/phpunit --filter=DownloadTest
```
Expected: FAIL — route `drop-share.download` doesn't exist.

- [ ] **Step 3: Create `DownloadController`**

`plugins/drop-share/src/Http/Controllers/DownloadController.php`:
```php
<?php

namespace Techysavvy\DropShare\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Techysavvy\DropShare\Services\DropShareService;

class DownloadController
{
    public function store(Request $request, DropShareService $service): RedirectResponse|Response
    {
        $validated = $request->validate(['phrase' => ['required', 'string']]);

        $sharedFile = $service->findValid($validated['phrase']);

        if (! $sharedFile) {
            return redirect()
                ->route('drop-share.home')
                ->with('drop_share_error', 'That phrase is invalid or has expired.');
        }

        return $service->buildDownloadResponse($sharedFile);
    }
}
```

- [ ] **Step 4: Add the download route**

Modify `plugins/drop-share/routes/web.php` to:
```php
<?php

use Illuminate\Support\Facades\Route;
use Techysavvy\DropShare\Http\Controllers\DownloadController;
use Techysavvy\DropShare\Http\Controllers\UploadController;

Route::get('/drop-share', fn () => view('drop-share::home'))->name('drop-share.home');

Route::post('/drop-share/upload', [UploadController::class, 'store'])
    ->name('drop-share.upload')
    ->middleware('throttle:drop-share-uploads');

Route::post('/drop-share/download', [DownloadController::class, 'store'])
    ->name('drop-share.download')
    ->middleware('throttle:drop-share-downloads');
```

- [ ] **Step 5: Register the second rate limiter**

Modify `plugins/drop-share/src/DropShareServiceProvider.php`, add after the uploads limiter in `boot()`:
```php
        RateLimiter::for('drop-share-downloads', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
```

- [ ] **Step 6: Run the tests to verify they pass**

```bash
../../host/vendor/bin/phpunit --filter=DownloadTest
```
Expected: PASS (4 tests).

- [ ] **Step 7: Commit**

```bash
git add plugins/drop-share/src/Http/Controllers/DownloadController.php plugins/drop-share/routes/web.php plugins/drop-share/src/DropShareServiceProvider.php plugins/drop-share/tests/Feature/DownloadTest.php
git commit -m "feat(drop-share): add rate-limited download endpoint"
```

---

## Task 9: Two-column home view

**Files:**
- Modify: `plugins/drop-share/resources/views/home.blade.php`
- Test: `plugins/drop-share/tests/Feature/HomeViewTest.php`

**Interfaces:**
- Consumes: session keys `drop_share_phrase` (Task 7) and `drop_share_error` (Task 8); routes `drop-share.upload`/`drop-share.download` (Tasks 7, 8); `<x-brand::layout>` (existing `ui` component).

- [ ] **Step 1: Write the failing tests**

`plugins/drop-share/tests/Feature/HomeViewTest.php`:
```php
<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Techysavvy\DropShare\Tests\TestCase;

class HomeViewTest extends TestCase
{
    public function test_home_page_renders_both_forms(): void
    {
        $response = $this->get(route('drop-share.home'));

        $response->assertOk();
        $response->assertSee(route('drop-share.upload'), false);
        $response->assertSee(route('drop-share.download'), false);
    }

    public function test_home_page_shows_the_flashed_phrase_after_upload(): void
    {
        $response = $this->withSession(['drop_share_phrase' => 'correct-horse-battery-staple'])
            ->get(route('drop-share.home'));

        $response->assertSee('correct-horse-battery-staple');
    }

    public function test_home_page_shows_the_flashed_download_error(): void
    {
        $response = $this->withSession(['drop_share_error' => 'That phrase is invalid or has expired.'])
            ->get(route('drop-share.home'));

        $response->assertSee('That phrase is invalid or has expired.');
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
../../host/vendor/bin/phpunit --filter=HomeViewTest
```
Expected: FAIL — placeholder view doesn't reference the upload/download routes or flashed session keys.

- [ ] **Step 3: Implement the two-column view**

`plugins/drop-share/resources/views/home.blade.php`:
```blade
<x-brand::layout title="Drop Share">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="rounded-brand border border-brand-100 bg-surface p-6">
            <h2 class="text-lg font-semibold text-ink mb-4">Upload a file</h2>

            @if (session('drop_share_phrase'))
                <p class="mb-4 rounded-brand border border-brand-100 bg-brand-50 p-3 text-ink">
                    Your phrase: <strong>{{ session('drop_share_phrase') }}</strong>
                </p>
            @endif

            <form method="POST" action="{{ route('drop-share.upload') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="file" name="file" required class="block w-full text-ink">
                @error('file')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <button type="submit" class="rounded-brand bg-brand-500 px-4 py-2 text-white">Upload</button>
            </form>
        </div>

        <div class="rounded-brand border border-brand-100 bg-surface p-6">
            <h2 class="text-lg font-semibold text-ink mb-4">Retrieve a file</h2>

            @if (session('drop_share_error'))
                <p class="mb-4 rounded-brand border border-brand-200 bg-brand-50 p-3 text-ink">
                    {{ session('drop_share_error') }}
                </p>
            @endif

            <form method="POST" action="{{ route('drop-share.download') }}" class="space-y-4">
                @csrf
                <input type="text" name="phrase" required placeholder="correct-horse-battery-staple" class="block w-full rounded-brand border border-brand-100 p-2 text-ink">
                @error('phrase')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <button type="submit" class="rounded-brand bg-brand-500 px-4 py-2 text-white">Download</button>
            </form>
        </div>
    </div>
</x-brand::layout>
```

- [ ] **Step 4: Run the tests to verify they pass**

```bash
../../host/vendor/bin/phpunit --filter=HomeViewTest
```
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add plugins/drop-share/resources/views/home.blade.php plugins/drop-share/tests/Feature/HomeViewTest.php
git commit -m "feat(drop-share): build two-column upload/retrieval home view"
```

---

## Task 10: Prune command and scheduling

**Files:**
- Create: `plugins/drop-share/src/Console/PruneExpiredFilesCommand.php`
- Modify: `plugins/drop-share/src/DropShareServiceProvider.php`
- Test: `plugins/drop-share/tests/Feature/PruneExpiredFilesCommandTest.php`

**Interfaces:**
- Consumes: `DropShareService::pruneExpired()` (Task 6).
- Produces: Artisan command `drop-share:prune`; scheduled every `config('drop-share.prune_interval_minutes')` minutes via the host's scheduler (requires `php artisan schedule:work`/cron on the host — no host files edited).

- [ ] **Step 1: Write the failing test**

`plugins/drop-share/tests/Feature/PruneExpiredFilesCommandTest.php`:
```php
<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Techysavvy\DropShare\Models\SharedFile;
use Techysavvy\DropShare\Tests\TestCase;

class PruneExpiredFilesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_expired_rows_and_files_and_reports_the_count(): void
    {
        Storage::fake(config('drop-share.disk'));
        $disk = config('drop-share.disk');

        Storage::disk($disk)->put('drop-share/expired-path', 'x');
        SharedFile::create([
            'phrase' => 'expired-one-two-three',
            'disk_path' => 'drop-share/expired-path',
            'original_name' => 'a.txt',
            'mime_type' => 'text/plain',
            'size' => 1,
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        Storage::disk($disk)->put('drop-share/valid-path', 'y');
        $valid = SharedFile::create([
            'phrase' => 'valid-one-two-three',
            'disk_path' => 'drop-share/valid-path',
            'original_name' => 'b.txt',
            'mime_type' => 'text/plain',
            'size' => 1,
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $this->artisan('drop-share:prune')
            ->expectsOutputToContain('Pruned 1 expired drop-share upload(s).')
            ->assertExitCode(0);

        $this->assertDatabaseCount('drop_share_uploads', 1);
        $this->assertDatabaseHas('drop_share_uploads', ['id' => $valid->id]);
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

```bash
../../host/vendor/bin/phpunit --filter=PruneExpiredFilesCommandTest
```
Expected: FAIL — command `drop-share:prune` not defined.

- [ ] **Step 3: Implement the command**

`plugins/drop-share/src/Console/PruneExpiredFilesCommand.php`:
```php
<?php

namespace Techysavvy\DropShare\Console;

use Illuminate\Console\Command;
use Techysavvy\DropShare\Services\DropShareService;

class PruneExpiredFilesCommand extends Command
{
    protected $signature = 'drop-share:prune';

    protected $description = 'Delete expired drop-share uploads (database rows and their files).';

    public function handle(DropShareService $service): int
    {
        $deleted = $service->pruneExpired();

        $this->info("Pruned {$deleted} expired drop-share upload(s).");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Register the command and its schedule in the service provider**

Modify `plugins/drop-share/src/DropShareServiceProvider.php` to its final form:
```php
<?php

namespace Techysavvy\DropShare;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Techysavvy\Core\ToolRegistry;
use Techysavvy\DropShare\Console\PruneExpiredFilesCommand;

class DropShareServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/drop-share.php', 'drop-share');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'drop-share');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->app->make(ToolRegistry::class)->register(new DropShareTool());

        RateLimiter::for('drop-share-uploads', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('drop-share-downloads', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        if ($this->app->runningInConsole()) {
            $this->commands([PruneExpiredFilesCommand::class]);
        }

        $this->app->booted(function () {
            $interval = max(1, (int) config('drop-share.prune_interval_minutes'));

            $this->app->make(Schedule::class)
                ->command(PruneExpiredFilesCommand::class)
                ->cron("*/{$interval} * * * *");
        });
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

```bash
../../host/vendor/bin/phpunit --filter=PruneExpiredFilesCommandTest
```
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add plugins/drop-share/src/Console plugins/drop-share/src/DropShareServiceProvider.php plugins/drop-share/tests/Feature/PruneExpiredFilesCommandTest.php
git commit -m "feat(drop-share): add drop-share:prune command and self-scheduling"
```

---

## Task 11: Plugin README

**Files:**
- Create: `plugins/drop-share/README.md`

**Interfaces:**
- None — documentation only.

- [ ] **Step 1: Write the README**

`plugins/drop-share/README.md`:
```markdown
# Drop Share

Upload a file, get a random phrase, share the phrase — anyone with it can
download the file until it expires. Built as a standalone tool plugin; see
the repo root `CLAUDE.md` for the overall monorepo shape.

## How it works

1. `GET /drop-share` — public page with two forms: upload, and retrieval.
2. Uploading a file (`POST /drop-share/upload`) encrypts its contents
   (AES-256-CBC via Laravel's `Crypt` facade, keyed off `APP_KEY`) and
   stores them on the configured disk, generates a unique 4-word phrase
   (via `drenso/genphrase`), and shows the phrase on the page.
3. Retrieving a file (`POST /drop-share/download`) with a valid,
   non-expired phrase decrypts and streams the original file back.
4. `php artisan drop-share:prune` deletes expired rows and their files. The
   plugin schedules this itself (no host file edits) — **the host's
   scheduler must actually be running** (`php artisan schedule:work` in
   dev, or a cron entry hitting `php artisan schedule:run` every minute in
   production) for automatic cleanup to happen. Expired files are also
   always treated as not-found on read, even if the schedule hasn't run
   yet, so a slow scheduler never serves an expired file.

## Configuration

All settings live in `config/drop-share.php` and are env-overridable —
nothing storage/size/lifespan-related is hardcoded in `src/`:

| Env var | Config key | Default | Meaning |
|---|---|---|---|
| `DROP_SHARE_DISK` | `disk` | `local` | Any disk name defined in `filesystems.php` (`local`, `public`, `s3`, ...). |
| `DROP_SHARE_MAX_UPLOAD_KB` | `max_upload_kb` | `25600` (25MB) | Max upload size in kilobytes. |
| `DROP_SHARE_LIFESPAN_HOURS` | `lifespan_hours` | `24` | Hours an upload lives before it's eligible for deletion. |
| `DROP_SHARE_PRUNE_INTERVAL_MINUTES` | `prune_interval_minutes` | `5` | How often the scheduled prune runs. |

## Security notes / caveats

- File bytes are encrypted at rest; plaintext only exists transiently in
  memory during upload processing and download response generation.
- Confidentiality is tied to the host's shared `APP_KEY` — rotating it
  invalidates any not-yet-downloaded uploads. Acceptable given the default
  24h lifespan; worth reconsidering if this pattern is reused for something
  more sensitive.
- Both forms are rate-limited per IP (`5` uploads/min, `10` downloads/min)
  to blunt scripted abuse and phrase brute-forcing. 4-word GenPhrase
  entropy makes phrase guessing impractical even without the limiter.
- **No file-type restriction and no malware/virus scanning** — any file
  type is accepted, size-limited only. This is an explicit, intentional
  gap, not an oversight.
- No per-file or total-storage quota — if abuse outpaces the rate limit and
  default 24h lifespan, that's a follow-up, not handled today.
- Upload size is also bound by PHP's `upload_max_filesize`/`post_max_size`
  ini settings, which this plugin cannot enforce or override — make sure
  they're set at least as high as `DROP_SHARE_MAX_UPLOAD_KB`.

## Tests

This plugin owns its tests in `tests/`, using `orchestra/testbench` (its
own `require-dev` dependency — not a repo-wide convention) to boot a real
Laravel app for HTTP/DB-level coverage, plus plain PHPUnit for pure logic
(`PhraseGenerator`). Run from this directory:

\`\`\`bash
../../host/vendor/bin/phpunit
\`\`\`
```

- [ ] **Step 2: Commit**

```bash
git add plugins/drop-share/README.md
git commit -m "docs(drop-share): add plugin README"
```

---

## Task 12: Full verification pass

**Files:** none (verification only).

- [ ] **Step 1: Run the plugin's full test suite**

```bash
cd plugins/drop-share && ../../host/vendor/bin/phpunit
```
Expected: PASS, all suites (Unit + Feature).

- [ ] **Step 2: Re-run host-level verification**

```bash
cd host
php artisan route:list --name=drop-share
php artisan test
```
Expected: all three `drop-share.*` routes listed; full host suite (including `ToolListingTest`) passes.

- [ ] **Step 3: Manual smoke check (if a dev server is running)**

Load `/` and confirm the "Drop Share" card renders with the `📦` icon and
correct description; click into `/drop-share`; upload a small file and
confirm a phrase appears; paste that phrase into the retrieval form and
confirm the file downloads with its original name and content.

- [ ] **Step 4: Commit** (only if Step 1–3 required any fixes; otherwise nothing to commit)

```bash
git status
```
If clean, verification is complete with no further commit needed.
