<?php

declare(strict_types=1);

namespace SeQura\Demo\Platform;

use SeQura\Core\BusinessLogic\Domain\Integration\Order\OrderCreationInterface;

/**
 * No-op order creation adapter for the demo application.
 */
final class DemoOrderCreation implements OrderCreationInterface
{
    /**
     * @inheritDoc
     */
    public function createOrder(string $cartId): string
    {
        return '';
    }
}
