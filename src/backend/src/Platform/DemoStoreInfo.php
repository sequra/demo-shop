<?php

declare(strict_types=1);

namespace SeQura\Demo\Platform;

use SeQura\Core\BusinessLogic\Domain\Integration\StoreInfo\StoreInfoServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Stores\Models\StoreInfo;

/**
 * Demo implementation of StoreInfoServiceInterface.
 *
 * Returns placeholder store metadata. The demo does not run on top of a
 * commerce platform and has no plugin registry, so values are constants.
 */
final class DemoStoreInfo implements StoreInfoServiceInterface
{
    /**
     * @inheritDoc
     */
    public function getStoreInfo(): StoreInfo
    {
        return new StoreInfo(
            'SeQura Checkout Demo',
            'http://localhost:8081',
            'demo-shop',
            '1.0.0',
            '1.0.0',
            PHP_VERSION,
            'none',
            PHP_OS,
            []
        );
    }
}
