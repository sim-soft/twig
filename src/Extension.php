<?php

namespace Simsoft\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Extension class.
 *
 * Implement Twig extension.
 */
class Extension extends AbstractExtension implements GlobalsInterface
{
    /** @var TwigFilter[] Filters */
    protected array $filters = [];

    /** @var TwigFunction[] Functions */
    protected array $functions = [];

    /** @var TwigTest[] Tests */
    protected array $tests = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize.
     *
     * @return void
     */
    protected function init(): void
    {

    }

    /**
     * @inheritdoc
     * @return array'
     */
    public function getGlobals(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @inheritdoc
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }

    /**
     * @inheritdoc
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return $this->tests;
    }

    /**
     * Add custom filter.
     *
     * @link https://twig.symfony.com/doc/3.x/advanced.html#environment-aware-filters Filter options reference.
     *
     * @param string $name The filter name.
     * @param callable $callable The filter execution.
     * @param array $options Filter options.
     * @return void
     */
    public function addFilter(string $name, callable $callable, array $options = []): void
    {
        $this->filters[] = new TwigFilter($name, $callable, $options);
    }

    /**
     * Add custom function.
     *
     * @link https://twig.symfony.com/doc/3.x/advanced.html#environment-aware-filters Filter options reference.
     *
     * @param string $name The function name.
     * @param callable $callable The function execution.
     * @param array $options The function options.
     * @return void
     */
    public function addFunction(string $name, callable $callable, array $options = []): void
    {
        $this->functions[] = new TwigFunction($name, $callable, $options);
    }

    /**
     * Add custom test.
     *
     * @link https://twig.symfony.com/doc/3.x/advanced.html#environment-aware-filters Filter options reference.
     *
     * @param string $name The test name.
     * @param callable $callable The test execution.
     * @param array $options The test options.
     * @return void
     */
    public function addTest(string $name, callable $callable, array $options = []): void
    {
        $this->tests[] = new TwigTest($name, $callable, $options);
    }
}
