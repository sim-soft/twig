<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Simsoft\Twig\Twig;
use Example\Extensions\MyExtension;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

try {
    $twig = new Twig([
        'path' => 'templates',
        'fileExtension' => '.twig',
        'debug' => true, // default is false
        'charset' => 'UTF-8',
        'cache' => 'cache',
        'extensions' => [new MyExtension()],
        /*'namespaces' => [
            'name' => '/path/to/template',
        ],*/
    ]);

    /*$twig->display('index', [
        'name' => 'John',
    ]);*/

    var_dump($twig->render('index', [
        'name' => 'John Doe',
    ]));

} catch (LoaderError|RuntimeError|SyntaxError $error) {
    echo $error->getMessage();
}
