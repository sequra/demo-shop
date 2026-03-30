<?php

declare(strict_types=1);

namespace SeQura\Demo;

/**
 * Immutable value object representing a single HTTP route.
 */
final readonly class Route
{
    /**
     * @param string $method HTTP method (GET, POST, PUT, ANY).
     * @param string $pattern URI pattern with {param} placeholders.
     * @param array{0: class-string, 1: string} $handler Controller/method pair.
     */
    public function __construct(
        public string $method,
        public string $pattern,
        public array $handler,
    ) {
    }

    /**
     * Create a GET route.
     *
     * @param string $pattern URI pattern.
     * @param array{0: class-string, 1: string} $handler Controller/method pair.
     *
     * @return self
     */
    public static function get(string $pattern, array $handler): self
    {
        return new self('GET', $pattern, $handler);
    }

    /**
     * Create a POST route.
     *
     * @param string $pattern URI pattern.
     * @param array{0: class-string, 1: string} $handler Controller/method pair.
     *
     * @return self
     */
    public static function post(string $pattern, array $handler): self
    {
        return new self('POST', $pattern, $handler);
    }

    /**
     * Create a PUT route.
     *
     * @param string $pattern URI pattern.
     * @param array{0: class-string, 1: string} $handler Controller/method pair.
     *
     * @return self
     */
    public static function put(string $pattern, array $handler): self
    {
        return new self('PUT', $pattern, $handler);
    }

    /**
     * Create a route matching any HTTP method.
     *
     * @param string $pattern URI pattern.
     * @param array{0: class-string, 1: string} $handler Controller/method pair.
     *
     * @return self
     */
    public static function any(string $pattern, array $handler): self
    {
        return new self('ANY', $pattern, $handler);
    }
}
