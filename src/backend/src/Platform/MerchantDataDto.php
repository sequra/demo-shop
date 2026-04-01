<?php

declare(strict_types=1);

namespace SeQura\Demo\Platform;

/**
 * Data Transfer Object for merchant data, including merchant ID and assets key.
 *
 * @package SeQura\Demo\Platform
 */
class MerchantDataDto
{
    public function __construct(private string $merchantId, private string $assetsKey)
    {
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function getAssetsKey(): string
    {
        return $this->assetsKey;
    }

    public function toArray(): array
    {
        return [
            'merchant_ref' => $this->merchantId,
            'assets_key'   => $this->assetsKey,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            merchantId: $data['merchant_ref'] ?? '',
            assetsKey: $data['assets_key'] ?? '',
        );
    }
}
