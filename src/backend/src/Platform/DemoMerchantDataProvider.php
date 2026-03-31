<?php

declare(strict_types=1);

namespace SeQura\Demo\Platform;

use SeQura\Core\BusinessLogic\Domain\Integration\Order\MerchantDataProviderInterface;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Options;
use SeQura\Demo\Config;

/**
 * Demo implementation of MerchantDataProviderInterface.
 *
 * Provides callback URLs and merchant options for the SeQura checkout flow.
 */
final class DemoMerchantDataProvider implements MerchantDataProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getApprovedCallback(): ?string
    {
        return '__sequraApproved';
    }

    /**
     * @inheritDoc
     */
    public function getRejectedCallback(): ?string
    {
        return '__sequraRejected';
    }

    /**
     * @inheritDoc
     */
    public function getPartPaymentDetailsGetter(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getNotifyUrl(): ?string
    {
        return Config::get('SEQURA_WEBHOOK_BASE_URL') . '/api/ipn';
    }

    /**
     * @inheritDoc
     */
    public function getReturnUrlForCartId(string $cartId): ?string
    {
        return Config::get('SEQURA_WEBHOOK_BASE_URL', '') ?: null;
    }

    /**
     * @inheritDoc
     */
    public function getEditUrl(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getAbortUrl(): ?string
    {
        return Config::get('SEQURA_WEBHOOK_BASE_URL', '') ?: null;
    }

    /**
     * @inheritDoc
     */
    public function getApprovedUrl(): ?string
    {
        return Config::get('SEQURA_WEBHOOK_BASE_URL', '') ?: null;
    }

    /**
     * @inheritDoc
     */
    public function getOptions(): ?Options
    {
        return new Options(false);
    }

    /**
     * @inheritDoc
     */
    public function getEventsWebhookUrl(): string
    {
        return Config::get('SEQURA_WEBHOOK_BASE_URL') . '/api/ipn';
    }

    /**
     * @inheritDoc
     */
    public function getNotificationParametersForCartId(string $cartId): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getEventsWebhookParametersForCartId(string $cartId): array
    {
        return [];
    }
}
