# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres
to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `TwigConfig` typed DTO as alternative to array configuration
- `addTest()` method on `Twig` class for adding custom tests at runtime
- `exists()` method to check if a template exists
- `renderIf()` method — renders template if it exists, returns empty string
  otherwise
- Config validation — throws `InvalidArgumentException` on unrecognized keys
- Support for multiple template paths (array of paths)
- `charset` config option now applied to Twig Environment
- `declare(strict_types=1)` in all source and test files
- PHPStan static analysis (level 8)
- PHP-CS-Fixer for code style enforcement (PSR-12)
- GitHub Actions CI workflow with code coverage
- Comprehensive unit test suite (101 tests, 124 assertions)
- `CONTRIBUTING.md` with development guidelines

### Fixed

- `debug` config now respects the actual value instead of always being `true`
- `TemplateWrapper` objects no longer have file extension appended incorrectly
- PHPDoc `@link` annotations point to correct Twig documentation sections

### Changed

- Minimum PHP version bumped to 8.2
- Config array no longer stored as class property (reduced memory footprint)
- Improved PHPDoc types with generic array annotations
- `.gitattributes` cleaned up with proper `export-ignore` rules
- Updated `twig/twig` to 3.27.0 (resolved all security advisories)
