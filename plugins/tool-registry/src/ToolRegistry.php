<?php

namespace Techysavvy\ToolRegistry;

use Illuminate\Support\Collection;

class ToolRegistry
{
    /** @var Collection<int, ToolContract> */
    protected Collection $tools;

    public function __construct()
    {
        $this->tools = new Collection();
    }

    public function register(ToolContract $tool): void
    {
        $this->tools->push($tool);
    }

    /** @return Collection<int, ToolContract> */
    public function all(): Collection
    {
        return $this->tools;
    }
}
