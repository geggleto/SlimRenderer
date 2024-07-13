<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/PHP-View/blob/3.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Headers;
use Slim\Psr7\Response;
use Slim\Psr7\Stream;
use Slim\Views\Exception\PhpTemplateNotFoundException;
use Slim\Views\PhpRenderer;
use Throwable;
use UnexpectedValueException;

class PhpRendererTest extends TestCase
{
    public function testRenderer(): void
    {
        $renderer = new PhpRenderer(__DIR__ . '/_files/');
        $headers = new Headers();
        $body = $this->createStream();
        $response = new Response(200, $headers, $body);
        $newResponse = $renderer->render($response, 'template.phtml', ['hello' => 'Hi']);
        $newResponse->getBody()->rewind();
        $this->assertEquals('Hi', $newResponse->getBody()->getContents());
    }

    public function testRenderConstructor(): void
    {
        $renderer = new PhpRenderer(__DIR__ . '/_files');
        $headers = new Headers();
        $body = $this->createStream();
        $response = new Response(200, $headers, $body);
        $newResponse = $renderer->render($response, 'template.phtml', ['hello' => 'Hi']);
        $newResponse->getBody()->rewind();
        $this->assertEquals('Hi', $newResponse->getBody()->getContents());
    }

    public function testAttributeMerging(): void
    {
        $renderer = new PhpRenderer(__DIR__ . '/_files/', [
            'hello' => 'Hello'
        ]);
        $headers = new Headers();
        $body = $this->createStream();
        $response = new Response(200, $headers, $body);
        $newResponse = $renderer->render($response, 'template.phtml', [
            'hello' => 'Hi'
        ]);
        $newResponse->getBody()->rewind();
        $this->assertEquals('Hi', $newResponse->getBody()->getContents());
    }

    public function testExceptionInTemplate(): void
    {
        $renderer = new PhpRenderer(__DIR__ . '/_files/');
        $headers = new Headers();
        $body = $this->createStream();
        $response = new Response(200, $headers, $body);
        try {
            $newResponse = $renderer->render($response, 'exception_layout.phtml');
        } catch (Throwable $t) {
            // Simulates an error template
            $newResponse = $renderer->render($response, 'template.phtml', [
                'hello' => 'Hi'
            ]);
        }

        $newResponse->getBody()->rewind();
        $this->assertEquals('Hi', $newResponse->getBody()->getContents());
    }

    public function testExceptionForTemplateInData(): void
    {
        $renderer = new PhpRenderer(__DIR__ . '/_files/');
        $headers = new Headers();
        $body = $this->createStream();
        $response = new Response(200, $headers, $body);
        $this->expectException(InvalidArgumentException::class);
        $renderer->render($response, 'template.phtml', [
            'template' => 'Hi'
        ]);
    }

    public function testTemplateNotFound(): void
    {
        $renderer = new PhpRenderer(__DIR__ . '/_files/');
        $headers = new Headers();
        $body = $this->createStream();
        $response = new Response(200, $headers, $body);
        $this->expectException(PhpTemplateNotFoundException::class);
        $renderer->render($response, 'adfadftemplate.phtml', []);
    }

    public function testLayout(): void
    {
        $renderer = new PhpRenderer(__DIR__ . '/_files/', ['title' => 'My App']);
        $renderer->setLayout('layout.phtml');
        $headers = new Headers();
        $body = $this->createStream();
        $response = new Response(200, $headers, $body);
        $newResponse = $renderer->render($response, 'template.phtml', ['title' => 'Hello - My App', 'hello' => 'Hi']);
        $newResponse->getBody()->rewind();
        $this->assertEquals(
            '<html><head><title>Hello - My App</title></head><body>Hi<footer>This is the footer'
            . '</footer></body></html>',
            $newResponse->getBody()->getContents()
        );
    }

    public function testLayoutConstructor(): void
    {
        $renderer = new PhpRenderer(__DIR__ . '/_files', ['title' => 'My App'], 'layout.phtml');
        $headers = new Headers();
        $body = $this->createStream();
        $response = new Response(200, $headers, $body);
        $newResponse = $renderer->render($response, 'template.phtml', ['title' => 'Hello - My App', 'hello' => 'Hi']);
        $newResponse->getBody()->rewind();
        $this->assertEquals(
            '<html><head><title>Hello - My App</title></head><body>Hi<footer>This is the footer'
            . '</footer></body></html>',
            $newResponse->getBody()->getContents()
        );
    }

    public function testExceptionInLayout(): void
    {
        $renderer = new PhpRenderer(__DIR__ . '/_files/');
        $renderer->setLayout('exception_layout.phtml');
        $headers = new Headers();
        $body = $this->createStream();
        $response = new Response(200, $headers, $body);
        try {
            $newResponse = $renderer->render($response, 'template.phtml');
        } catch (Throwable $t) {
            // PHP 7+
            // Simulates an error template
            $renderer->setLayout('');
            $newResponse = $renderer->render($response, 'template.phtml', [
                'hello' => 'Hi'
            ]);
        }

        $newResponse->getBody()->rewind();
        $this->assertEquals('Hi', $newResponse->getBody()->getContents());
    }

    public function testLayoutNotFound(): void
    {
        $renderer = new PhpRenderer(__DIR__ . '/_files/');
        $this->expectException(PhpTemplateNotFoundException::class);
        $renderer->setLayout('non-existent_layout.phtml');
    }

    public function testContentDataKeyShouldBeIgnored(): void
    {
        $renderer = new PhpRenderer(__DIR__ . '/_files/');
        $renderer->setLayout('layout.phtml');
        $headers = new Headers();
        $body = $this->createStream();
        $response = new Response(200, $headers, $body);
        $newResponse = $renderer->render(
            $response,
            'template.phtml',
            ['title' => 'Hello - My App', 'hello' => 'Hi', 'content' => 'Ho']
        );
        $newResponse->getBody()->rewind();
        $this->assertEquals(
            '<html><head><title>Hello - My App</title></head><body>Hi<footer>This is the footer'
            . '</footer></body></html>',
            $newResponse->getBody()->getContents()
        );
    }

    public function testTemplateExists(): void
    {
        $renderer = new PhpRenderer(__DIR__ . '/_files/');
        $this->assertTrue($renderer->templateExists('layout.phtml'));
        $this->assertFalse($renderer->templateExists('non-existant-template'));
    }

    private function createStream(): Stream
    {
        $resource = fopen('php://temp', 'r+');
        if ($resource === false) {
            throw new UnexpectedValueException();
        }

        return new Stream($resource);
    }
}
