# Contributing

Contributions are welcome. Please follow these guidelines.

## Requirements

- PHP 8.2+
- Composer

## Setup

```shell
git clone https://github.com/simsoft/twig.git
cd twig
composer install
```

## Workflow

1. Fork the repository and create a feature branch from `main`
2. Write tests for any new functionality
3. Ensure all checks pass before submitting a PR:

```shell
# Tests
composer test

# Static analysis (PHPStan level 8)
composer analyse

# Code style
composer cs-check

# Auto-fix code style
composer cs-fix
```

## Code Standards

- PSR-12 coding style (enforced by PHP-CS-Fixer)
- `declare(strict_types=1)` in all PHP files
- PHPDoc on all public and protected methods
- PHPStan level 8 must pass with zero errors

## Pull Requests

- Keep PRs focused on a single change
- Include tests for new features or bug fixes
- Update `CHANGELOG.md` under `[Unreleased]`
- Rebase on `main` before submitting
