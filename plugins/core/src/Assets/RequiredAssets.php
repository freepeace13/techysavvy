<?php

namespace Techysavvy\Core\Assets;

use InvalidArgumentException;

/**
 * Which bundles the current request's page asked for. Bound with scoped() so
 * long-lived workers (Octane, queues) start each request empty.
 */
class RequiredAssets
{
    /** @var list<string> */
    private array $names = [];

    public function __construct(private readonly AssetRegistry $registry)
    {
    }

    public function add(string $name): void
    {
        if (! $this->registry->has($name)) {
            throw new InvalidArgumentException("Cannot require unknown asset bundle [{$name}].");
        }

        if (! in_array($name, $this->names, true)) {
            $this->names[] = $name;
        }
    }

    /** @return list<string> */
    public function names(): array
    {
        return $this->names;
    }
}
