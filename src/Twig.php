<?php

declare(strict_types=1);

namespace Simsoft\Twig;

use InvalidArgumentException;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\CoreExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Twig class
 *
 * A lightweight wrapper around the Twig template engine.
 */
class Twig
{
    /** @var string[] Valid configuration keys */
    private const VALID_CONFIG_KEYS = [
        'path',
        'fileExtension',
        'debug',
        'charset',
        'cache',
        'timezone',
        'extensions',
        'namespaces',
        'minify',
    ];

    /** @var Environment Twig environment. */
    protected Environment $twig;

    /** @var string Template file extension */
    protected string $fileExtension = '.twig';

    /** @var bool Whether to minify HTML output */
    protected bool $minify = false;

    /**
     * Constructor.
     *
     * @param array<string, mixed>|TwigConfig $config Configuration options:
     *     - path: string|string[] Template directory path(s)
     *     - fileExtension: string Template file extension (default: '.twig')
     *     - debug: bool Enable debug mode (default: false)
     *     - Charset: string Template charset (default: 'UTF-8')
     *     - cache: string Compiled template cache directory
     *     - timezone: string Timezone for date formatting
     *     - extensions: ExtensionInterface[] Extensions to register
     *     - namespaces: array<string, string> Namespace => path mappings
     *     - Minify: bool Minify HTML output (default: false)
     * @throws LoaderError
     * @throws InvalidArgumentException If an unrecognized config key is provided.
     */
    public function __construct(array|TwigConfig $config)
    {
        if ($config instanceof TwigConfig) {
            $config = $config->toArray();
        }

        $this->validateConfig($config);
        $this->twig = $this->buildEngine($config);
    }

