<?php

declare(strict_types=1);

namespace Simsoft\Twig;

use Twig\Extension\ExtensionInterface;

/**
 * TwigConfig class
 *
 * Typed a configuration object for the Twig wrapper.
 * Provides IDE autocompletion and validation as an alternative to array config.
 */
readonly class TwigConfig
{
    /**
     * @param string|string[] $path Template directory path(s).
     * @param string $fileExtension Template file extension.
     * @param bool $debug Enable debug mode.
     * @param string $charset Template charset.
     * @param string|null $cache Compiled template cache directory.
     * @param string|null $timezone Timezone for date formatting.
     * @param ExtensionInterface[] $extensions Extensions to register.
     * @param array<string, string> $namespaces Namespace => path mappings.
     * @param bool $minify Minify HTML output.
     */
    public function __construct(
        public string|array $path = '/',
        public string       $fileExtension = '.twig',
        public bool         $debug = false,
        public string       $charset = 'UTF-8',
        public ?string      $cache = null,
        public ?string      $timezone = null,
        public array        $extensions = [],
        public array        $namespaces = [],
        public bool         $minify = false,
    ) {
    }

    /**
     * Convert to array format compatible with Twig constructor.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $config = [
            'path' => $this->path,
            'fileExtension' => $this->fileExtension,
            'debug' => $this->debug,
            'charset' => $this->charset,
            'minify' => $this->minify,
        ];

        if ($this->cache !== null) {
            $config['cache'] = $this->cache;
        }

        if ($this->timezone !== null) {
            $config['timezone'] = $this->timezone;
        }

        if (!empty($this->extensions)) {
            $config['extensions'] = $this->extensions;
        }

        if (!empty($this->namespaces)) {
            $config['namespaces'] = $this->namespaces;
        }

        return $config;
    }
}
