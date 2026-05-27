<?php

declare(strict_types=1);

namespace Simsoft\Twig\Tests\Fixtures;

use Simsoft\Twig\Extension;

class NullGlobalExtension extends Extension
{
    public function getGlobals(): array
    {
        return [
            'nullable' => null,
        ];
    }
}
