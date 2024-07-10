
[![Build Status](https://github.com/slimphp/PHP-View/actions/workflows/tests.yml/badge.svg?branch=3.x)](https://github.com/slimphp/PHP-View/actions)
[![Latest Stable Version](https://poser.pugx.org/slim/PHP-View/v)](https://packagist.org/packages/slim/PHP-View)
[![License](https://poser.pugx.org/slim/PHP-View/license)](https://packagist.org/packages/slim/PHP-View)

## PHP Renderer

This is a renderer for rendering PHP view scripts into a PSR-7 Response object. It works well with Slim Framework 4.


### Cross-site scripting (XSS) risks

Note that PHP-View has no built-in mitigation from XSS attacks. It is the developer's responsibility to use `htmlspecialchars()` or a component like [laminas-escaper](https://github.com/laminas/laminas-escaper). Alternatively, consider  [Twig-View](https://github.com/slimphp/Twig-View).


## Installation

Install with [Composer](http://getcomposer.org):

```
composer require slim/php-view
```

## Usage with Slim 4

```php
use Slim\Views\PhpRenderer;

include "vendor/autoload.php";

$app = Slim\AppFactory::create();

$app->get('/hello/{name}', function ($request, $response, $args) {
    $renderer = new PhpRenderer('path/to/templates');
    return $renderer->render($response, "hello.php", $args);
});

$app->run();
```

Note that you could place the PhpRenderer instantiation within your DI Container. 

## Usage with any PSR-7 Project

```php
//Construct the View
$phpView = new PhpRenderer("path/to/templates");

//Render a Template
$response = $phpView->render(new Response(), "hello.php", $yourData);
```

## Template Variables
You can now add variables to your renderer that will be available to all templates you render.

```php
// via the constructor
$templateVariables = [
    "title" => "Title"
];
$phpView = new PhpRenderer("path/to/templates", $templateVariables);

// or setter
$phpView->setAttributes($templateVariables);

// or individually
$phpView->addAttribute($key, $value);
```

Data passed in via `->render()` takes precedence over attributes.

```php
$templateVariables = [
    "title" => "Title"
];
$phpView = new PhpRenderer("path/to/templates", $templateVariables);

//...

$phpView->render($response, $template, [
    "title" => "My Title"
]);
// In the view above, the $title will be "My Title" and not "Title"
```

## Sub-templates

Inside your templates you may use `$this` to refer to the PhpRenderer object to render sub-templates. 
If using a layout the `fetch()` method can be used instead of `render()` to avoid appling the layout to the sub-template.

```phtml
<?=$this->fetch('./path/to/partial.phtml', ["name" => "John"])?>
```

## Rendering in Layouts
You can now render view in another views called layouts, this allows you to compose modular view templates
and help keep your views DRY.

Create your layout `path/to/templates/layout.php`.
```phtml
<html><head><title><?=$title?></title></head><body><?=$content?></body></html>
```

Create your view template `path/to/templates/hello.php`.
```phtml
Hello <?=$name?>!
```

Rendering in your code.
```php
$phpView = new PhpRenderer("path/to/templates", ["title" => "My App"]);
$phpView->setLayout("layout.php");

//...

$phpview->render($response, "hello.php", ["title" => "Hello - My App", "name" => "John"]);
```

Response will be
```html
<html><head><title>Hello - My App</title></head><body>Hello John!</body></html>
```

Please note, the $content is special variable used inside layouts to render the wrapped view and should not be set
in your view paramaters.

## Exceptions
`\RuntimeException` - if template does not exist

`\InvalidArgumentException` - if $data contains 'template'
