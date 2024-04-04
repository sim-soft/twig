<?php

namespace Example\Extensions;

use Simsoft\Twig\Extension;

/**
 * MyExtension class
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
        // filters
        $this->addFilter('obj_to_array', fn($object) => (array)$object);

        // functions
        $this->addFunction('dump', fn(...$args) => call_user_func_array('var_dump', $args));
    }
}
