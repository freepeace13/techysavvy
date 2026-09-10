<?php

namespace Techysavvy\DocToMarkdown\Tests\Support;

/**
 * Builds a minimal, syntactically valid PDF from a list of pages (each a
 * list of text lines) for tests — no PDF-writing library is a project
 * dependency, so this hand-rolls just enough PDF structure for
 * smalot/pdfparser to read back.
 */
class MinimalPdfBuilder
{
    /**
     * @param array<int, string[]> $pages Each entry is a page's lines; an empty string is a blank line (paragraph break).
     */
    public static function build(array $pages): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        $pageObjNums = [];
        $nextObj = 3;
        $contentObjNums = [];
        foreach ($pages as $i => $lines) {
            $pageObjNums[] = $nextObj;
            $contentObjNums[$nextObj] = $nextObj + 1;
            $nextObj += 2;
        }

        $kids = implode(' ', array_map(fn (int $n) => "{$n} 0 R", $pageObjNums));
        $objects[2] = "<< /Type /Pages /Kids [{$kids}] /Count ".count($pages).' >>';

        $fontObjNum = $nextObj;
        $objects[$fontObjNum] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        foreach ($pages as $i => $lines) {
            $pageNum = $pageObjNums[$i];
            $contentNum = $contentObjNums[$pageNum];
            $objects[$pageNum] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] "
                ."/Resources << /Font << /F1 {$fontObjNum} 0 R >> >> /Contents {$contentNum} 0 R >>";
            $objects[$contentNum] = self::contentStream($lines);
        }

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }

        $xrefStart = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";

        return $pdf;
    }

    /** @param string[] $lines */
    private static function contentStream(array $lines): string
    {
        $ops = "BT /F1 12 Tf 50 700 Td\n";
        foreach ($lines as $i => $line) {
            if ($i > 0) {
                $ops .= "0 -20 Td\n";
            }
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $ops .= "({$escaped}) Tj\n";
        }
        $ops .= 'ET';

        return '<< /Length '.strlen($ops)." >>\nstream\n{$ops}\nendstream";
    }
}
