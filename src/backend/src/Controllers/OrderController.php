<?php

declare(strict_types=1);

namespace SeQura\Demo\Controllers;

use Exception;
use SeQura\Core\BusinessLogic\Domain\Order\RepositoryContracts\SeQuraOrderRepositoryInterface;
use SeQura\Demo\Repository\DemoSeQuraOrderRepository;
use SeQura\Demo\Request;
use SeQura\Demo\Response;

/**
 * Provides order status polling for the frontend (IPN result).
 */
final readonly class OrderController
{
    /**
     * @param DemoSeQuraOrderRepository $orderRepository
     */
    public function __construct(private SeQuraOrderRepositoryInterface $orderRepository)
    {
    }

    /**
     * Poll the status of an order by its reference.
     *
     * @param Request $request The incoming HTTP request.
     *
     * @return Response
     *
     * @throws Exception
     */
    public function getStatus(Request $request): Response
    {
        $params = $request->params;
        $orderId = $params['id'] ?? '';

        if ($orderId === '') {
            return Response::json(['error' => 'Missing order id'], 400);
        }

        $repo = $this->orderRepository;
        $tracking = $repo->getTracking($orderId);

        if ($tracking === null) {
            $order = $repo->getByOrderReference($orderId);

            if ($order === null) {
                return Response::json(['error' => 'Order not found'], 404);
            }

            return Response::json([
                'status' => 'pending',
                'sq_state' => null,
                'ipnReceived' => false,
                'confirmedAt' => null,
            ]);
        }

        return Response::json([
            'status' => $tracking['status'] ?? 'pending',
            'sq_state' => $tracking['sq_state'] ?? null,
            'ipnReceived' => $tracking['ipnReceived'] ?? false,
            'confirmedAt' => $tracking['confirmedAt'] ?? null,
        ]);
    }
}
