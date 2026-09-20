<?php

namespace Techysavvy\Core\Assets;

use InvalidArgumentException;

/**
 * A plugin's prebuilt CSS/JS: one directory plus the file names it declares.
 * Only declared names can ever be served or emitted.
 */
final class AssetBundle
{
    /** @var array<string, array{module: bool}> */
    private array $scripts = [];

    /** @var list<string> */
    private array $styles = [];

    /**
     * @param  list<string>|array<string, array{module?: bool}>  $scripts  'a.js' or 'a.js' => ['module' => true]
     * @param  list<string>  $styles
     */
    public function __construct(
        public readonly string $directory,
        array $scripts = [],
        array $styles = [],
    ) {
        foreach ($scripts as $key => $value) {
            [$file, $options] = is_int($key) ? [$value, []] : [$key, $value];

            self::assertFileName($file, '.js');

            foreach ($options as $option => $setting) {
                if ($option !== 'module' || ! is_bool($setting)) {
                    throw new InvalidArgumentException("Unsupported option for script [{$file}]: only 'module' (bool) is allowed.");
                }
            }

            $this->scripts[$file] = ['module' => $options['module'] ?? false];
        }

        foreach ($styles as $file) {
            self::assertFileName($file, '.css');

            $this->styles[] = $file;
        }
    }

    /** @return array<string, array{module: bool}> */
    public function scripts(): array
    {
        return $this->scripts;
    }

    /** @return list<string> */
    public function styles(): array
    {
        return $this->styles;
    }

    /** @return list<string> */
    public function files(): array
    {
        return [...array_keys($this->scripts), ...$this->styles];
    }

    public function has(string $file): bool
    {
        return in_array($file, $this->files(), true);
    }

    public function path(string $file): string
    {
        if (! $this->has($file)) {
            throw new InvalidArgumentException("[{$file}] is not a declared asset of this bundle.");
        }

        return rtrim($this->directory, '/').'/'.$file;
    }

    private static function assertFileName(mixed $file, string $extension): void
    {
        if (! is_string($file)
            || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $file)
            || str_contains($file, '..')
            || ! str_ends_with($file, $extension)) {
            $shown = is_string($file) ? $file : get_debug_type($file);

            throw new InvalidArgumentException("Invalid asset file name [{$shown}]: expected a plain name ending in {$extension}.");
        }
    }
}
