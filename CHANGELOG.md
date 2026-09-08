# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres
to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.0.0] - 2026-09-08

### Added

- `HtmlMinifier` class implementing tag-aware HTML minification
- Regression test suite for minification (35 tests)
- Validation of the `path` config option

### Breaking

- `path` is now required. It previously defaulted to `/`, which resolved to the
  current working directory and silently exposed arbitrary project files as
  renderable templates. Omitting `path`, or passing an empty string or array,
  now throws an `InvalidArgumentException`.
- `TwigConfig::__construct()` now takes `$path` as a required first argument.
  Code calling `new TwigConfig()` with no arguments must pass a path.

  Migration — add an explicit path:

  ```php
  // Before (silently used the working directory)
  $twig = new Twig(['cache' => __DIR__ . '/cache']);

  // After
  $twig = new Twig([
      'path' => __DIR__ . '/templates',
      'cache' => __DIR__ . '/cache',
  ]);
  ```

### Fixed

- Minification no longer corrupts `<script>` and `<style>` bodies. Previously
  any `-->` inside JavaScript or CSS — including inside a string literal — was
  treated as a comment terminator and the surrounding code was deleted.
- Minification no longer collapses whitespace inside `<pre>` and `<textarea>`,
  which destroyed indentation and user-entered content.
- Minification no longer rewrites attribute values containing `>` or `<`
  (e.g. `title="a >   < b"`).
- Whitespace between inline elements now collapses to a single space instead of
  being removed, so adjacent words are no longer joined together.
- Conditional comments are now preserved regardless of casing; previously
  `<!--[If IE]>` was stripped while `<!--[if IE]>` was kept.
- Minification no longer discards content when given invalid UTF-8.

### Changed

- `Twig::minify()` delegates to `HtmlMinifier`. The method signature and
  behaviour on well-formed input are unchanged.
- `twig/twig` constraint narrowed from `^3.0` to `^3.27`. Every release below
  3.27.0 is affected by published security advisories and cannot be installed
  under Composer's default audit policy, so `^3.0` claimed support for
  versions that were neither installable nor tested.
- CI now runs against both the lowest and highest allowed `twig/twig` release
  instead of only the newest, and runs weekly so that upstream releases are
  caught without a push to this repository.
- CI now tests PHP 8.5, which has been stable since November 2025 and was
  already permitted by the `^8.2` constraint but never exercised.
- Added an advisory `twig/twig` 4.x job. It is `continue-on-error` and does
  not gate merges; 4.x remains outside the supported constraint.
- PHPUnit now fails on deprecations, notices and warnings raised from `src/`.
  Twig announces removals through `trigger_deprecation()`, which uses
  `@trigger_error()`; `ignoreSuppressionOfDeprecations` is enabled so these
  are not silently discarded.

## [2.0.0] - 2026-05-28

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
- Comprehensive unit test suite (114 tests, 139 assertions)
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

## [1.0.1] - 2025-07-19

### Added

- `timezone` config option, applied to Twig's `CoreExtension`

## [1.0.0] - 2024-04-04

### Added

- Initial release: `Twig` wrapper class and `Extension` base class

[Unreleased]: https://github.com/sim-soft/twig/compare/3.0.0...HEAD

[3.0.0]: https://github.com/sim-soft/twig/compare/2.0.0...3.0.0

[2.0.0]: https://github.com/sim-soft/twig/compare/1.0.1...2.0.0

[1.0.1]: https://github.com/sim-soft/twig/compare/1.0.0...1.0.1

[1.0.0]: https://github.com/sim-soft/twig/releases/tag/1.0.0