    /**
     * Validate configuration keys.
     *
     * @param array<string, mixed> $config
     * @throws InvalidArgumentException
     */
    protected function validateConfig(array $config): void
    {
        $invalidKeys = array_diff(array_keys($config), self::VALID_CONFIG_KEYS);

        if (!empty($invalidKeys)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unrecognized configuration key(s): "%s". Valid keys are: %s',
                    implode('", "', $invalidKeys),
                    implode(', ', self::VALID_CONFIG_KEYS),
                ),
            );
        }
    }

    /**
     * Build a template engine from configuration.
     *
     * @param array<string, mixed> $config
     * @return Environment
     * @throws LoaderError
     */
    protected function buildEngine(array $config): Environment
    {
        $options = [];

        if (array_key_exists('cache', $config)) {
            $options['cache'] = $config['cache'];
        }

        if (array_key_exists('debug', $config)) {
            $options['debug'] = (bool)$config['debug'];
        }

        if (array_key_exists('charset', $config)) {
            $options['charset'] = $config['charset'];
        }

        if (array_key_exists('fileExtension', $config)) {
            $this->fileExtension = $config['fileExtension'];
        }

        if (array_key_exists('minify', $config)) {
            $this->minify = (bool)$config['minify'];
        }

        $path = $config['path'] ?? '/';
        $loader = new FilesystemLoader($path);

        if (array_key_exists('namespaces', $config) && is_array($config['namespaces'])) {
            foreach ($config['namespaces'] as $namespace => $namespacePath) {
                $loader->addPath($namespacePath, $namespace);
            }
        }

        $twig = new Environment($loader, $options);

        if (!empty($config['timezone'])) {
            $twig->getExtension(CoreExtension::class)->setTimezone($config['timezone']);
        }

        if (array_key_exists('extensions', $config)) {
            foreach ($config['extensions'] as $extension) {
                $twig->addExtension($extension);
            }
        }

        return $twig;
    }

    /**
     * Add extension.
     *
     * @param ExtensionInterface $extension
     * @return $this
     */
    public function addExtension(ExtensionInterface $extension): static
    {
        $this->twig->addExtension($extension);
        return $this;
    }

    /**
     * Add custom filter.
     *
     * @link https://twig.symfony.com/doc/3.x/advanced.html#filters
     *
     * @param string $name The filter name.
     * @param callable $callable The filter execution.
     * @param array<string, mixed> $options Filter options.
     * @return $this
     */
    public function addFilter(string $name, callable $callable, array $options = []): static
    {
        $this->twig->addFilter(new TwigFilter($name, $callable, $options));
        return $this;
    }

    /**
     * Add custom function.
     *
     * @link https://twig.symfony.com/doc/3.x/advanced.html#functions
     *
     * @param string $name The function name.
     * @param callable $callable The function execution.
     * @param array<string, mixed> $options The function options.
     * @return $this
     */
    public function addFunction(string $name, callable $callable, array $options = []): static
    {
        $this->twig->addFunction(new TwigFunction($name, $callable, $options));
        return $this;
    }

    /**
     * Add custom test.
     *
     * @link https://twig.symfony.com/doc/3.x/advanced.html#tests
     *
     * @param string $name The test name.
     * @param callable $callable The test execution.
     * @param array<string, mixed> $options The test options.
     * @return $this
     */
    public function addTest(string $name, callable $callable, array $options = []): static
    {
        $this->twig->addTest(new TwigTest($name, $callable, $options));
        return $this;
    }

    /**
     * Get Twig environment instance.
     *
     * @return Environment
     */
    public function getInstance(): Environment
    {
        return $this->twig;
    }

    /**
     * Check if minification is enabled.
     *
     * @return bool
     */
    public function isMinified(): bool
    {
        return $this->minify;
    }

    /**
     * Check if a template exists.
     *
     * @param string $name Template name (without file extension).
     * @return bool
     */
    public function exists(string $name): bool
    {
        return $this->twig->getLoader()->exists($name . $this->fileExtension);
    }

    /**
     * Registers global context.
     *
     * @param string|array<string, mixed> $name Variable name or associative array of name => value pairs.
     * @param mixed|null $value The value when $name is a string.
     * @return $this
     */
    public function share(string|array $name, mixed $value = null): static
    {
        if (is_array($name)) {
            foreach ($name as $contextName => $contextValue) {
                $this->twig->addGlobal($contextName, $contextValue);
            }
        } else {
            $this->twig->addGlobal($name, $value);
        }

        return $this;
    }

    /**
     * Resolve the template name with a file extension.
     *
     * @param string|TemplateWrapper $name
     * @return string|TemplateWrapper
     */
    protected function resolveTemplateName(string|TemplateWrapper $name): string|TemplateWrapper
    {
        if ($name instanceof TemplateWrapper) {
            return $name;
        }

        return $name . $this->fileExtension;
    }

    /**
     * Apply minification if enabled.
     *
     * @param string $html
     * @return string
     */
    protected function applyMinify(string $html): string
    {
        if (!$this->minify) {
            return $html;
        }

        return self::minify($html);
    }

    /**
     * Get rendered template content.
     *
     * @param string|TemplateWrapper $name Name of the template file.
     * @param array<string, mixed> $context Additional context shared to a template file.
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string|TemplateWrapper $name, array $context = []): string
    {
        $html = $this->twig->render($this->resolveTemplateName($name), $context);

        return $this->applyMinify($html);
    }

    /**
     * Render a template only if it exists, otherwise return an empty string.
     *
     * @param string $name Name of the template file.
     * @param array<string, mixed> $context Additional context shared to a template file.
     * @return string
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function renderIf(string $name, array $context = []): string
    {
        if (!$this->exists($name)) {
            return '';
        }

        return $this->render($name, $context);
    }

    /**
     * Get rendered template block content.
     *
     * @param string|TemplateWrapper $name Name of the template file.
     * @param string $blockName The block name from the template file.
     * @param array<string, mixed> $context Additional context shared to a template file.
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Throwable
     */
    public function renderBlock(string|TemplateWrapper $name, string $blockName, array $context = []): string
    {
        $templateName = $this->resolveTemplateName($name);

        if ($templateName instanceof TemplateWrapper) {
            $html = $templateName->renderBlock($blockName, $context);
        } else {
            $html = $this->twig
                ->load($templateName)
                ->renderBlock($blockName, $context);
        }

        return $this->applyMinify($html);
    }

    /**
     * Display template content.
     *
     * When minify is enabled, the output is buffered, minified, then echoed.
     *
     * @param string|TemplateWrapper $name Name of the template file.
     * @param array<string, mixed> $context Additional context shared to a template file.
     * @return void
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function display(string|TemplateWrapper $name, array $context = []): void
    {
        if ($this->minify) {
            echo $this->render($name, $context);

            return;
        }

        $this->twig->display($this->resolveTemplateName($name), $context);
    }

    /**
     * Minify an HTML string.
     *
     * Removes HTML comments (except conditionals), collapses whitespace
     * between tags, and trims the result.
     *
     * @param string $html Raw HTML content.
     * @return string Minified HTML.
     */
    public static function minify(string $html): string
    {
        // Remove HTML comments (but preserve IE conditional comments)
        $html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html) ?? $html;

        // Remove whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html) ?? $html;

        // Collapse multiple whitespace into a single space
        $html = preg_replace('/\s{2,}/', ' ', $html) ?? $html;

        return trim($html);
    }
}
