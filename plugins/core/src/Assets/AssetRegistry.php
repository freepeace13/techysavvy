<?php

namespace Techysavvy\Core\Assets;

use InvalidArgumentException;
use LogicException;

/**
 * Boot-time map of bundle name => AssetBundle. Bound as a plain singleton:
 * registrations happen once in each plugin's ServiceProvider::boot() and must
 * outlive any request. Per-request state lives in RequiredAssets.
 */
class AssetRegistry
{
    /** @var array<string, AssetBundle> */
    private array $bundles = [];

    public function register(string $name, AssetBundle $bundle): void
    {
        if (! preg_match('/^[a-z0-9][a-z0-9-]*$/', $name)) {
            throw new InvalidArgumentException("Invalid asset bundle name [{$name}]: use lowercase letters, digits and dashes.");
        }

        if (isset($this->bundles[$name])) {
            throw new LogicException("Asset bundle [{$name}] is already registered.");
        }

        $this->bundles[$name] = $bundle;
    }

    public function get(string $name): ?AssetBundle
    {
        return $this->bundles[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->bundles[$name]);
    }

    /** @return array<string, AssetBundle> */
    public function all(): array
    {
        return $this->bundles;
    }

    /**
     * Relative URL for a declared file. The `v` query is a short content hash so
     * the immutable cache header is safe: the URL changes only when the bytes do.
     */
    public function url(string $bundle, string $file): string
    {
        $url = route('core.assets', ['bundle' => $bundle, 'file' => $file], absolute: false);

        $path = $this->get($bundle)?->path($file);

        if ($path !== null && is_file($path)) {
            $url .= '?v='.substr(hash_file('xxh128', $path), 0, 12);
        }

        return $url;
    }
}
