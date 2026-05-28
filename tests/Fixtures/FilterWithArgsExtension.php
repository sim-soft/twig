<?php

declare(strict_types=1);

namespace Simsoft\Twig\Tests\Fixtures;

use Simsoft\Twig\Extension;

class FilterWithArgsExtension extends Extension
{
    protected function init(): void
    {
        $this->addFilter('repeat', fn (string $s, int $times) => str_repeat($s, $times));
    }
}
