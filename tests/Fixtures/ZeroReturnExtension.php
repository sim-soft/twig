<?php

declare(strict_types=1);

namespace Simsoft\Twig\Tests\Fixtures;

use Simsoft\Twig\Extension;

class ZeroReturnExtension extends Extension
{
    protected function init(): void
    {
        $this->addFunction('zero', fn() => 0);
    }
}
