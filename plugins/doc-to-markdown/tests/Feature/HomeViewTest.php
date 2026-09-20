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

    public function test_the_home_page_loads_the_bundle_through_the_core_asset_route(): void
    {
        if (! is_file(__DIR__.'/../../resources/dist/doc-to-markdown.js')) {
            $this->markTestSkipped('The bundle is not built (npm run build --prefix plugins/doc-to-markdown).');
        }

        $html = $this->get(route('doc-to-markdown.home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '~<script src="/_plugin-assets/doc-to-markdown/doc-to-markdown\.js\?v=[0-9a-f]{12}"></script>~',
            $html,
        );
        $this->assertStringNotContainsString('vendor/doc-to-markdown', $html, 'The page must no longer reference a published copy.');
    }

    public function test_the_page_still_renders_when_the_bundle_has_not_been_built(): void
    {
        // Core skips a declared-but-missing file instead of failing the page; the
        // inline fallback then tells the user the script did not load.
        $this->get(route('doc-to-markdown.home'))
            ->assertOk()
            ->assertSee('Drop a .docx or .pdf here');
    }

    public function test_the_markdown_preview_grows_with_its_content_instead_of_scrolling(): void
    {
        $html = $this->get(route('doc-to-markdown.home'))->assertOk()->getContent();

        preg_match('~<div class="([^"]*markdown-render[^"]*)"~', $html, $matches);

        $this->assertNotEmpty($matches, 'Expected the markdown preview container.');
        $this->assertStringNotContainsString('h-64', $matches[1], 'The preview must not be pinned to a fixed height.');
        $this->assertStringNotContainsString('overflow-y-auto', $matches[1], 'The preview must not scroll internally.');
    }

    public function test_the_preview_styles_restore_what_tailwinds_preflight_resets(): void
    {
        // Preflight sets headings to font-size:inherit and links to
        // color:inherit. Without explicit rules every heading renders at body
        // size and every link renders as plain text.
        $html = $this->get(route('doc-to-markdown.home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~\.markdown-render h1\s*\{[^}]*font-size~', $html);
        $this->assertMatchesRegularExpression('~\.markdown-render h2\s*\{[^}]*font-size~', $html);
        $this->assertMatchesRegularExpression('~\.markdown-render a\s*\{[^}]*color~', $html);
    }

    // Copy-to-clipboard behaviour (html + plain text, fallbacks) lives in the
    // bundle now and is covered by tests/js/component.test.js.

    public function test_the_page_advertises_the_effective_upload_limit_and_passes_it_to_the_component(): void
    {
        config(['doc-to-markdown.max_upload_kb' => 2048]);

        $html = $this->get(route('doc-to-markdown.home'))->assertOk()->getContent();

        $this->assertStringContainsString('2 MB', $html);
        $this->assertStringContainsString('maxBytes: 2097152', $html);
    }

    public function test_the_page_has_a_fallback_when_the_bundle_fails_to_load(): void
    {
        $this->get(route('doc-to-markdown.home'))
            ->assertOk()
            ->assertSee('The converter script failed to load', false);
    }

    public function test_the_progress_bar_and_errors_are_exposed_to_assistive_tech(): void
    {
        $html = $this->get(route('doc-to-markdown.home'))->assertOk()->getContent();

        $this->assertStringContainsString('role="progressbar"', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
    }
}
