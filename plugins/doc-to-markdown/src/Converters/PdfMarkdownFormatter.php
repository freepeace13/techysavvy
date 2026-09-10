<?php

namespace Techysavvy\DocToMarkdown\Converters;

/**
 * Turns the flat, positionless text a PDF text layer yields into Markdown.
 * PDFs carry no semantic structure, so headings/lists/tables are inferred
 * from line shape rather than read from real document structure.
 */
class PdfMarkdownFormatter
{
    private const BULLET_PATTERN = '/^[•●▪\-\*]\s+(.*)$/u';

    private const NUMBERED_PATTERN = '/^(\d+)[\.\)]\s+(.*)$/u';

    private const URL_PATTERN = '/\bhttps?:\/\/[^\s<>()]+[^\s<>().,;:!?]/i';

    public function format(string $text): string
    {
        $normalized = str_replace("\r\n", "\n", $text);
        $blocks = preg_split('/\n\s*\n/', $normalized);

        $rendered = array_map(fn (string $block) => $this->formatBlock($block), $blocks);

        return implode("\n\n", array_filter($rendered, fn (string $block) => $block !== ''));
    }

    private function formatBlock(string $block): string
    {
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", trim($block))),
            fn (string $line) => $line !== ''
        ));

        if ($lines === []) {
            return '';
        }

        if (count($lines) === 1 && $this->looksLikeHeading($lines[0])) {
            return '## '.$this->linkify($lines[0]);
        }

        if ($this->allMatch($lines, self::BULLET_PATTERN)) {
            return implode("\n", array_map(
                fn (string $line) => '- '.$this->linkify(preg_replace(self::BULLET_PATTERN, '$1', $line)),
                $lines
            ));
        }

        if ($this->allMatch($lines, self::NUMBERED_PATTERN)) {
            return implode("\n", array_map(function (string $line) {
                preg_match(self::NUMBERED_PATTERN, $line, $matches);

                return $matches[1].'. '.$this->linkify($matches[2]);
            }, $lines));
        }

        if (count($lines) >= 2 && ($table = $this->formatTable($lines)) !== null) {
            return $table;
        }

        return $this->linkify(implode(' ', $lines));
    }

    private function looksLikeHeading(string $line): bool
    {
        if (mb_strlen($line) > 80) {
            return false;
        }

        if (preg_match(self::BULLET_PATTERN, $line) === 1 || preg_match(self::NUMBERED_PATTERN, $line) === 1) {
            return false;
        }

        return preg_match('/[.,;:!?]$/', $line) !== 1;
    }

    /** @param string[] $lines */
    private function allMatch(array $lines, string $pattern): bool
    {
        foreach ($lines as $line) {
            if (preg_match($pattern, $line) !== 1) {
                return false;
            }
        }

        return true;
    }

    /** @param string[] $lines */
    private function formatTable(array $lines): ?string
    {
        $rows = array_map(fn (string $line) => preg_split('/\s{2,}/', $line), $lines);

        $columnCount = count($rows[0]);
        if ($columnCount < 2) {
            return null;
        }

        foreach ($rows as $row) {
            if (count($row) !== $columnCount) {
                return null;
            }
        }

        $formatRow = fn (array $row) => '| '.implode(' | ', array_map(
            fn (string $cell) => str_replace('|', '\\|', $this->linkify($cell)),
            $row
        )).' |';

        $lines = [$formatRow($rows[0])];
        $lines[] = '| '.implode(' | ', array_fill(0, $columnCount, '---')).' |';
        for ($r = 1; $r < count($rows); $r++) {
            $lines[] = $formatRow($rows[$r]);
        }

        return implode("\n", $lines);
    }

    private function linkify(string $text): string
    {
        return preg_replace_callback(
            self::URL_PATTERN,
            fn (array $matches) => '['.$matches[0].']('.$matches[0].')',
            $text
        );
    }
}
