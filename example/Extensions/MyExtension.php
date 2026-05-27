<?php

declare(strict_types=1);

namespace Example\Extensions;

use Simsoft\Twig\Extension;

/**
 * MyExtension class
 *
 * Example extension demonstrating filters, functions, and globals.
 */
class MyExtension extends Extension
{
    public function getGlobals(): array
    {
        return [
            'meta_title' => 'Hello World',
        ];
    }

    protected function init(): void
    {
        // Filters
        $this->addFilter('obj_to_array', fn(object $obj) => (array)$obj);
        $this->addFilter('slug', fn(string $s) => strtolower(str_replace(' ', '-', $s)));

        // Functions
        $this->addFunction('dump', fn(...$args) => call_user_func_array('var_dump', $args));
        $this->addFunction('asset', fn(string $path) => "/assets/{$path}");

        // Tests
        $this->addTest('even', fn(int $n) => $n % 2 === 0);
    }
}
