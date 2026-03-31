<?php

declare(strict_types=1);

namespace SeQura\Demo\Platform;

use SeQura\Core\BusinessLogic\Domain\Integration\StoreIntegration\StoreIntegrationServiceInterface;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\Models\Capability;
use SeQura\Core\BusinessLogic\Domain\URL\Model\URL;

/**
 * Demo implementation of StoreIntegrationServiceInterface.
 *
 * Returns default webhook URL and supported capabilities for the demo store.
 */
final class DemoStoreIntegration implements StoreIntegrationServiceInterface
{
    /**
     * @inheritDoc
     */
    public function getWebhookUrl(): URL
    {
        return new URL('https://localhost/webhook');
    }

    /**
     * @inheritDoc
     */
    public function getSupportedCapabilities(): array
    {
        return [
            Capability::general(),
            Capability::widget(),
            Capability::orderStatus(),
        ];
    }
}
