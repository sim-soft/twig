# Project Structure

```
simsoft/twig/
├── src/                    # Library source code (PSR-4: Simsoft\Twig\)
│   ├── Twig.php            # Main wrapper class — config, render, display
│   ├── TwigConfig.php      # Typed config DTO (alternative to array config)
│   ├── HtmlMinifier.php    # Tag-aware HTML minifier used by Twig::minify()
│   └── Extension.php       # Base class for building custom Twig extensions
├── tests/                  # PHPUnit suite (PSR-4: Simsoft\Twig\Tests\)
│   └── Fixtures/           # Test extension fixtures
├── example/                # Usage examples (PSR-4: Example\)
│   ├── index.php           # Entry point demonstrating library usage
│   ├── Extensions/         # Example extension implementations
│   └── templates/          # Example Twig template files (.twig)
│       └── layouts/        # Layout/base templates for inheritance
├── vendor/                 # Composer dependencies (gitignored)
├── composer.json           # Package manifest and autoload config
└── .editorconfig           # Editor formatting rules
```

## Architecture

- **Twig class** (`src/Twig.php`): Central facade. Accepts a config array or
  `TwigConfig` object, validates the keys, initializes the Twig `Environment`
  and `FilesystemLoader`, and exposes `render()`, `renderIf()`,
  `renderBlock()`, `display()`, `exists()`, `share()`, and methods to add
  filters/functions/tests/extensions.
- **TwigConfig class** (`src/TwigConfig.php`): Readonly DTO providing a typed,
  IDE-friendly alternative to array config. Converted via `toArray()`.
- **HtmlMinifier class** (`src/HtmlMinifier.php`): Internal tag-aware minifier
  backing `Twig::minify()`. Tokenizes the document before collapsing
  whitespace so raw-text elements and attribute values are left intact.
- **Extension class** (`src/Extension.php`): Concrete base class extending
  `Twig\Extension\AbstractExtension` with `GlobalsInterface`. Subclasses
  override `init()` to register filters, functions, and tests via helper
  methods (`addFilter`, `addFunction`, `addTest`).

## Conventions

- All source classes live in `src/` under the `Simsoft\Twig` namespace.
- Extensions should extend `Simsoft\Twig\Extension` and register their
  filters/functions/tests inside the `init()` method.
- Template files use the `.twig` extension by default (configurable).
- The library uses fluent return types (`static`) for chainable API calls.
- PHPDoc blocks are required on all public and protected methods.
