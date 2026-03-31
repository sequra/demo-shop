<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use SeQura\Demo\Bootstrap;
use SeQura\Demo\Config;
use SeQura\Demo\Controllers\PageController;
use SeQura\Demo\Router;
use SeQura\Demo\RouteRegistry;
use SeQura\Demo\Request;
use SeQura\Demo\Security\SecurityMiddleware;

try {
    // Load environment configuration
    Config::load(__DIR__ . '/../.env');

    // Initialize integration-core with all demo service registrations
    Bootstrap::init();

    // Secure session configuration
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();

    // Set up routes
    $router = new Router(RouteRegistry::initRoutes());

    // Security gate — run checks before dispatching
    $request = Request::fromGlobals();
    $securityResponse = SecurityMiddleware::handle($request);
    if ($securityResponse !== null) {
        $securityResponse = SecurityMiddleware::addCorsHeaders($securityResponse, $request);
        $securityResponse->send();

        exit;
    }
    // Dispatch the request
    $response = $router->dispatch($request);
    $response = SecurityMiddleware::addCorsHeaders($response, $request);
    $response->send();
} catch (Throwable $e) {
    \SeQura\Demo\Response::view('error', ['errorMessage' => $e->getMessage()], 500)->send();
}
