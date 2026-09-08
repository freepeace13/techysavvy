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
}
