<?php

declare(strict_types=1);

namespace SeQura\Demo;

use ReflectionClass;
use ReflectionException;
use SeQura\Core\Infrastructure\ServiceRegister;

/**
 * Simple HTTP router with pattern-based route matching.
 *
 * Supports {param} placeholders that are converted to regex named groups.
 * The special {path} placeholder captures nested path segments (e.g. /a/b/c).
 */
final readonly class Router
{
    /**
     * @param Route[] $routes The routes to match against.
     */
    public function __construct(private array $routes)
    {
    }

    /**
     * Dispatch the request to the matching route handler.
     *
     * Iterates over registered routes and returns the first match.
     * Returns a 404 JSON response if no route matches.
     *
     * @param Request $request The incoming HTTP request.
     *
     * @return Response
     *
     * @throws ReflectionException
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        foreach ($this->routes as $route) {
            if ($route->method !== 'ANY' && $route->method !== $method) {
                continue;
            }

            $regex = $this->patternToRegex($route->pattern);

            if (preg_match($regex, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request->params = $params;

                [$className, $methodName] = $route->handler;

                $controller = $this->resolveController($className);

                return $controller->$methodName($request);
            }
        }

        return Response::json(['error' => 'Not Found'], 404);
    }

    /**
     * Resolve a controller, injecting constructor dependencies via ServiceRegister.
     *
     * @param class-string $className The controller class name.
     *
     * @return object
     *
     * @throws ReflectionException
     */
    private function resolveController(string $className): object
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if (!$constructor || $constructor->getNumberOfParameters() === 0) {
            return new $className();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType()?->getName();
            $args[] = $type ? ServiceRegister::getService($type) : null;
        }

        return $reflection->newInstanceArgs($args);
    }

    /**
     * Convert a URI pattern with {param} placeholders to a regex.
     *
     * The special placeholder {path} uses (.+) to capture nested path segments.
     * All other placeholders use ([^/]+) to capture a single segment.
     *
     * @param string $pattern URI pattern.
     *
     * @return string Regex pattern.
     */
    private function patternToRegex(string $pattern): string
    {
        $regex = preg_replace_callback(
            '/\{(\w+)\}/',
            static function (array $matches): string {
                $name = $matches[1];
                if ($name === 'path') {
                    return '(?P<' . $name . '>.+)';
                }

                return '(?P<' . $name . '>[^/]+)';
            },
            $pattern
        );

        return '#^' . $regex . '$#';
    }
}
