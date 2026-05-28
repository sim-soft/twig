# Tech Stack

## Language & Runtime

- PHP 8.2+
- Twig 3.x (template engine)

## Package Management

- Composer (dependency manager)
- Package name: `simsoft/twig`
- Autoloading: PSR-4 (`Simsoft\Twig\` → `src/`)

## Dependencies

| Package                   | Version | Purpose               |
|---------------------------|---------|-----------------------|
| twig/twig                 | ^3.0    | Core template engine  |
| phpunit/phpunit           | ^11.0   | Testing (dev)         |
| phpstan/phpstan           | ^2.0    | Static analysis (dev) |
| friendsofphp/php-cs-fixer | ^3.0    | Code style (dev)      |

## Commands

```shell
composer test        # Run PHPUnit tests
composer analyse     # Run PHPStan level 8
composer cs-check    # Check code style (dry-run)
composer cs-fix      # Auto-fix code style
```

## Code Style

- `declare(strict_types=1)` in all PHP files
- PSR-12 (enforced by PHP-CS-Fixer)
- 4 spaces indentation, LF line endings, UTF-8
- PHPDoc on all public/protected methods
- PHPStan level 8 must pass with zero errors
