<?php

declare(strict_types=1);

namespace SeQura\Demo\Webhook;

use SeQura\Core\BusinessLogic\Domain\Order\Builders\MerchantOrderRequestBuilder;
use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraOrder;
use SeQura\Core\BusinessLogic\Domain\Order\RepositoryContracts\SeQuraOrderRepositoryInterface;
use SeQura\Core\BusinessLogic\Webhook\Handler\WebhookHandler;
use SeQura\Core\BusinessLogic\Webhook\Services\ShopOrderService;

/**
 * Demo webhook handler that resolves orders from the file-backed repository.
 */
class DemoWebhookHandler extends WebhookHandler
{
    /**
     * @param ShopOrderService $shopOrderService The shop order service.
     * @param MerchantOrderRequestBuilder $merchantOrderRequestBuilder The order request builder.
     * @param SeQuraOrderRepositoryInterface $orderRepository The order repository.
     */
    public function __construct(
        ShopOrderService $shopOrderService,
        MerchantOrderRequestBuilder $merchantOrderRequestBuilder,
        private readonly SeQuraOrderRepositoryInterface $orderRepository,
    ) {
        parent::__construct($shopOrderService, $merchantOrderRequestBuilder);
    }

    /**
     * @inheritDoc
     */
    protected function getSeQuraOrderByOrderReference(string $orderRef): ?SeQuraOrder
    {
        return $this->orderRepository->getByOrderReference($orderRef);
    }
}
