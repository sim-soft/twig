<?php

declare(strict_types=1);

namespace Simsoft\Twig\Tests\Fixtures;

use Simsoft\Twig\Extension;

class EmptyReturnExtension extends Extension
{
    protected function init(): void
    {
        $this->addFunction('empty_str', fn () => '');
    }
}
