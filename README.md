# # Simsoft Twig

A Twig wrapper, built for Twig template engine.

## Install

```shell
composer require simsoft/twig
```

## Basic Usage

Examples setup in bootstrap or entry script file.

```php
<?php
require "vendor/autoload.php";

use Simsoft\Twig\Twig;

$twig = new Twig([
    'path' => 'path/to/templates',
    'fileExtension' => '.twig',
    'debug' => true, // default is false
    'charset' => 'UTF-8',
    'cache' => 'path/to/cache',
    'extensions' => [new \App\MyExtension()],
    'namespaces' => [
        'name' => '/path/to/template',
    ],
]);

$twig->display('template_name', ['name' => 'John']);
```

## Building Extension

For further tutorial, please refer
to [Extending Twig](https://twig.symfony.com/doc/3.x/advanced.html).

```php
<?php

namespace App;

use use Simsoft\Twig\Extension;

class MyExtension extends Extension
{
    public function getGlobals() : array
    {
        return [
            'guest_name' => 'John Doe',
        ];
    }

    public function init(): void
    {
        // add filters
        $this->addFilter('obj_to_array', fn ($object) => (array)$object);

        // add functions
        $this->addFunction('dump', fn(...$args) => call_user_func_array('var_dump', $args));

        // add test
        $this->addTest('red', function ($value) {
            if (isset($value->color) && $value->color == 'red') {
                return true;
            }
            if (isset($value->paint) && $value->paint == 'red') {
                return true;
            }
            return false;
        });
    }
}

```

### Building Template files.

Please refer
to [Twig for Template Designers](https://twig.symfony.com/doc/3.x/templates.html).

## License

The Simsoft Validator is licensed under the MIT License. See
the [LICENSE](LICENSE) file for details
