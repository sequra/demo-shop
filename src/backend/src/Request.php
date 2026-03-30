<?php

declare(strict_types=1);

namespace SeQura\Demo;

/**
 * HTTP Request wrapper.
 *
 * Wraps superglobals ($_SERVER, $_GET, php://input) into an immutable request object.
 */
final class Request
{
    /** @var array<string, string> */
    public array $params = [] {
        get {
            return $this->params;
        }
        set {
            $this->params = $value;
        }
    }

    /**
     * @param string $method HTTP method (GET, POST, etc.).
     * @param string $uri Request URI path.
     * @param array<string, string> $queryParams Query string parameters.
     * @param string $rawBody Raw request body.
     * @param array<string, string> $headers HTTP headers (normalised to lowercase keys).
     */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $queryParams,
        private readonly string $rawBody,
        private readonly array $headers,
    ) {
    }

    /**
     * Create a Request from PHP superglobals.
     *
     * @return static
     */
    public static function fromGlobals(): Request
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $queryParams = $_GET;
        $rawBody = file_get_contents('php://input') ?: '';

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }

        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
        }

        return new Request($method, $uri, $queryParams, $rawBody, $headers);
    }

    /**
     * Get the HTTP method.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the request URI path.
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get a query parameter by key.
     *
     * @param string $key Parameter name.
     * @param string|null $default Default value if the key is missing.
     *
     * @return string|null
     */
    public function getQueryParam(string $key, ?string $default = null): ?string
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Get the parsed request body.
     *
     * Returns decoded JSON (as array) if the Content-Type is application/json,
     * otherwise falls back to parse_str for form-encoded data.
     *
     * @return array<string, mixed>
     */
    public function getBody(): array
    {
        $contentType = $this->getHeader('content-type') ?? '';

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($this->rawBody, true);

            return is_array($decoded) ? $decoded : [];
        }

        parse_str($this->rawBody, $parsed);

        return $parsed;
    }

    /**
     * Get the raw (unparsed) request body.
     *
     * @return string
     */
    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    /**
     * Get a header value by name (case-insensitive).
     *
     * @param string $name Header name.
     *
     * @return string|null
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
