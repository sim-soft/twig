<?php

namespace Simsoft\Twig;

use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig class
 *
 */
class Twig
{
    /** @var null|Environment Twig environment. */
    protected ?Environment $twig = null;

    /** @var string Template file extension */
    protected string $fileExtension = '.twig';

    /**
     * Constructor.
     *
     * @param array $config
     * @throws LoaderError
     */
    public function __construct(protected array $config)
    {
        $this->getEngine();
    }

    /**
     * Build template engine.
     *
     * @return Environment
     * @throws LoaderError
     */
    protected function getEngine(): Environment
    {
        if ($this->twig === null) {
            $options = [];

            if (array_key_exists('cache', $this->config)) {
                $options['cache'] = $this->config['cache'];
            }

            if (array_key_exists('debug', $this->config)) {
                $options['debug'] = true;
            }

            if (array_key_exists('fileExtension', $this->config)) {
                $this->fileExtension = $this->config['fileExtension'];
            }

            $loader = new FilesystemLoader($this->config['path'] ?? '/');

            if (array_key_exists('namespaces', $this->config) && is_array($this->config['namespaces'])) {
                foreach ($this->config['namespaces'] as $namespace => $path) {
                    $loader->addPath($path, $namespace);
                }
            }

            $this->twig = new Environment($loader, $options);

            if (array_key_exists('extensions', $this->config)) {
                foreach ($this->config['extensions'] as $extensionClass) {
                    $this->twig->addExtension($extensionClass);
                }
            }
        }

        return $this->twig;
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
     * @link https://twig.symfony.com/doc/3.x/advanced.html#environment-aware-filters Filter options reference.
     *
     * @param string $name The filter name.
     * @param callable $callable The filter execution.
     * @param array $options Filter options.
     * @return $this
     * @throws LoaderError
     */
    public function addFilter(string $name, callable $callable, array $options = []): static
    {
        $this->getEngine()->addFilter(new TwigFilter($name, $callable, $options));
        return $this;
    }

    /**
     * Add custom function.
     *
     * @link https://twig.symfony.com/doc/3.x/advanced.html#environment-aware-filters Filter options reference.
     *
     * @param string $name The function name.
     * @param callable $callable The function execution.
     * @param array $options The function options.
     * @return $this
     * @throws LoaderError
     */
    public function addFunction(string $name, callable $callable, array $options = []): static
    {
        $this->getEngine()->addFunction(new TwigFunction($name, $callable, $options));
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
     * Registers global context.
     *
     * @param string|array $name
     * @param mixed|null $value
     * @return $this
     */
    public function share(string|array $name, mixed $value = null): static
    {
        if (is_array($name)) {
            foreach ($name as $contextName => $value) {
                $this->twig->addGlobal($contextName, $value);
            }
        } else {
            $this->twig->addGlobal($name, $value);
        }

        return $this;
    }

    /**
     * Get rendered template content.
     *
     * @param string|TemplateWrapper $name Name of the template file.
     * @param array $context Additional context share to template file.
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string|TemplateWrapper $name, array $context = []): string
    {
        return $this->twig->render($name . $this->fileExtension, $context);
    }

    /**
     * Get rendered template block content.
     *
     * @param string|TemplateWrapper $name Name of the template file.
     * @param string $blockName The block name from the template file.
     * @param array $context Additional context share to template file.
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Throwable
     */
    public function renderBlock(string|TemplateWrapper $name, string $blockName, array $context = []): string
    {
        return $this->twig
            ->load($name . $this->fileExtension)
            ->renderBlock($blockName, $context);
    }

    /**
     * Display template content.
     *
     * @param string|TemplateWrapper $name Name of the template file.
     * @param array $context Additional context share to template file.
     * @return void
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function display(string|TemplateWrapper $name, array $context = []): void
    {
        $this->twig->display($name . $this->fileExtension, $context);
    }

}
