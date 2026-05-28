<?php

declare(strict_types=1);

namespace Simsoft\Twig\Tests\Fixtures;

use Simsoft\Twig\Extension;

class NullSafeFilterExtension extends Extension
{
    protected function init(): void
    {
        $this->addFilter('default_val', fn (?string $value, string $default) => $value ?? $default);
    }
}
