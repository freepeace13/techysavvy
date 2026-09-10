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
}
