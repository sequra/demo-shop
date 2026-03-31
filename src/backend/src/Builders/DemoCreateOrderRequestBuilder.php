<?php

declare(strict_types=1);

namespace SeQura\Demo\Builders;

use SeQura\Core\BusinessLogic\Domain\Order\Builders\CreateOrderRequestBuilder;
use SeQura\Core\BusinessLogic\Domain\Order\Exceptions\InvalidCartItemsException;
use SeQura\Core\BusinessLogic\Domain\Order\Exceptions\InvalidDateException;
use SeQura\Core\BusinessLogic\Domain\Order\Exceptions\InvalidDurationException;
use SeQura\Core\BusinessLogic\Domain\Order\Exceptions\InvalidGuiLayoutValueException;
use SeQura\Core\BusinessLogic\Domain\Order\Exceptions\InvalidQuantityException;
use SeQura\Core\BusinessLogic\Domain\Order\Exceptions\InvalidServiceEndTimeException;
use SeQura\Core\BusinessLogic\Domain\Order\Exceptions\InvalidTimestampException;
use SeQura\Core\BusinessLogic\Domain\Order\Exceptions\InvalidUrlException;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\CreateOrderRequest;

/**
 * Demo implementation of CreateOrderRequestBuilder.
 *
 * Receives the order sub-key from the frontend payload and delegates
 * hydration entirely to CreateOrderRequest::fromArray(), since the
 * frontend already sends snake_case data in the expected format.
 */
final readonly class DemoCreateOrderRequestBuilder implements CreateOrderRequestBuilder
{
    /**
     * @param array<string, mixed> $orderData The 'order' payload from the frontend.
     */
    public function __construct(private array $orderData)
    {
    }

    /**
     * Build a CreateOrderRequest from the stored order data.
     *
     * @return CreateOrderRequest
     *
     * @throws InvalidServiceEndTimeException
     * @throws InvalidDateException
     * @throws InvalidQuantityException
     * @throws InvalidDurationException
     * @throws InvalidUrlException
     * @throws InvalidGuiLayoutValueException
     * @throws InvalidTimestampException
     * @throws InvalidCartItemsException
     */
    public function build(): CreateOrderRequest
    {
        return CreateOrderRequest::fromArray($this->orderData);
    }
}
