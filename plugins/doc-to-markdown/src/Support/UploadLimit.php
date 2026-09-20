<?php

namespace Techysavvy\DocToMarkdown\Support;

/**
 * The upload size the plugin can actually accept: the configured maximum,
 * capped by PHP's own upload_max_filesize / post_max_size, which reject the
 * request before Laravel sees it. Advertising the configured number alone
 * would promise more than the server delivers.
 */
class UploadLimit
{
    public static function kilobytes(): int
    {
        $limits = [(int) config('doc-to-markdown.max_upload_kb')];

        foreach (['upload_max_filesize', 'post_max_size'] as $key) {
            $bytes = self::iniBytes((string) ini_get($key));

            if ($bytes > 0) {
                $limits[] = intdiv($bytes, 1024);
            }
        }

        return max(1, min($limits));
    }

    public static function label(): string
    {
        $kb = self::kilobytes();

        if ($kb < 1024) {
            return "{$kb} KB";
        }

        return rtrim(rtrim(number_format($kb / 1024, 1, '.', ''), '0'), '.').' MB';
    }

    private static function iniBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return 0;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
