<?php

namespace Techysavvy\DocToMarkdown\Tests\Feature;

use Techysavvy\DocToMarkdown\Tests\TestCase;

class HomeViewTest extends TestCase
{
    public function test_the_home_page_shows_the_upload_form_and_the_pdf_fidelity_caveat(): void
    {
        $response = $this->get(route('doc-to-markdown.home'));

        $response->assertOk();
        $response->assertSee('Drop a .docx or .pdf here');
        $response->assertSee('PDF conversion preserves text, not formatting');
    }

    public function test_the_home_page_loads_the_published_markdown_bundle(): void
    {
        $response = $this->get(route('doc-to-markdown.home'));

        $response->assertOk();
        $response->assertSee('vendor/doc-to-markdown/doc-to-markdown.js');
    }

    public function test_the_bundle_url_is_cache_busted_by_the_published_files_contents(): void
    {
        $this->publishBundle('// first build');

        $first = $this->bundleUrlOnPage();

        $this->assertStringContainsString('?id=', $first, 'Expected a cache-busting query on the bundle URL.');

        // A rebuild with different contents must produce a different URL,
        // otherwise browsers keep serving the previous bundle from cache.
        $this->publishBundle('// second build, different contents');

        $this->assertNotSame($first, $this->bundleUrlOnPage());
    }

    public function test_the_page_still_renders_when_the_bundle_has_not_been_published(): void
    {
        $this->app->usePublicPath($this->makePublicPath());

        $response = $this->get(route('doc-to-markdown.home'));

        $response->assertOk();
        $response->assertSee('vendor/doc-to-markdown/doc-to-markdown.js');
    }

    private function makePublicPath(): string
    {
        $path = sys_get_temp_dir().'/d2m-public-'.bin2hex(random_bytes(6));
        mkdir($path.'/vendor/doc-to-markdown', 0777, true);

        return $path;
    }

    private function publishBundle(string $contents): void
    {
        $path = $this->makePublicPath();
        file_put_contents($path.'/vendor/doc-to-markdown/doc-to-markdown.js', $contents);

        $this->app->usePublicPath($path);
    }

    private function bundleUrlOnPage(): string
    {
        $html = $this->get(route('doc-to-markdown.home'))->assertOk()->getContent();

        preg_match('~src="([^"]*vendor/doc-to-markdown/doc-to-markdown\.js[^"]*)"~', $html, $matches);

        $this->assertNotEmpty($matches, 'Expected a script tag for the bundle.');

        return $matches[1];
    }
}
