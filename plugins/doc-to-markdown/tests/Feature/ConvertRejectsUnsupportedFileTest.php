<?php

namespace Techysavvy\DocToMarkdown\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Techysavvy\DocToMarkdown\Tests\TestCase;

class ConvertRejectsUnsupportedFileTest extends TestCase
{
    public function test_it_rejects_an_unsupported_file_type(): void
    {
        $file = UploadedFile::fake()->create('notes.txt', 5, 'text/plain');

        $response = $this->post(route('doc-to-markdown.convert'), ['file' => $file], ['Accept' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    }

    public function test_it_rejects_a_missing_file(): void
    {
        $response = $this->post(route('doc-to-markdown.convert'), [], ['Accept' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    }

    public function test_it_rejects_an_oversize_file_with_a_readable_message(): void
    {
        config(['doc-to-markdown.max_upload_kb' => 1]);

        $file = UploadedFile::fake()->create('big.pdf', 50, 'application/pdf');

        $response = $this->post(route('doc-to-markdown.convert'), ['file' => $file], ['Accept' => 'application/json']);

        $response->assertStatus(422);
        $this->assertStringContainsString('upload limit', $response->json('errors.file.0'));
    }

    public function test_it_reports_a_file_with_no_extractable_text(): void
    {
        $pdf = \Techysavvy\DocToMarkdown\Tests\Support\MinimalPdfBuilder::build([['']]);
        $path = tempnam(sys_get_temp_dir(), 'empty-pdf-').'.pdf';
        file_put_contents($path, $pdf);
        $file = new UploadedFile($path, 'scan.pdf', 'application/pdf', null, true);

        $response = $this->post(route('doc-to-markdown.convert'), ['file' => $file], ['Accept' => 'application/json']);

        unlink($path);

        $response->assertStatus(422);
        $this->assertStringContainsString('No text could be extracted', $response->json('message'));
    }

    public function test_it_picks_the_converter_from_the_file_content_not_its_name(): void
    {
        $pdf = \Techysavvy\DocToMarkdown\Tests\Support\MinimalPdfBuilder::build([['Really a pdf.']]);
        $path = tempnam(sys_get_temp_dir(), 'renamed-').'.docx';
        file_put_contents($path, $pdf);
        $file = new UploadedFile($path, 'renamed.docx', 'application/pdf', null, true);

        $response = $this->post(route('doc-to-markdown.convert'), ['file' => $file], ['Accept' => 'application/json']);

        unlink($path);

        $response->assertOk();
        $response->assertJson(['markdown' => 'Really a pdf.']);
    }

    public function test_its_routes_do_not_use_closures_so_route_caching_works(): void
    {
        foreach (['doc-to-markdown.home', 'doc-to-markdown.convert'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);

            $this->assertFalse($route->getAction('uses') instanceof \Closure, "{$name} is a closure route.");
        }
    }

    public function test_the_convert_route_is_rate_limited(): void
    {
        $route = app('router')->getRoutes()->getByName('doc-to-markdown.convert');

        $this->assertNotEmpty(array_filter($route->gatherMiddleware(), fn ($m) => is_string($m) && str_starts_with($m, 'throttle:')));
    }
}
