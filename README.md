# Simsoft Twig

A lightweight PHP wrapper for the [Twig 3.x](https://twig.symfony.com/) template
engine. Simplifies setup with configuration-driven initialization, namespace
support, and easy extension authoring.

## Features

- Configuration-based initialization (paths, caching, debug, charset, timezone)
- Built-in HTML minification for production output
- Template namespaces for organized directory structures
- Simplified extension base class with helper methods for filters, functions,
  and tests
- Fluent API for runtime customization
- Auto-escaping enabled by default (XSS protection)

## Requirements

- PHP 8.2+
- Composer

## Installation

```shell
composer require simsoft/twig
```

## Quick Start

```php
use Simsoft\Twig\Twig;

$twig = new Twig([
    'path' => __DIR__ . '/templates',
    'cache' => __DIR__ . '/cache',
]);

// Render to string
$html = $twig->render('hello', ['name' => 'World']);

// Output directly
$twig->display('hello', ['name' => 'World']);
```

## Configuration Options

| Option          | Type             | Default | Description                                                 |
|-----------------|------------------|---------|-------------------------------------------------------------|
| `path`          | string\|string[] | `/`     | Path(s) to templates directory                              |
| `fileExtension` | string           | `.twig` | Template file extension                                     |
| `debug`         | bool             | `false` | Enable debug mode                                           |
| `charset`       | string           | `UTF-8` | Template charset                                            |
| `cache`         | string           | —       | Compiled template cache directory                           |
| `timezone`      | string           | —       | Timezone for date formatting                                |
| `extensions`    | array            | `[]`    | Array of `ExtensionInterface` instances                     |
| `namespaces`    | array            | `[]`    | Map of namespace name → template path                       |
| `minify`        | bool             | `false` | Minify HTML output (removes comments, collapses whitespace) |

Unrecognized config keys will throw an `InvalidArgumentException` to catch typos
early.

## Typed Configuration (Alternative)

For IDE autocompletion, use the `TwigConfig` object instead of an array:

```php
use Simsoft\Twig\Twig;
use Simsoft\Twig\TwigConfig;

$twig = new Twig(new TwigConfig(
    path: __DIR__ . '/templates',
    cache: __DIR__ . '/cache',
    debug: true,
    timezone: 'Asia/Kuala_Lumpur',
    minify: true,
    extensions: [new \App\MyExtension()],
    namespaces: [
        'layouts' => __DIR__ . '/templates/layouts',
    ],
));
```

## Full Configuration Example

```php
use Simsoft\Twig\Twig;

$twig = new Twig([
    'path' => __DIR__ . '/templates',
    'fileExtension' => '.twig',
    'debug' => true,
    'charset' => 'UTF-8',
    'cache' => __DIR__ . '/cache',
    'timezone' => 'Asia/Kuala_Lumpur',
    'minify' => true,
    'extensions' => [new \App\MyExtension()],
    'namespaces' => [
        'layouts' => __DIR__ . '/templates/layouts',
        'components' => __DIR__ . '/templates/components',
        'macros' => __DIR__ . '/templates/macros',
    ],
]);
```

## Template Namespaces

Namespaces let you reference templates from different directories:

```php
// Renders @layouts/base.twig
$twig->render('@layouts/base', ['title' => 'Home']);
```

## HTML Minification

Enable `minify` to automatically strip HTML comments, collapse whitespace
between tags, and reduce output size across all rendering methods (`render()`,
`display()`, `renderBlock()`, `renderIf()`):

```php
$twig = new Twig([
    'path' => __DIR__ . '/templates',
    'minify' => true, // All output is minified
]);

// This output will be minified automatically
$html = $twig->render('page', ['title' => 'Home']);
```

The static helper `Twig::minify()` is also available for one-off use on any HTML
string:

```php
$minified = Twig::minify($rawHtml);
```

Minification preserves IE conditional comments (`<!--[if IE]>`) and does not
alter content inside `<pre>`, `<code>`, or `<script>` inline text (only
whitespace between tags is collapsed).

## Runtime API

```php
// Share global variables
$twig->share('site_name', 'My Site');
$twig->share(['app' => 'MyApp', 'version' => '1.0']);

// Add filters
$twig->addFilter('slug', fn (string $s) => strtolower(str_replace(' ', '-', $s)));

// Add functions
$twig->addFunction('asset', fn (string $path) => "/assets/{$path}");

// Add tests
$twig->addTest('even', fn (int $n) => $n % 2 === 0);

// Check if a template exists
if ($twig->exists('email/welcome')) {
    $twig->display('email/welcome', $data);
}

// Render only if template exists (returns empty string otherwise)
$sidebar = $twig->renderIf('partials/sidebar', ['items' => $menuItems]);

// Render a specific block
$header = $twig->renderBlock('page', 'header', ['title' => 'Welcome']);

// Access underlying Twig Environment
$env = $twig->getInstance();
```

## Building Extensions

Extend `Simsoft\Twig\Extension` and register filters, functions, and tests in
the `init()` method:

```php
<?php

declare(strict_types=1);

namespace App;

use Simsoft\Twig\Extension;

class MyExtension extends Extension
{
    public function getGlobals(): array
    {
        return [
            'app_name' => 'My Application',
        ];
    }

    protected function init(): void
    {
        $this->addFilter('obj_to_array', fn (object $obj) => (array) $obj);

        $this->addFunction('dump', fn (...$args) => call_user_func_array('var_dump', $args));

        $this->addTest('red', function ($value) {
            return ($value->color ?? $value->paint ?? null) === 'red';
        });
    }
}
```

For advanced extension features,
see [Extending Twig](https://twig.symfony.com/doc/3.x/advanced.html).

## Template Authoring

See [Twig for Template Designers](https://twig.symfony.com/doc/3.x/templates.html)
for template syntax reference.

## Why Simsoft Twig?

|                         | **simsoft/twig**                              | **slim/twig-view**        | **rcrowe/twigbridge**    | **twig/twig** (raw)          |
|-------------------------|-----------------------------------------------|---------------------------|--------------------------|------------------------------|
| **Purpose**             | Framework-agnostic wrapper                    | Slim 4 integration        | Laravel integration      | Core engine                  |
| **Framework coupling**  | None                                          | Slim (PSR-7/15)           | Laravel                  | None                         |
| **Setup**               | Single constructor call                       | DI container + middleware | ServiceProvider + config | Manual loader + environment  |
| **Config**              | Array or typed DTO                            | Constructor params        | Laravel config file      | Manual PHP code              |
| **Extension authoring** | Base class with `init()`                      | Use raw Twig              | Laravel-specific helpers | Extend `AbstractExtension`   |
| **Namespace support**   | Built-in via config                           | Manual                    | Via config               | Manual `addPath()`           |
| **Convenience methods** | `exists()`, `renderIf()`, `share()`, `minify` | No                        | No                       | `getLoader()->exists()` only |
| **Config validation**   | Throws on typos                               | No                        | No                       | No                           |
| **Static analysis**     | PHPStan level 8                               | Level 5                   | None                     | Level 5                      |

**Use simsoft/twig when** you want Twig in any PHP project (custom frameworks,
legacy apps, microservices, CLI tools) without framework lock-in or manual
wiring.

**Use something else when** you're already in Laravel (`rcrowe/twigbridge`) or
Slim (`slim/twig-view`), or need custom loaders like database/S3 (use
`twig/twig` directly).

## License

MIT — see [LICENSE](LICENSE) for details.
