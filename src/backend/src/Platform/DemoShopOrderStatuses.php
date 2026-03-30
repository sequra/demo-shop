<?php

declare(strict_types=1);

namespace SeQura\Demo\Platform;

use SeQura\Core\BusinessLogic\Domain\Integration\ShopOrderStatuses\ShopOrderStatusesServiceInterface;

/**
 * No-op shop order statuses adapter for the demo application.
 */
final class DemoShopOrderStatuses implements ShopOrderStatusesServiceInterface
{
    /**
     * @inheritDoc
     */
    public function getShopOrderStatuses(): array
    {
        return [];
    }
}
