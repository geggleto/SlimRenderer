
[![Latest Version on Packagist](https://img.shields.io/github/release/slimphp/php-view.svg)](https://packagist.org/packages/slim/PHP-View)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)
[![Build Status](https://github.com/slimphp/PHP-View/actions/workflows/tests.yml/badge.svg?branch=3.x)](https://github.com/slimphp/PHP-View/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/slim/PHP-View.svg)](https://packagist.org/packages/slim/PHP-View/stats)


## PHP Renderer

This is a renderer for rendering PHP view scripts into a PSR-7 Response object. It works well with Slim Framework 4.


### Cross-site scripting (XSS) risks

Note that PHP-View has no built-in mitigation from XSS attacks. 
It is the developer's responsibility to use `htmlspecialchars()` 
or a component like [laminas-escaper](https://github.com/laminas/laminas-escaper). Alternatively, consider  [Twig-View](https://github.com/slimphp/Twig-View).

## Installation

```
composer require slim/php-view
```

## Usage with any PSR-7 Project

```php
//Construct the View
$renderer = new PhpRenderer('path/to/templates');

$viewData = [
    'key1' => 'value1',
    'key2' => 'value2',
];

// Render a template
$response = $renderer->render(new Response(), 'hello.php', $viewData);
```

## Usage with Slim 4

```php
use Slim\AppFactory;
use Slim\Views\PhpRenderer;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->get('/hello', function ($request, $response) {
    $renderer = new PhpRenderer('path/to/templates');
    
    $viewData = [
        'name' => 'John',
    ];
    
    return $renderer->render($response, 'hello.php', $viewData);
});

$app->run();
```

## DI Container Setup

You can place the `PhpRenderer` instantiation within your DI Container.

```php
<?php

use Psr\Container\ContainerInterface;
use Slim\Views\PhpRenderer;
// ...

return [
    PhpRenderer::class => function (ContainerInterface $container) {
        $renderer = new PhpRenderer('path/to/templates');

        return $renderer;
    },
];

```

## Template Variables

You can now add variables to your renderer that will be available to all templates you render.

```php
// Via the constructor
$globalViewData = [
    'title' => 'Title'
];

$renderer = new PhpRenderer('path/to/templates', $globalViewData);

// or setter
$viewData = [
    'key1' => 'value1',
    'key2' => 'value2',
];
$renderer->setAttributes($viewData);

// or individually
$renderer->addAttribute($key, $value);
```

Data passed in via the `render()` method takes precedence over attributes.

```php
$viewData = [
    'title' => 'Title'
];
$renderer = new PhpRenderer('path/to/templates', $viewData);

//...

$response = $renderer->render($response, $template, [
    'title' => 'My Title'
]);

// In the view above, the $title will be "My Title" and not "Title"
```

## Sub-templates

Inside your templates you may use `$this` to refer to the PhpRenderer object to render sub-templates. 
If using a layout the `fetch()` method can be used instead of `render()` to avoid applying the layout to the sub-template.

```php
<?=$this->fetch('./path/to/partial.phtml', ['name' => 'John'])?>
```

## Rendering in Layouts

You can now render view in another views called layouts, 
this allows you to compose modular view templates
and help keep your views DRY.

Create your layout `path/to/templates/layout.php`

```php
<html><head><title><?=$title?></title></head><body><?=$content?></body></html>
```

Create your view template `path/to/templates/hello.php`

```php
Hello <?=$name?>!
```

Rendering in your code.

```php
$renderer = new PhpRenderer('path/to/templates', ['title' => 'My App']);
$renderer->setLayout('layout.php');

$viewData = [
    'title' => 'Hello - My App',
    'name' => 'John',
];

//...

$response = $renderer->render($response, 'hello.php', $viewData);
```

Response will be

```html
<html><head><title>Hello - My App</title></head><body>Hello John!</body></html>
```

Please note, the `$content` is special variable used inside layouts 
to render the wrapped view and should not be set in your view parameters.

## Escaping values

It's essential to ensure that the HTML output is secure to 
prevent common web vulnerabilities like Cross-Site Scripting (XSS).
This package has no built-in mitigation from XSS attacks.

The following function uses the `htmlspecialchars` function 
with specific flags to ensure proper encoding:

```php
function html(string $text = null): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
```

You could consider setting it up as a global function in [composer.json](https://getcomposer.org/doc/04-schema.md#files).

**Usage**

```php
Hello <?= html($name) ?>
```

## Exceptions

* `\Slim\Views\Exception\PhpTemplateNotFoundException` - If template layout does not exist
* `\Slim\Views\Exception\PhpTemplateNotFoundException` - If template does not exist
* `\RuntimeException` - If the template output could not be fetched
* `\InvalidArgumentException` - If $data contains 'template'
