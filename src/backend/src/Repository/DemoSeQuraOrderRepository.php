<?php

declare(strict_types=1);

namespace SeQura\Demo\Repository;

use Exception;
use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraOrder;
use SeQura\Core\BusinessLogic\Domain\Order\RepositoryContracts\SeQuraOrderRepositoryInterface;
use SeQura\Demo\Storage\EncryptionHelper;

/**
 * File-based implementation of SeQuraOrderRepositoryInterface.
 *
 * Uses encrypted file storage so that both browser sessions and
 * server-to-server IPN webhooks can access the data.
 */
final class DemoSeQuraOrderRepository implements SeQuraOrderRepositoryInterface
{
    /**
     * @var string $filePath
     */
    private string $filePath;

    /**
     * @param ?string $filePath
     */
    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? sys_get_temp_dir() . '/sequra-demo-checkout-orders.enc';
    }

    /** @inheritDoc
     *
     * @throws Exception
     */
    public function getByShopReference(string $shopOrderReference): ?SeQuraOrder
    {
        foreach ($this->readAll() as $entry) {
            if (($entry['order_ref_1'] ?? '') === $shopOrderReference) {
                return $this->hydrate($entry);
            }
        }

        return null;
    }

    /** @inheritDoc */
    public function getOrderBatchByShopReferences(array $shopOrderReferences): array
    {
        $orders = [];

        foreach ($this->readAll() as $entry) {
            if (in_array($entry['order_ref_1'] ?? '', $shopOrderReferences, true)) {
                $orders[] = $this->hydrate($entry);
            }
        }

        return $orders;
    }

    /** @inheritDoc
     *
     * @throws Exception
     */
    public function getByCartId(string $cartId): ?SeQuraOrder
    {
        $all = $this->readAll();

        return isset($all[$cartId]) ? $this->hydrate($all[$cartId]) : null;
    }

    /** @inheritDoc
     *
     * @throws Exception
     */
    public function getByOrderReference(string $sequraOrderReference): ?SeQuraOrder
    {
        foreach ($this->readAll() as $entry) {
            if (($entry['reference'] ?? '') === $sequraOrderReference) {
                return $this->hydrate($entry);
            }
        }

        return null;
    }

    /** @inheritDoc */
    public function setSeQuraOrder(SeQuraOrder $order): void
    {
        $all = $this->readAll();
        $cartId = $order->getCartId();

        if ($cartId === '') {
            return;
        }

        // Preserve tracking fields (prefixed with _) across core overwrites
        $existing = $all[$cartId] ?? [];

        $tracking = array_filter($existing, function ($k) {
            return str_starts_with($k, '_');
        }, ARRAY_FILTER_USE_KEY);

        $all[$cartId] = array_merge($order->toArray(), $tracking);
        $this->writeAll($all);
    }

    /**
     * Update tracking fields on an order found by SeQura order reference.
     *
     * Tracking keys are stored with a _ prefix to avoid conflicts with SeQuraOrder fields.
     *
     * @param string $orderRef The SeQura order reference.
     * @param array<string, mixed> $data Tracking data to merge.
     *
     * @return void
     */
    public function updateTracking(string $orderRef, array $data): void
    {
        $all = $this->readAll();

        foreach ($all as &$entry) {
            if (($entry['reference'] ?? '') === $orderRef) {
                foreach ($data as $k => $v) {
                    $entry['_' . $k] = $v;
                }

                $this->writeAll($all);

                return;
            }
        }
    }

    /**
     * Read tracking fields for an order found by SeQura order reference.
     *
     * @param string $orderRef The SeQura order reference.
     *
     * @return array<string, mixed>|null
     */
    public function getTracking(string $orderRef): ?array
    {
        foreach ($this->readAll() as $entry) {
            if (($entry['reference'] ?? '') === $orderRef) {
                $tracking = [];

                foreach ($entry as $k => $v) {
                    if (str_starts_with($k, '_')) {
                        $tracking[substr($k, 1)] = $v;
                    }
                }

                return $tracking;
            }
        }

        return null;
    }

    /** @inheritDoc */
    public function deleteOrder(SeQuraOrder $existingOrder): void
    {
        $all = $this->readAll();
        $cartId = $existingOrder->getCartId();

        if ($cartId !== '' && isset($all[$cartId])) {
            unset($all[$cartId]);
            $this->writeAll($all);
        }
    }

    /** @inheritDoc */
    public function deleteAllOrders(): void
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function readAll(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $contents = file_get_contents($this->filePath);

        if ($contents === false || $contents === '') {
            return [];
        }

        try {
            $json = EncryptionHelper::decrypt($contents);
        } catch (\RuntimeException $e) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, array<string, mixed>> $data
     */
    private function writeAll(array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $encrypted = EncryptionHelper::encrypt($json);

        file_put_contents($this->filePath, $encrypted, LOCK_EX);
    }

    /**
     * @param array<string, mixed> $data
     * @throws Exception
     */
    private function hydrate(array $data): SeQuraOrder
    {
        $order = new SeQuraOrder();
        $order->inflate($data);

        return $order;
    }
}
