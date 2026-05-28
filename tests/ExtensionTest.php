<?php

declare(strict_types=1);

namespace Simsoft\Twig\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simsoft\Twig\Extension;
use Simsoft\Twig\Twig;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class ExtensionTest extends TestCase
{
    private string $templatePath;

    protected function setUp(): void
    {
        $this->templatePath = sys_get_temp_dir() . '/simsoft_twig_ext_test_' . uniqid();
        mkdir($this->templatePath, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->templatePath)) {
            array_map('unlink', glob($this->templatePath . '/*.twig') ?: []);
            rmdir($this->templatePath);
        }
    }

    private function createTemplate(string $name, string $content): void
    {
        file_put_contents($this->templatePath . '/' . $name . '.twig', $content);
    }

    // =========================================================================
    // Base Extension Class
    // =========================================================================

    #[Test]
    public function it_instantiates_base_extension(): void
    {
        $extension = new class () extends Extension {
        };

        $this->assertInstanceOf(Extension::class, $extension);
    }

    #[Test]
    public function it_returns_empty_globals_by_default(): void
    {
        $extension = new class () extends Extension {
        };

        $this->assertSame([], $extension->getGlobals());
    }

    #[Test]
    public function it_returns_empty_filters_by_default(): void
    {
        $extension = new class () extends Extension {
        };

        $this->assertSame([], $extension->getFilters());
    }

    #[Test]
    public function it_returns_empty_functions_by_default(): void
    {
        $extension = new class () extends Extension {
        };

        $this->assertSame([], $extension->getFunctions());
    }

    #[Test]
    public function it_returns_empty_tests_by_default(): void
    {
        $extension = new class () extends Extension {
        };

        $this->assertSame([], $extension->getTests());
    }

    // =========================================================================
    // Globals
    // =========================================================================

    #[Test]
    public function it_provides_globals_to_templates(): void
    {
        $this->createTemplate('global', '{{ app_name }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\TestExtension()],
        ]);

        $this->assertSame('TestApp', $twig->render('global'));
    }

    #[Test]
    public function it_provides_multiple_globals(): void
    {
        $this->createTemplate('multi', '{{ app_name }} v{{ app_version }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\MultiGlobalExtension()],
        ]);

        $this->assertSame('MyApp v2.0', $twig->render('multi'));
    }

    #[Test]
    public function it_provides_null_global(): void
    {
        $this->createTemplate('null', '{{ nullable is null ? "yes" : "no" }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\NullGlobalExtension()],
        ]);

        $this->assertSame('yes', $twig->render('null'));
    }

    // =========================================================================
    // Filters
    // =========================================================================

    #[Test]
    public function it_registers_filter_via_init(): void
    {
        $this->createTemplate('filter', '{{ "hello"|shout }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\TestExtension()],
        ]);

        $this->assertSame('HELLO', $twig->render('filter'));
    }

    #[Test]
    public function it_registers_filter_with_arguments(): void
    {
        $this->createTemplate('filter_args', '{{ "Hello World"|repeat(3) }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\FilterWithArgsExtension()],
        ]);

        $this->assertSame('Hello WorldHello WorldHello World', $twig->render('filter_args'));
    }

    #[Test]
    public function it_returns_twig_filter_instances(): void
    {
        $extension = new Fixtures\TestExtension();
        $filters = $extension->getFilters();

        $this->assertNotEmpty($filters);
        $this->assertContainsOnlyInstancesOf(TwigFilter::class, $filters);
    }

    #[Test]
    public function it_handles_filter_with_null_input(): void
    {
        $this->createTemplate('null_filter', '{{ value|default_val("fallback") }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\NullSafeFilterExtension()],
        ]);

        $this->assertSame('fallback', $twig->render('null_filter', ['value' => null]));
    }

    // =========================================================================
    // Functions
    // =========================================================================

    #[Test]
    public function it_registers_function_via_init(): void
    {
        $this->createTemplate('func', '{{ greet("Sam") }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\TestExtension()],
        ]);

        $this->assertSame('Hi, Sam!', $twig->render('func'));
    }

    #[Test]
    public function it_registers_function_with_no_arguments(): void
    {
        $this->createTemplate('no_args', '{{ app_version() }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\NoArgFunctionExtension()],
        ]);

        $this->assertSame('1.0.0', $twig->render('no_args'));
    }

    #[Test]
    public function it_registers_function_with_variadic_arguments(): void
    {
        $this->createTemplate('variadic', '{{ sum(1, 2, 3, 4) }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\VariadicFunctionExtension()],
        ]);

        $this->assertSame('10', $twig->render('variadic'));
    }

    #[Test]
    public function it_returns_twig_function_instances(): void
    {
        $extension = new Fixtures\TestExtension();
        $functions = $extension->getFunctions();

        $this->assertNotEmpty($functions);
        $this->assertContainsOnlyInstancesOf(TwigFunction::class, $functions);
    }

    // =========================================================================
    // Tests
    // =========================================================================

    #[Test]
    public function it_registers_test_via_init(): void
    {
        $this->createTemplate('test', '{% if 4 is even_number %}yes{% else %}no{% endif %}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\TestExtension()],
        ]);

        $this->assertSame('yes', $twig->render('test'));
    }

    #[Test]
    public function it_registers_test_that_fails(): void
    {
        $this->createTemplate('test_fail', '{% if 3 is even_number %}yes{% else %}no{% endif %}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\TestExtension()],
        ]);

        $this->assertSame('no', $twig->render('test_fail'));
    }

    #[Test]
    public function it_returns_twig_test_instances(): void
    {
        $extension = new Fixtures\TestExtension();
        $tests = $extension->getTests();

        $this->assertNotEmpty($tests);
        $this->assertContainsOnlyInstancesOf(TwigTest::class, $tests);
    }

    // =========================================================================
    // Init Method
    // =========================================================================

    #[Test]
    public function it_calls_init_on_construction(): void
    {
        $extension = new Fixtures\TestExtension();

        // If init was called, filters/functions/tests should be populated
        $this->assertNotEmpty($extension->getFilters());
        $this->assertNotEmpty($extension->getFunctions());
        $this->assertNotEmpty($extension->getTests());
    }

    #[Test]
    public function it_allows_empty_init(): void
    {
        $extension = new class () extends Extension {
        };

        $this->assertSame([], $extension->getFilters());
        $this->assertSame([], $extension->getFunctions());
        $this->assertSame([], $extension->getTests());
    }

    // =========================================================================
    // Multiple Extensions
    // =========================================================================

    #[Test]
    public function it_supports_multiple_extensions(): void
    {
        $this->createTemplate('multi_ext', '{{ app_name }} - {{ greet("World") }} - {{ app_version() }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [
                new Fixtures\TestExtension(),
                new Fixtures\NoArgFunctionExtension(),
            ],
        ]);

        $this->assertSame('TestApp - Hi, World! - 1.0.0', $twig->render('multi_ext'));
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    #[Test]
    public function it_handles_filter_with_empty_string(): void
    {
        $this->createTemplate('empty_filter', '{{ ""|shout }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\TestExtension()],
        ]);

        $this->assertSame('', $twig->render('empty_filter'));
    }

    #[Test]
    public function it_handles_filter_with_special_characters(): void
    {
        $this->createTemplate('special_filter', '{{ text|shout }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\TestExtension()],
        ]);

        $result = $twig->render('special_filter', ['text' => 'café']);
        $this->assertSame('CAFÉ', $result);
    }

    #[Test]
    public function it_handles_function_returning_empty_string(): void
    {
        $this->createTemplate('empty_func', '[{{ empty_str() }}]');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\EmptyReturnExtension()],
        ]);

        $this->assertSame('[]', $twig->render('empty_func'));
    }

    #[Test]
    public function it_handles_function_returning_zero(): void
    {
        $this->createTemplate('zero_func', '{{ zero() }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\ZeroReturnExtension()],
        ]);

        $this->assertSame('0', $twig->render('zero_func'));
    }
}
