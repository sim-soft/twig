<?php

declare(strict_types=1);

namespace Simsoft\Twig\Tests\Fixtures;

use Simsoft\Twig\Extension;

class TestExtension extends Extension
{
    public function getGlobals(): array
    {
        return [
            'app_name' => 'TestApp',
        ];
    }

    protected function init(): void
    {
        $this->addFilter('shout', fn(string $s) => mb_strtoupper($s));

        $this->addFunction('greet', fn(string $name) => "Hi, {$name}!");

        $this->addTest('even_number', fn(int $n) => $n % 2 === 0);
    }
}
