<?php

declare(strict_types=1);

namespace SeQura\Demo\Security;

use SeQura\Demo\Config;
use SeQura\Demo\Request;
use SeQura\Demo\Response;

/**
 * Security middleware that protects API routes.
 *
 * Provides CSRF token validation, Origin/Referer checking, and CORS header
 * management. Runs before the Router dispatches.
 */
final class SecurityMiddleware
{
    /**
     * Run security checks on the incoming request.
     *
     * Returns null if the request passes all checks, or a Response to
     * short-circuit the request (403 Forbidden).
     */
    public static function handle(Request $request): ?Response
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        // CORS preflight — respond immediately with allowed headers
        if ($method === 'OPTIONS') {
            return self::handlePreflight($request);
        }

        // Homepage — no security checks needed
        if ($uri === '/' || $uri === '') {
            return null;
        }

        // Only protect /api/* routes
        if (!str_starts_with($uri, '/api/')) {
            return null;
        }

        // IPN webhook is exempt from CSRF and Origin checks
        // (SeQura servers can't get a CSRF token; relies on core webhook signature validation)
        if (self::isIpnRoute($method, $uri)) {
            return null;
        }

        // Origin/Referer validation
        $originResponse = self::validateOrigin($request);
        if ($originResponse !== null) {
            return $originResponse;
        }

        // CSRF token validation
        return self::validateCsrf($request);
    }

    /**
     * Add CORS headers to an outgoing response based on the request Origin.
     *
     * @param Response $response The outgoing response.
     * @param Request $request The incoming request.
     *
     * @return Response
     */
    public static function addCorsHeaders(Response $response, Request $request): Response
    {
        $origin = $request->getHeader('origin');

        if ($origin === null || $origin === '') {
            return $response;
        }

        if (!self::isAllowedOrigin($origin)) {
            return $response;
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Accept, X-CSRF-Token')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Vary', 'Origin');
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    /**
     * @param string $method
     * @param string $uri
     *
     * @return bool
     */
    private static function isIpnRoute(string $method, string $uri): bool
    {
        return $method === 'POST' && $uri === '/api/ipn';
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    private static function handlePreflight(Request $request): Response
    {
        $response = Response::empty(204);

        $origin = $request->getHeader('origin');
        if ($origin !== null && self::isAllowedOrigin($origin)) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Accept, X-CSRF-Token')
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Access-Control-Max-Age', '86400')
                ->withHeader('Vary', 'Origin');
        }

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return Response|null
     */
    private static function validateCsrf(Request $request): ?Response
    {
        $token = $request->getHeader('x-csrf-token');

        if ($token === null || $token === '') {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        if (!CsrfTokenManager::validateToken($token)) {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        return null;
    }

    /**
     * @param Request $request
     *
     * @return Response|null
     */
    private static function validateOrigin(Request $request): ?Response
    {
        $origin = $request->getHeader('origin');
        $referer = $request->getHeader('referer');

        // Extract hostname from Origin or Referer
        $hostname = null;

        if ($origin !== null && $origin !== '') {
            $hostname = self::extractHostname($origin);
        } elseif ($referer !== null && $referer !== '') {
            $hostname = self::extractHostname($referer);
        }

        // No Origin or Referer — blocks curl/Postman and other non-browser clients
        if ($hostname === null) {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        if (!in_array($hostname, self::getAllowedHosts(), true)) {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        return null;
    }

    /**
     * Get the list of allowed hostnames from configuration.
     *
     * @return string[]
     */
    private static function getAllowedHosts(): array
    {
        $hosts = ['localhost', '127.0.0.1'];

        // From VITE_ALLOWED_HOSTS env var
        $allowedHosts = Config::get('VITE_ALLOWED_HOSTS', '');
        if ($allowedHosts !== '') {
            foreach (explode(',', $allowedHosts) as $host) {
                $host = trim($host);
                if ($host !== '') {
                    $hosts[] = $host;
                }
            }
        }

        // From SEQURA_WEBHOOK_BASE_URL env var
        $webhookUrl = Config::get('SEQURA_WEBHOOK_BASE_URL', '');
        if ($webhookUrl !== '') {
            $webhookHost = self::extractHostname($webhookUrl);
            if ($webhookHost !== null) {
                $hosts[] = $webhookHost;
            }
        }

        return array_unique($hosts);
    }

    /**
     * @param string $origin
     *
     * @return bool
     */
    private static function isAllowedOrigin(string $origin): bool
    {
        $hostname = self::extractHostname($origin);

        return $hostname !== null && in_array($hostname, self::getAllowedHosts(), true);
    }

    /**
     * @param string $url
     *
     * @return string|null
     */
    private static function extractHostname(string $url): ?string
    {
        $parsed = parse_url($url);

        return $parsed['host'] ?? null;
    }
}
