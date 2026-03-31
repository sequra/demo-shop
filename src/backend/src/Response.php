<?php

declare(strict_types=1);

namespace SeQura\Demo;

/**
 * HTTP Response object.
 *
 * Provides static factories for common response types and sends headers + body.
 */
final class Response
{
    /**
     * @param string $body Response body.
     * @param int $status HTTP status code.
     * @param array<string, string> $headers Response headers.
     */
    public function __construct(
        private readonly string $body = '',
        private readonly int $status = 200,
        private array $headers = [],
    ) {
    }

    /**
     * Create a JSON response.
     *
     * @param mixed $data Data to encode as JSON.
     * @param int $status HTTP status code.
     *
     * @return self
     */
    public static function json(mixed $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    /**
     * Create an HTML response.
     *
     * @param string $content HTML content.
     * @param int $status HTTP status code.
     *
     * @return self
     */
    public static function html(string $content, int $status = 200): self
    {
        return new self(
            $content,
            $status,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    /**
     * Create an HTML response by rendering a PHP view template.
     *
     * @param string $view Template name (without .php extension).
     * @param array<string, mixed> $vars Variables to extract into the template scope.
     * @param int $status HTTP status code.
     *
     * @return self
     */
    public static function view(string $view, array $vars = [], int $status = 200): self
    {
        extract($vars, EXTR_SKIP);
        ob_start();

        include __DIR__ . '/../views/' . $view . '.php';
        $content = ob_get_clean();

        return self::html($content !== false ? $content : '', $status);
    }

    /**
     * Create an empty response (204 No Content by default).
     *
     * @param int $status HTTP status code.
     *
     * @return self
     */
    public static function empty(int $status = 204): self
    {
        return new self('', $status, []);
    }

    /**
     * Return a new Response with an additional header.
     *
     * @param string $name Header name.
     * @param string $value Header value.
     *
     * @return self
     */
    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    /**
     * Send the response to the client.
     *
     * Sets the HTTP status code, sends all headers, and echoes the body.
     *
     * @return void
     */
    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        echo $this->body;
    }

    /**
     * Get the HTTP status code.
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Get the response body.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get all response headers.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
