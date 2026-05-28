<?php

declare(strict_types=1);

namespace Simsoft\Twig\Tests\Fixtures;

use Simsoft\Twig\Extension;

class MultiGlobalExtension extends Extension
{
    public function getGlobals(): array
    {
        return [
            'app_name' => 'MyApp',
            'app_version' => '2.0',
        ];
    }
}
