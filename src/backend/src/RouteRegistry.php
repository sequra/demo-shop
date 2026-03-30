<?php

declare(strict_types=1);

namespace SeQura\Demo;

use SeQura\Demo\Controllers\CheckoutController;
use SeQura\Demo\Controllers\OrderController;
use SeQura\Demo\Controllers\PageController;
use SeQura\Demo\Controllers\WebhookController;

/**
 * Central registry of all application routes.
 */
final class RouteRegistry
{
    /**
     * Return all application routes.
     *
     * @return Route[]
     */
    public static function initRoutes(): array
    {
        return [
            Route::get('/', [PageController::class, 'homepage']),

            // Webhook
            Route::post('/api/ipn', [WebhookController::class, 'handleIpn']),

            // Orders
            Route::get('/api/orders/{id}/status', [OrderController::class, 'getStatus']),

            // Checkout
            Route::post('/api/checkout/solicitation', [CheckoutController::class, 'solicitation']),
            Route::get('/api/checkout/form', [CheckoutController::class, 'getForm']),
        ];
    }
}
