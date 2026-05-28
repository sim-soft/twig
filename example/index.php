<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Example\Extensions\MyExtension;
use Simsoft\Twig\Twig;
use Simsoft\Twig\TwigConfig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

try {
    // Option 1: Array configuration
    $twig = new Twig([
        'path' => __DIR__ . '/templates',
        'fileExtension' => '.twig',
        'debug' => true,
        'charset' => 'UTF-8',
        'extensions' => [new MyExtension()],
        'namespaces' => [
            'layouts' => __DIR__ . '/templates/layouts',
        ],
    ]);

    // Option 2: Typed config object (IDE-friendly)
    // $twig = new Twig(new TwigConfig(
    //     path: __DIR__ . '/templates',
    //     debug: true,
    //     extensions: [new MyExtension()],
    //     namespaces: ['layouts' => __DIR__ . '/templates/layouts'],
    // ));

    // Render to string
    $html = $twig->render('index', [
        'name' => 'John Doe',
    ]);

    echo $html;

    // Conditional rendering
    $optional = $twig->renderIf('optional_sidebar', ['items' => []]);
    echo $optional; // Empty string if template doesn't exist

} catch (LoaderError|RuntimeError|SyntaxError $error) {
    echo $error->getMessage();
}
