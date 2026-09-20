<?php

namespace Techysavvy\DocToMarkdown\Converters;

use Smalot\PdfParser\Parser;

class PdfConverter implements DocumentConverter
{
    public function __construct(private readonly PdfMarkdownFormatter $formatter) {}

    public function convert(string $filePath): string
    {
        $pdf = (new Parser)->parseFile($filePath);

        $pages = [];
        foreach ($pdf->getPages() as $page) {
            try {
                $formatted = $this->formatter->format($page->getText());
            } catch (\Throwable $e) {
                // One unreadable page shouldn't discard the rest of the document.
                report($e);

                continue;
            }

            if ($formatted !== '') {
                $pages[] = $formatted;
            }
        }

        return $this->joinPages($pages);
    }

    /**
     * Pages are separated by a rule — except where a paragraph runs across
     * the page break (previous page ends mid-sentence, next starts lowercase),
     * in which case the halves are stitched back together.
     *
     * @param  string[]  $pages
     */
    private function joinPages(array $pages): string
    {
        $out = '';

        foreach ($pages as $page) {
            if ($out === '') {
                $out = $page;

                continue;
            }

            if ($this->continuesParagraph($out, $page)) {
                [$first, $rest] = array_pad(explode("\n\n", $page, 2), 2, null);

                $out .= ' '.$first.($rest === null ? '' : "\n\n".$rest);

                continue;
            }

            $out .= "\n\n---\n\n".$page;
        }

        return $out;
    }

    private function continuesParagraph(string $previous, string $next): bool
    {
        $lastBlock = substr($previous, (int) strrpos($previous, "\n\n"));
        $lastBlock = ltrim($lastBlock);

        $isPlainParagraph = preg_match('/^(#|- |\d+\. |\||\[)/u', $lastBlock) !== 1;
        $endsMidSentence = preg_match('/[.!?:;"”)\]]$/u', $lastBlock) !== 1;
        $startsLowercase = preg_match('/^\p{Ll}/u', $next) === 1;

        return $isPlainParagraph && $endsMidSentence && $startsLowercase;
    }
}
