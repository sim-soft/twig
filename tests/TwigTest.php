<?php

declare(strict_types=1);

namespace Simsoft\Twig\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simsoft\Twig\Twig;
use Simsoft\Twig\TwigConfig;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class TwigTest extends TestCase
{
    private string $templatePath;

    protected function setUp(): void
    {
        $this->templatePath = sys_get_temp_dir() . '/simsoft_twig_test_' . uniqid();
        mkdir($this->templatePath, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->cleanDirectory($this->templatePath);
    }

    private function cleanDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->cleanDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function createTemplate(string $name, string $content, ?string $subdir = null): void
    {
        $dir = $subdir ? $this->templatePath . DIRECTORY_SEPARATOR . $subdir : $this->templatePath;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dir . DIRECTORY_SEPARATOR . $name . '.twig', $content);
    }

    // =========================================================================
    // Constructor & Configuration
    // =========================================================================

    #[Test]
    public function it_creates_instance_with_minimal_config(): void
    {
        $this->createTemplate('test', 'hello');
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertInstanceOf(Twig::class, $twig);
    }

    #[Test]
    public function it_creates_instance_with_full_config(): void
    {
        $this->createTemplate('test', 'hello');
        $cachePath = $this->templatePath . '/cache';
        mkdir($cachePath, 0777, true);

        $twig = new Twig([
            'path' => $this->templatePath,
            'fileExtension' => '.twig',
            'debug' => true,
            'charset' => 'UTF-8',
            'cache' => $cachePath,
            'timezone' => 'UTC',
        ]);

        $this->assertInstanceOf(Twig::class, $twig);
    }

    #[Test]
    public function it_uses_custom_file_extension(): void
    {
        file_put_contents($this->templatePath . '/page.html', 'custom ext');

        $twig = new Twig([
            'path' => $this->templatePath,
            'fileExtension' => '.html',
        ]);

        $this->assertSame('custom ext', $twig->render('page'));
    }

    #[Test]
    public function it_throws_loader_error_for_invalid_path(): void
    {
        $this->expectException(LoaderError::class);

        new Twig(['path' => '/nonexistent/path/that/does/not/exist']);
    }

    #[Test]
    public function it_returns_environment_instance(): void
    {
        $this->createTemplate('test', 'hello');
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertInstanceOf(Environment::class, $twig->getInstance());
    }

    // =========================================================================
    // Rendering
    // =========================================================================

    #[Test]
    public function it_renders_simple_template(): void
    {
        $this->createTemplate('hello', 'Hello, {{ name }}!');
        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig->render('hello', ['name' => 'World']);

        $this->assertSame('Hello, World!', $result);
    }

    #[Test]
    public function it_renders_template_without_context(): void
    {
        $this->createTemplate('static', 'Static content');
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertSame('Static content', $twig->render('static'));
    }

    #[Test]
    public function it_renders_template_with_multiple_variables(): void
    {
        $this->createTemplate('multi', '{{ first }} {{ last }} ({{ age }})');
        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig->render('multi', [
            'first' => 'John',
            'last' => 'Doe',
            'age' => 30,
        ]);

        $this->assertSame('John Doe (30)', $result);
    }

    #[Test]
    public function it_renders_template_with_array_context(): void
    {
        $this->createTemplate('list', '{% for item in items %}{{ item }}{% endfor %}');
        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig->render('list', ['items' => ['a', 'b', 'c']]);

        $this->assertSame('abc', $result);
    }

    #[Test]
    public function it_renders_template_with_nested_context(): void
    {
        $this->createTemplate('nested', '{{ user.name }} - {{ user.email }}');
        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig->render('nested', [
            'user' => ['name' => 'Alice', 'email' => 'alice@example.com'],
        ]);

        $this->assertSame('Alice - alice@example.com', $result);
    }

    #[Test]
    public function it_throws_syntax_error_for_invalid_template(): void
    {
        $this->createTemplate('bad', '{{ unclosed');
        $twig = new Twig(['path' => $this->templatePath]);

        $this->expectException(SyntaxError::class);
        $twig->render('bad');
    }

    #[Test]
    public function it_throws_loader_error_for_missing_template(): void
    {
        $twig = new Twig(['path' => $this->templatePath]);

        $this->expectException(LoaderError::class);
        $twig->render('nonexistent');
    }

    // =========================================================================
    // Display
    // =========================================================================

    #[Test]
    public function it_displays_template_output(): void
    {
        $this->createTemplate('display', 'Displayed: {{ msg }}');
        $twig = new Twig(['path' => $this->templatePath]);

        ob_start();
        $twig->display('display', ['msg' => 'test']);
        $output = ob_get_clean();

        $this->assertSame('Displayed: test', $output);
    }

    #[Test]
    public function it_displays_template_without_context(): void
    {
        $this->createTemplate('plain', 'No variables here');
        $twig = new Twig(['path' => $this->templatePath]);

        ob_start();
        $twig->display('plain');
        $output = ob_get_clean();

        $this->assertSame('No variables here', $output);
    }

    // =========================================================================
    // Render Block
    // =========================================================================

    #[Test]
    public function it_renders_specific_block(): void
    {
        $template = <<<TWIG
{% block header %}Header Content{% endblock %}
{% block body %}Body Content{% endblock %}
{% block footer %}Footer: {{ year }}{% endblock %}
TWIG;
        $this->createTemplate('blocks', $template);
        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig->renderBlock('blocks', 'header');
        $this->assertSame('Header Content', $result);

        $result = $twig->renderBlock('blocks', 'footer', ['year' => 2026]);
        $this->assertSame('Footer: 2026', $result);
    }

    #[Test]
    public function it_throws_runtime_error_for_missing_block(): void
    {
        $this->createTemplate('noblock', '{% block existing %}content{% endblock %}');
        $twig = new Twig(['path' => $this->templatePath]);

        $this->expectException(RuntimeError::class);
        $twig->renderBlock('noblock', 'nonexistent');
    }

    // =========================================================================
    // Namespaces
    // =========================================================================

    #[Test]
    public function it_supports_template_namespaces(): void
    {
        $layoutsPath = $this->templatePath . '/layouts';
        mkdir($layoutsPath, 0777, true);
        file_put_contents($layoutsPath . '/base.twig', 'Layout: {{ title }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'namespaces' => [
                'layouts' => $layoutsPath,
            ],
        ]);

        $result = $twig->render('@layouts/base', ['title' => 'Home']);
        $this->assertSame('Layout: Home', $result);
    }

    #[Test]
    public function it_supports_multiple_namespaces(): void
    {
        $layoutsPath = $this->templatePath . '/layouts';
        $componentsPath = $this->templatePath . '/components';
        mkdir($layoutsPath, 0777, true);
        mkdir($componentsPath, 0777, true);

        file_put_contents($layoutsPath . '/main.twig', 'Layout');
        file_put_contents($componentsPath . '/btn.twig', 'Button');

        $twig = new Twig([
            'path' => $this->templatePath,
            'namespaces' => [
                'layouts' => $layoutsPath,
                'components' => $componentsPath,
            ],
        ]);

        $this->assertSame('Layout', $twig->render('@layouts/main'));
        $this->assertSame('Button', $twig->render('@components/btn'));
    }

    // =========================================================================
    // Share (Global Context)
    // =========================================================================

    #[Test]
    public function it_shares_single_global_variable(): void
    {
        $this->createTemplate('global', '{{ site_name }}');
        $twig = new Twig(['path' => $this->templatePath]);
        $twig->share('site_name', 'My Site');

        $this->assertSame('My Site', $twig->render('global'));
    }

    #[Test]
    public function it_shares_multiple_globals_via_array(): void
    {
        $this->createTemplate('globals', '{{ app }} v{{ version }}');
        $twig = new Twig(['path' => $this->templatePath]);
        $twig->share(['app' => 'TestApp', 'version' => '1.0']);

        $this->assertSame('TestApp v1.0', $twig->render('globals'));
    }

    #[Test]
    public function it_shares_null_value(): void
    {
        $this->createTemplate('nullable', '{{ value is null ? "null" : "not null" }}');
        $twig = new Twig(['path' => $this->templatePath]);
        $twig->share('value', null);

        $this->assertSame('null', $twig->render('nullable'));
    }

    #[Test]
    public function it_shares_array_as_global(): void
    {
        $this->createTemplate('arr', '{% for i in items %}{{ i }}{% endfor %}');
        $twig = new Twig(['path' => $this->templatePath]);
        $twig->share('items', ['x', 'y', 'z']);

        $this->assertSame('xyz', $twig->render('arr'));
    }

    #[Test]
    public function it_returns_fluent_interface_from_share(): void
    {
        $this->createTemplate('test', '');
        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig->share('key', 'value');

        $this->assertSame($twig, $result);
    }

    // =========================================================================
    // Filters
    // =========================================================================

    #[Test]
    public function it_adds_custom_filter(): void
    {
        $this->createTemplate('filter', '{{ name|reverse_str }}');
        $twig = new Twig(['path' => $this->templatePath]);
        $twig->addFilter('reverse_str', fn(string $s) => strrev($s));

        $this->assertSame('dlroW', $twig->render('filter', ['name' => 'World']));
    }

    #[Test]
    public function it_adds_filter_with_multiple_arguments(): void
    {
        $this->createTemplate('filter_args', '{{ text|truncate(5, "...") }}');
        $twig = new Twig(['path' => $this->templatePath]);
        $twig->addFilter('truncate', function (string $text, int $length, string $suffix = '') {
            return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . $suffix : $text;
        });

        $this->assertSame('Hello...', $twig->render('filter_args', ['text' => 'Hello World']));
    }

    #[Test]
    public function it_returns_fluent_interface_from_add_filter(): void
    {
        $this->createTemplate('test', '');
        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig->addFilter('noop', fn($v) => $v);

        $this->assertSame($twig, $result);
    }

    // =========================================================================
    // Functions
    // =========================================================================

    #[Test]
    public function it_adds_custom_function(): void
    {
        $this->createTemplate('func', '{{ add(2, 3) }}');
        $twig = new Twig(['path' => $this->templatePath]);
        $twig->addFunction('add', fn(int $a, int $b) => $a + $b);

        $this->assertSame('5', $twig->render('func'));
    }

    #[Test]
    public function it_adds_function_returning_html(): void
    {
        $this->createTemplate('html_func', '{{ badge("new")|raw }}');
        $twig = new Twig(['path' => $this->templatePath]);
        $twig->addFunction('badge', fn(string $text) => "<span class=\"badge\">{$text}</span>");

        $this->assertSame('<span class="badge">new</span>', $twig->render('html_func'));
    }

    #[Test]
    public function it_adds_function_with_no_arguments(): void
    {
        $this->createTemplate('no_args', '{{ current_year() }}');
        $twig = new Twig(['path' => $this->templatePath]);
        $twig->addFunction('current_year', fn() => '2026');

        $this->assertSame('2026', $twig->render('no_args'));
    }

    #[Test]
    public function it_returns_fluent_interface_from_add_function(): void
    {
        $this->createTemplate('test', '');
        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig->addFunction('noop', fn() => '');

        $this->assertSame($twig, $result);
    }

    // =========================================================================
    // Extensions
    // =========================================================================

    #[Test]
    public function it_loads_extensions_from_config(): void
    {
        $this->createTemplate('ext', '{{ app_name }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'extensions' => [new Fixtures\TestExtension()],
        ]);

        $this->assertSame('TestApp', $twig->render('ext'));
    }

    #[Test]
    public function it_adds_extension_after_construction(): void
    {
        $this->createTemplate('ext', '{{ app_name }}');
        $twig = new Twig(['path' => $this->templatePath]);
        $twig->addExtension(new Fixtures\TestExtension());

        $this->assertSame('TestApp', $twig->render('ext'));
    }

    #[Test]
    public function it_returns_fluent_interface_from_add_extension(): void
    {
        $this->createTemplate('test', '');
        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig->addExtension(new Fixtures\TestExtension());

        $this->assertSame($twig, $result);
    }

    // =========================================================================
    // Timezone Configuration
    // =========================================================================

    #[Test]
    public function it_sets_timezone(): void
    {
        $this->createTemplate('tz', '{{ date|date("e") }}');
        $twig = new Twig([
            'path' => $this->templatePath,
            'timezone' => 'Asia/Kuala_Lumpur',
        ]);

        $result = $twig->render('tz', ['date' => '2026-01-01 00:00:00']);
        $this->assertSame('Asia/Kuala_Lumpur', $result);
    }

    // =========================================================================
    // Caching
    // =========================================================================

    #[Test]
    public function it_uses_cache_directory(): void
    {
        $cachePath = $this->templatePath . '/cache';
        mkdir($cachePath, 0777, true);

        $this->createTemplate('cached', 'Cached: {{ val }}');

        $twig = new Twig([
            'path' => $this->templatePath,
            'cache' => $cachePath,
        ]);

        // First render compiles and caches
        $result = $twig->render('cached', ['val' => 'first']);
        $this->assertSame('Cached: first', $result);

        // Cache directory should now contain files
        $cacheFiles = glob($cachePath . '/**/*') ?: glob($cachePath . '/*');
        $this->assertNotEmpty($cacheFiles);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    #[Test]
    public function it_handles_empty_template(): void
    {
        $this->createTemplate('empty', '');
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertSame('', $twig->render('empty'));
    }

    #[Test]
    public function it_handles_template_with_only_whitespace(): void
    {
        $this->createTemplate('whitespace', '   ');
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertSame('   ', $twig->render('whitespace'));
    }

    #[Test]
    public function it_handles_special_characters_in_context(): void
    {
        $this->createTemplate('special', '{{ text }}');
        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig->render('special', ['text' => '<script>alert("xss")</script>']);
        $this->assertSame('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $result);
    }

    #[Test]
    public function it_handles_unicode_content(): void
    {
        $this->createTemplate('unicode', '{{ text }}');
        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig->render('unicode', ['text' => '日本語テスト 🎉']);
        $this->assertSame('日本語テスト 🎉', $result);
    }

    #[Test]
    public function it_handles_large_context_array(): void
    {
        $vars = [];
        $parts = [];
        for ($i = 0; $i < 100; $i++) {
            $vars["var_{$i}"] = "value_{$i}";
            $parts[] = "{{ var_{$i} }}";
        }

        $this->createTemplate('large', implode('|', $parts));
        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig->render('large', $vars);
        $this->assertStringContainsString('value_0', $result);
        $this->assertStringContainsString('value_99', $result);
    }

    #[Test]
    public function it_handles_boolean_context_values(): void
    {
        $this->createTemplate('bool', '{% if active %}yes{% else %}no{% endif %}');
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertSame('yes', $twig->render('bool', ['active' => true]));
        $this->assertSame('no', $twig->render('bool', ['active' => false]));
    }

    #[Test]
    public function it_handles_integer_zero_in_context(): void
    {
        $this->createTemplate('zero', '{{ count }}');
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertSame('0', $twig->render('zero', ['count' => 0]));
    }

    #[Test]
    public function it_handles_empty_string_in_context(): void
    {
        $this->createTemplate('emptystr', '[{{ val }}]');
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertSame('[]', $twig->render('emptystr', ['val' => '']));
    }

    #[Test]
    public function it_handles_template_inheritance(): void
    {
        $this->createTemplate('base', '<!DOCTYPE html><html>{% block content %}{% endblock %}</html>');
        $this->createTemplate('child', '{% extends "base.twig" %}{% block content %}Hello{% endblock %}');

        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig->render('child');
        $this->assertSame('<!DOCTYPE html><html>Hello</html>', $result);
    }

    #[Test]
    public function it_handles_template_includes(): void
    {
        $this->createTemplate('partial', 'Partial: {{ name }}');
        $this->createTemplate('main', 'Before|{% include "partial.twig" %}|After');

        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig->render('main', ['name' => 'Test']);
        $this->assertSame('Before|Partial: Test|After', $result);
    }

    #[Test]
    public function it_handles_conditional_logic(): void
    {
        $template = '{% if score >= 90 %}A{% elseif score >= 80 %}B{% else %}C{% endif %}';
        $this->createTemplate('grade', $template);
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertSame('A', $twig->render('grade', ['score' => 95]));
        $this->assertSame('B', $twig->render('grade', ['score' => 85]));
        $this->assertSame('C', $twig->render('grade', ['score' => 70]));
    }

    #[Test]
    public function it_handles_loop_with_empty_array(): void
    {
        $this->createTemplate('loop', '{% for item in items %}{{ item }}{% else %}empty{% endfor %}');
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertSame('empty', $twig->render('loop', ['items' => []]));
    }

    #[Test]
    public function it_chains_multiple_operations(): void
    {
        $this->createTemplate('chain', '{{ greeting }} {{ site }}');
        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig
            ->share('site', 'MySite')
            ->addFilter('upper', fn(string $s) => strtoupper($s))
            ->addFunction('now', fn() => '2026')
            ->render('chain', ['greeting' => 'Hello']);

        $this->assertSame('Hello MySite', $result);
    }

    // =========================================================================
    // Data Provider Tests
    // =========================================================================

    public static function scalarValuesProvider(): array
    {
        return [
            'string' => ['hello', 'hello'],
            'integer' => [42, '42'],
            'float' => [3.14, '3.14'],
            'boolean true' => [true, '1'],
            'empty string' => ['', ''],
        ];
    }

    #[Test]
    #[DataProvider('scalarValuesProvider')]
    public function it_renders_scalar_values(mixed $input, string $expected): void
    {
        $this->createTemplate('scalar', '{{ val }}');
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertSame($expected, $twig->render('scalar', ['val' => $input]));
    }

    // =========================================================================
    // TemplateWrapper Support
    // =========================================================================

    #[Test]
    public function it_renders_with_template_wrapper(): void
    {
        $this->createTemplate('wrapper', 'Wrapped: {{ val }}');
        $twig = new Twig(['path' => $this->templatePath]);

        $template = $twig->getInstance()->load('wrapper.twig');
        $result = $twig->render($template, ['val' => 'test']);

        $this->assertSame('Wrapped: test', $result);
    }

    #[Test]
    public function it_displays_with_template_wrapper(): void
    {
        $this->createTemplate('wrapper_display', 'Display: {{ msg }}');
        $twig = new Twig(['path' => $this->templatePath]);

        $template = $twig->getInstance()->load('wrapper_display.twig');

        ob_start();
        $twig->display($template, ['msg' => 'hello']);
        $output = ob_get_clean();

        $this->assertSame('Display: hello', $output);
    }

    #[Test]
    public function it_renders_block_with_template_wrapper(): void
    {
        $this->createTemplate('wrapper_block', '{% block title %}Block: {{ name }}{% endblock %}');
        $twig = new Twig(['path' => $this->templatePath]);

        $template = $twig->getInstance()->load('wrapper_block.twig');
        $result = $twig->renderBlock($template, 'title', ['name' => 'test']);

        $this->assertSame('Block: test', $result);
    }

    // =========================================================================
    // Charset Configuration
    // =========================================================================

    #[Test]
    public function it_applies_charset_config(): void
    {
        $this->createTemplate('charset', 'test');
        $twig = new Twig([
            'path' => $this->templatePath,
            'charset' => 'ISO-8859-1',
        ]);

        $this->assertSame('ISO-8859-1', $twig->getInstance()->getCharset());
    }

    // =========================================================================
    // Debug Configuration
    // =========================================================================

    #[Test]
    public function it_respects_debug_false(): void
    {
        $this->createTemplate('debug', 'test');
        $twig = new Twig([
            'path' => $this->templatePath,
            'debug' => false,
        ]);

        $this->assertFalse($twig->getInstance()->isDebug());
    }

    #[Test]
    public function it_respects_debug_true(): void
    {
        $this->createTemplate('debug', 'test');
        $twig = new Twig([
            'path' => $this->templatePath,
            'debug' => true,
        ]);

        $this->assertTrue($twig->getInstance()->isDebug());
    }

    // =========================================================================
    // addTest() Method
    // =========================================================================

    #[Test]
    public function it_adds_custom_test(): void
    {
        $this->createTemplate('custom_test', '{% if 4 is divisible_by(2) %}yes{% else %}no{% endif %}');
        $twig = new Twig(['path' => $this->templatePath]);
        $twig->addTest('divisible_by', fn(int $value, int $divisor) => $value % $divisor === 0);

        $this->assertSame('yes', $twig->render('custom_test'));
    }

    #[Test]
    public function it_adds_custom_test_that_fails(): void
    {
        $this->createTemplate('custom_test_fail', '{% if 5 is divisible_by(2) %}yes{% else %}no{% endif %}');
        $twig = new Twig(['path' => $this->templatePath]);
        $twig->addTest('divisible_by', fn(int $value, int $divisor) => $value % $divisor === 0);

        $this->assertSame('no', $twig->render('custom_test_fail'));
    }

    #[Test]
    public function it_returns_fluent_interface_from_add_test(): void
    {
        $this->createTemplate('test', '');
        $twig = new Twig(['path' => $this->templatePath]);

        $result = $twig->addTest('noop', fn($v) => true);

        $this->assertSame($twig, $result);
    }

    // =========================================================================
    // exists() Method
    // =========================================================================

    #[Test]
    public function it_returns_true_for_existing_template(): void
    {
        $this->createTemplate('exists_test', 'content');
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertTrue($twig->exists('exists_test'));
    }

    #[Test]
    public function it_returns_false_for_nonexistent_template(): void
    {
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertFalse($twig->exists('nonexistent'));
    }

    #[Test]
    public function it_checks_existence_with_custom_extension(): void
    {
        file_put_contents($this->templatePath . '/page.html', 'content');

        $twig = new Twig([
            'path' => $this->templatePath,
            'fileExtension' => '.html',
        ]);

        $this->assertTrue($twig->exists('page'));
        $this->assertFalse($twig->exists('missing'));
    }

    // =========================================================================
    // Multiple Paths Support
    // =========================================================================

    #[Test]
    public function it_accepts_array_of_paths(): void
    {
        $path1 = $this->templatePath . '/dir1';
        $path2 = $this->templatePath . '/dir2';
        mkdir($path1, 0777, true);
        mkdir($path2, 0777, true);

        file_put_contents($path1 . '/from_dir1.twig', 'Dir1');
        file_put_contents($path2 . '/from_dir2.twig', 'Dir2');

        $twig = new Twig(['path' => [$path1, $path2]]);

        $this->assertSame('Dir1', $twig->render('from_dir1'));
        $this->assertSame('Dir2', $twig->render('from_dir2'));
    }

    // =========================================================================
    // Config Validation
    // =========================================================================

    #[Test]
    public function it_throws_on_unrecognized_config_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unrecognized configuration key(s): "debg"');

        new Twig([
            'path' => $this->templatePath,
            'debg' => true,
        ]);
    }

    #[Test]
    public function it_throws_on_multiple_unrecognized_keys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unrecognized configuration key(s): "foo", "bar"');

        new Twig([
            'path' => $this->templatePath,
            'foo' => 'x',
            'bar' => 'y',
        ]);
    }

    #[Test]
    public function it_accepts_all_valid_config_keys(): void
    {
        $cachePath = $this->templatePath . '/cache';
        mkdir($cachePath, 0777, true);
        $this->createTemplate('test', 'ok');

        $twig = new Twig([
            'path' => $this->templatePath,
            'fileExtension' => '.twig',
            'debug' => true,
            'charset' => 'UTF-8',
            'cache' => $cachePath,
            'timezone' => 'UTC',
            'extensions' => [],
            'namespaces' => [],
        ]);

        $this->assertSame('ok', $twig->render('test'));
    }

    // =========================================================================
    // renderIf() Method
    // =========================================================================

    #[Test]
    public function it_renders_if_template_exists(): void
    {
        $this->createTemplate('sidebar', 'Sidebar content');
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertSame('Sidebar content', $twig->renderIf('sidebar'));
    }

    #[Test]
    public function it_returns_empty_string_if_template_missing(): void
    {
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertSame('', $twig->renderIf('nonexistent_template'));
    }

    #[Test]
    public function it_passes_context_to_render_if(): void
    {
        $this->createTemplate('greeting', 'Hello {{ name }}');
        $twig = new Twig(['path' => $this->templatePath]);

        $this->assertSame('Hello World', $twig->renderIf('greeting', ['name' => 'World']));
    }

    // =========================================================================
    // TwigConfig DTO
    // =========================================================================

    #[Test]
    public function it_accepts_twig_config_object(): void
    {
        $this->createTemplate('dto', 'DTO works');

        $config = new TwigConfig(path: $this->templatePath);
        $twig = new Twig($config);

        $this->assertSame('DTO works', $twig->render('dto'));
    }

    #[Test]
    public function it_accepts_twig_config_with_all_options(): void
    {
        $cachePath = $this->templatePath . '/cache';
        mkdir($cachePath, 0777, true);
        $this->createTemplate('full_dto', '{{ msg }}');

        $config = new TwigConfig(
            path: $this->templatePath,
            fileExtension: '.twig',
            debug: true,
            charset: 'UTF-8',
            cache: $cachePath,
            timezone: 'UTC',
        );
        $twig = new Twig($config);

        $this->assertSame('hello', $twig->render('full_dto', ['msg' => 'hello']));
        $this->assertTrue($twig->getInstance()->isDebug());
    }

    #[Test]
    public function it_converts_twig_config_to_array(): void
    {
        $config = new TwigConfig(
            path: '/templates',
            debug: true,
            timezone: 'UTC',
            namespaces: ['layouts' => '/layouts'],
        );

        $array = $config->toArray();

        $this->assertSame('/templates', $array['path']);
        $this->assertTrue($array['debug']);
        $this->assertSame('UTC', $array['timezone']);
        $this->assertSame(['layouts' => '/layouts'], $array['namespaces']);
        $this->assertArrayNotHasKey('cache', $array);
    }

    // =========================================================================
    // Minify (Config-driven)
    // =========================================================================

    #[Test]
    public function it_minifies_render_output_when_enabled(): void
    {
        $template = "<div>\n    <p>Hello {{ name }}</p>\n</div>";
        $this->createTemplate('minify', $template);
        $twig = new Twig(['path' => $this->templatePath, 'minify' => true]);

        $result = $twig->render('minify', ['name' => 'World']);

        $this->assertSame('<div><p>Hello World</p></div>', $result);
    }

    #[Test]
    public function it_does_not_minify_when_disabled(): void
    {
        $template = "<div>\n    <p>Hello</p>\n</div>";
        $this->createTemplate('no_minify', $template);
        $twig = new Twig(['path' => $this->templatePath, 'minify' => false]);

        $result = $twig->render('no_minify');

        $this->assertSame("<div>\n    <p>Hello</p>\n</div>", $result);
    }

    #[Test]
    public function it_minifies_display_output_when_enabled(): void
    {
        $template = "<div>\n    <span>Test</span>\n</div>";
        $this->createTemplate('minify_display', $template);
        $twig = new Twig(['path' => $this->templatePath, 'minify' => true]);

        ob_start();
        $twig->display('minify_display');
        $output = ob_get_clean();

        $this->assertSame('<div><span>Test</span></div>', $output);
    }

    #[Test]
    public function it_minifies_render_block_when_enabled(): void
    {
        $template = "{% block content %}\n    <p>Block content</p>\n{% endblock %}";
        $this->createTemplate('minify_block', $template);
        $twig = new Twig(['path' => $this->templatePath, 'minify' => true]);

        $result = $twig->renderBlock('minify_block', 'content');

        $this->assertSame('<p>Block content</p>', $result);
    }

    #[Test]
    public function it_minifies_render_if_when_enabled(): void
    {
        $template = "<nav>\n    <a>Link</a>\n</nav>";
        $this->createTemplate('minify_if', $template);
        $twig = new Twig(['path' => $this->templatePath, 'minify' => true]);

        $result = $twig->renderIf('minify_if');

        $this->assertSame('<nav><a>Link</a></nav>', $result);
    }

    #[Test]
    public function it_removes_html_comments_when_minified(): void
    {
        $template = "<!-- navigation -->\n<nav>Menu</nav>\n<!-- end navigation -->";
        $this->createTemplate('comments', $template);
        $twig = new Twig(['path' => $this->templatePath, 'minify' => true]);

        $result = $twig->render('comments');

        $this->assertSame('<nav>Menu</nav>', $result);
    }

    #[Test]
    public function it_preserves_conditional_comments_when_minified(): void
    {
        $template = "<!--[if IE]><link href=\"ie.css\"><![endif]-->\n<div>Content</div>";
        $this->createTemplate('conditional', $template);
        $twig = new Twig(['path' => $this->templatePath, 'minify' => true]);

        $result = $twig->render('conditional');

        $this->assertStringContainsString('<!--[if IE]>', $result);
        $this->assertStringContainsString('<div>Content</div>', $result);
    }

    #[Test]
    public function it_collapses_multiple_whitespace_when_minified(): void
    {
        $template = '<p>Hello     World</p>';
        $this->createTemplate('spaces', $template);
        $twig = new Twig(['path' => $this->templatePath, 'minify' => true]);

        $result = $twig->render('spaces');

        $this->assertSame('<p>Hello World</p>', $result);
    }

    #[Test]
    public function it_reports_minify_status(): void
    {
        $this->createTemplate('test', '');
        $enabled = new Twig(['path' => $this->templatePath, 'minify' => true]);
        $disabled = new Twig(['path' => $this->templatePath, 'minify' => false]);

        $this->assertTrue($enabled->isMinified());
        $this->assertFalse($disabled->isMinified());
    }

    #[Test]
    public function it_enables_minify_via_twig_config(): void
    {
        $template = "<div>\n    <p>DTO</p>\n</div>";
        $this->createTemplate('minify_dto', $template);

        $config = new TwigConfig(path: $this->templatePath, minify: true);
        $twig = new Twig($config);

        $this->assertSame('<div><p>DTO</p></div>', $twig->render('minify_dto'));
    }

    // =========================================================================
    // Static Minify Helper
    // =========================================================================

    #[Test]
    public function it_minifies_static_string(): void
    {
        $html = "<html>\n  <body>\n    <h1>Title</h1>\n  </body>\n</html>";

        $result = Twig::minify($html);

        $this->assertSame('<html><body><h1>Title</h1></body></html>', $result);
    }

    #[Test]
    public function it_minifies_empty_string(): void
    {
        $this->assertSame('', Twig::minify(''));
    }

    #[Test]
    public function it_minifies_already_minified_html(): void
    {
        $html = '<div><p>Already minified</p></div>';

        $this->assertSame($html, Twig::minify($html));
    }
}
