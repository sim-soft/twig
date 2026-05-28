<?php

declare(strict_types=1);

namespace Simsoft\Twig\Tests\Fixtures;

use Simsoft\Twig\Extension;

class NoArgFunctionExtension extends Extension
{
    protected function init(): void
    {
        $this->addFunction('app_version', fn () => '1.0.0');
    }
}
