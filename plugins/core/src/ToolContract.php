<?php

namespace Techysavvy\Core;

interface ToolContract
{
    public function icon(): string;

    public function name(): string;

    public function description(): string;

    public function url(): string;
}
