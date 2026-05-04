<?php

declare(strict_types=1);

namespace SeQura\Demo\Platform;

use Exception;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\CreateOrderRequest;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\MerchantReference;
use SeQura\Core\BusinessLogic\Domain\Order\RepositoryContracts\SeQuraOrderRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Webhook\Models\Webhook;
use SeQura\Core\BusinessLogic\Webhook\Services\ShopOrderService;
use SeQura\Demo\Repository\DemoSeQuraOrderRepository;

/**
 * Demo implementation of ShopOrderService.
 *
 * Persists order status updates from IPN webhooks into the file-backed repository.
 */
final readonly class DemoShopOrderService implements ShopOrderService
{
    /**
     * @param DemoSeQuraOrderRepository $orderRepository
     */
    public function __construct(private SeQuraOrderRepositoryInterface $orderRepository)
    {
    }

    /**
     * @inheritDoc
     */
    public function updateStatus(
        Webhook $webhook,
        string $status,
        ?int $reasonCode = null,
        ?string $message = null,
    ): void {
        $sqState = $webhook->getSqState();
        $state = ($sqState === 'approved') ? 'confirmed' : 'on_hold';
        $orderRef = $webhook->getOrderRef();

        $order = $this->orderRepository->getByOrderReference($orderRef);

        if ($order !== null && (string) $order->getMerchantReference()->getOrderRef1() === '') {
            $orderRef1 = self::generateNativeShortId();
            $order->setOrderRef1($orderRef1);
            $order->setMerchantReference(new MerchantReference($orderRef1));
            $this->orderRepository->setSeQuraOrder($order);
        }

        $this->orderRepository->updateTracking($orderRef, [
            'status' => $state,
            'sq_state' => $sqState,
            'ipnReceived' => true,
            'confirmedAt' => date('c'),
        ]);
    }

    /**
     * Generate a URL-safe random short identifier for use as the shop's order reference.
     *
     * @param int $length Desired length of the identifier.
     *
     * @return string
     *
     * @throws Exception
     */
    private static function generateNativeShortId(int $length = 8): string
    {
        $bytes = random_bytes((int) ceil($length * 3 / 4));

        return substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $length);
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function getCreateOrderRequest(string $orderReference): CreateOrderRequest
    {
        $order = $this->orderRepository->getByOrderReference($orderReference);

        return new CreateOrderRequest(
            $order->getState() ?? '',
            $order->getUnshippedCart(),
            $order->getDeliveryMethod(),
            $order->getCustomer(),
            $order->getPlatform(),
            $order->getDeliveryAddress(),
            $order->getInvoiceAddress(),
            $order->getGui(),
            $order->getMerchant(),
            $order->getMerchantReference()
        );
    }

    /**
     * @inheritDoc
     */
    public function getReportOrderIds(int $page, int $limit = 5000): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getStatisticsOrderIds(int $page, int $limit = 5000): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getOrderUrl(string $merchantReference): string
    {
        return '';
    }
}
