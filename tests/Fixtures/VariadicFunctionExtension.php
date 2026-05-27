<?php

declare(strict_types=1);

namespace Simsoft\Twig\Tests\Fixtures;

use Simsoft\Twig\Extension;

class VariadicFunctionExtension extends Extension
{
    protected function init(): void
    {
        $this->addFunction('sum', fn(...$args) => array_sum($args), ['is_variadic' => true]);
    }
}
