<?php

declare(strict_types=1);

namespace SeQura\Demo\Webhook;

use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraOrder;
use SeQura\Core\BusinessLogic\Domain\Order\RepositoryContracts\SeQuraOrderRepositoryInterface;
use SeQura\Core\BusinessLogic\Webhook\Validator\WebhookValidator;

/**
 * Demo webhook validator that resolves orders from the file-backed repository.
 */
class DemoWebhookValidator extends WebhookValidator
{
    /**
     * @param SeQuraOrderRepositoryInterface $orderRepository The order repository.
     */
    public function __construct(private readonly SeQuraOrderRepositoryInterface $orderRepository)
    {
    }

    /**
     * @inheritDoc
     */
    protected function getSeQuraOrderByOrderReference(string $orderRef): ?SeQuraOrder
    {
        return $this->orderRepository->getByOrderReference($orderRef);
    }
}
