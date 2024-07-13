<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/PHP-View/blob/3.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Views;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Slim\Views\Exception\PhpTemplateNotFoundException;
use Throwable;

class PhpRenderer
{
    protected string $templatePath;

    /**
     * @var array<string, mixed>
     */
    protected array $attributes;

    protected string $layout;

    /**
     * @param string $templatePath
     * @param array<string, mixed> $attributes
     * @param string $layout
     */
    public function __construct(string $templatePath = '', array $attributes = [], string $layout = '')
    {
        $this->templatePath = rtrim($templatePath, '/\\') . '/';
        $this->attributes = $attributes;
        $this->setLayout($layout);
    }

    /**
     * @param ResponseInterface $response
     * @param string $template
     * @param array<string, mixed> $data
     *
     * @throws Throwable
     *
     * @return ResponseInterface
     */
    public function render(ResponseInterface $response, string $template, array $data = []): ResponseInterface
    {
        $output = $this->fetch($template, $data, true);
        $response->getBody()->write($output);
        return $response;
    }

    /**
     * @return string
     */
    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * @param string $layout
     *
     * @throws PhpTemplateNotFoundException
     *
     * @return void
     */
    public function setLayout(string $layout): void
    {
        if ($layout && !$this->templateExists($layout)) {
            throw new PhpTemplateNotFoundException('Layout template "' . $layout . '" does not exist');
        }

        $this->layout = $layout;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return void
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function addAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * @param string $key
     *
     * @return bool|mixed
     */
    public function getAttribute(string $key)
    {
        if (!isset($this->attributes[$key])) {
            return false;
        }

        return $this->attributes[$key];
    }

    /**
     * @return string
     */
    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    /**
     * @param string $templatePath
     */
    public function setTemplatePath(string $templatePath): void
    {
        $this->templatePath = rtrim($templatePath, '/\\') . '/';
    }

    /**
     * @param string $template
     * @param array<string, mixed> $data
     * @param bool $useLayout
     *
     * @throws Throwable
     *
     * @return string
     */
    public function fetch(string $template, array $data = [], bool $useLayout = false): string
    {
        $output = $this->fetchTemplate($template, $data);
        if ($this->layout && $useLayout) {
            $data['content'] = $output;
            $output = $this->fetchTemplate($this->layout, $data);
        }

        return $output;
    }

    /**
     * @param string $template
     * @param array<string, mixed> $data
     *
     * @throws Throwable
     *
     * @return string
     */
    public function fetchTemplate(string $template, array $data = []): string
    {
        if (isset($data['template'])) {
            throw new InvalidArgumentException('Duplicate template key found');
        }

        if (!$this->templateExists($template)) {
            throw new PhpTemplateNotFoundException(
                'View cannot render "' . $template . '" because the template does not exist'
            );
        }

        $data = array_merge($this->attributes, $data);
        try {
            ob_start();
            $this->protectedIncludeScope($this->templatePath . $template, $data);
            $output = ob_get_clean();
            if ($output === false) {
                throw new RuntimeException('Failed to fetch the template output');
            }
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return $output;
    }

    /**
     * Returns true is template exists, false if not
     *
     * @param string $template
     *
     * @return bool
     */
    public function templateExists(string $template): bool
    {
        $path = $this->templatePath . $template;
        return is_file($path) && is_readable($path);
    }

    /**
     * @param string $template
     * @param array<string, mixed> $data
     *
     * @return void
     */
    protected function protectedIncludeScope(string $template, array $data): void
    {
        extract($data);
        include func_get_arg(0);
    }
}
