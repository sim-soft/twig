# Simsoft Twig

A lightweight PHP wrapper for the [Twig 3.x](https://twig.symfony.com/) template
engine. Simplifies setup with configuration-driven initialization, namespace
support, and easy extension authoring.

## Features

- Configuration-based initialization (paths, caching, debug, charset, timezone)
- Optional tag-aware HTML minification
  ([what it guarantees](#what-minification-guarantees))
- Template namespaces for organized directory structures
- Simplified extension base class with helper methods for filters, functions,
  and tests
- Fluent API for configuring the engine before the first render
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
| `path`          | string\|string[] | —       | **Required.** Path(s) to templates directory                |
| `fileExtension` | string           | `.twig` | Template file extension                                     |
| `debug`         | bool             | `false` | Enable debug mode                                           |
| `charset`       | string           | `UTF-8` | Template charset                                            |
| `cache`         | string           | —       | Compiled template cache directory                           |
| `timezone`      | string           | —       | Timezone for date formatting                                |
| `extensions`    | array            | `[]`    | Array of `ExtensionInterface` instances                     |
| `namespaces`    | array            | `[]`    | Map of namespace name → template path                       |
| `minify`        | bool             | `false` | Minify HTML output ([details](#what-minification-guarantees)) |

Unrecognized config keys will throw an `InvalidArgumentException` to catch typos
early. Note that key names are case-sensitive (`charset`, not `Charset`).

`path` is required — omitting it, or passing an empty string or array, throws an
`InvalidArgumentException`.

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

### What minification guarantees

Minification is tag-aware — the document is tokenized before any whitespace is
touched, so the following hold:

- **Raw-text elements are preserved byte for byte.** Content inside `<pre>`,
  `<textarea>`, `<script>`, and `<style>` is never altered, including
  indentation and blank lines.
- **JavaScript and CSS are safe.** A string such as `var x = "<!-- hi -->";`
  or an expression like `a --> b` passes through untouched.
- **Attribute values are never rewritten,** including values containing `>`,
  `<`, quotes, or newlines.
- **Word spacing is preserved.** Whitespace between inline elements collapses
  to a single space rather than being removed, so `</span>\n<span>` becomes
  `</span> <span>` and words stay separated. Whitespace around block-level
  elements is removed entirely, since it has no rendered effect.
- **Conditional comments are preserved** regardless of casing (`<!--[if IE]>`
  and `<!--[If IE]>` both survive). All other comments are removed.
- **Malformed input is safe.** Unclosed tags, unterminated comments, and
  invalid UTF-8 are passed through rather than dropped.

Minification is idempotent — minifying already-minified output is a no-op.

## Runtime API

> [!IMPORTANT]
> All registration must happen **before the first render**. Twig locks its
> extension set once the environment is initialized, so calling `share()`,
> `addFilter()`, `addFunction()`, `addTest()`, or `addExtension()` after any
> `render()`, `display()`, `renderBlock()`, or `renderIf()` call throws a
> `LogicException`.

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

**Use simsoft/twig when** you want Twig in any PHP project (custom frameworks,
legacy apps, microservices, CLI tools) without framework lock-in or manual
wiring.

**Use something else when** you're already in Laravel (`rcrowe/twigbridge`) or
Slim (`slim/twig-view`), or need custom loaders like database/S3 (use
`twig/twig` directly).

## License

MIT — see [LICENSE](LICENSE) for details.
