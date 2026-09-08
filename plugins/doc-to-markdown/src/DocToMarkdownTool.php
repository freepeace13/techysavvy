<?php

namespace Techysavvy\DocToMarkdown;

use Techysavvy\Core\ToolContract;

class DocToMarkdownTool implements ToolContract
{
    public function icon(): string
    {
        return '📝';
    }

    public function name(): string
    {
        return 'Doc to Markdown';
    }

    public function description(): string
    {
        return 'Convert a Word document or PDF into clean Markdown — nothing is stored.';
    }

    public function url(): string
    {
        return route('doc-to-markdown.home');
    }
}
