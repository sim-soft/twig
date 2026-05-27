# Project Structure

```
simsoft/twig/
├── src/                    # Library source code (PSR-4: Simsoft\Twig\)
│   ├── Twig.php            # Main wrapper class — config, render, display
│   └── Extension.php       # Base class for building custom Twig extensions
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

- **Twig class** (`src/Twig.php`): Central facade. Accepts a config array,
  initializes the Twig `Environment` and `FilesystemLoader`, and exposes
  `render()`, `renderBlock()`, `display()`, `share()`, and methods to add
  filters/functions/extensions.
- **Extension class** (`src/Extension.php`): Abstract base extending
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
